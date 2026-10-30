<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\ChildThemeFixture;
use Tests\Support\TestCase;
use WordpressStarter\Acf\Options;
use WordpressStarter\Providers\DesignTokenServiceProvider;
use WordpressStarter\Providers\ThemeServiceProvider;
use WordpressStarter\ThemeContext;

/**
 * Parent behaviour that changes with a child theme or without ACF: the
 * Design tokens page turns read-only when the child supplies the tokens, and
 * the frontend answers with a fallback page instead of failing without ACF.
 */
final class ChildThemeModeTest extends TestCase
{
    use ChildThemeFixture;

    protected function tearDown(): void
    {
        $this->removeChildTheme();
        parent::tearDown();
    }

    public function testDesignTokensPageIsWritableWithoutChildTokens(): void
    {
        $this->assertFalse(DesignTokenServiceProvider::tokensFromChildTheme());

        $provider = new DesignTokenServiceProvider();
        $provider->register();
        $provider->boot();

        $this->assertArrayHasKey('admin_init', $GLOBALS['wp_mock_hooks']['actions']);
        $this->assertArrayHasKey('acf/save_post', $GLOBALS['wp_mock_hooks']['actions']);
    }

    public function testDesignTokensPageIsReadOnlyWhenTheChildSuppliesTokens(): void
    {
        $this->makeChildTheme($this->childSettings(), $this->childAssets());
        $this->assertTrue(DesignTokenServiceProvider::tokensFromChildTheme());

        $provider = new DesignTokenServiceProvider();
        $provider->register();
        $provider->boot();

        $this->assertSame(['actions' => [], 'filters' => []], $GLOBALS['wp_mock_hooks'], 'no upload, restore, regenerate or colour sync');

        $this->invokeStaticMethod(Options::class, 'registerDesignTokensFields');
        $group = end($GLOBALS['wp_mock_registered_field_groups']);

        $this->assertSame('group_options_design_tokens', $group['key']);
        $this->assertCount(1, $group['fields']);
        $this->assertStringContainsString('#token-generator', $group['fields'][0]['message']);
    }

    public function testTokenGeneratorLinkIsFilterable(): void
    {
        $this->makeChildTheme($this->childSettings(), $this->childAssets());
        add_filter(ThemeContext::prefix() . '_token_generator_url', fn () => 'https://generator.example/#token-generator');

        $this->invokeStaticMethod(Options::class, 'registerDesignTokensFields');
        $group = end($GLOBALS['wp_mock_registered_field_groups']);

        $this->assertStringContainsString('https://generator.example/#token-generator', $group['fields'][0]['message']);
    }

    public function testHandMadeChildKeepsTheTokensPageWritable(): void
    {
        $this->makeChildTheme(null);

        $this->assertTrue(ThemeContext::isChildActive());
        $this->assertFalse(DesignTokenServiceProvider::tokensFromChildTheme());
    }

    public function testFrontendFallsBackToAPlainPageWithoutAcf(): void
    {
        $provider = new ThemeServiceProvider();
        $this->invokeMethod($provider, 'addTemplateFilter');
        $filter = $GLOBALS['wp_mock_hooks']['filters']['template_include'][0]['callback'];

        $this->assertTrue(ThemeServiceProvider::acfAvailable());
        $this->assertSame('/theme/page.php', $filter('/theme/page.php'), 'with ACF and no Blade the template stays');

        add_filter(ThemeContext::prefix() . '_acf_available', '__return_false');
        $this->assertFalse(ThemeServiceProvider::acfAvailable());
        $this->assertSame(get_template_directory() . '/config/acf-missing.php', $filter('/theme/page.php'));
    }

    public function testFallbackPageExistsAndAnswers503(): void
    {
        $page = (string) file_get_contents(dirname(__DIR__, 3) . '/config/acf-missing.php');

        $this->assertStringContainsString('status_header(503)', $page);
        $this->assertStringContainsString('noindex', $page);
        $this->assertStringContainsString("current_user_can('activate_plugins')", $page);
    }
}
