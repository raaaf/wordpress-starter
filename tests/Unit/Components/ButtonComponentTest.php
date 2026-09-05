<?php

declare(strict_types=1);

namespace Tests\Unit\Components;

use Tests\Support\TestCase;
use WordpressStarter\Application;

/**
 * Tests for the x-button and x-link components' disabled/aria-label handling.
 *
 * Renders raw <x-...> component-tag strings through a throwaway view file so
 * the Blade component tag compiler resolves them exactly as callers use them.
 */
final class ButtonComponentTest extends TestCase
{
    private string $probeDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->probeDir = sys_get_temp_dir() . '/button-component-probe-' . uniqid();
        mkdir($this->probeDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->probeDir . '/*.blade.php') ?: []);
        rmdir($this->probeDir);
        parent::tearDown();
    }

    /**
     * Renders raw component-tag markup. Registers BOTH the theme's real
     * templates/ directory (so x-button/x-link resolve to the actual
     * components) AND the throwaway probe directory (so the markup itself
     * can be found as a view) as view-finder locations.
     */
    private function renderMarkup(string $markup): string
    {
        $view = 'probe-' . uniqid();
        file_put_contents($this->probeDir . '/' . $view . '.blade.php', $markup);

        $app = Application::getInstance();
        $app->boot();

        $factory = blade();
        $finder = $factory->getFinder();
        $finder->addLocation(dirname(__DIR__, 3) . '/templates');
        $finder->addLocation($this->probeDir);

        return $factory->make($view, [])->render();
    }

    public function testCallerAriaLabelIsComposedWithNewTabNotice(): void
    {
        $html = $this->renderMarkup(
            '<x-button url="https://x.test" target="_blank" aria-label="LinkedIn" title="" />',
        );

        $this->assertStringContainsString('aria-label="LinkedIn (öffnet in neuem Tab)"', $html);
        $this->assertSame(1, substr_count($html, 'öffnet in neuem Tab'));
    }

    public function testWithoutAriaLabelSrOnlyNoticeAppearsOnce(): void
    {
        $html = $this->renderMarkup(
            '<x-button url="https://x.test" target="_blank" title="LinkedIn" />',
        );

        $this->assertSame(1, substr_count($html, 'sr-only'));
        $this->assertSame(1, substr_count($html, 'öffnet in neuem Tab'));
    }

    public function testDisabledLinkButtonRendersAsSpanNotAnchor(): void
    {
        $html = $this->renderMarkup(
            '<x-button url="https://x.test" title="Los" disabled="true" />',
        );

        $this->assertDoesNotMatchRegularExpression('/<a[\s>]/', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringContainsString('<span', $html);
        $this->assertStringContainsString('aria-disabled="true"', $html);
    }

    public function testEnabledLinkButtonStillRendersAsAnchor(): void
    {
        $html = $this->renderMarkup(
            '<x-button url="https://x.test" title="Los" />',
        );

        $this->assertMatchesRegularExpression('/<a[\s>]/', $html);
        $this->assertStringNotContainsString('<span', $html);
    }

    public function testUppercaseTargetIsNormalized(): void
    {
        $html = $this->renderMarkup(
            '<x-button url="https://x.test" target="_BLANK" title="Los" />',
        );

        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function testDisabledLinkComponentRendersAsSpanNotAnchor(): void
    {
        $html = $this->renderMarkup(
            '<x-link url="https://x.test" disabled="true">Los</x-link>',
        );

        $this->assertDoesNotMatchRegularExpression('/<a[\s>]/', $html);
        $this->assertStringContainsString('<span', $html);
        $this->assertStringContainsString('aria-disabled="true"', $html);
    }

    public function testEnabledLinkComponentStillRendersAsAnchor(): void
    {
        $html = $this->renderMarkup(
            '<x-link url="https://x.test">Los</x-link>',
        );

        $this->assertMatchesRegularExpression('/<a[\s>]/', $html);
        $this->assertStringNotContainsString('<span', $html);
    }

    public function testDisabledIconOnlyButtonKeepsAriaLabel(): void
    {
        $html = $this->renderMarkup(
            '<x-button url="https://x.test" title="" aria-label="LinkedIn" disabled="true" />',
        );

        $this->assertStringContainsString('<span', $html);
        $this->assertStringContainsString('aria-label="LinkedIn"', $html);
        // Disabled means it never navigates, so no new-tab notice is added.
        $this->assertStringNotContainsString('öffnet in neuem Tab', $html);
    }

    public function testLinkAriaLabelIsComposedWithNewTabNotice(): void
    {
        $html = $this->renderMarkup(
            '<x-link url="https://x.test" target="_blank" aria-label="LinkedIn">Los</x-link>',
        );

        $this->assertStringContainsString('aria-label="LinkedIn (öffnet in neuem Tab)"', $html);
        $this->assertSame(1, substr_count($html, 'öffnet in neuem Tab'));
    }

    public function testEmptyCallerAriaLabelIsTreatedAsAbsent(): void
    {
        $html = $this->renderMarkup(
            '<x-button url="https://x.test" target="_blank" title="Los" aria-label="" />',
        );

        $this->assertStringNotContainsString('aria-label=""', $html);
        $this->assertSame(1, substr_count($html, 'öffnet in neuem Tab'));
        $this->assertSame(1, substr_count($html, 'sr-only'));
    }

    public function testDisabledLinkButtonSpanDropsCursorPointerAndFocusVisible(): void
    {
        $html = $this->renderMarkup(
            '<x-button url="https://x.test" title="Los" disabled="true" />',
        );

        $this->assertStringNotContainsString('cursor-pointer', $html);
        $this->assertStringNotContainsString('focus-visible:outline-3', $html);
        $this->assertStringContainsString('cursor-not-allowed', $html);
    }

    public function testLinkUppercaseTargetIsNormalized(): void
    {
        $html = $this->renderMarkup(
            '<x-link url="https://x.test" target="_BLANK">Los</x-link>',
        );

        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringContainsString('öffnet in neuem Tab', $html);
    }

    public function testButtonLinkBranchDropsDisallowedAttributes(): void
    {
        $html = $this->renderMarkup(
            '<x-button url="https://x.test" title="Los" formaction="https://evil.test" onfocus="alert(1)" />',
        );

        $this->assertStringNotContainsString('formaction', $html);
        $this->assertStringNotContainsString('onfocus', $html);
    }

    public function testButtonFormBranchDropsDisallowedAttributes(): void
    {
        $html = $this->renderMarkup(
            '<x-button title="Los" formaction="https://evil.test" onfocus="alert(1)" />',
        );

        $this->assertStringNotContainsString('formaction', $html);
        $this->assertStringNotContainsString('onfocus', $html);
    }

    public function testButtonDisabledSpanDropsInteractiveAttributes(): void
    {
        $html = $this->renderMarkup(
            '<x-button url="https://x.test" title="Los" disabled="true" tabindex="3" name="cta" value="1" />',
        );

        preg_match('/<span\b[^>]*>/', $html, $matches);
        $spanTag = $matches[0] ?? '';

        $this->assertStringNotContainsString('tabindex', $spanTag);
        $this->assertStringNotContainsString('name=', $spanTag);
        $this->assertStringNotContainsString('value=', $spanTag);
    }
}
