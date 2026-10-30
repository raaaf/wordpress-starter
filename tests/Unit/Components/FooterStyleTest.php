<?php

declare(strict_types=1);

namespace Tests\Unit\Components;

use Tests\Support\TestCase;
use WordpressStarter\Application;

/**
 * Theme option footer_style: "simple" renders the compact two-row footer, an empty
 * value or "default" keeps the column footer unchanged.
 */
final class FooterStyleTest extends TestCase
{
    private function renderFooter(?string $style): string
    {
        Application::getInstance()->boot();

        $this->setMockField('company_name', 'Beispiel GmbH', 'option');
        $this->setMockField('address', "Musterstraße 1\n90762 Fürth", 'option');
        $this->setMockField('phone', '0911 123456', 'option');
        $this->setMockField('fax', '0911 123457', 'option');
        $this->setMockField('email', 'info@example.test', 'option');
        $this->setMockField('copyright_text', '© {year} Beispiel GmbH', 'option');
        if ($style !== null) {
            $this->setMockField('footer_style', $style, 'option');
        }

        $factory = blade();
        $factory->getFinder()->addLocation(dirname(__DIR__, 3) . '/templates');

        return $factory->make('partials.footer-menu')->render();
    }

    public function testSimpleStyleRendersBrandContactCopyrightAndLegalLinks(): void
    {
        $html = $this->renderFooter('simple');

        $this->assertStringContainsString('footer--simple', $html);
        $this->assertStringContainsString('Beispiel GmbH', $html);
        $this->assertStringContainsString('Musterstraße 1, 90762 Fürth', $html);
        $this->assertStringContainsString('tel:', $html);
        $this->assertStringContainsString('mailto:info@example.test', $html);
        // No mandatory detail of the column footer is dropped: fax and copyright stay.
        $this->assertStringContainsString('Telefax: 0911 123457', $html);
        $this->assertStringContainsString('© ' . wp_date('Y') . ' Beispiel GmbH', $html);
        $this->assertStringContainsString('legal-nav', $html);
        $this->assertStringNotContainsString('grid-cols-1', $html);
    }

    public function testDefaultAndEmptyStyleKeepTheColumnFooter(): void
    {
        foreach ([null, '', 'default'] as $style) {
            $html = $this->renderFooter($style);
            $label = var_export($style, true);

            $this->assertStringNotContainsString('footer--simple', $html, $label);
            $this->assertStringContainsString('grid-cols-1', $html, $label);
        }
    }
}
