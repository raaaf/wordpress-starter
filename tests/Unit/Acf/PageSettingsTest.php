<?php

declare(strict_types=1);

namespace Tests\Unit\Acf;

use Tests\Support\TestCase;
use WordpressStarter\Acf\PageSettings;

/**
 * Tests for the "Als Landingpage anzeigen" page switch.
 */
final class PageSettingsTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['wp_mock_is_page'], $GLOBALS['wp_mock_registered_field_groups']);
        parent::tearDown();
    }

    public function testRegisterAddsFieldGroupWithLandingPageField(): void
    {
        PageSettings::register();

        $groups = $GLOBALS['wp_mock_registered_field_groups'] ?? [];
        $group = null;
        foreach ($groups as $candidate) {
            if (($candidate['key'] ?? null) === 'group_page_settings') {
                $group = $candidate;
                break;
            }
        }

        $this->assertNotNull($group, 'group_page_settings was not registered');

        $fieldNames = array_map(static fn (array $field) => $field['name'], $group['fields']);
        $this->assertContains('page_is_landing_page', $fieldNames);
    }

    public function testIsLandingPageFalseWhenNotOnPage(): void
    {
        $GLOBALS['wp_mock_is_page'] = false;

        $this->assertFalse(PageSettings::isLandingPage());
    }

    public function testIsLandingPageTrueWhenOnPageAndFieldTrue(): void
    {
        $GLOBALS['wp_mock_is_page'] = true;
        $GLOBALS['wp_mock_fields']['page_is_landing_page'] = true;

        $this->assertTrue(PageSettings::isLandingPage());
    }

    public function testIsLandingPageFalseWhenOnPageAndFieldFalse(): void
    {
        $GLOBALS['wp_mock_is_page'] = true;
        $GLOBALS['wp_mock_fields']['page_is_landing_page'] = false;

        $this->assertFalse(PageSettings::isLandingPage());
    }

    public function testHeaderMenuHidesNavigationWhenLandingPage(): void
    {
        $GLOBALS['wp_mock_is_page'] = true;
        $GLOBALS['wp_mock_fields']['page_is_landing_page'] = true;

        $html = $this->renderHeaderMenu();

        $this->assertStringNotContainsString('<nav', $html);
        $this->assertStringNotContainsString('mobile-navigation', $html);
        $this->assertStringNotContainsString('href="', $html);
    }

    public function testHeaderMenuShowsNavigationWhenNotLandingPage(): void
    {
        $GLOBALS['wp_mock_is_page'] = false;

        $html = $this->renderHeaderMenu();

        $this->assertStringContainsString('mobile-navigation', $html);
    }

    public function testHeaderMenuHidesCtaOnLandingPage(): void
    {
        $GLOBALS['wp_mock_fields']['header_cta_show:option'] = true;
        $GLOBALS['wp_mock_fields']['header_cta:option'] = ['url' => 'https://example.com/kontakt', 'title' => 'Kontakt'];
        $GLOBALS['wp_mock_is_page'] = true;
        $GLOBALS['wp_mock_fields']['page_is_landing_page'] = true;

        $html = $this->renderHeaderMenu();

        $this->assertStringNotContainsString('href="', $html);
    }

    public function testHeaderMenuShowsCtaWhenNotLandingPage(): void
    {
        $GLOBALS['wp_mock_fields']['header_cta_show:option'] = true;
        $GLOBALS['wp_mock_fields']['header_cta:option'] = ['url' => 'https://example.com/kontakt', 'title' => 'Kontakt'];
        $GLOBALS['wp_mock_is_page'] = false;

        $html = $this->renderHeaderMenu();

        $this->assertStringContainsString('https://example.com/kontakt', $html);
    }

    public function testHeaderNavLandmarkOmittedOnLandingPage(): void
    {
        $GLOBALS['wp_mock_is_page'] = true;
        $GLOBALS['wp_mock_fields']['page_is_landing_page'] = true;

        $output = $this->renderHeader();

        $this->assertStringNotContainsString('aria-label="Hauptnavigation"', $output);
        $this->assertStringNotContainsString('<nav', $output);
    }

    public function testHeaderNavLandmarkPresentWhenNotLandingPage(): void
    {
        $GLOBALS['wp_mock_is_page'] = false;

        $output = $this->renderHeader();

        $this->assertStringContainsString('aria-label="Hauptnavigation"', $output);
    }

    private function renderHeaderMenu(): string
    {
        return $this->renderTemplate('partials.header-menu');
    }

    private function renderHeader(): string
    {
        return $this->renderTemplate('partials.header');
    }
}
