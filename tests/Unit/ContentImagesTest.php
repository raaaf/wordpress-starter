<?php

declare(strict_types=1);

namespace Tests\Unit;

use WordpressStarter\Support\ContentImages;
use Tests\Support\TestCase;

/**
 * Tests for restoring attachment references on content images.
 */
final class ContentImagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['wp_mock_attachments'] = [];
        $GLOBALS['wp_mock_cache'] = [];
    }

    public function testLeavesContentWithoutImagesUntouched(): void
    {
        $html = '<p>Nur ein <strong>Absatz</strong>.</p>';

        $this->assertSame($html, ContentImages::addAttachmentIds($html));
    }

    public function testLeavesImagesThatAlreadyCarryAnAttachmentClass(): void
    {
        $html = '<img class="alignnone size-full wp-image-644" src="https://example.test/a.webp" />';

        $this->assertSame($html, ContentImages::addAttachmentIds($html));
    }

    public function testLeavesImagesThatCannotBeResolved(): void
    {
        $html = '<img class="size-content" src="https://example.test/unknown.webp" />';

        $this->assertSame($html, ContentImages::addAttachmentIds($html));
    }

    public function testHandlesImagesWithoutSrcAttribute(): void
    {
        $html = '<img class="size-content" alt="" />';

        $this->assertSame($html, ContentImages::addAttachmentIds($html));
    }

    public function testAddsTheAttachmentClassToAnExistingClassList(): void
    {
        $GLOBALS['wp_mock_attachments']['https://example.test/a.webp'] = 12;

        $result = ContentImages::addAttachmentIds('<img class="size-content" src="https://example.test/a.webp" />');

        $this->assertSame('<img class="size-content wp-image-12" src="https://example.test/a.webp" />', $result);
    }

    public function testAddsAClassAttributeWhenTheImageHasNone(): void
    {
        $GLOBALS['wp_mock_attachments']['https://example.test/a.webp'] = 7;

        $result = ContentImages::addAttachmentIds('<img src="https://example.test/a.webp" alt="" />');

        $this->assertSame('<img class="wp-image-7" src="https://example.test/a.webp" alt="" />', $result);
    }

    public function testFallsBackToTheUnsizedFilename(): void
    {
        // The editor inserts a generated size, only the original is an attachment.
        $GLOBALS['wp_mock_attachments']['https://example.test/shutterstock_2578745199.webp'] = 99;

        $result = ContentImages::addAttachmentIds(
            '<img class="size-content" src="https://example.test/shutterstock_2578745199-1792x1195.webp" />',
        );

        $this->assertStringContainsString('wp-image-99', $result);
    }

    public function testFallsBackToTheScaledOriginal(): void
    {
        // Live case from goldene-strategie.de: the bare filename 404s, WordPress
        // registered the big image as -scaled because it exceeded the threshold.
        $GLOBALS['wp_mock_attachments']['https://example.test/shutterstock_2578745199-scaled.webp'] = 55;

        $result = ContentImages::addAttachmentIds(
            '<img class="size-content" src="https://example.test/shutterstock_2578745199-1792x1195.webp" />',
        );

        $this->assertStringContainsString('wp-image-55', $result);
    }

    public function testAnnotatesEveryImageInTheContent(): void
    {
        $GLOBALS['wp_mock_attachments']['https://example.test/a.webp'] = 1;
        $GLOBALS['wp_mock_attachments']['https://example.test/b.webp'] = 2;

        $result = ContentImages::addAttachmentIds(
            '<p><img src="https://example.test/a.webp" /></p><p><img src="https://example.test/b.webp" /></p>',
        );

        $this->assertStringContainsString('wp-image-1', $result);
        $this->assertStringContainsString('wp-image-2', $result);
    }
}
