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

        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);
        add_action('enqueue_block_editor_assets', [self::class, 'enqueueEditorAssets']);

        // Add type="module" to Vite scripts
        add_filter('script_loader_tag', [self::class, 'addModuleType'], 10, 3);
    }

    /**
     * Add type="module" to Vite-built scripts for ES module support.
     */
    public static function addModuleType(string $tag, string $handle, string $src): string
    {
        $moduleHandles = ['app-js', 'editor-js', 'vite-client', 'vite-client-editor'];

        if (in_array($handle, $moduleHandles, true)) {
            // Remove existing type attribute first, then add type="module"
            $tag = preg_replace('/\s+type=["\'][^"\']*["\']/', '', $tag) ?? $tag;
            return str_replace('<script ', '<script type="module" ', $tag);
        }

        return $tag;
    }

    public static function enqueueAssets(): void
    {
        if (self::$isDev) {
            $host = config('vite.dev_server.host', 'localhost');
            $port = config('vite.dev_server.port', 5173);
            $devServerUrl = "http://{$host}:{$port}";

            // Development mode - inject Vite client
            wp_enqueue_script('vite-client', "{$devServerUrl}/@vite/client", [], null, false);

            // Load assets from dev server
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

    public static function enqueueEditorAssets(): void
    {
        if (self::$isDev) {
            $host = config('vite.dev_server.host', 'localhost');
            $port = config('vite.dev_server.port', 5173);
            $devServerUrl = "http://{$host}:{$port}";

            // Development mode - inject Vite client for HMR
            wp_enqueue_script('vite-client-editor', "{$devServerUrl}/@vite/client", [], null, false);

            // Development mode - load CSS from dev server
            wp_enqueue_style('editor-style', "{$devServerUrl}/resources/css/editor-style.css", [], null);
            wp_enqueue_style('app-css-editor', "{$devServerUrl}/resources/css/app.css", [], null);

            // Development mode - load editor JS (Alpine.js for block previews)
            wp_enqueue_script('editor-js', "{$devServerUrl}/resources/js/editor.ts", [], null, true);

            // Add inline script BEFORE the module to pass data (wp_localize_script doesn't work with ES modules)
            wp_add_inline_script('editor-js', 'window.themeData = ' . wp_json_encode([
                'themeUrl' => get_template_directory_uri(),
            ]) . ';', 'before');
        } else {
            self::loadManifest();

            // Load editor-specific styles
            if (isset(self::$manifest['resources/css/editor-style.css'])) {
                $cssFile = self::$manifest['resources/css/editor-style.css']['file'];
                wp_enqueue_style('editor-style', get_theme_file_uri('dist/' . $cssFile), [], null);
            }

            // Load main app CSS for block previews (needed for TailwindCSS classes)
            if (isset(self::$manifest['resources/css/app.css'])) {
                $cssFile = self::$manifest['resources/css/app.css']['file'];
                wp_enqueue_style('app-css-editor', get_theme_file_uri('dist/' . $cssFile), [], null);
            }

            // Load editor JS (Alpine.js for block previews)
            if (isset(self::$manifest['resources/js/editor.ts'])) {
                $jsFile = self::$manifest['resources/js/editor.ts']['file'];
                wp_enqueue_script('editor-js', get_theme_file_uri('dist/' . $jsFile), [], null, true);

                // Localize theme URL for icon loading
                wp_localize_script('editor-js', 'themeData', [
                    'themeUrl' => get_template_directory_uri(),
                ]);
            }
        }
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
