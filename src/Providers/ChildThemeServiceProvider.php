<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\Support\ChildThemeSettings;
use WordpressStarter\Support\MediaImport;
use WordpressStarter\ThemeContext;

/**
 * Applies a generated child theme (child contract, Support\ChildThemeSettings).
 *
 * The child carries no logic: this parent enqueues its token stylesheet after
 * the theme's own CSS, so the child's custom properties win, adds its fonts
 * and editor tokens, imports its logos once and reports a settings.json it
 * cannot use. Nothing happens while no child theme is active.
 */
class ChildThemeServiceProvider extends ServiceProvider
{
    /** ACF field keys and option names of the two logo options (Acf\Options). */
    private const LOGO_FIELDS = [
        'light' => ['key' => 'field_options_logo', 'name' => 'site_logo'],
        'dark' => ['key' => 'field_options_logo_dark', 'name' => 'site_logo_dark'],
    ];

    public function register(): void
    {
        if (!ThemeContext::isChildActive()) {
            return;
        }

        // After Vite::enqueueAssets (priority 10), which registers app-css.
        add_action('wp_enqueue_scripts', [$this, 'enqueueStyles'], 20);
        // After EditorStylesServiceProvider (priority 10), so the child tokens win in TinyMCE too.
        add_filter('mce_css', [$this, 'addEditorStylesheet'], 20);
        add_action('admin_notices', [$this, 'displayContractNotice']);
        // After the ACF options pages and the setup prefill (priority 10).
        add_action('acf/init', [$this, 'importLogos'], 20);
    }

    public function boot(): void
    {
        // Nothing to boot.
    }

    public function enqueueStyles(): void
    {
        if (!ChildThemeSettings::active()) {
            return;
        }

        $version = ChildThemeSettings::buildId();
        $prefix = ThemeContext::kebabPrefix();

        $fonts = ChildThemeSettings::fontsCss();
        if ($fonts !== null) {
            wp_enqueue_style("{$prefix}-child-fonts", $fonts['url'], [], $version);
        }

        $tokens = ChildThemeSettings::tokensCss();
        if ($tokens !== null) {
            wp_enqueue_style("{$prefix}-child-tokens", $tokens['url'], ['app-css'], $version);
        }
    }

    public function addEditorStylesheet(string $mceCss): string
    {
        if (!ChildThemeSettings::active()) {
            return $mceCss;
        }

        // The build ID busts the TinyMCE cache like the ver argument of an enqueue.
        // ChildThemeSettings URLs carry no query, and a build ID is a bare UUID.
        $buildId = ChildThemeSettings::buildId();
        $urls = [];
        foreach ([ChildThemeSettings::fontsCss(), ChildThemeSettings::editorCss()] as $file) {
            if ($file !== null) {
                $urls[] = $buildId !== null ? $file['url'] . '?ver=' . $buildId : $file['url'];
            }
        }

        if ($urls === []) {
            return $mceCss;
        }

        return implode(',', array_filter([$mceCss, ...$urls], static fn (string $url): bool => $url !== ''));
    }

    public function displayContractNotice(): void
    {
        if (!current_user_can('edit_theme_options')) {
            return;
        }

        $problem = ChildThemeSettings::problem();
        if ($problem === ChildThemeSettings::PROBLEM_INVALID) {
            printf(
                '<div class="notice notice-warning"><p>%s</p></div>',
                esc_html__('Die settings.json des Child-Themes ist ungültig oder nennt keine Vertragsversion. Das Child-Theme wird ignoriert, bis die Datei neu erzeugt ist.', 'wp-starter'),
            );
        } elseif ($problem === ChildThemeSettings::PROBLEM_NEWER) {
            printf(
                '<div class="notice notice-warning"><p>%s</p></div>',
                esc_html(sprintf(
                    /* translators: 1: contract version of the child theme, 2: highest version this theme supports */
                    __('Das Child-Theme nutzt Vertragsversion %1$d, dieses Theme kennt bis Version %2$d. Bekannte Einstellungen werden übernommen, neuere ignoriert. Bitte das Parent-Theme aktualisieren.', 'wp-starter'),
                    ChildThemeSettings::contract(),
                    ChildThemeSettings::SUPPORTED_CONTRACT,
                )),
            );
        }
    }

    /**
     * Copies the child's logos into the media library and the logo options,
     * once per build and only into empty options: a logo chosen in the
     * backend is never replaced.
     */
    public function importLogos(): void
    {
        if (!function_exists('get_field') || !function_exists('update_field') || !ChildThemeSettings::active()) {
            return;
        }

        $buildId = ChildThemeSettings::buildId();
        if ($buildId === null) {
            return;
        }

        // INTENTIONAL: the flag is claimed before the import, not after it.
        // add_option() fails when the flag exists, so two parallel requests
        // cannot import twice, and a failed import (unwritable uploads) is not
        // retried on every page view. A new build ID tries again.
        if (!add_option(ThemeContext::optionKey('child_import_' . $buildId), time())) {
            return;
        }

        $titles = [
            'light' => __('Logo', 'wp-starter'),
            'dark' => __('Logo (dunkel)', 'wp-starter'),
        ];

        foreach (self::LOGO_FIELDS as $mode => $field) {
            $logo = ChildThemeSettings::logo($mode);
            if ($logo === null || !empty(get_field($field['name'], 'option'))) {
                continue;
            }

            $attachmentId = MediaImport::file($logo['path'], $titles[$mode]);
            if ($attachmentId === null) {
                LogServiceProvider::warning('Child theme logo could not be imported', ['mode' => $mode]);

                continue;
            }

            update_field($field['key'], $attachmentId, 'option');
        }
    }
}
