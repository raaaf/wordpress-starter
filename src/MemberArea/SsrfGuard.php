<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

use InvalidArgumentException;
use RuntimeException;

/**
 * Centralised SSRF protection for all outbound requests in the MemberArea module.
 *
 * Checks the literal host/IP and, if the host is not already an IP address, the
 * DNS-resolved IP too, against private/reserved IP ranges at validation time.
 *
 * Known limitation (TOCTOU / DNS-rebinding): this class only validates the host
 * at check time. The actual connection is made afterwards by SftpClient::connect()
 * and by wp_remote_head() in FolderSync, and each performs its own independent DNS
 * lookup. An attacker controlling a low-TTL DNS record can therefore resolve to a
 * public IP during this check and to a private IP at connect time. Closing that
 * gap requires pinning the connection to the IP validated here, which this class
 * does not do.
 */
final class SsrfGuard
{
    private const ALLOWED_PROTOCOLS = ['https'];

    private function __construct()
    {
    }

    /**
     * Assert that a bare hostname/IP is not in a blocked range.
     *
     * After checking the literal value, if the host is not already an IP address
     * the method resolves it via gethostbyname() and validates the resolved IP
     * against the blocked ranges too.
     *
     * @throws RuntimeException if the host is blocked.
     */
    public static function assertSafeHost(string $host): void
    {
        self::assertHostNotBlocked($host);
        self::assertResolvedHostNotBlocked($host, 'SFTP host could not be resolved: ');
    }

    /**
     * Assert that a URL uses an allowed protocol and that its host is safe.
     *
     * If the URL host is not already an IP, the method resolves it and validates
     * the resolved IP too. Unresolvable hosts are blocked.
     *
     * @throws InvalidArgumentException on invalid URL / disallowed protocol.
     * @throws RuntimeException if the host is blocked or unresolvable.
     */ // phpcs:ignore Squiz.Commenting.FunctionCommentThrowTag -- both exceptions genuinely propagate from this method (the second via the helper methods it calls), the sniff only sees the single literal throw in this method's own body
    public static function assertSafeUrl(string $url): void
    {
        $parsed = wp_parse_url($url);

        if (!$parsed || empty($parsed['scheme']) || !in_array($parsed['scheme'], self::ALLOWED_PROTOCOLS, true)) {
            throw new InvalidArgumentException('Invalid or disallowed URL scheme: ' . $url); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        $host = $parsed['host'] ?? '';

        self::assertHostNotBlocked($host);
        self::assertResolvedHostNotBlocked($host, 'URL host could not be resolved: ');
    }

    /**
     * If the host is not already an IP address, resolve it via gethostbyname()
     * and validate the resolved IP against the blocked ranges too. Shared by
     * assertSafeHost() and assertSafeUrl() so a future SSRF fix only needs to
     * land in one place.
     *
     * @throws RuntimeException if the host cannot be resolved or the resolved IP is blocked.
     */
    private static function assertResolvedHostNotBlocked(string $host, string $unresolvableMessage): void
    {
        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            $resolved = gethostbyname($host);
            // gethostbyname() returns the original string on failure — treat as blocked.
            if ($resolved === $host) {
                throw new RuntimeException($unresolvableMessage . $host); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            }
            self::assertHostNotBlocked($resolved);
        }
    }

    /**
     * Check a single host/IP literal against the blocked ranges.
     *
     * Bare "localhost" is blocked explicitly since filter_var() only validates
     * IPs. Any other non-IP hostname falls through here unblocked and is
     * caught, if at all, by the DNS-resolution check in assertResolvedHostNotBlocked().
     *
     * @throws RuntimeException if blocked.
     */
    private static function assertHostNotBlocked(string $host): void
    {
        if (strcasecmp($host, 'localhost') === 0) {
            throw new RuntimeException('Host is in a blocked IP range: ' . $host); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        if (
            filter_var($host, FILTER_VALIDATE_IP) !== false
            && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
        ) {
            throw new RuntimeException('Host is in a blocked IP range: ' . $host); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
    }
}
