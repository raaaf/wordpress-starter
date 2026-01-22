<?php

declare(strict_types=1);

namespace WordpressStarter;

/**
 * Vite Asset Management
 *
 * Handles loading of Vite-compiled assets in both development (HMR)
 * and production (manifest-based) modes.
 */
class Vite
{
    /** @var array<string, array{file: string, src?: string, css?: array<string>}>|null */
    private static ?array $manifest = null;
    private static bool $isDev = false;

    public static function init(): void
    {
        self::$isDev = defined('WP_DEBUG') && WP_DEBUG && self::isDevServerRunning();

        // Frontend assets (wp_enqueue_scripts only fires on frontend)
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);

        // Admin assets for ACF field enhancements
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminAssets']);

        // Add type="module" to Vite scripts
        add_filter('script_loader_tag', [self::class, 'addModuleType'], 10, 3);
    }

    /**
     * Add type="module" to Vite-built scripts for ES module support.
     */
    public static function addModuleType(string $tag, string $handle, string $src): string
    {
        $moduleHandles = ['app-js', 'vite-client'];

        if (in_array($handle, $moduleHandles, true)) {
            // Remove existing type attribute first, then add type="module"
            $tag = preg_replace('/\s+type=["\'][^"\']*["\']/', '', $tag) ?? $tag;
            return str_replace('<script ', '<script type="module" ', $tag);
        }

        return $tag;
    }

    public static function enqueueAssets(): void
    {
        // This hook (wp_enqueue_scripts) only fires on frontend, never in admin/editor
        if (self::$isDev) {
            $host = config('vite.dev_server.host', 'localhost');
            $port = config('vite.dev_server.port', 5173);
            $devServerUrl = "http://{$host}:{$port}";

            // Development mode - inject Vite client
            wp_enqueue_script('vite-client', "{$devServerUrl}/@vite/client", [], null, false);

            // Load CSS from dev server (separate entry point, not imported in JS)
            wp_enqueue_style('app-css', "{$devServerUrl}/resources/css/app.css", [], null);

            // Load JS from dev server
            wp_enqueue_script('app-js', "{$devServerUrl}/resources/js/app.ts", [], null, true);
        } else {
            // Production mode - use manifest
            self::loadManifest();

            // Load app CSS
            if (isset(self::$manifest['resources/css/app.css'])) {
                $cssFile = self::$manifest['resources/css/app.css']['file'];
                wp_enqueue_style('app-css', get_theme_file_uri('dist/' . $cssFile), [], null);
            }

            // Load app JS with proper defer
            if (isset(self::$manifest['resources/js/app.ts'])) {
                $jsFile = self::$manifest['resources/js/app.ts']['file'];
                wp_enqueue_script('app-js', get_theme_file_uri('dist/' . $jsFile), [], null, true);
                wp_script_add_data('app-js', 'defer', true);
            }
        }

        // Localize strings for frontend JavaScript
        wp_localize_script('app-js', 'wpStarterStrings', self::getFrontendStrings());
    }

    /**
     * Get translatable strings for frontend JavaScript.
     *
     * @return array<string, string>
     */
    private static function getFrontendStrings(): array
    {
        return [
            'submenuOpen' => __('Untermenü öffnen', 'wp-starter'),
            'submenuClose' => __('Untermenü schließen', 'wp-starter'),
            'image' => __('Bild', 'wp-starter'),
            'imageZoomInstruction' => __('Klicken oder Enter zum Vergrößern', 'wp-starter'),
        ];
    }

    /**
     * Enqueue admin assets for ACF field enhancements.
     */
    public static function enqueueAdminAssets(): void
    {
        // Only load on ACF-related pages
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        // Load ACF Icon Radio CSS and JS on post edit screens (including Classic Editor)
        // The 'post' base covers both Block Editor and Classic Editor
        if ($screen->base === 'post') {
            // Add CSS inline in admin head
            add_action('admin_head', [self::class, 'outputAcfIconRadioCss']);

            // Add JS in admin footer (more reliable than attaching to acf-input)
            add_action('admin_footer', [self::class, 'outputAcfIconRadioJs']);

            // Localize admin strings for JavaScript
            add_action('admin_footer', [self::class, 'outputAdminStrings'], 5);
        }
    }

    /**
     * Output localized admin strings before other scripts.
     */
    public static function outputAdminStrings(): void
    {
        $strings = [
            'noIcon' => __('Kein Icon', 'wp-starter'),
            'entry' => __('Eintrag', 'wp-starter'),
            'entries' => __('Einträge', 'wp-starter'),
        ];
        $json = wp_json_encode($strings);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted internal JSON for localization
        echo "<script id=\"wp-starter-admin-strings\">var wpStarterAdminStrings = {$json};</script>";
    }

    /**
     * Output ACF Icon Radio CSS in admin head.
     */
    public static function outputAcfIconRadioCss(): void
    {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted internal CSS
        echo '<style id="acf-icon-radio-css">' . self::getAcfIconRadioCss() . '</style>';
    }

    /**
     * Output ACF Icon Radio JS in admin footer.
     */
    public static function outputAcfIconRadioJs(): void
    {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted internal JS
        echo '<script id="acf-icon-radio-js">' . self::getAcfIconRadioJs() . '</script>';
    }

    /**
     * Get all theme icons as SVG strings for admin use.
     * Uses transient caching to avoid reading SVG files on every request.
     *
     * @return array<string, string>
     */
    private static function getThemeIcons(): array
    {
        // Try to get cached icons first
        $cacheKey = 'theme_icons_' . wp_get_theme()->get('Version');
        $cachedIcons = get_transient($cacheKey);

        if ($cachedIcons !== false && is_array($cachedIcons)) {
            return $cachedIcons;
        }

        $iconDir = get_template_directory() . '/resources/icons/';
        $icons = [];

        $iconNames = [
            'calendar', 'check', 'chevron', 'chevron-up', 'chevron-down', 'chevron-left', 'chevron-right',
            'close', 'eye', 'lock', 'mail', 'minus', 'phone', 'plus', 'search', 'user', 'warning',
            'facebook', 'instagram', 'linkedin', 'x', 'xing', 'youtube',
        ];

        foreach ($iconNames as $name) {
            $path = $iconDir . $name . '.svg';
            if (file_exists($path)) {
                $svg = file_get_contents($path);
                if ($svg !== false) {
                    // Remove width/height attributes so CSS can control size
                    $svg = preg_replace('/\s*(width|height)="[^"]*"/', '', $svg) ?? $svg;
                    $icons[$name] = $svg;
                }
            }
        }

        // Cache for 24 hours (invalidated by theme version in cache key)
        set_transient($cacheKey, $icons, DAY_IN_SECONDS);

        return $icons;
    }

    /**
     * Get JavaScript for ACF Icon Radio Field enhancement.
     * This injects SVG icons into the radio button labels.
     */
    private static function getAcfIconRadioJs(): string
    {
        $icons = self::getThemeIcons();
        $iconsJson = wp_json_encode($icons);

        return <<<JS
(function() {
    const icons = {$iconsJson};

    function enhanceLabels(container) {
        const labels = container.querySelectorAll('.acf-radio-list label');

        labels.forEach(function(label) {
            // Skip if already enhanced
            if (label.dataset.iconEnhanced) return;
            label.dataset.iconEnhanced = 'true';

            const input = label.querySelector('input[type="radio"]');
            if (!input) return;

            const iconName = input.value;
            label.dataset.icon = iconName;

            // Get the text content (the label text)
            const textContent = label.textContent.trim();

            if (iconName && icons[iconName]) {
                // Replace content with SVG
                label.innerHTML = '';
                label.appendChild(input);
                label.insertAdjacentHTML('beforeend', icons[iconName]);
                // Add hidden text for accessibility
                const srText = document.createElement('span');
                srText.className = 'screen-reader-text';
                srText.textContent = textContent;
                label.appendChild(srText);
            } else if (iconName === '') {
                // "No icon" option - keep text but wrap it
                label.innerHTML = '';
                label.appendChild(input);
                const textSpan = document.createElement('span');
                textSpan.textContent = window.wpStarterAdminStrings?.noIcon || 'No Icon';
                label.appendChild(textSpan);
            }
        });
    }

    function enhanceAllIconFields() {
        const fields = document.querySelectorAll('.acf-icon-radio-field');
        fields.forEach(function(wrapper) {
            enhanceLabels(wrapper);
        });
    }

    // Wait for ACF to be ready, then use proper hooks
    function initAcfHooks() {
        if (typeof acf === 'undefined') {
            setTimeout(initAcfHooks, 100);
            return;
        }

        // Use ACF's field-specific hooks for radio fields
        acf.addAction('ready_field/type=radio', function(field) {
            if (field.\$el && field.\$el[0].classList.contains('acf-icon-radio-field')) {
                enhanceLabels(field.\$el[0]);
            }
        });

        acf.addAction('append_field/type=radio', function(field) {
            if (field.\$el && field.\$el[0].classList.contains('acf-icon-radio-field')) {
                enhanceLabels(field.\$el[0]);
            }
        });

        // Also run on general ready/append for safety
        acf.addAction('ready', function() {
            enhanceAllIconFields();
        });
        acf.addAction('append', function() {
            enhanceAllIconFields();
        });
    }

    // Start initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAcfHooks);
    } else {
        initAcfHooks();
    }
})();
JS;
    }

    /**
     * Get CSS for ACF Icon Radio Field enhancement.
     * This styles the admin UI for icon selection fields.
     */
    private static function getAcfIconRadioCss(): string
    {
        return <<<'CSS'
/* ACF Icon Radio Field Enhancement */
.acf-icon-radio-field .acf-radio-list {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}
.acf-icon-radio-field .acf-radio-list li {
    margin: 0 !important;
}
.acf-icon-radio-field .acf-radio-list input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    pointer-events: none;
}
.acf-icon-radio-field .acf-radio-list label {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
    margin: 0;
    position: relative;
}
.acf-icon-radio-field .acf-radio-list label:hover {
    border-color: #007cba;
    background: #f0f7fc;
}
.acf-icon-radio-field .acf-radio-list label:has(input[type="radio"]:checked) {
    border-color: #007cba;
    background: #007cba;
    color: #fff;
}
.acf-icon-radio-field .acf-radio-list label:has(input[type="radio"]:checked) svg {
    color: #fff;
}
.acf-icon-radio-field .acf-radio-list label svg {
    width: 20px;
    height: 20px;
}
.acf-icon-radio-field .acf-radio-list label[data-icon=""] {
    width: auto;
    padding: 0 12px;
    font-size: 12px;
    color: #666;
}
.acf-icon-radio-field .acf-radio-list label .acf-icon-label-text {
    display: none;
}
CSS;
    }

    private static function loadManifest(): void
    {
        if (self::$manifest === null) {
            $manifestPath = get_theme_file_path('dist/.vite/manifest.json');

            if (!file_exists($manifestPath)) {
                self::$manifest = [];

                // Log warning in development mode
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log('WP-Starter: Vite manifest not found at ' . $manifestPath . '. Run "npm run build".');
                }
                return;
            }

            $content = file_get_contents($manifestPath);

            if ($content === false) {
                self::$manifest = [];
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('WP-Starter: Could not read Vite manifest at ' . $manifestPath);
                return;
            }

            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                self::$manifest = [];
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('WP-Starter: Invalid JSON in Vite manifest: ' . json_last_error_msg());
                return;
            }

            self::$manifest = $decoded;
        }
    }

    /**
     * Check if Vite dev server is running.
     * Uses a short 100ms timeout to avoid blocking page loads.
     */
    public static function isDevServerRunning(): bool
    {
        $host = config('vite.dev_server.host', 'localhost');
        $port = config('vite.dev_server.port', 5173);

        $socket = @fsockopen($host, (int) $port, $errno, $errstr, 0.1);
        if ($socket) {
            fclose($socket);
            return true;
        }
        return false;
    }

    /**
     * Get the URL for an asset, handling both dev and production modes.
     */
    public static function getAssetUrl(string $path): string
    {
        if (self::$isDev) {
            $host = config('vite.dev_server.host', 'localhost');
            $port = config('vite.dev_server.port', 5173);
            return "http://{$host}:{$port}/" . ltrim($path, '/');
        }

        self::loadManifest();

        if (isset(self::$manifest[$path])) {
            return get_theme_file_uri('dist/' . self::$manifest[$path]['file']);
        }

        return get_theme_file_uri($path);
    }
}
