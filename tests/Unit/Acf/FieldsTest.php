<?php

declare(strict_types=1);

namespace Tests\Unit\Acf;

use Tests\Support\TestCase;
use WordpressStarter\Acf\Fields;

/**
 * Tests for ACF Fields helper class.
 */
final class FieldsTest extends TestCase
{
    // ==========================================
    // get() method tests
    // ==========================================

    public function testGetReturnsFieldValue(): void
    {
        $this->setMockField('test_field', 'test_value');

        $result = Fields::get('test_field');

        $this->assertSame('test_value', $result);
    }

    public function testGetReturnsDefaultForNullValue(): void
    {
        $this->setMockField('null_field', null);

        $result = Fields::get('null_field', null, 'default_value');

        $this->assertSame('default_value', $result);
    }

    public function testGetReturnsDefaultForFalseValue(): void
    {
        $this->setMockField('false_field', false);

        $result = Fields::get('false_field', null, 'default_value');

        $this->assertSame('default_value', $result);
    }

    public function testGetReturnsZeroAsValidValue(): void
    {
        $this->setMockField('zero_field', 0);

        $result = Fields::get('zero_field', null, 'default_value');

        $this->assertSame(0, $result);
    }

    public function testGetWithPostId(): void
    {
        $this->setMockField('post_field', 'post_value', 123);

        $result = Fields::get('post_field', 123);

        $this->assertSame('post_value', $result);
    }

    // ==========================================
    // option() method tests
    // ==========================================

    public function testOptionReturnsCachedValue(): void
    {
        $this->setMockCache('wordpress_starter_theme_acf_option_cached_field', 'cached_value', 'theme');

        $result = Fields::option('cached_field');

        $this->assertSame('cached_value', $result);
    }

    public function testOptionCachesNewValue(): void
    {
        $this->setMockField('option_field', 'fresh_value', 'option');

        $result = Fields::option('option_field');

        $this->assertSame('fresh_value', $result);
        $this->assertSame('fresh_value', $this->getMockCache('wordpress_starter_theme_acf_option_option_field', 'theme'));
    }

    public function testOptionReturnsDefaultForNullField(): void
    {
        $this->setMockField('empty_option', null, 'option');

        $result = Fields::option('empty_option', 'fallback');

        $this->assertSame('fallback', $result);
    }

    // ==========================================
    // repeater() method tests
    // ==========================================

    public function testRepeaterReturnsEmptyArrayForNonArray(): void
    {
        $this->setMockField('string_repeater', 'not an array');

        $result = Fields::repeater('string_repeater');

        $this->assertSame([], $result);
    }

    public function testRepeaterReturnsArrayAsIs(): void
    {
        $items = [
            ['title' => 'Item 1'],
            ['title' => 'Item 2'],
        ];
        $this->setMockField('valid_repeater', $items);

        $result = Fields::repeater('valid_repeater');

        $this->assertSame($items, $result);
    }

    public function testRepeaterReturnsEmptyArrayForNull(): void
    {
        $this->setMockField('null_repeater', null);

        $result = Fields::repeater('null_repeater');

        $this->assertSame([], $result);
    }

    // ==========================================
    // flexible() method tests
    // ==========================================

    public function testFlexibleReturnsArrayOfLayouts(): void
    {
        $layouts = [
            ['acf_fc_layout' => 'hero', 'title' => 'Hero Title'],
            ['acf_fc_layout' => 'cta', 'button' => 'Click Me'],
        ];
        $this->setMockField('flexible_content', $layouts);

        $result = Fields::flexible('flexible_content');

        $this->assertSame($layouts, $result);
    }

    // ==========================================
    // group() method tests
    // ==========================================

    public function testGroupReturnsGroupData(): void
    {
        $groupData = ['name' => 'John', 'email' => 'john@example.com'];
        $this->setMockField('group_field', $groupData);

        $result = Fields::group('group_field');

        $this->assertSame($groupData, $result);
    }

    public function testGroupReturnsEmptyArrayForNonArray(): void
    {
        $this->setMockField('invalid_group', 'string value');

        $result = Fields::group('invalid_group');

        $this->assertSame([], $result);
    }

    // ==========================================
    // has() method tests
    // ==========================================

    public function testHasReturnsTrueForNonEmptyValue(): void
    {
        $this->setMockField('has_value', 'content');

        $result = Fields::has('has_value');

        $this->assertTrue($result);
    }

    public function testHasReturnsFalseForEmptyArray(): void
    {
        $this->setMockField('empty_array', []);

        $result = Fields::has('empty_array');

        $this->assertFalse($result);
    }

    public function testHasReturnsFalseForEmptyString(): void
    {
        $this->setMockField('empty_string', '');

        $result = Fields::has('empty_string');

        $this->assertFalse($result);
    }

    public function testHasReturnsFalseForNull(): void
    {
        $this->setMockField('null_value', null);

        $result = Fields::has('null_value');

        $this->assertFalse($result);
    }

    public function testHasReturnsFalseForFalse(): void
    {
        $this->setMockField('false_value', false);

        $result = Fields::has('false_value');

        $this->assertFalse($result);
    }

    public function testHasReturnsTrueForNonEmptyArray(): void
    {
        $this->setMockField('filled_array', ['item']);

        $result = Fields::has('filled_array');

        $this->assertTrue($result);
    }

    public function testHasReturnsTrueForZero(): void
    {
        $this->setMockField('zero_value', 0);

        $result = Fields::has('zero_value');

        $this->assertTrue($result);
    }

    // ==========================================
    // image() method tests
    // ==========================================

    public function testImageReturnsNullForNoImage(): void
    {
        $this->setMockField('no_image', null);

        $result = Fields::image('no_image');

        $this->assertNull($result);
    }

    public function testImageReturnsArrayForValidImageId(): void
    {
        $this->setMockField('image_field', 42);
        $this->setMockAttachment(42, 'full', ['https://example.com/image.jpg', 800, 600]);
        $this->setMockPostMeta(42, '_wp_attachment_image_alt', 'Alt Text');

        $result = Fields::image('image_field', 'full');

        $this->assertIsArray($result);
        $this->assertSame('https://example.com/image.jpg', $result['url']);
        $this->assertSame(800, $result['width']);
        $this->assertSame(600, $result['height']);
        $this->assertSame('Alt Text', $result['alt']);
    }

    public function testImageReturnsDirectlyIfAlreadyArray(): void
    {
        $imageArray = [
            'url' => 'https://example.com/preset.jpg',
            'width' => 1920,
            'height' => 1080,
            'alt' => 'Preset Alt',
        ];
        $this->setMockField('array_image', $imageArray);

        $result = Fields::image('array_image');

        $this->assertSame($imageArray, $result);
    }

    public function testImageReturnsNullForInvalidAttachment(): void
    {
        $this->setMockField('broken_image', 999);
        // No attachment mock set, so wp_get_attachment_image_src returns false

        $result = Fields::image('broken_image');

        $this->assertNull($result);
    }

    // ==========================================
    // responsiveImage() method tests
    // ==========================================

    public function testResponsiveImageReturnsHtml(): void
    {
        $this->setMockField('responsive_image', 50);
        $this->setMockAttachment(50, 'large', ['https://example.com/large.jpg', 1024, 768]);

        $result = Fields::responsiveImage('responsive_image', 'large');

        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString('src="https://example.com/large.jpg"', $result);
    }

    public function testResponsiveImageReturnsEmptyForArrayValue(): void
    {
        $this->setMockField('array_value', ['id' => 50, 'url' => 'test.jpg']);

        $result = Fields::responsiveImage('array_value');

        $this->assertSame('', $result);
    }

    // ==========================================
    // link() method tests
    // ==========================================

    public function testLinkReturnsNullForInvalidData(): void
    {
        $this->setMockField('invalid_link', 'not a link array');

        $result = Fields::link('invalid_link');

        $this->assertNull($result);
    }

    public function testLinkReturnsNullForMissingUrl(): void
    {
        $this->setMockField('no_url_link', ['title' => 'Title', 'target' => '_self']);

        $result = Fields::link('no_url_link');

        $this->assertNull($result);
    }

    public function testLinkReturnsNormalizedArray(): void
    {
        $linkData = [
            'url' => 'https://example.com',
            'title' => 'Example',
            'target' => '_blank',
        ];
        $this->setMockField('valid_link', $linkData);

        $result = Fields::link('valid_link');

        $this->assertIsArray($result);
        $this->assertSame('https://example.com', $result['url']);
        $this->assertSame('Example', $result['title']);
        $this->assertSame('_blank', $result['target']);
    }

    public function testLinkUsesDefaultTargetWhenMissing(): void
    {
        $linkData = [
            'url' => 'https://example.com',
            'title' => 'No Target',
        ];
        $this->setMockField('no_target_link', $linkData);

        $result = Fields::link('no_target_link');

        $this->assertSame('_self', $result['target']);
    }

    // ==========================================
    // linkHtml() method tests
    // ==========================================

    public function testLinkHtmlGeneratesCorrectMarkup(): void
    {
        $linkData = [
            'url' => 'https://example.com',
            'title' => 'Click Here',
            'target' => '_self',
        ];
        $this->setMockField('html_link', $linkData);

        $result = Fields::linkHtml('html_link');

        $this->assertStringContainsString('<a', $result);
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringContainsString('target="_self"', $result);
        $this->assertStringContainsString('>Click Here</a>', $result);
    }

    public function testLinkHtmlAddsRelForBlankTarget(): void
    {
        $linkData = [
            'url' => 'https://external.com',
            'title' => 'External',
            'target' => '_blank',
        ];
        $this->setMockField('external_link', $linkData);

        $result = Fields::linkHtml('external_link');

        $this->assertStringContainsString('rel="noopener noreferrer"', $result);
    }

    public function testLinkHtmlAddsClassWhenProvided(): void
    {
        $linkData = [
            'url' => 'https://example.com',
            'title' => 'Styled',
            'target' => '_self',
        ];
        $this->setMockField('styled_link', $linkData);

        $result = Fields::linkHtml('styled_link', 'btn btn-primary');

        $this->assertStringContainsString('class="btn btn-primary"', $result);
    }

    public function testLinkHtmlReturnsEmptyForInvalidLink(): void
    {
        $this->setMockField('broken_link', null);

        $result = Fields::linkHtml('broken_link');

        $this->assertSame('', $result);
    }

    public function testLinkHtmlEscapesSpecialCharacters(): void
    {
        $linkData = [
            'url' => 'https://example.com?foo=bar&baz=qux',
            'title' => 'Link with <script>',
            'target' => '_self',
        ];
        $this->setMockField('xss_link', $linkData);

        $result = Fields::linkHtml('xss_link');

        $this->assertStringContainsString('&amp;', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }
}
