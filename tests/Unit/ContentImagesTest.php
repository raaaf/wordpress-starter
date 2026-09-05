<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;
use WordpressStarter\Support\ContentImages;

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

    public function testAttributeValueContainingGreaterThanStillGetsAnnotated(): void
    {
        // Regression test: the tag-boundary regex in addAttachmentIds() is
        // quote-aware, so an attribute value containing a literal ">"
        // (e.g. pasted text "a > b") does not truncate the <img> tag match
        // before "src=" is reached.
        $GLOBALS['wp_mock_attachments']['https://example.test/a.webp'] = 3;

        $html = '<img alt="a > b" src="https://example.test/a.webp" />';

        $result = ContentImages::addAttachmentIds($html);

        $this->assertSame(
            '<img class="wp-image-3" alt="a > b" src="https://example.test/a.webp" />',
            $result,
            'Attribute value containing ">" must not truncate the <img> tag match before "src=" is reached.',
        );
    }

    public function testClassValueWithARepeatedSubstringIsAppendedOnce(): void
    {
        // "lazy-wrapper" contains "lazy" as a substring; this must not cause the
        // attachment class to be appended more than once.
        $GLOBALS['wp_mock_attachments']['https://example.test/a.webp'] = 8;

        $result = ContentImages::addAttachmentIds(
            '<img class="lazy lazy-wrapper" src="https://example.test/a.webp" />',
        );

        $this->assertSame(
            '<img class="lazy lazy-wrapper wp-image-8" src="https://example.test/a.webp" />',
            $result,
        );
    }

    public function testDoesNotCorruptADuplicateIdenticalClassAttribute(): void
    {
        // Regression test: str_replace() on the matched class attribute string
        // rewrites every identical occurrence in the tag, not just the one that
        // was matched. Malformed markup with a duplicated class attribute (both
        // carrying the same value) exposes this: only the first, matched
        // occurrence may receive the wp-image-* class.
        $GLOBALS['wp_mock_attachments']['https://example.test/a.webp'] = 9;

        $html = '<img class="lazy lazy-wrapper" data-x="y" class="lazy lazy-wrapper" '
            . 'src="https://example.test/a.webp" />';

        $result = ContentImages::addAttachmentIds($html);

        $this->assertSame(
            '<img class="lazy lazy-wrapper wp-image-9" data-x="y" class="lazy lazy-wrapper" '
            . 'src="https://example.test/a.webp" />',
            $result,
        );
    }

    public function testImageWithAnUnbalancedQuoteInAnAttributeIsLeftUntouched(): void
    {
        // The tag-boundary regex requires every quote it opens to close before
        // the tag's own ">". An unterminated attribute value (missing closing
        // quote) makes the whole <img ...> unmatchable, so it is left exactly
        // as-is instead of being rewritten or crashing.
        $GLOBALS['wp_mock_attachments']['https://example.test/a.webp'] = 3;

        $html = '<img src="https://example.test/a.webp" alt="broken /><p>tail</p>';

        $result = ContentImages::addAttachmentIds($html);

        $this->assertSame($html, $result);
    }

    public function testSrcNestedInsideAnotherAttributesValueIsNotMistakenForTheRealSrc(): void
    {
        // Regression test: the src/class lookup is quote-aware and attribute-
        // boundary aware, so a "src=" occurring inside another attribute's
        // quoted value (e.g. a pasted caption containing markup) is never
        // mistaken for the tag's own src attribute.
        $GLOBALS['wp_mock_attachments']['https://example.test/real.webp'] = 21;

        $html = '<img data-caption="<img src=\'https://example.test/fake.webp\'>" '
            . 'src="https://example.test/real.webp" class="size-content" />';

        $result = ContentImages::addAttachmentIds($html);

        $this->assertSame(
            '<img data-caption="<img src=\'https://example.test/fake.webp\'>" '
            . 'src="https://example.test/real.webp" class="size-content wp-image-21" />',
            $result,
        );
    }

    public function testAttributeValueContainingAnEmbeddedQuoteStillResolves(): void
    {
        $GLOBALS['wp_mock_attachments']['https://example.test/a.webp'] = 4;

        $html = '<img alt=\'x"y\' src="https://example.test/a.webp" />';

        $result = ContentImages::addAttachmentIds($html);

        $this->assertStringContainsString('wp-image-4', $result);
        $this->assertStringContainsString('alt=\'x"y\'', $result, 'The attribute quoted with the other quote style must survive untouched.');
    }
}
