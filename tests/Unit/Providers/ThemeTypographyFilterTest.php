<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\ThemeServiceProvider;

/**
 * Editor text gets the German typography glue through the content filters.
 */
final class ThemeTypographyFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ( new ThemeServiceProvider() )->boot();
    }

    public function testContentFilterGluesPercentSign(): void
    {
        $out = apply_filters('the_content', '<p class="a b">Rendite 50 % p. a.</p>');

        $this->assertSame("<p class=\"a b\">Rendite 50\u{00A0}% p.\u{00A0}a.</p>", $out);
    }

    public function testWysiwygFilterIsRegisteredAndIdempotent(): void
    {
        $once = apply_filters('acf/format_value/type=wysiwyg', '<p>50 % und 5 kWh</p>');
        $twice = apply_filters('the_content', apply_filters('acf/format_value/type=wysiwyg', '<p>50 % und 5 kWh</p>'));

        $this->assertSame("<p>50\u{00A0}% und 5\u{00A0}kWh</p>", $once);
        $this->assertSame($once, $twice);
    }
}
