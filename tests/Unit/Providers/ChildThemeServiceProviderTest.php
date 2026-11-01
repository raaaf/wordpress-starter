<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\ChildThemeFixture;
use Tests\Support\TestCase;
use WordpressStarter\Providers\ChildThemeServiceProvider;
use WordpressStarter\ThemeContext;

/**
 * The parent applies a generated child theme: token and font stylesheets,
 * editor tokens, a contract notice and a one-time logo import.
 */
final class ChildThemeServiceProviderTest extends TestCase
{
    use ChildThemeFixture;

    private string $uploads = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->uploads = sys_get_temp_dir() . '/wps-uploads-' . bin2hex(random_bytes(6));
        mkdir($this->uploads);
        $GLOBALS['wp_mock_upload_path'] = $this->uploads;
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->uploads . '/*') ?: []);
        rmdir($this->uploads);
        $this->removeChildTheme();
        parent::tearDown();
    }

    private function flag(): string
    {
        return ThemeContext::optionKey('child_import_' . self::CHILD_BUILD_ID);
    }

    public function testRegistersNothingWithoutChildTheme(): void
    {
        ( new ChildThemeServiceProvider() )->register();

        $this->assertSame(['actions' => [], 'filters' => []], $GLOBALS['wp_mock_hooks']);
    }

    public function testRegistersHooksAfterTheParentAssets(): void
    {
        $this->makeChildTheme($this->childSettings(), $this->childAssets());
        ( new ChildThemeServiceProvider() )->register();

        $this->assertSame(20, $GLOBALS['wp_mock_hooks']['actions']['wp_enqueue_scripts'][0]['priority']);
        $this->assertSame(20, $GLOBALS['wp_mock_hooks']['filters']['mce_css'][0]['priority']);
        $this->assertSame(20, $GLOBALS['wp_mock_hooks']['actions']['acf/init'][0]['priority']);
        $this->assertArrayHasKey('admin_notices', $GLOBALS['wp_mock_hooks']['actions']);
    }

    public function testEnqueuesChildTokensAfterAppCss(): void
    {
        $this->makeChildTheme($this->childSettings(), $this->childAssets());
        ( new ChildThemeServiceProvider() )->enqueueStyles();

        $prefix = ThemeContext::kebabPrefix();
        $styles = $GLOBALS['wp_mock_enqueued']['styles'];
        $base = 'https://example.com/wp-content/themes/wp-starter-child/assets/';

        $this->assertSame($base . 'tokens.css', $styles["{$prefix}-child-tokens"]['src']);
        $this->assertSame(['app-css'], $styles["{$prefix}-child-tokens"]['deps']);
        $this->assertSame(self::CHILD_BUILD_ID, $styles["{$prefix}-child-tokens"]['ver']);
        $this->assertSame($base . 'fonts/fonts.css', $styles["{$prefix}-child-fonts"]['src']);
    }

    public function testEnqueuesNothingForInvalidSettings(): void
    {
        $this->makeChildTheme('{"tokens": {"css": "assets/tokens.css"}}', $this->childAssets());
        ( new ChildThemeServiceProvider() )->enqueueStyles();

        $this->assertSame([], $GLOBALS['wp_mock_enqueued']['styles']);
    }

    public function testAppendsFontsAndEditorTokensToTinyMce(): void
    {
        $this->makeChildTheme($this->childSettings(), $this->childAssets());
        $base = 'https://example.com/wp-content/themes/wp-starter-child/assets/';
        $ver = '?ver=' . self::CHILD_BUILD_ID;

        $this->assertSame(
            "parent.css,{$base}fonts/fonts.css{$ver},{$base}tokens-editor.css{$ver}",
            ( new ChildThemeServiceProvider() )->addEditorStylesheet('parent.css'),
        );
        $this->assertSame(
            "{$base}fonts/fonts.css{$ver},{$base}tokens-editor.css{$ver}",
            ( new ChildThemeServiceProvider() )->addEditorStylesheet(''),
        );
    }

    public function testNoticeForInvalidAndNewerContracts(): void
    {
        $GLOBALS['wp_mock_current_user_can']['edit_theme_options'] = true;
        $provider = new ChildThemeServiceProvider();

        $this->makeChildTheme('not json');
        ob_start();
        $provider->displayContractNotice();
        $this->assertStringContainsString('settings.json', (string) ob_get_clean());

        $this->removeChildTheme();
        \WordpressStarter\Support\ChildThemeSettings::reset();
        $this->makeChildTheme($this->childSettings(['contract' => 3]), $this->childAssets());
        ob_start();
        $provider->displayContractNotice();
        $this->assertStringContainsString('Vertragsversion 3', (string) ob_get_clean());
    }

    public function testNoNoticeForValidSettingsOrWithoutCapability(): void
    {
        $provider = new ChildThemeServiceProvider();
        $this->makeChildTheme('not json');
        ob_start();
        $provider->displayContractNotice();
        $this->assertSame('', ob_get_clean(), 'editors without edit_theme_options see nothing');

        $this->removeChildTheme();
        \WordpressStarter\Support\ChildThemeSettings::reset();
        $GLOBALS['wp_mock_current_user_can']['edit_theme_options'] = true;
        $this->makeChildTheme($this->childSettings(), $this->childAssets());
        ob_start();
        $provider->displayContractNotice();
        $this->assertSame('', ob_get_clean());
    }

    public function testImportsBothLogosOnceAndSanitizesTheSvg(): void
    {
        $this->makeChildTheme($this->childSettings(), $this->childAssets());
        $provider = new ChildThemeServiceProvider();
        $provider->importLogos();

        $updates = $GLOBALS['wp_mock_updated_fields'];
        $this->assertSame(['field_options_logo', 'field_options_logo_dark'], array_column($updates, 'selector'));
        $this->assertCount(2, $GLOBALS['wp_mock_attachments']);

        $svg = $GLOBALS['wp_mock_attachments'][$updates[0]['value']];
        $this->assertSame('image/svg+xml', $svg['args']['post_mime_type']);
        $this->assertStringNotContainsString('<script', (string) file_get_contents( (string) $svg['file']));
        $this->assertStringContainsString('<rect', (string) file_get_contents( (string) $svg['file']));

        $png = $updates[1]['value'];
        $this->assertSame('image/png', $GLOBALS['wp_mock_attachments'][$png]['args']['post_mime_type']);
        $this->assertArrayHasKey($png, $GLOBALS['wp_mock_attachment_metadata']);
        $this->assertNotFalse(get_option($this->flag()));

        $provider->importLogos();
        $this->assertCount(2, $GLOBALS['wp_mock_attachments'], 'the same build imports only once');
    }

    public function testKeepsALogoChosenInTheBackend(): void
    {
        $this->makeChildTheme($this->childSettings(), $this->childAssets());
        $GLOBALS['wp_mock_fields']['site_logo:option'] = 42;

        ( new ChildThemeServiceProvider() )->importLogos();

        $this->assertSame(['field_options_logo_dark'], array_column($GLOBALS['wp_mock_updated_fields'], 'selector'));
    }

    public function testSkipsImportWithoutBuildIdOrWhenFlagged(): void
    {
        $this->makeChildTheme($this->childSettings(['build_id' => 'nope']), $this->childAssets());
        ( new ChildThemeServiceProvider() )->importLogos();
        $this->assertSame([], $GLOBALS['wp_mock_updated_fields']);

        $this->removeChildTheme();
        \WordpressStarter\Support\ChildThemeSettings::reset();
        $this->makeChildTheme($this->childSettings(), $this->childAssets());
        update_option($this->flag(), 1);
        ( new ChildThemeServiceProvider() )->importLogos();
        $this->assertSame([], $GLOBALS['wp_mock_updated_fields']);
    }

    public function testUnsanitizableSvgIsNotImported(): void
    {
        $this->makeChildTheme($this->childSettings(['logo' => ['light' => 'assets/logo-light.svg', 'dark' => null]]), [
            'assets/logo-light.svg' => 'not an svg <<<',
        ]);

        // error_log() goes to a temp file, so the warning is checked, not printed.
        $log = $this->uploads . '.log';
        $previous = ini_set('error_log', $log);
        try {
            ( new ChildThemeServiceProvider() )->importLogos();
        } finally {
            ini_set('error_log', $previous === false ? '' : $previous);
        }

        $this->assertSame([], $GLOBALS['wp_mock_updated_fields']);
        $this->assertSame([], glob($this->uploads . '/*'));
        $this->assertStringContainsString('Child theme logo could not be imported', (string) file_get_contents($log));
        unlink($log);
    }
}
