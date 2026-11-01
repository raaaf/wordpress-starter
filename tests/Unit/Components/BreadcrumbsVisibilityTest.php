<?php

declare(strict_types=1);

namespace Tests\Unit\Components;

use Tests\Support\TestCase;
use WordpressStarter\Application;

/**
 * The breadcrumb trail is hidden visually but stays in the accessibility tree:
 * sr-only, never display:none or the hidden attribute.
 */
final class BreadcrumbsVisibilityTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['wp_mock_is_front_page'], $GLOBALS['wp_mock_is_singular'], $GLOBALS['wp_mock_is_page']);
        parent::tearDown();
    }

    public function testTrailIsScreenReaderOnlyAndBarHasNoFrameForVisitors(): void
    {
        Application::getInstance()->boot();
        $GLOBALS['wp_mock_is_front_page'] = false;
        $GLOBALS['wp_mock_is_singular'] = false;
        $GLOBALS['wp_mock_is_page'] = false;
        $factory = blade();
        $factory->getFinder()->addLocation(dirname(__DIR__, 3) . '/templates');

        $html = $factory->make('partials.breadcrumbs', [])->render();

        $this->assertSame(1, preg_match('/<nav\b[^>]*aria-label="Breadcrumb"[^>]*>/s', $html, $nav));
        $this->assertMatchesRegularExpression('/class="[^"]*\bsr-only\b/', $nav[0]);
        $this->assertDoesNotMatchRegularExpression('/\bhidden\b|display:\s*none/', $nav[0]);
        $this->assertStringContainsString('Startseite', $html);
        $this->assertDoesNotMatchRegularExpression('/class="bg-surface border-b/', $html);
    }
}
