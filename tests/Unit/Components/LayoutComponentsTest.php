<?php

declare(strict_types=1);

namespace Tests\Unit\Components;

use Tests\Support\TestCase;
use WordpressStarter\Application;

/**
 * Contract tests for x-card, x-badge, x-section, x-grid, x-alert, x-prose.
 *
 * Renders raw <x-...> component-tag strings through a throwaway view file so
 * the Blade component tag compiler resolves them exactly as callers use them.
 * See ButtonComponentTest for the pattern this reuses.
 */
final class LayoutComponentsTest extends TestCase
{
    private string $probeDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->probeDir = sys_get_temp_dir() . '/layout-component-probe-' . uniqid();
        mkdir($this->probeDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->probeDir . '/*.blade.php') ?: []);
        rmdir($this->probeDir);
        parent::tearDown();
    }

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

    public function testCardRendersHeadingLevelAndClampsInvalidLevel(): void
    {
        $htmlH2 = $this->renderMarkup('<x-card title="T2" level="2" />');
        $htmlH4 = $this->renderMarkup('<x-card title="T4" level="4" />');
        $htmlClamped = $this->renderMarkup('<x-card title="T9" level="9" />');

        $this->assertMatchesRegularExpression('/<h2\b[^>]*>T2<\/h2>/', $htmlH2);
        $this->assertMatchesRegularExpression('/<h4\b[^>]*>T4<\/h4>/', $htmlH4);
        $this->assertMatchesRegularExpression('/<h4\b[^>]*>T9<\/h4>/', $htmlClamped);
    }

    public function testCardRootMergesCallerClass(): void
    {
        $html = $this->renderMarkup('<x-card title="T" class="my-caller-class">Body</x-card>');

        preg_match('/<div\b[^>]*\bclass="([^"]+)"/', $html, $matches);

        $this->assertStringContainsString('my-caller-class', $matches[1] ?? '');
    }

    public function testBadgeVariantAccentRendersIdenticalClassesToBrand(): void
    {
        $accentHtml = $this->renderMarkup('<x-badge variant="accent">Neu</x-badge>');
        $brandHtml = $this->renderMarkup('<x-badge variant="brand">Neu</x-badge>');

        preg_match('/<span\b[^>]*\bclass="([^"]+)"/', $accentHtml, $accent);
        preg_match('/<span\b[^>]*\bclass="([^"]+)"/', $brandHtml, $brand);

        $this->assertSame($brand[1] ?? null, $accent[1] ?? null);
    }

    public function testSectionAnchorBecomesValidIdAndDuplicateGetsDistinctId(): void
    {
        $html = $this->renderMarkup(
            '<x-section anchor="Über uns">Erst</x-section>'
            . '<x-section anchor="Über uns">Zweit</x-section>',
        );

        preg_match_all('/<section\b[^>]*\bid="([^"]+)"/', $html, $matches);
        [, $ids] = $matches;

        $this->assertCount(2, $ids);
        $this->assertDoesNotMatchRegularExpression('/\s/', $ids[0]);
        $this->assertNotSame($ids[0], $ids[1]);
    }

    public function testSectionAnchorSlugMatchesComponentIdAnchorHelper(): void
    {
        // styleguide-nav.blade.php builds its "#anchor" jump links from the
        // raw section_anchor field via ComponentId::anchor() (the same
        // sanitize_title() call section.blade.php uses for the rendered id),
        // instead of using the raw field value. Asserting the helper's
        // output against the actually-rendered id is a direct proxy for that
        // contract without needing to render the nav partial, which depends
        // on ACF row iteration state page-sections-loop.blade.php sets up.
        $html = $this->renderMarkup('<x-section anchor="Über uns">Content</x-section>');

        preg_match('/<section\b[^>]*\bid="([^"]+)"/', $html, $matches);
        $renderedId = $matches[1] ?? '';

        // Not 'ueber-uns': see ComponentIdTest::testAnchorSlugifiesUmlautsSpacesAndUppercase
        // for why the single-locale test double maps ü to plain 'u'.
        $this->assertSame('uber-uns', $renderedId);
        $this->assertSame($renderedId, \WordpressStarter\Helpers\ComponentId::anchor('Über uns'));
    }

    public function testSectionCallerSuppliedIdDoesNotDuplicateTheAnchorId(): void
    {
        $html = $this->renderMarkup('<x-section anchor="komponenten" id="caller-id">Content</x-section>');

        $this->assertSame(1, substr_count($html, ' id="'));
        $this->assertStringContainsString('id="komponenten"', $html);
        $this->assertStringNotContainsString('id="caller-id"', $html);
    }

    public function testIconOnlyStripsWidthHeightFromRootSvgNotNestedElements(): void
    {
        $iconDir = dirname(__DIR__, 2) . '/fixtures/resources/icons';
        if (!is_dir($iconDir)) {
            mkdir($iconDir, 0777, true);
        }
        $iconName = 'test-icon-' . uniqid();
        $iconPath = $iconDir . '/' . $iconName . '.svg';
        file_put_contents(
            $iconPath,
            '<svg width="24" height="24" viewBox="0 0 24 24"><rect width="10" height="10" /></svg>'
        );

        try {
            $html = $this->renderMarkup('<x-icon name="' . $iconName . '" />');
        } finally {
            unlink($iconPath);
        }

        $this->assertDoesNotMatchRegularExpression('/<svg\b[^>]*\bwidth=/', $html);
        $this->assertStringContainsString('<rect width="10" height="10" />', $html);
    }

    public function testGridKeepsExplicitGapClassAndMergesCallerClass(): void
    {
        $html = $this->renderMarkup('<x-grid gap="lg" class="my-grid-class">Content</x-grid>');

        preg_match('/<div\b[^>]*\bclass="([^"]+)"/', $html, $matches);
        $class = $matches[1] ?? '';

        $this->assertStringContainsString('gap-8', $class);
        $this->assertStringContainsString('my-grid-class', $class);
    }

    public function testAlertErrorVariantHasRoleAlertAndDismissButtonKeepsFocusVisible(): void
    {
        $html = $this->renderMarkup('<x-alert variant="error" message="Fehler" dismissible="true" />');

        $this->assertStringContainsString('role="alert"', $html);

        preg_match('/<button\b[^>]*\bclass="([^"]+)"/', $html, $matches);
        $buttonClass = $matches[1] ?? '';

        // The dismiss button carries its own component-level focus-visible
        // utility class (not just the global `:focus-visible` reset in
        // app.css), so keyboard users always get a visible ring on it.
        $this->assertStringContainsString('focus-visible:outline-3', $buttonClass);
        $this->assertStringContainsString('focus-visible:outline-offset-2', $buttonClass);
        $this->assertStringNotContainsString('outline-none', $buttonClass);
        $this->assertStringNotContainsString('outline-0', $buttonClass);
    }

    public function testProseRootMergesCallerClassAndRendersSlotRaw(): void
    {
        $html = $this->renderMarkup('<x-prose class="my-prose">Text <b>bold</b></x-prose>');

        preg_match('/<div\b[^>]*\bclass="([^"]+)"/', $html, $matches);

        $this->assertStringContainsString('my-prose', $matches[1] ?? '');
        $this->assertStringContainsString('<b>bold</b>', $html);
    }
}
