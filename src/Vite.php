<?php

declare(strict_types=1);

namespace StiftungsNavigatorGmbH;

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

        // Editor JavaScript only (CSS is handled via add_editor_style in ThemeServiceProvider)
        add_action('enqueue_block_editor_assets', [self::class, 'enqueueEditorAssets']);

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
    }

    /**
     * Enqueue editor assets.
     *
     * Note: All ACF blocks are set to edit mode (no preview rendering).
     * This means we don't need Alpine.js or complex CSS for block previews.
     * Only minimal admin UI CSS is loaded here.
     */
    public static function enqueueEditorAssets(): void
    {
        // All ACF blocks are in edit mode - no preview rendering.
        // Admin UI CSS for ACF field enhancements is loaded inline below.

        // ACF Icon Radio Field CSS (admin UI enhancement)
        wp_add_inline_style('wp-block-editor', self::getAcfIconRadioCss());

        // Force edit mode script - ensures all ACF blocks show form fields
        wp_enqueue_script(
            'acf-force-edit-mode',
            get_theme_file_uri('resources/js/editor-force-edit.js'),
            ['wp-blocks', 'wp-data', 'wp-element'],
            null,
            true
        );
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
