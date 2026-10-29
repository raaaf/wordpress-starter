<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\BrandingServiceProvider;

/**
 * The web manifest is served per site by the theme (no static file, every blog of
 * the network has its own name and icons) and the extra icon links are optional.
 *
 * Path matching is the part that can go wrong: in the subdirectory network the
 * manifest of a sub-directory site lives at /blog/site.webmanifest, and a manifest request of
 * one blog must never be answered for another blog's path.
 */
final class BrandingManifestTest extends TestCase
{
    public function testManifestRequestOnRootHome(): void
    {
        $this->assertTrue(BrandingServiceProvider::isManifestRequest('/site.webmanifest', '/'));
        $this->assertTrue(BrandingServiceProvider::isManifestRequest('/site.webmanifest?v=2', '/'));
    }

    public function testManifestRequestUnderSubdirectoryHome(): void
    {
        $this->assertTrue(BrandingServiceProvider::isManifestRequest('/blog/site.webmanifest', '/blog/'));
        $this->assertFalse(BrandingServiceProvider::isManifestRequest('/site.webmanifest', '/blog/'));
        $this->assertFalse(BrandingServiceProvider::isManifestRequest('/blog/site.webmanifest', '/'));
    }

    public function testOtherPathsAreNotManifestRequests(): void
    {
        $this->assertFalse(BrandingServiceProvider::isManifestRequest('/impressum/', '/'));
        $this->assertFalse(BrandingServiceProvider::isManifestRequest('/impressum/site.webmanifest', '/'));
    }

    public function testManifestHasBrandColourAndOmitsIconsWhenThereAreNone(): void
    {
        $manifest = BrandingServiceProvider::buildManifest('Beispiel GmbH', 'Beispiel', 'https://example.com/blog/', []);

        $this->assertSame('#F5F5F5', $manifest['theme_color']);
        $this->assertSame('#ffffff', $manifest['background_color']);
        $this->assertSame('Beispiel', $manifest['short_name']);
        $this->assertArrayNotHasKey('icons', $manifest);
    }

    public function testShortNameFallsBackToName(): void
    {
        $manifest = BrandingServiceProvider::buildManifest('Beispiel', '', 'https://example.com/', []);

        $this->assertSame('Beispiel', $manifest['short_name']);
    }

    public function testManifestCarriesTheGivenIcons(): void
    {
        $icons = [['src' => 'https://example.com/i-192.png', 'sizes' => '192x192', 'type' => 'image/png']];
        $manifest = BrandingServiceProvider::buildManifest('A', 'A', 'https://example.com/', $icons);

        $this->assertSame($icons, $manifest['icons']);
    }

    public function testIconLinksOnlyOutputWhatIsGiven(): void
    {
        $this->assertSame('', BrandingServiceProvider::iconLinks(null, null, null));

        $html = BrandingServiceProvider::iconLinks('https://example.com/f.svg', null, 'https://example.com/site.webmanifest');
        $this->assertStringContainsString('type="image/svg+xml" sizes="any" href="https://example.com/f.svg"', $html);
        $this->assertStringContainsString('rel="manifest" href="https://example.com/site.webmanifest"', $html);
        $this->assertStringNotContainsString('image/x-icon', $html);

        $html = BrandingServiceProvider::iconLinks(null, 'https://example.com/f.ico', null);
        $this->assertStringContainsString('sizes="16x16 32x32 48x48" href="https://example.com/f.ico"', $html);
        $this->assertStringNotContainsString('rel="manifest"', $html);
    }
}
