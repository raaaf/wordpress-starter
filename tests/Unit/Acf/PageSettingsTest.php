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
        unset($GLOBALS['wp_mock_is_page'], $GLOBALS['wp_mock_registered_field_groups'], $GLOBALS['wp_mock_nav_menu_items']);
        parent::tearDown();
    }

    public function testRegisterAddsFieldGroupWithLandingPageField(): void
    {
        PageSettings::register();

        $groups = $GLOBALS['wp_mock_registered_field_groups'] ?? [];
        $group = null;
        foreach ($groups as $candidate) {
            if (( $candidate['key'] ?? null ) === 'group_page_settings') {
                $group = $candidate;
                break;
            }
        }

        $this->assertNotNull($group, 'group_page_settings was not registered');

        $fieldNames = array_map(static fn (array $field) => $field['name'], $group['fields']);
        $this->assertContains('page_is_landing_page', $fieldNames);
        $fields = array_column($group['fields'], null, 'name');
        foreach ([
            'page_hide_global_notice' => 'Seitenweiten Hinweis ausblenden',
            'page_reduced_footer' => 'Reduzierten Footer anzeigen',
        ] as $name => $label) {
            $this->assertArrayHasKey($name, $fields);
            $this->assertSame('true_false', $fields[$name]['type']);
            $this->assertSame(0, $fields[$name]['default_value']);
            $this->assertSame($label, $fields[$name]['label']);
            $this->assertSame([[['field' => 'field_page_is_landing_page', 'operator' => '==', 'value' => '1']]], $fields[$name]['conditional_logic']);
        }
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

    public function testReducedFooterKeepsOnlyCopyrightAndLegalLinks(): void
    {
        foreach ([
            'company_name' => 'Example company',
            'footer_text' => 'Company description',
            'address' => 'Example street 42',
            'email' => 'contact@example.com',
            'copyright_text' => 'Copyright {year} Example',
            'social_links' => [['platform' => 'custom', 'url' => 'https://social.example.com']],
        ] as $field => $value) {
            $this->setMockField($field, $value, 'option');
        }
        $GLOBALS['wp_mock_nav_menu_items']['legal-menu'] = '<li><a href="/impressum">Impressum</a></li>';

        $cases = [
            'reduced landing footer' => [true, true, true, false, true],
            'independent of hidden notice' => [true, true, true, true, true],
            'default landing footer' => [true, true, null, false, false],
            'notice switch does not reduce footer' => [true, true, false, true, false],
            'ordinary page ignores stale flag' => [true, false, true, false, false],
            'non-page ignores stale flags' => [false, true, true, false, false],
        ];
        foreach ($cases as $label => [$isPage, $landing, $reduced, $hideNotice, $expectedReduced]) {
            $GLOBALS['wp_mock_is_page'] = $isPage;
            $this->setMockField('page_is_landing_page', $landing);
            $this->setMockField('page_reduced_footer', $reduced);
            $this->setMockField('page_hide_global_notice', $hideNotice);
            $html = $this->renderTemplate('partials.footer-menu');

            $this->assertStringContainsString('Copyright ' . wp_date('Y') . ' Example', $html, $label);
            $this->assertStringContainsString('href="/impressum"', $html, $label);
            foreach (['default-logo.png', 'Example company', 'Company description', 'Example street 42', 'mailto:contact@example.com'] as $content) {
                $this->assertSame(!$expectedReduced, str_contains($html, $content), $label . ': ' . $content);
            }
            $this->assertSame(!$isPage || !$landing, str_contains($html, 'class="footer-nav"'), $label);
            $this->assertSame(!$isPage || !$landing, str_contains($html, 'https://social.example.com'), $label);
        }
    }

    public function testReducedFooterRespectsDisabledLegalMenu(): void
    {
        $GLOBALS['wp_mock_is_page'] = true;
        $this->setMockField('page_is_landing_page', true);
        $this->setMockField('page_reduced_footer', true);
        $this->setMockField('footer_show_legal', false, 'option');

        $this->assertStringNotContainsString('class="legal-nav"', $this->renderTemplate('partials.footer-menu'));
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
