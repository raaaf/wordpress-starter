<?php

declare(strict_types=1);

namespace Tests\Unit\Components;

use Tests\Support\TestCase;
use WordpressStarter\Application;

/**
 * Markup contract of the download list layout.
 */
final class DownloadListMarkupTest extends TestCase
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

    /**
     * @return array<string, mixed>
     */
    private function downloadListFields(): array
    {
        return [
            'groups' => [
                [
                    'title' => 'Formulare',
                    'items' => [
                        ['heading' => 'Antraege', 'label' => 'Antrag A', 'url' => 'https://example.test/a.pdf', 'note' => 'Stand 2026'],
                        ['heading' => '', 'label' => 'Praesentation folgt', 'url' => '', 'note' => ''],
                    ],
                ],
            ],
            'columns' => '3',
        ];
    }

    public function testLinkedItemShowsUrlAndFileType(): void
    {
        $GLOBALS['wp_mock_sub_fields'] = $this->downloadListFields();

        $html = $this->renderView('flexible.download-list', ['sectionAnchor' => '']);

        $this->assertStringContainsString('href="https://example.test/a.pdf"', $html);
        $this->assertMatchesRegularExpression('#>\s*PDF\s*<#', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        // Title left, file type pill right in the same row, the note below.
        $this->assertMatchesRegularExpression('#justify-between[^>]*>\s*<span class="underline[^"]*">Antrag A</span>#', $html);
        $this->assertLessThan(strpos($html, 'Stand 2026'), strpos($html, '>PDF<') ?: strpos($html, 'PDF'));
    }

    public function testItemWithoutUrlIsPlainText(): void
    {
        $GLOBALS['wp_mock_sub_fields'] = $this->downloadListFields();

        $html = $this->renderView('flexible.download-list', ['sectionAnchor' => '']);

        $this->assertStringContainsString('Praesentation folgt', $html);
        $this->assertSame(1, substr_count($html, '<a '), 'Only the linked item renders an anchor.');
    }

    public function testShowsHeadingAndNote(): void
    {
        $GLOBALS['wp_mock_sub_fields'] = $this->downloadListFields();

        $html = $this->renderView('flexible.download-list', ['sectionAnchor' => '']);

        $this->assertStringContainsString('Antraege', $html);
        $this->assertStringContainsString('Stand 2026', $html);
    }

    public function testMailtoOpensInTheSameTab(): void
    {
        $fields = $this->downloadListFields();
        $fields['groups'][0]['items'] = [['label' => 'Mail', 'url' => 'mailto:info@example.test']];
        $GLOBALS['wp_mock_sub_fields'] = $fields;

        $html = $this->renderView('flexible.download-list', ['sectionAnchor' => '']);

        $this->assertStringContainsString('href="mailto:info@example.test"', $html);
        $this->assertStringNotContainsString('target="_blank"', $html);
    }

    public function testJavascriptUrlIsNotRenderedAsLink(): void
    {
        $fields = $this->downloadListFields();
        $fields['groups'][0]['items'] = [['label' => 'Boese', 'url' => 'javascript:alert(1)']];
        $GLOBALS['wp_mock_sub_fields'] = $fields;

        $html = $this->renderView('flexible.download-list', ['sectionAnchor' => '']);

        $this->assertStringNotContainsString('javascript:', $html);
    }
}
