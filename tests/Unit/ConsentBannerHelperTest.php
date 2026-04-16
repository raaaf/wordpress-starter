<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;

/**
 * Tests for the `wp_starter_consent_banner()` helper.
 */
final class ConsentBannerHelperTest extends TestCase
{
    public function testHelperFunctionExists(): void
    {
        $this->assertTrue(function_exists('wp_starter_consent_banner'));
    }

    public function testHelperEmitsNothingByDefault(): void
    {
        ob_start();
        wp_starter_consent_banner();
        $out = (string) ob_get_clean();

        $this->assertSame('', $out);
    }

    public function testHelperDispatchesActionHook(): void
    {
        add_action('wp_starter_consent_banner', function (): void {
            echo '<div class="consent-banner">Cookies?</div>';
        });

        ob_start();
        wp_starter_consent_banner();
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('consent-banner', $out);
        $this->assertStringContainsString('Cookies?', $out);
    }
}
