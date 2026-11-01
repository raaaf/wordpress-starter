<?php

declare(strict_types=1);

namespace Tests\Unit\Components;

use Tests\Support\TestCase;
use WordpressStarter\Application;

/**
 * Image layout width "full" spans the whole content width (same edges as a
 * two-column module), the other widths keep their max-width cap.
 */
final class ImageWidthTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['wp_mock_sub_fields'], $GLOBALS['wp_mock_attachments'][42]);
        parent::tearDown();
    }

    private function figureClass(?string $width): string
    {
        Application::getInstance()->boot();

        $GLOBALS['wp_mock_attachments'][42]['content'] = ['https://example.test/haus.jpg', 1792, 1008];
        $fields = ['image' => 42];
        if ($width !== null) {
            $fields['width'] = $width;
        }
        $GLOBALS['wp_mock_sub_fields'] = $fields;

        $factory = blade();
        $factory->getFinder()->addLocation(dirname(__DIR__, 3) . '/templates');
        $html = $factory->make('flexible.image', ['sectionAnchor' => ''])->render();

        $this->assertMatchesRegularExpression('#<figure class="([^"]*)"#', $html);
        preg_match('#<figure class="([^"]*)"#', $html, $m);

        return $m[1];
    }

    public function testFullWidthHasNoMaxWidthCap(): void
    {
        $class = $this->figureClass('full');

        $this->assertStringContainsString('w-full', $class);
        $this->assertStringNotContainsString('max-w-', $class);
    }

    public function testDefaultAndWideKeepTheirCap(): void
    {
        $this->assertSame('mx-auto max-w-4xl', $this->figureClass(null));
        $this->assertSame('mx-auto max-w-6xl', $this->figureClass('wide'));
    }
}
