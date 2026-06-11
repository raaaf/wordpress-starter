<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

/**
 * Asset Optimization Service Provider
 *
 * Improves frontend performance by deferring non-critical scripts,
 * preloading critical fonts and stylesheets, and inlining above-the-fold CSS.
 */
class AssetOptimizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No bindings required
    }

    public function boot(): void
    {
        $this->optimizeScriptLoading();
        $this->addResourcePreloading();
    }

    /**
     * Optimize script loading with defer attribute
     *
     * Adds defer attribute to non-critical scripts to prevent blocking
     * the initial page render. This improves First Contentful Paint (FCP)
     * and Largest Contentful Paint (LCP) metrics.
     *
     * Note: Scripts with type="module" are already deferred by browsers.
     */
    private function optimizeScriptLoading(): void
    {
        add_filter('script_loader_tag', function (string $tag, string $handle, string $src): string {
            // Skip admin scripts
            if (is_admin()) {
                return $tag;
            }

            // Skip scripts that already have defer, async, or type="module"
            if (
                str_contains($tag, ' defer')
                || str_contains($tag, ' async')
                || str_contains($tag, 'type="module"')
            ) {
                return $tag;
            }

            // Scripts that should NOT be deferred (critical for page functionality)
            $noDeferHandles = [
                'wp-polyfill',  // Polyfills must load first
                'wp-hooks',     // Required by wp-i18n (defines wp.hooks)
                'wp-i18n',      // Required by inline translation scripts (Contact Form 7, etc.)
            ];

            if (in_array($handle, $noDeferHandles, true)) {
                return $tag;
            }

            // Add defer to all other scripts
            return str_replace('<script ', '<script defer ', $tag);
        }, 20, 3);
    }

    /**
     * Add resource preloading for critical assets
     *
     * Preloads critical fonts (curated per-theme list) and inlines critical CSS
     * to improve Largest Contentful Paint (LCP) and reduce render-blocking.
     * This is the single source of truth for font preloads; the header blade
     * partial does not emit additional font preloads.
     */
    private function addResourcePreloading(): void
    {
        // Add preload links early in head
        add_action('wp_head', function (): void {
            if (is_admin()) {
                return;
            }

            $fontsDir = get_theme_file_uri('resources/fonts/');

            // Preload critical fonts (headline and body, most used weights)
            $criticalFonts = [
                'colabthi-webfont.woff2',        // ColaborateLight (headlines)
                'inter-v20-latin-regular.woff2', // Inter Regular (body)
                'inter-v20-latin-700.woff2',     // Inter Bold (body emphasis)
            ];

            foreach ($criticalFonts as $font) {
                printf(
                    '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin="anonymous">%s',
                    esc_url($fontsDir . $font),
                    "\n",
                );
            }
        }, 1);

        // Inline critical CSS if file exists
        add_action('wp_head', function (): void {
            if (is_admin()) {
                return;
            }

            $criticalCssPath = get_theme_file_path('resources/css/critical.css');

            if (file_exists($criticalCssPath)) {
                $cacheKey = 'critical_css_' . get_template_directory();
                $criticalCss = wp_cache_get($cacheKey, 'theme');
                if ($criticalCss === false) {
                    $criticalCss = file_get_contents($criticalCssPath);
                    wp_cache_set($cacheKey, $criticalCss, 'theme', DAY_IN_SECONDS);
                }
                if ($criticalCss) {
                    $nonce = \WordpressStarter\Security::getNonce();
                    printf(
                        '<style id="critical-css"%s>%s</style>%s',
                        $nonce ? ' nonce="' . esc_attr($nonce) . '"' : '',
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted internal CSS
                        $criticalCss,
                        "\n",
                    );
                }
            }
        }, 2); // Priority 2 = right after preloads

        // Preload main stylesheet for faster loading
        add_action('wp_head', function (): void {
            if (is_admin()) {
                return;
            }

            // Get CSS URL from Vite manifest in production
            $cssUrl = \WordpressStarter\Vite::getAssetUrl('resources/css/app.css');
            if (!$cssUrl) {
                return;
            }

            printf(
                '<link rel="preload" href="%s" as="style">%s',
                esc_url($cssUrl),
                "\n",
            );
        }, 1);
    }
}
