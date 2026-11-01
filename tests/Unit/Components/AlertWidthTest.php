<?php

declare(strict_types=1);

namespace Tests\Unit\Components;

use Tests\Support\TestCase;
use WordpressStarter\Acf\FieldDefinitions;
use WordpressStarter\Application;

/**
 * Alert and CTA keep the text column by default. Only an explicit "container"
 * spans the box over the whole container; content saved before the field
 * existed (empty value) keeps the text column.
 */
final class AlertWidthTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['wp_mock_sub_fields']);
        parent::tearDown();
    }

    private function render(string $layout, array $fields): string
    {
        Application::getInstance()->boot();
        $GLOBALS['wp_mock_sub_fields'] = $fields;

        $factory = blade();
        $factory->getFinder()->addLocation(dirname(__DIR__, 3) . '/templates');

        return $factory->make('flexible.' . $layout, ['sectionAnchor' => ''])->render();
    }

    private function renderAlert(?string $contentWidth): string
    {
        $fields = ['variant' => 'info', 'title' => 'Hinweis', 'content' => '<p>Text</p>'];
        if ($contentWidth !== null) {
            $fields['content_width'] = $contentWidth;
        }

        return $this->render('alert', $fields);
    }

    public function testMissingEmptyOrReadableWidthKeepsTheTextColumn(): void
    {
        foreach ([null, '', 'readable'] as $width) {
            $this->assertStringContainsString('max-w-3xl', $this->renderAlert($width), var_export($width, true));
        }
    }

    public function testContainerWidthDropsTheTextColumnWrapper(): void
    {
        $this->assertStringNotContainsString('max-w-3xl', $this->renderAlert('container'));
    }

    public function testCtaContainerWidthSpansTheContainerAndCapsTextAt80Characters(): void
    {
        $html = $this->render('cta', ['title' => 'Jetzt starten', 'content' => '<p>Text</p>', 'content_width' => 'container']);

        $this->assertStringNotContainsString('max-w-3xl', $html);
        $this->assertStringContainsString('max-w-[80ch]', $html);
    }

    public function testCtaWithoutWidthKeepsTheTextColumn(): void
    {
        $html = $this->render('cta', ['title' => 'Jetzt starten', 'content' => '<p>Text</p>']);

        $this->assertStringContainsString('max-w-3xl', $html);
        $this->assertStringNotContainsString('max-w-[80ch]', $html);
    }

    public function testWidthFieldDefaultsToTheTextColumn(): void
    {
        $this->assertSame('readable', FieldDefinitions::contentWidthField('cta')['default_value']);
    }
}
