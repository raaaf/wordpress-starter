<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

use phpseclib3\Net\SFTP;
use WordpressStarter\Providers\LogServiceProvider;

class SftpClient
{
    private const SSRF_BLOCKED_RANGES = [
        '/^127\./',
        '/^10\./',
        '/^192\.168\./',
        '/^172\.(1[6-9]|2[0-9]|3[01])\./',
        '/^::1$/',
        '/^localhost$/i',
        '/^0\./',
        '/^169\.254\./',
        '/^fc[0-9a-f]{2}:/i',
    ];

    private const CONNECT_TIMEOUT = 15;

    /**
     * Validate that the host is not a private/reserved IP range.
     *
     * Note: DNS rebinding SSRF is not mitigated here — the host is checked
     * as-is before DNS resolution. This is the same accepted tradeoff as in
     * FileHandler::assertSafeUrl().
     *
     * @throws \RuntimeException
     */
    public static function assertSafeHost(string $host): void
    {
        foreach (self::SSRF_BLOCKED_RANGES as $pattern) {
            if (preg_match($pattern, $host)) {
                throw new \RuntimeException('SFTP host is in a blocked IP range: ' . $host); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            }
        }
    }

    /**
     * Open an authenticated SFTP connection.
     *
     * @throws \RuntimeException on connection or authentication failure
     */
    public static function connect(string $host, int $port, string $username, string $password): SFTP
    {
        self::assertSafeHost($host);

        try {
            $sftp = new SFTP($host, $port, self::CONNECT_TIMEOUT);
        } catch (\Throwable $e) {
            throw new \RuntimeException( // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                sprintf('SFTP connection failed for %s:%d — %s', $host, $port, $e->getMessage()), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                0,
                $e // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }

        if (!$sftp->login($username, $password)) {
            throw new \RuntimeException( // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                sprintf('SFTP authentication failed for %s@%s:%d', $username, $host, $port) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }

        return $sftp;
    }

    /**
     * List files in a remote directory, filtered by allowed extensions.
     *
     * Note: files are loaded into memory via get(). This works well for typical
     * document sizes (< 50 MB). For larger files, consider streaming to a temp
     * file using phpseclib's $sftp->get($remote, $localPath) overload.
     *
     * @param string[] $allowedExtensions
     * @return array<int, array{filename: string, ext: string, mtime: int|null, size: int}>
     */
    public static function listFiles(SFTP $sftp, string $remotePath, array $allowedExtensions): array
    {
        $normalizedPath = rtrim($remotePath, '/') . '/';
        $rawList        = $sftp->nlist($normalizedPath);

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
            $stat     = $sftp->stat($filePath);
            $mtime    = isset($stat['mtime']) && is_int($stat['mtime']) ? $stat['mtime'] : null;
            $size     = isset($stat['size']) ? (int) $stat['size'] : 0;

            $files[] = [
                'filename' => $entry,
                'ext'      => $ext,
                'mtime'    => $mtime,
                'size'     => $size,
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

    /**
     * Read entire file contents from SFTP into a string.
     * Returns null on failure.
     */
    public static function readFile(SFTP $sftp, string $remotePath): ?string
    {
        $contents = $sftp->get($remotePath);
        return $contents === false ? null : $contents;
    }
}
