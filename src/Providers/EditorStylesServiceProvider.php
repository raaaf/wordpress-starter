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
            $plugins['themeicons'] = admin_url('admin-ajax.php?action=theme_tinymce_icon_picker&ver=' . rawurlencode( (string) wp_get_theme()->get('Version')));
            return $plugins;
        });

        add_action('wp_ajax_theme_tinymce_icon_picker', function (): void {
            // Loaded as a plain <script src> by TinyMCE, so no nonce can travel
            // with the request; the capability gate mirrors the editor screen.
            if (!current_user_can('edit_posts')) {
                status_header(403);
                exit;
            }
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Authenticated admin-ajax response, response is cacheable privately per logged-in user
            header('Content-Type: application/javascript; charset=utf-8');
            header('Cache-Control: private, max-age=3600');
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

    var STYLE_ID = 'theme-icon-picker-style';
    var CSS = ''
        + '.tip-dialog{width:min(760px,calc(100vw - 32px));height:min(680px,calc(100vh - 64px));padding:0;border:0;border-radius:8px;box-shadow:0 20px 60px rgba(0,0,0,.35);color:#1d2327;font-size:13px;overflow:hidden;}'
        + '.tip-dialog[open]{display:flex;flex-direction:column;}'
        + '.tip-dialog::backdrop{background:rgba(0,0,0,.55);}'
        + '.tip-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 20px 12px;}'
        + '.tip-title{margin:0;font-size:16px;font-weight:600;line-height:1.3;}'
        + '.tip-close{display:grid;place-items:center;width:32px;height:32px;margin:-4px -8px -4px 0;padding:0;border:0;border-radius:4px;background:none;color:#50575e;font-size:22px;line-height:1;cursor:pointer;}'
        + '.tip-close:hover{background:#f0f0f1;color:#1d2327;}'
        + '.tip-search{padding:0 20px 12px;border-bottom:1px solid #dcdcde;}'
        + '.tip-search input{width:100%;min-height:40px;padding:0 12px;font-size:14px;border:1px solid #8c8f94;border-radius:4px;box-sizing:border-box;}'
        + '.tip-search input:focus{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1;outline:none;}'
        + '.tip-count{margin:8px 0 0;color:#646970;font-size:12px;}'
        + '.tip-grid{flex:1 1 auto;min-height:0;overflow-y:auto;overscroll-behavior:contain;display:grid;grid-template-columns:repeat(auto-fill,minmax(104px,1fr));align-content:start;gap:8px;padding:16px 20px 20px;margin:0;list-style:none;}'
        + '.tip-grid li{margin:0;}'
        + '.tip-tile{display:flex;flex-direction:column;align-items:center;justify-content:flex-start;gap:8px;width:100%;height:100%;min-height:88px;padding:14px 6px 10px;border:1px solid #dcdcde;border-radius:6px;background:#fff;color:#1d2327;cursor:pointer;box-sizing:border-box;}'
        + '.tip-tile img{width:28px;height:28px;flex:none;}'
        + '.tip-tile span{font-size:12px;line-height:1.3;text-align:center;overflow-wrap:break-word;hyphens:auto;max-width:100%;}'
        + '.tip-tile:hover{border-color:#2271b1;background:#f0f6fc;}'
        + '.tip-tile:focus-visible{outline:2px solid #2271b1;outline-offset:1px;border-color:#2271b1;background:#f0f6fc;}'
        + '.tip-empty{grid-column:1/-1;padding:32px 0;text-align:center;color:#646970;}';

    function ensureStyle() {
        if (document.getElementById(STYLE_ID)) { return; }
        var style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = CSS;
        document.head.appendChild(style);
    }

    function normalize(value) {
        return String(value).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }

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
                openPicker(editor, data);
            }
        });
    });

    function openPicker(editor, data) {
        ensureStyle();
        var labels = data.labels || {};
        var icons = data.icons.map(function(slug) {
            var label = labels[slug] || slug;
            return { slug: slug, label: label, haystack: normalize(label + ' ' + slug.replace(/-/g, ' ')) };
        }).sort(function(a, b) { return a.label.localeCompare(b.label, 'de'); });

        var dialog = document.createElement('dialog');
        dialog.className = 'tip-dialog';
        dialog.lang = 'de';
        dialog.setAttribute('aria-labelledby', 'tip-title');

        var head = document.createElement('div');
        head.className = 'tip-head';
        var title = document.createElement('h2');
        title.className = 'tip-title';
        title.id = 'tip-title';
        title.textContent = 'Icon einfügen';
        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'tip-close';
        close.setAttribute('aria-label', 'Schließen');
        close.textContent = '×';
        head.appendChild(title);
        head.appendChild(close);

        var searchWrap = document.createElement('div');
        searchWrap.className = 'tip-search';
        var input = document.createElement('input');
        input.type = 'search';
        input.placeholder = 'Suchen, z. B. Pfeil, Telefon, Haus';
        input.setAttribute('aria-label', 'Icons durchsuchen');
        input.autocomplete = 'off';
        var count = document.createElement('p');
        count.className = 'tip-count';
        count.setAttribute('aria-live', 'polite');
        searchWrap.appendChild(input);
        searchWrap.appendChild(count);

        var grid = document.createElement('ul');
        grid.className = 'tip-grid';
        var tiles = icons.map(function(icon) {
            var li = document.createElement('li');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'tip-tile';
            btn.setAttribute('data-icon', icon.slug);
            btn.title = icon.slug;
            var img = document.createElement('img');
            img.src = data.baseUrl + encodeURIComponent(icon.slug) + '.svg';
            img.alt = '';
            img.loading = 'lazy';
            var text = document.createElement('span');
            text.textContent = icon.label;
            btn.appendChild(img);
            btn.appendChild(text);
            li.appendChild(btn);
            grid.appendChild(li);
            return { icon: icon, li: li, btn: btn };
        });
        var empty = document.createElement('li');
        empty.className = 'tip-empty';
        empty.hidden = true;
        grid.appendChild(empty);

        dialog.appendChild(head);
        dialog.appendChild(searchWrap);
        dialog.appendChild(grid);
        document.body.appendChild(dialog);

        function visibleButtons() {
            return tiles.filter(function(t) { return !t.li.hidden; }).map(function(t) { return t.btn; });
        }

        function filter() {
            var terms = normalize(input.value.trim()).split(/\s+/).filter(Boolean);
            var shown = 0;
            tiles.forEach(function(t) {
                var match = terms.every(function(term) { return t.icon.haystack.indexOf(term) !== -1; });
                t.li.hidden = !match;
                if (match) { shown++; }
            });
            empty.hidden = shown !== 0;
            empty.textContent = 'Kein Icon gefunden für „' + input.value.trim() + '“.';
            count.textContent = terms.length ? shown + ' Treffer' + (shown ? ' · Enter fügt den ersten ein' : '') : icons.length + ' Icons';
            grid.scrollTop = 0;
        }

        function insert(slug) {
            dialog.close();
            editor.focus();
            editor.insertContent('[icon name="' + slug.replace(/"/g, '') + '"]');
        }

        function columns() {
            var buttons = visibleButtons();
            if (buttons.length < 2) { return 1; }
            var top = buttons[0].offsetTop;
            for (var i = 1; i < buttons.length; i++) {
                if (buttons[i].offsetTop !== top) { return i; }
            }
            return buttons.length;
        }

        input.addEventListener('input', filter);
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var first = visibleButtons()[0];
                if (first) { insert(first.getAttribute('data-icon')); }
            } else if (e.key === 'ArrowDown') {
                var firstBtn = visibleButtons()[0];
                if (firstBtn) { e.preventDefault(); firstBtn.focus(); }
            }
        });

        grid.addEventListener('click', function(e) {
            var target = e.target.closest('[data-icon]');
            if (target) { insert(target.getAttribute('data-icon')); }
        });

        grid.addEventListener('keydown', function(e) {
            var steps = { ArrowRight: 1, ArrowLeft: -1, ArrowDown: columns(), ArrowUp: -columns() };
            if (!(e.key in steps)) { return; }
            var buttons = visibleButtons();
            var index = buttons.indexOf(document.activeElement);
            if (index === -1) { return; }
            e.preventDefault();
            var next = index + steps[e.key];
            if (next < 0) { input.focus(); return; }
            if (next < buttons.length) { buttons[next].focus(); }
        });

        close.addEventListener('click', function() { dialog.close(); });
        dialog.addEventListener('click', function(e) {
            if (e.target === dialog) { dialog.close(); }
        });
        dialog.addEventListener('close', function() {
            dialog.remove();
            editor.focus();
        });

        filter();
        dialog.showModal();
        input.focus();
    }
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

            $labels = array_filter(
                \WordpressStarter\Acf\FieldDefinitions::getThemeIcons(),
                fn(string $key) => $key !== '',
                ARRAY_FILTER_USE_KEY
            );

            wp_add_inline_script(
                'editor',
                'window.themeIconsData = ' . wp_json_encode([
                    'icons' => array_keys($labels),
                    'labels' => $labels,
                    'baseUrl' => get_template_directory_uri() . '/resources/icons/',
                ]) . ';',
                'before'
            );
        });
    }
}
