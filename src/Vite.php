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
        add_action('admin_enqueue_scripts', [self::class, 'enqueueEditorAssets']);
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
        if (!self::$isDev) {
            self::loadManifest();

            if (isset(self::$manifest['resources/css/editor-style.css'])) {
                $cssFile = self::$manifest['resources/css/editor-style.css']['file'];
                wp_enqueue_style('editor-style', get_theme_file_uri('dist/' . $cssFile), [], null);
            }
        }
    }

    private static function loadManifest(): void
    {
        if (self::$manifest === null) {
            $manifestPath = get_theme_file_path('dist/.vite/manifest.json');

            if (file_exists($manifestPath)) {
                $content = file_get_contents($manifestPath);
                self::$manifest = $content ? json_decode($content, true) : [];
            } else {
                self::$manifest = [];
            }
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
