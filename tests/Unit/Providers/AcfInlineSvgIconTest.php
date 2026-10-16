<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\AcfServiceProvider;

/**
 * Pins the kses allowance that keeps the [icon] shortcode SVG in WYSIWYG output.
 */
final class AcfInlineSvgIconTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        unset($GLOBALS['wp_mock_is_admin'], $GLOBALS['wp_mock_current_user_can']['unfiltered_html']);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['wp_mock_is_admin'], $GLOBALS['wp_mock_current_user_can']['unfiltered_html']);
        parent::tearDown();
    }

    public function testSvgAndPathAreAllowedInPostContext(): void
    {
        $GLOBALS['wp_mock_is_admin'] = false;

        $tags = AcfServiceProvider::allowInlineSvgIcon([], 'post');

        $this->assertArrayHasKey('svg', $tags);
        $this->assertArrayHasKey('path', $tags);
        foreach (['class', 'aria-hidden', 'viewbox', 'fill', 'xmlns'] as $attr) {
            $this->assertArrayHasKey($attr, $tags['svg']);
        }
        foreach (['d', 'fill'] as $attr) {
            $this->assertArrayHasKey($attr, $tags['path']);
        }
        $this->assertArrayNotHasKey('style', $tags['svg']);
        $this->assertArrayNotHasKey('use', $tags);
        $this->assertArrayNotHasKey('width', $tags['svg']);
    }

    public function testOtherContextsAreUnchanged(): void
    {
        $input = ['p' => ['class' => true]];

        $this->assertSame($input, AcfServiceProvider::allowInlineSvgIcon($input, 'data'));
    }

    public function testDeniedInAdminWithoutUnfilteredHtml(): void
    {
        $GLOBALS['wp_mock_is_admin'] = true;
        $GLOBALS['wp_mock_current_user_can']['unfiltered_html'] = false;
        $input = ['p' => ['class' => true]];

        $this->assertSame($input, AcfServiceProvider::allowInlineSvgIcon($input, 'post'));
    }

    public function testAllowedInAdminWithUnfilteredHtml(): void
    {
        $GLOBALS['wp_mock_is_admin'] = true;
        $GLOBALS['wp_mock_current_user_can']['unfiltered_html'] = true;

        $tags = AcfServiceProvider::allowInlineSvgIcon([], 'post');

        $this->assertArrayHasKey('svg', $tags);
    }

    public function testExistingSvgAttributesSurvive(): void
    {
        $GLOBALS['wp_mock_is_admin'] = false;

        $tags = AcfServiceProvider::allowInlineSvgIcon(['svg' => ['data-x' => true]], 'post');

        $this->assertArrayHasKey('data-x', $tags['svg']);
        $this->assertArrayHasKey('class', $tags['svg']);
    }
}
