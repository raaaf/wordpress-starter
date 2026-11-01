<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\RedirectServiceProvider;

final class RedirectServiceProviderTest extends TestCase
{
    private const MAP = ['/warum-mit-uns/' => '/ueber-uns/'];

    public function testHitWithTrailingSlash(): void
    {
        $this->assertSame('/ueber-uns/', RedirectServiceProvider::resolve('/warum-mit-uns/', '/', self::MAP));
    }

    public function testHitWithoutTrailingSlashAndCaseInsensitive(): void
    {
        $this->assertSame('/ueber-uns/', RedirectServiceProvider::resolve('/Warum-Mit-Uns', '/', self::MAP));
    }

    public function testSubdirectoryPrefixIsStripped(): void
    {
        $this->assertSame('/ueber-uns/', RedirectServiceProvider::resolve('/blog/warum-mit-uns/', '/blog/', self::MAP));
    }

    public function testQueryStringIsKeptOnTarget(): void
    {
        $this->assertSame('/ueber-uns/?a=1&b=2', RedirectServiceProvider::resolve('/warum-mit-uns/?a=1&b=2', '/', self::MAP));
    }

    public function testUnknownPathReturnsNull(): void
    {
        $this->assertNull(RedirectServiceProvider::resolve('/unbekannt/', '/', self::MAP));
    }

    public function testUnsafeTargetsAreIgnored(): void
    {
        foreach (['//evil', 'https://x', '\\x', '/a//b', '/a\\b'] as $bad) {
            $this->assertNull(RedirectServiceProvider::resolve('/alt/', '/', ['/alt/' => $bad]), $bad);
        }
    }

    public function testSelfRedirectIsIgnored(): void
    {
        $this->assertNull(RedirectServiceProvider::resolve('/alt/', '/', ['/alt/' => '/alt']));
    }

    public function testPercentEncodedRequestMatchesDecodedMapKey(): void
    {
        $this->assertSame('/ueber-uns/', RedirectServiceProvider::resolve('/%C3%BCber-uns/', '/', ['/über-uns/' => '/ueber-uns/']));
    }

    public function testQueryIsJoinedToTargetQueryWithAmpersand(): void
    {
        $this->assertSame('/neu/?a=1&b=2', RedirectServiceProvider::resolve('/alt/?b=2', '/', ['/alt/' => '/neu/?a=1']));
    }

    public function testQueryIsInsertedBeforeTargetFragment(): void
    {
        $this->assertSame('/neu/?b=2#k', RedirectServiceProvider::resolve('/alt/?b=2', '/', ['/alt/' => '/neu/#k']));
    }
}
