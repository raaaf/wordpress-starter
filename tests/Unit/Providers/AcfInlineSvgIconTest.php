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
    public function testSvgAndPathAreAllowedInPostContext(): void
    {
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
    }

    public function testOtherContextsAreUnchanged(): void
    {
        $input = ['p' => ['class' => true]];

        $this->assertSame($input, AcfServiceProvider::allowInlineSvgIcon($input, 'data'));
    }
}
