<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;

/**
 * Tests for the breadcrumbs partial's visual trail.
 *
 * The partial used to also render its own BreadcrumbList JSON-LD in the
 * non-Yoast branch. That duplicated SeoServiceProvider::addBreadcrumbSchema(),
 * which already emits a BreadcrumbList (including page ancestors) on wp_head
 * regardless of Yoast. The JSON-LD sink was removed from the partial; only
 * the visual <ol> trail remains here.
 */
final class BreadcrumbsSchemaTest extends TestCase
{
    protected function tearDown(): void
    {
        unset(
            $GLOBALS['wp_mock_is_front_page'],
            $GLOBALS['wp_mock_is_singular'],
            $GLOBALS['wp_mock_is_page'],
            $GLOBALS['wp_mock_titles'],
        );
        parent::tearDown();
    }

    public function testVisualTrailEscapesHostileTitle(): void
    {
        $GLOBALS['wp_mock_is_front_page'] = false;
        $GLOBALS['wp_mock_is_singular'] = false;
        $GLOBALS['wp_mock_is_page'] = false;
        $GLOBALS['wp_mock_titles'][0] = 'Angebot & Nachfrage </script><script>alert(1)</script>';

        $output = $this->renderBreadcrumbs();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $output);
        $this->assertStringContainsString('aria-current="page"', $output);
        $this->assertStringContainsString('&amp; Nachfrage', $output);
    }

    public function testPartialNoLongerEmitsItsOwnJsonLd(): void
    {
        $GLOBALS['wp_mock_is_front_page'] = false;
        $GLOBALS['wp_mock_is_singular'] = false;
        $GLOBALS['wp_mock_is_page'] = false;

        $output = $this->renderBreadcrumbs();

        // Regression guard: BreadcrumbList JSON-LD comes from
        // SeoServiceProvider::addBreadcrumbSchema() alone now.
        $this->assertStringNotContainsString('application/ld+json', $output);
        $this->assertStringNotContainsString('BreadcrumbList', $output);
    }

    private function renderBreadcrumbs(): string
    {
        return $this->renderTemplate('partials.breadcrumbs');
    }
}
