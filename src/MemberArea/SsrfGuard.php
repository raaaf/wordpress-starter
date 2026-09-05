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
 *
 * That gap is knowingly left open. Two facts bound it. First, the host is not
 * attacker-supplied from the outside: it comes from `download_sftp_host` post
 * meta on a download entry (FileHandler::129, FolderSync::155), so exploiting
 * this needs editor capability already — it is an escalation from editor to
 * internal-network access, not an anonymous request. Second, the endpoints
 * involved are virtual-hosted, so pinning the connection to the IP validated
 * here is not a simple substitution. Host-key pinning now exists: SftpClient::
 * connect() pins each server's host key on first connection, trust-on-first-use,
 * keyed by host:port, and verifies it against the stored value before the
 * password is sent on every later connection. That does not close the gap
 * above, since the pin is bound to host:port rather than to the IP validated
 * here: a low-TTL DNS rebind still reaches SftpClient::connect() with a
 * different IP than this check saw, and host-key pinning does not catch that.
 * Tracked as issue #9; revisit if either bounding fact changes.
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
     * the method resolves both its A and AAAA records and validates every
     * resolved IP against the blocked ranges too.
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
     * If the URL host is not already an IP, the method resolves its A and AAAA
     * records and validates every resolved IP too. Unresolvable hosts are blocked.
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
     * If the host is not already an IP address, resolve its A and AAAA records
     * and validate every resolved IP against the blocked ranges too. Checking
     * only the A record would let a host with a public A record and an AAAA
     * record pointing at a private/reserved address (e.g. ::1) pass while the
     * connection may prefer IPv6. Shared by assertSafeHost() and assertSafeUrl()
     * so a future SSRF fix only needs to land in one place.
     *
     * @throws RuntimeException if the host cannot be resolved or any resolved IP is blocked.
     */
    private static function assertResolvedHostNotBlocked(string $host, string $unresolvableMessage): void
    {
        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            $addresses = self::resolveHost($host);

            if ($addresses === []) {
                throw new RuntimeException($unresolvableMessage . $host); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            }

            foreach ($addresses as $address) {
                self::assertHostNotBlocked($address);
            }
        }
    }

    /**
     * Resolve a hostname to all of its A and AAAA addresses.
     *
     * Uses dns_get_record() with DNS_A | DNS_AAAA so an IPv6-only or dual-stack
     * rebind cannot slip past a check that only ever looked at the A record.
     * Falls back to gethostbyname() (A records only) if dns_get_record() fails
     * or returns nothing, so hosts on a DNS setup where only the legacy lookup
     * works still resolve.
     *
     * @return string[] resolved IP addresses, empty if the host could not be resolved.
     */
    private static function resolveHost(string $host): array
    {
        $records = dns_get_record($host, DNS_A | DNS_AAAA);

        if (is_array($records) && $records !== []) {
            $addresses = [];
            foreach ($records as $record) {
                if (isset($record['ip'])) {
                    $addresses[] = $record['ip'];
                } elseif (isset($record['ipv6'])) {
                    $addresses[] = $record['ipv6'];
                }
            }

            if ($addresses !== []) {
                return $addresses;
            }
        }

        $resolved = gethostbyname($host);

        // gethostbyname() returns the original string on failure.
        return $resolved === $host ? [] : [$resolved];
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
