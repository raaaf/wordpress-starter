<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use Tests\Support\TestCase;
use WordpressStarter\Helpers\ColorScheme;

/**
 * Light-only sites must not hand the browser a dark theme-color: the page
 * stays light, so a dark address bar on a dark-mode phone would not match.
 */
final class ColorSchemeTest extends TestCase
{
    public function testLightOnlyEmitsOnlyTheLightThemeColorWithoutMediaQuery(): void
    {
        $this->assertSame('<meta name="theme-color" content="#F5F5F5">', ColorScheme::themeColorMeta('light'));
    }

    public function testDarkOnlyEmitsOnlyTheDarkThemeColor(): void
    {
        $this->assertSame('<meta name="theme-color" content="#000000">', ColorScheme::themeColorMeta('dark'));
    }

    public function testSystemEmitsOneThemeColorPerSchemeWithMediaQuery(): void
    {
        $meta = ColorScheme::themeColorMeta('system');

        $this->assertStringContainsString('content="#F5F5F5" media="(prefers-color-scheme: light)"', $meta);
        $this->assertStringContainsString('content="#000000" media="(prefers-color-scheme: dark)"', $meta);
    }

    public function testUnknownValueFallsBackToSystem(): void
    {
        $this->assertSame('system', ColorScheme::normalize(''));
        $this->assertSame('system', ColorScheme::normalize(null));
        $this->assertSame('light', ColorScheme::normalize('light'));
    }
}
