<?php

declare(strict_types=1);

namespace Tests\Unit\Components;

use Tests\Support\TestCase;
use WordpressStarter\Application;

/**
 * Markup contract of the stats module.
 */
final class StatsMarkupTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['wp_mock_sub_fields']);
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderView(string $view, array $data = []): string
    {
        Application::getInstance()->boot();

        $factory = blade();
        $factory->getFinder()->addLocation(dirname(__DIR__, 3) . '/templates');

        return $factory->make($view, $data)->render();
    }

    public function testScreenReaderTextUsesGermanNumberFormat(): void
    {
        $GLOBALS['wp_mock_sub_fields'] = [
            'title' => 'Kennzahlen',
            'stats' => [
                ['number' => '3.64', 'suffix' => 'MWp', 'label' => 'A'],
                ['number' => '3800000', 'suffix' => 'kWh/a', 'label' => 'B'],
            ],
            'background_color' => 'primary',
        ];

        $html = $this->renderView('flexible.stats', ['sectionAnchor' => '']);

        // The visible counter formats as de-DE in JS; the screen reader text must match it.
        $this->assertStringContainsString('<span class="sr-only">3,64 MWp</span>', $html);
        $this->assertStringContainsString('<span class="sr-only">3.800.000 kWh/a</span>', $html);
    }
}
