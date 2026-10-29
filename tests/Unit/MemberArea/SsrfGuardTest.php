<?php

declare(strict_types=1);

namespace Tests\Unit\MemberArea;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\TestCase;
use WordpressStarter\MemberArea\SsrfGuard;

/**
 * Tests for the blocked-range and scheme branches of SsrfGuard (no DNS needed).
 */
final class SsrfGuardTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function blockedHosts(): array
    {
        return [
            'localhost'       => ['localhost'],
            'localhost caps'  => ['LOCALHOST'],
            'loopback v4'     => ['127.0.0.1'],
            'private 10/8'    => ['10.0.0.5'],
            'private 172.16'  => ['172.16.4.1'],
            'private 192.168' => ['192.168.1.10'],
            'link-local v4'   => ['169.254.169.254'],
            'reserved 240/4'  => ['240.0.0.1'],
            'unspecified'     => ['0.0.0.0'],
            'loopback v6'     => ['::1'],
            'unique local v6' => ['fd00::1'],
            'link-local v6'   => ['fe80::1'],
        ];
    }

    #[DataProvider('blockedHosts')]
    public function testAssertSafeHostRejectsBlockedHosts(string $host): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('blocked IP range');

        SsrfGuard::assertSafeHost($host);
    }

    public function testAssertSafeHostAcceptsPublicIpLiteral(): void
    {
        SsrfGuard::assertSafeHost('93.184.216.34');

        $this->addToAssertionCount(1);
    }

    #[DataProvider('blockedHosts')]
    public function testAssertSafeUrlRejectsBlockedHosts(string $host): void
    {
        // Bracketed IPv6 hosts keep their brackets after wp_parse_url() and are rejected as
        // unresolvable, so only the exception type is pinned here.
        $this->expectException(RuntimeException::class);

        $literal = str_contains($host, ':') ? '[' . $host . ']' : $host;
        SsrfGuard::assertSafeUrl('https://' . $literal . '/file.pdf');
    }

    public function testAssertSafeUrlRejectsHttpScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SsrfGuard::assertSafeUrl('http://93.184.216.34/file.pdf');
    }

    public function testAssertSafeUrlRejectsNonHttpScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SsrfGuard::assertSafeUrl('ftp://93.184.216.34/file.pdf');
    }

    public function testAssertSafeUrlRejectsMissingScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SsrfGuard::assertSafeUrl('93.184.216.34/file.pdf');
    }

    public function testAssertSafeUrlAcceptsHttpsPublicIp(): void
    {
        SsrfGuard::assertSafeUrl('https://93.184.216.34/file.pdf');

        $this->addToAssertionCount(1);
    }
}
