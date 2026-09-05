<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use Tests\Support\TestCase;
use WordpressStarter\Helpers\ImageData;

/**
 * Tests for the ImageData helper, which normalizes the three shapes ACF
 * hands back for an image field (numeric attachment ID, ACF array, URL
 * string) into one consistent structure.
 */
final class ImageDataTest extends TestCase
{
    public function testResolvesNumericAttachmentId(): void
    {
        $GLOBALS['wp_mock_attachments'][42]['hero-split'] = ['https://example.test/img.jpg', 800, 600];
        $GLOBALS['wp_mock_post_meta'][42]['_wp_attachment_image_alt'] = 'Alt text';

        $result = ImageData::resolve(42, 'hero-split');

        $this->assertSame([
            'id' => 42,
            'url' => 'https://example.test/img.jpg',
            'alt' => 'Alt text',
            'width' => 800,
            'height' => 600,
        ], $result);
    }

    public function testResolvesAcfArrayWithId(): void
    {
        $GLOBALS['wp_mock_attachments'][7]['hero-background'] = ['https://example.test/bg.jpg', 1920, 1080];

        $result = ImageData::resolve(['ID' => 7], 'hero-background');

        $this->assertSame(7, $result['id']);
        $this->assertSame('https://example.test/bg.jpg', $result['url']);
    }

    public function testResolvesUrlOnlyArrayFallback(): void
    {
        $result = ImageData::resolve([
            'url' => 'https://example.test/manual.jpg',
            'alt' => 'Manual alt',
            'width' => 400,
            'height' => 300,
        ], 'hero-split');

        $this->assertSame([
            'id' => null,
            'url' => 'https://example.test/manual.jpg',
            'alt' => 'Manual alt',
            'width' => 400,
            'height' => 300,
        ], $result);
    }

    public function testResolvesPlainUrlString(): void
    {
        $result = ImageData::resolve('https://example.test/plain.jpg', 'hero-split');

        $this->assertSame('https://example.test/plain.jpg', $result['url']);
        $this->assertNull($result['id']);
        $this->assertSame('', $result['alt']);
    }

    public function testReturnsNullForEmptyValue(): void
    {
        $this->assertNull(ImageData::resolve(null, 'hero-split'));
        $this->assertNull(ImageData::resolve('', 'hero-split'));
        $this->assertNull(ImageData::resolve([], 'hero-split'));
    }

    public function testRejectsJavascriptSchemeUrlString(): void
    {
        $this->assertNull(ImageData::resolve('javascript:alert(1)', 'hero-split'));
    }

    public function testRejectsDataSchemeUrlInArrayFallback(): void
    {
        $this->assertNull(ImageData::resolve([
            'url' => 'data:text/html,<script>alert(1)</script>',
        ], 'hero-split'));
    }

    public function testAcceptsRootRelativeUrlString(): void
    {
        $result = ImageData::resolve('/wp-content/uploads/manual.jpg', 'hero-split');

        $this->assertSame('/wp-content/uploads/manual.jpg', $result['url']);
    }

    public function testRejectsProtocolRelativeUrlHiddenBehindTab(): void
    {
        // Browsers strip tab/newline anywhere in a URL before parsing it, so
        // "/\t/evil.test" becomes "//evil.test" - protocol-relative to a
        // foreign host - even though a naive check sees a single leading
        // slash followed by non-slash and calls it root-relative.
        $this->assertNull(ImageData::resolve("/\t/evil.test", 'hero-split'));
    }

    public function testAcceptsUppercaseHttpsScheme(): void
    {
        $result = ImageData::resolve('HTTPS://example.test/img.jpg', 'hero-split');

        $this->assertSame('HTTPS://example.test/img.jpg', $result['url']);
    }

    public function testRejectsPlainProtocolRelativeUrlString(): void
    {
        $this->assertNull(ImageData::resolve('//evil.test/img.jpg', 'hero-split'));
    }
}
