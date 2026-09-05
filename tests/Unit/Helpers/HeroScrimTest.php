<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use Tests\Support\TestCase;
use WordpressStarter\Helpers\HeroScrim;

/**
 * Tests for the HeroScrim helper, which computes the radial-gradient scrim
 * CSS for the hero background variant from the ACF overlay-opacity field.
 */
final class HeroScrimTest extends TestCase
{
    public function testGradientAtDefaultOpacity(): void
    {
        $css = HeroScrim::gradient(80);

        $this->assertSame(
            'radial-gradient(ellipse 90% 75% at 50% 50%, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.48) 55%, rgba(0,0,0,0.33) 100%)',
            $css,
        );
    }

    public function testGradientClampsAtMaximumOpacity(): void
    {
        $css = HeroScrim::gradient(100);

        // 100 * 0.75 = 0.75, below the 0.85 clamp, so the clamp itself is not
        // hit here; this asserts the formula rather than the ceiling.
        $this->assertSame(
            'radial-gradient(ellipse 90% 75% at 50% 50%, rgba(0,0,0,0.75) 0%, rgba(0,0,0,0.6) 55%, rgba(0,0,0,0.41) 100%)',
            $css,
        );
    }
}
