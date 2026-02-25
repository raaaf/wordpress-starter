<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\Vite;

class EditorStylesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Nothing to bind in the container
    }

    public function boot(): void
    {
        $this->addEditorStylesheet();
        $this->addStyleselectToToolbar();
        $this->addCustomFormats();
        $this->ensureStyleselectInAcfToolbar();
        $this->addIconPickerButton();
        $this->localizeIconData();
    }

    /**
     * Inject the editor stylesheet into TinyMCE via the mce_css filter.
     *
     * Works in both dev mode (Vite dev server URL) and production
     * (hashed dist URL from manifest). The CSS is loaded inside the
     * TinyMCE iframe, so :root tokens from tokens-editor.css apply directly.
     */
    private function addEditorStylesheet(): void
    {
        add_filter('mce_css', function (string $mce_css): string {
            $url = Vite::getAssetUrl('resources/css/editor-style.css');

            if ($mce_css) {
                return $mce_css . ',' . $url;
            }

            return $url;
        });
    }

    /**
     * Add the "styleselect" button to TinyMCE toolbar row 2.
     *
     * Styleselect renders the "Formats" dropdown with all format_tags
     * registered via tiny_mce_before_init.
     */
    private function addStyleselectToToolbar(): void
    {
        add_filter('mce_buttons_2', function (array $buttons): array {
            if (!in_array('styleselect', $buttons, true)) {
                array_unshift($buttons, 'styleselect');
            }

            return $buttons;
        });
    }

    /**
     * Register custom formats for the TinyMCE Formats dropdown.
     *
     * Two groups:
     * - Typografie: Typography styles (Display, Headings, Body variants, Caption, Overline, Code)
     * - Buttons: Link button styles (Primary, Secondary, Ghost)
     *
     * Headings (h1-h5) use only the block element change — no extra class needed
     * because base heading styles in editor-style.css cover them completely.
     * The text-h* utility classes exist for applying heading styles to non-heading
     * elements; they are not needed here.
     */
    private function addCustomFormats(): void
    {
        add_filter('tiny_mce_before_init', function (array $init): array {
            $styleFormats = [
                [
                    'title' => __('Typografie', 'wp-starter'),
                    'items' => [
                        [
                            'title' => __('Display', 'wp-starter'),
                            'block' => 'p',
                            'classes' => 'text-display',
                            'wrapper' => false,
                        ],
                        [
                            'title' => __('Heading 1', 'wp-starter'),
                            'block' => 'h1',
                        ],
                        [
                            'title' => __('Heading 2', 'wp-starter'),
                            'block' => 'h2',
                        ],
                        [
                            'title' => __('Heading 3', 'wp-starter'),
                            'block' => 'h3',
                        ],
                        [
                            'title' => __('Heading 4', 'wp-starter'),
                            'block' => 'h4',
                        ],
                        [
                            'title' => __('Heading 5', 'wp-starter'),
                            'block' => 'h5',
                        ],
                        [
                            'title' => __('Body Large', 'wp-starter'),
                            'block' => 'p',
                            'classes' => 'text-body-large',
                            'wrapper' => false,
                        ],
                        [
                            'title' => __('Body', 'wp-starter'),
                            'block' => 'p',
                        ],
                        [
                            'title' => __('Body Small', 'wp-starter'),
                            'block' => 'p',
                            'classes' => 'text-body-small',
                            'wrapper' => false,
                        ],
                        [
                            'title' => __('Caption', 'wp-starter'),
                            'inline' => 'span',
                            'classes' => 'text-caption',
                        ],
                        [
                            'title' => __('Overline', 'wp-starter'),
                            'inline' => 'span',
                            'classes' => 'text-overline',
                        ],
                        [
                            'title' => __('Code', 'wp-starter'),
                            'inline' => 'code',
                            'classes' => 'text-code',
                        ],
                    ],
                ],
                [
                    'title' => __('Buttons', 'wp-starter'),
                    'items' => [
                        [
                            'title' => __('Klein', 'wp-starter'),
                            'items' => [
                                [
                                    'title' => __('Primary', 'wp-starter'),
                                    'selector' => 'a',
                                    'classes' => 'button-primary button-sm',
                                ],
                                [
                                    'title' => __('Secondary', 'wp-starter'),
                                    'selector' => 'a',
                                    'classes' => 'button-secondary button-sm',
                                ],
                                [
                                    'title' => __('Ghost', 'wp-starter'),
                                    'selector' => 'a',
                                    'classes' => 'button-ghost button-sm',
                                ],
                            ],
                        ],
                        [
                            'title' => __('Mittel', 'wp-starter'),
                            'items' => [
                                [
                                    'title' => __('Primary', 'wp-starter'),
                                    'selector' => 'a',
                                    'classes' => 'button-primary button-md',
                                ],
                                [
                                    'title' => __('Secondary', 'wp-starter'),
                                    'selector' => 'a',
                                    'classes' => 'button-secondary button-md',
                                ],
                                [
                                    'title' => __('Ghost', 'wp-starter'),
                                    'selector' => 'a',
                                    'classes' => 'button-ghost button-md',
                                ],
                            ],
                        ],
                        [
                            'title' => __('Groß', 'wp-starter'),
                            'items' => [
                                [
                                    'title' => __('Primary', 'wp-starter'),
                                    'selector' => 'a',
                                    'classes' => 'button-primary button-lg',
                                ],
                                [
                                    'title' => __('Secondary', 'wp-starter'),
                                    'selector' => 'a',
                                    'classes' => 'button-secondary button-lg',
                                ],
                                [
                                    'title' => __('Ghost', 'wp-starter'),
                                    'selector' => 'a',
                                    'classes' => 'button-ghost button-lg',
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $init['style_formats'] = wp_json_encode($styleFormats);

            return $init;
        });
    }

    /**
     * Ensure "styleselect" is present in ACF's "full" WYSIWYG toolbar (row 2).
     *
     * ACF defines its own toolbar configurations. This filter appends
     * styleselect to the second toolbar row if it is not already there.
     */
    private function ensureStyleselectInAcfToolbar(): void
    {
        add_filter('acf/fields/wysiwyg/toolbars', function (array $toolbars): array {
            if (!isset($toolbars['Full'])) {
                return $toolbars;
            }

            // ACF toolbar rows are 1-indexed
            $row2 = $toolbars['Full'][2] ?? [];

            if (!in_array('styleselect', $row2, true)) {
                array_unshift($row2, 'styleselect');
                $toolbars['Full'][2] = $row2;
            }

            return $toolbars;
        });
    }

    /**
     * Add the icon picker button to the TinyMCE toolbar (row 1).
     *
     * Mce_external_plugins requires a URL to a plain .js file. Vite .ts URLs don't
     * work in dev mode and TinyMCE 4 cannot load ES modules. We serve the plugin
     * via admin-ajax.php so it works in both dev and production without a build step.
     */
    private function addIconPickerButton(): void
    {
        add_filter('mce_buttons', function (array $buttons): array {
            $buttons[] = 'themeicons';
            return $buttons;
        });

        add_filter('mce_external_plugins', function (array $plugins): array {
            $plugins['themeicons'] = admin_url('admin-ajax.php?action=theme_tinymce_icon_picker');
            return $plugins;
        });

        add_action('wp_ajax_theme_tinymce_icon_picker', function (): void {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public TinyMCE plugin asset
            header('Content-Type: application/javascript; charset=utf-8');
            header('Cache-Control: public, max-age=3600');
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted internal JS
            echo $this->getIconPickerPluginJs();
            exit;
        });
    }

    /**
     * Returns the TinyMCE icon picker plugin as a plain JavaScript string.
     * Served via admin-ajax.php so TinyMCE 4 can load it without Vite.
     */
    private function getIconPickerPluginJs(): string
    {
        return <<<'JS'
(function() {
    if (typeof tinymce === 'undefined') { return; }

    tinymce.PluginManager.add('themeicons', function(editor) {
        editor.addButton('themeicons', {
            title: 'Icon einfügen',
            icon: 'image',
            onclick: function() {
                var data = window.themeIconsData;
                if (!data || !data.icons || data.icons.length === 0) {
                    editor.windowManager.alert('Keine Icons gefunden.');
                    return;
                }

                var html = '<div style="display:flex;flex-wrap:wrap;gap:8px;max-width:580px;padding:8px;">';
                data.icons.forEach(function(name) {
                    html += '<button type="button" data-icon="' + name + '" title="' + name + '" style="display:flex;flex-direction:column;align-items:center;gap:4px;padding:8px 12px;border:1px solid #ddd;border-radius:4px;background:#fff;cursor:pointer;font-size:11px;min-width:64px;" onmouseover="this.style.background=\'#f0f0f0\'" onmouseout="this.style.background=\'#fff\'">'
                        + '<img src="' + data.baseUrl + name + '.svg" width="24" height="24" alt="' + name + '" style="opacity:0.7;">'
                        + name
                        + '</button>';
                });
                html += '</div>';

                editor.windowManager.open({
                    title: 'Icon auswählen',
                    body: [{ type: 'container', html: html }],
                    buttons: [{ text: 'Schließen', onclick: 'close' }],
                    width: 640,
                    height: 400,
                    onopen: function() {
                        var wins = editor.windowManager.getWindows();
                        var win = wins[wins.length - 1];
                        var container = win.find('container')[0];
                        if (!container) { return; }
                        var el = container.getEl();
                        if (!el) { return; }
                        el.addEventListener('click', function(e) {
                            var target = e.target.closest('[data-icon]');
                            if (target) {
                                editor.insertContent('[icon name="' + target.getAttribute('data-icon') + '"]');
                                editor.windowManager.close();
                            }
                        });
                    }
                });
            }
        });
    });
})();
JS;
    }

    /**
     * Pass available theme icons to the TinyMCE icon picker via localized script data.
     */
    private function localizeIconData(): void
    {
        add_action('admin_enqueue_scripts', function (string $hook): void {
            if (!str_contains($hook, 'post') && $hook !== 'post.php' && $hook !== 'post-new.php') {
                return;
            }

            $icons = array_keys(array_filter(
                \WordpressStarter\Acf\FieldDefinitions::getThemeIcons(),
                fn(string $key) => $key !== '',
                ARRAY_FILTER_USE_KEY
            ));

            wp_add_inline_script(
                'editor',
                'window.themeIconsData = ' . wp_json_encode([
                    'icons' => $icons,
                    'baseUrl' => get_template_directory_uri() . '/resources/icons/',
                ]) . ';',
                'before'
            );
        });
    }
}
