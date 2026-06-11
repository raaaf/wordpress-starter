<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

use InvalidArgumentException;
use RuntimeException;

/**
 * Centralised SSRF protection for all outbound requests in the MemberArea module.
 *
 * Checks both the literal hostname/IP AND the DNS-resolved IP so that a public
 * hostname that resolves to a private address (DNS-rebinding / split-horizon DNS)
 * is also blocked.
 */
final class SsrfGuard
{
    private const ALLOWED_PROTOCOLS = ['https'];

    private const BLOCKED_RANGES = [
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

        // If it is not already an IP, resolve and check the resolved address.
        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            $resolved = gethostbyname($host);
            // gethostbyname() returns the original string on failure — treat as blocked.
            if ($resolved === $host) {
                throw new RuntimeException('SFTP host could not be resolved: ' . $host); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            }
            self::assertHostNotBlocked($resolved);
        }
    }

    /**
     * Assert that a URL uses an allowed protocol and that its host is safe.
     *
     * If the URL host is not already an IP, the method resolves it and validates
     * the resolved IP too. Unresolvable hosts are blocked.
     *
     * @throws InvalidArgumentException on invalid URL / disallowed protocol.
     * @throws RuntimeException if the host is blocked or unresolvable.
     */
    public static function assertSafeUrl(string $url): void
    {
        $parsed = wp_parse_url($url);

        if (!$parsed || empty($parsed['scheme']) || !in_array($parsed['scheme'], self::ALLOWED_PROTOCOLS, true)) {
            throw new InvalidArgumentException('Invalid or disallowed URL scheme: ' . $url); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        $host = $parsed['host'] ?? '';

        self::assertHostNotBlocked($host);

        // Resolve and check DNS if the host is not already an IP.
        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            $resolved = gethostbyname($host);
            // gethostbyname() returns the original string when resolution fails — treat as blocked.
            if ($resolved === $host) {
                throw new RuntimeException('URL host could not be resolved: ' . $host); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            }
            self::assertHostNotBlocked($resolved);
        }
    }

    /**
     * Check a single host/IP literal against the blocked ranges.
     *
     * @throws RuntimeException if matched.
     */
    private static function assertHostNotBlocked(string $host): void
    {
        foreach (self::BLOCKED_RANGES as $pattern) {
            if (preg_match($pattern, $host)) {
                throw new RuntimeException('Host is in a blocked IP range: ' . $host); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            }
        }
    }
}
