<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;
use WordpressStarter\Application;

/**
 * Tests for the breadcrumb JSON-LD schema output.
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

    public function testJsonLdEscapesScriptTagsInTitle(): void
    {
        $GLOBALS['wp_mock_is_front_page'] = false;
        $GLOBALS['wp_mock_is_singular'] = false;
        $GLOBALS['wp_mock_is_page'] = false;
        $GLOBALS['wp_mock_titles'][0] = 'Angebot </script><script>alert(1)</script>';

        $output = $this->renderBreadcrumbs();

        $ldJsonStart = strpos($output, '<script type="application/ld+json"');
        $this->assertNotFalse($ldJsonStart, 'ld+json script block not found');
        $ldJsonEnd = strpos($output, '</script>', $ldJsonStart);
        $ldJsonBlock = substr($output, $ldJsonStart, $ldJsonEnd - $ldJsonStart);

        $this->assertStringNotContainsString('</script><script>', $ldJsonBlock);
        $this->assertStringContainsString('</script>', $output);
    }

    private function renderBreadcrumbs(): string
    {
        $app = Application::getInstance();
        $app->boot();

        $factory = blade();
        $factory->getFinder()->addLocation(dirname(__DIR__, 2) . '/templates');

        return $factory->make('partials.breadcrumbs')->render();
    }
}
