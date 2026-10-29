<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\BrandingServiceProvider;

/**
 * Favicon and apple-touch-icon must not point at WebP sub-sizes of a PNG
 * upload: iOS ignores WebP touch icons and the link tags announce PNG.
 */
final class BrandingIconUrlTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['wp_mock_attachments']);
        parent::tearDown();
    }

    public function testWebpSubSizeOfAPngUploadFallsBackToTheOriginal(): void
    {
        $GLOBALS['wp_mock_attachments'][7] = [
            'full' => ['https://example.com/favicon.png', 512, 512],
            '180x180' => ['https://example.com/favicon-300x300.webp', 300, 300],
        ];

        $this->assertSame('https://example.com/favicon.png', BrandingServiceProvider::iconUrl(7, 180));
    }

    public function testConvertedMainFileFallsBackToTheUploadedPng(): void
    {
        // The WebP conversion replaces the main file too; the PNG survives as original_image.
        $GLOBALS['wp_mock_attachments'][7] = [
            'original' => ['https://example.com/favicon-512.png', 512, 512],
            'full' => ['https://example.com/favicon-512.webp', 512, 512],
            '180x180' => ['https://example.com/favicon-512-300x300.webp', 300, 300],
        ];

        $this->assertSame('https://example.com/favicon-512.png', BrandingServiceProvider::iconUrl(7, 180));
    }

    public function testSubSizeInTheSameFormatIsKept(): void
    {
        $GLOBALS['wp_mock_attachments'][7] = [
            'full' => ['https://example.com/favicon.png', 512, 512],
            '32x32' => ['https://example.com/favicon-32x32.png', 32, 32],
        ];

        $this->assertSame('https://example.com/favicon-32x32.png', BrandingServiceProvider::iconUrl(7, 32));
    }
}
