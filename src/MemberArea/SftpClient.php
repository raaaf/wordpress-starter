<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

use phpseclib3\Net\SFTP;
use RuntimeException;
use Throwable;
use WordpressStarter\Providers\LogServiceProvider;
use WordpressStarter\ThemeContext;

class SftpClient
{
    private const CONNECT_TIMEOUT = 15;

    /**
     * Validate that the host is not a private/reserved IP range.
     * Delegates to SsrfGuard, which also checks the DNS-resolved IP.
     *
     * @throws RuntimeException
     */
    public static function assertSafeHost(string $host): void
    {
        SsrfGuard::assertSafeHost($host);
    }

    /**
     * Option key suffix (see ThemeContext::optionKey()) holding the pinned
     * (trust-on-first-use) SFTP host keys, keyed by "host:port" so the pin
     * does not depend on which post/entry happens to be first in a query.
     *
     * @var string
     */
    private const HOST_KEYS_OPTION = 'member_sftp_host_keys';

    /**
     * Transient key suffix (prefixed via ThemeContext::prefix()) recording the
     * "host:port" of the most recent host key mismatch, so an admin notice can
     * offer a one-click re-trust action.
     */
    private const HOST_KEY_MISMATCH_TRANSIENT = 'member_sftp_host_key_mismatch';

    /**
     * Open an authenticated SFTP connection.
     *
     * The server's host key is pinned on first successful connection to a given
     * host:port (trust-on-first-use) and verified against the stored value on
     * every later connection, to prevent a MITM host from silently receiving
     * the stored SFTP password.
     *
     * @throws RuntimeException on connection, authentication, or host key mismatch
     */
    public static function connect(string $host, int $port, string $username, string $password): SFTP
    {
        self::assertSafeHost($host);

        // getServerPublicHostKey() triggers the SSH key exchange on demand
        // (SSH2::connect(), called internally when not yet connected) without
        // performing any authentication attempt, so the pinned host key can be
        // verified before any credential is sent to the server. A credential-free
        // login($username) probe was used here previously, but a server that
        // refuses 'none' authentication by disconnecting left no host key to
        // check at all; calling getServerPublicHostKey() directly avoids that
        // failure mode. The SFTP constructor only stores host/port and does not
        // itself connect, so the connection attempt actually happens inside
        // getServerPublicHostKey() — both calls must be covered by the same
        // try/catch for a real connection failure to surface as our own
        // RuntimeException instead of an uncaught phpseclib exception.
        try {
            $sftp = new SFTP($host, $port, self::CONNECT_TIMEOUT);
            $currentHostKey = $sftp->getServerPublicHostKey();
        } catch (Throwable $e) {
            throw new RuntimeException( // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                sprintf('SFTP connection failed for %s:%d — %s', $host, $port, $e->getMessage()), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                0,
                $e, // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }

        if (!is_string($currentHostKey) || $currentHostKey === '') {
            throw new RuntimeException( // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                sprintf('SFTP connection failed for %s:%d — no host key received', $host, $port), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }

        self::pinHostKey($currentHostKey, $host, $port);

        if (!$sftp->login($username, $password)) {
            throw new RuntimeException( // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                sprintf('SFTP authentication failed for %s:%d', $host, $port), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }

        return $sftp;
    }

    /**
     * Trust-on-first-use host key pinning, keyed by "host:port": store the
     * server's host key when none is pinned yet for that host:port, otherwise
     * verify it matches. On mismatch, nothing is stored (the stale pin is left
     * in place so a legitimate retry against the real host still verifies
     * correctly), the mismatch is logged, a transient records the affected
     * host:port for the admin notice, and the connection is aborted before any
     * password reaches the unrecognised host.
     *
     * @throws RuntimeException on host key mismatch
     */
    private static function pinHostKey(string $hostKey, string $host, int $port): void
    {
        // Normalise so the same logical host cannot pin two different
        // entries (e.g. differing case or a trailing dot from DNS), used
        // identically here and everywhere else this connection key is built
        // (the mismatch transient and the admin re-trust notice).
        $connKey = strtolower(rtrim(trim($host), '.')) . ':' . $port;
        $optionKey = ThemeContext::optionKey(self::HOST_KEYS_OPTION);
        $hostKeys = get_option($optionKey, []);
        if (!is_array($hostKeys)) {
            $hostKeys = [];
        }

        if (!isset($hostKeys[$connKey])) {
            $hostKeys[$connKey] = $hostKey;
            update_option($optionKey, $hostKeys, autoload: false);

            return;
        }

        if (!hash_equals( (string) $hostKeys[$connKey], $hostKey)) {
            LogServiceProvider::error('SFTP host key mismatch', [
                'host' => $connKey,
            ]);

            set_transient(ThemeContext::prefix() . '_' . self::HOST_KEY_MISMATCH_TRANSIENT, $connKey, HOUR_IN_SECONDS);

            throw new RuntimeException( // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                sprintf('SFTP host key changed for %s; re-trust it in wp-admin to continue', $connKey), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }
    }

    /**
     * List files in a remote directory, filtered by allowed extensions.
     *
     * Note: files are loaded into memory via get(). This works well for typical
     * document sizes (< 50 MB). For larger files, consider streaming to a temp
     * file using phpseclib's $sftp->get($remote, $localPath) overload.
     *
     * @param string[] $allowedExtensions
     *
     * @return array<int, array{filename: string, ext: string, mtime: int|null, size: int}>
     */
    public static function listFiles(SFTP $sftp, string $remotePath, array $allowedExtensions): array
    {
        $normalizedPath = rtrim($remotePath, '/') . '/';
        $rawList = $sftp->nlist($normalizedPath);

        if ($rawList === false) {
            LogServiceProvider::warning('SFTP nlist failed', ['path' => $normalizedPath]);

            return [];
        }

        $files = [];

        foreach ($rawList as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions, true)) {
                continue;
            }

            $filePath = $normalizedPath . $entry;
            $stat = $sftp->stat($filePath);
            $mtime = isset($stat['mtime']) && is_int($stat['mtime']) ? $stat['mtime'] : null;
            $size = isset($stat['size']) ? (int) $stat['size'] : 0;

            $files[] = [
                'filename' => $entry,
                'ext' => $ext,
                'mtime' => $mtime,
                'size' => $size,
            ];
        }

        return $files;
    }

    /**
     * Check whether a file exists on the remote server.
     */
    public static function fileExists(SFTP $sftp, string $remotePath): bool
    {
        return $sftp->file_exists($remotePath);
    }
}
