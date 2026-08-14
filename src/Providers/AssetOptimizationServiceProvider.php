<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WP_Post;

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
        $this->disableEmojiAssets();
        $this->limitFormAssets();
    }

    /**
     * Drop the WordPress emoji detection script and styles.
     *
     * Every browser the theme supports renders emoji natively, so the script
     * is pure overhead on every request.
     */
    private function disableEmojiAssets(): void
    {
        add_action('init', function (): void {
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_filter('the_content_feed', 'wp_staticize_emoji');
            remove_filter('comment_text_rss', 'wp_staticize_emoji');
            remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        });

        add_filter('tiny_mce_plugins', function (array $plugins): array {
            return array_values(array_diff($plugins, ['wpemoji']));
        });
    }

    /**
     * Load the Contact Form 7 assets only where a form is rendered.
     *
     * CF7 enqueues a stylesheet plus three scripts on every request, including
     * pages that contain no form at all.
     */
    private function limitFormAssets(): void
    {
        add_action('wp_enqueue_scripts', function (): void {
            if ($this->currentPageHasForm()) {
                return;
            }

            global $wp_scripts, $wp_styles;

            $scriptQueue = $wp_scripts->queue ?? [];
            $styleQueue = $wp_styles->queue ?? [];

            foreach ($scriptQueue as $handle) {
                $src = strval($wp_scripts->registered[$handle]->src ?? '');
                if (str_contains($src, '/contact-form-7')) {
                    wp_dequeue_script($handle);
                }
            }

            foreach ($styleQueue as $handle) {
                $src = strval($wp_styles->registered[$handle]->src ?? '');
                if (str_contains($src, '/contact-form-7')) {
                    wp_dequeue_style($handle);
                }
            }
        }, 100);
    }

    /**
     * Whether the current request renders a contact form.
     *
     * Errs on the side of keeping the assets: an unknown context returns true.
     */
    private function currentPageHasForm(): bool
    {
        static $cache = [];

        $postId = get_queried_object_id();

        if (!$postId) {
            return true;
        }

        if (array_key_exists($postId, $cache)) {
            return $cache[$postId];
        }

        $sections = get_post_meta($postId, 'page_sections', true);
        if (is_array($sections) && in_array('contact_form', $sections, true)) {
            $cache[$postId] = true;

            return $cache[$postId];
        }

        $post = get_post($postId);
        if ($post instanceof WP_Post) {
            $content = strval($post->post_content);
            if (has_shortcode($content, 'contact-form-7')) {
                $cache[$postId] = true;

                return $cache[$postId];
            }
        }

        // Editors can also paste the shortcode into any ACF field, so scan the
        // post meta. get_post_meta() without a key is served from the object
        // cache primed by the main query, so this costs no extra round trip.
        foreach (get_post_meta($postId) as $values) {
            $values = is_array($values) ? $values : [$values];
            foreach ($values as $value) {
                if (is_string($value) && str_contains($value, '[contact-form-7')) {
                    $cache[$postId] = true;

                    return $cache[$postId];
                }
            }
        }

        $cache[$postId] = (bool) apply_filters('theme_needs_form_assets', false, $postId);

        return $cache[$postId];
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

            // Preload critical fonts (headline and body, most used weights)
            // The URL has to come from the Vite manifest: the stylesheet requests
            // the hashed file in dist/, so preloading the raw path in
            // resources/fonts/ downloads a second copy that is never used.
            // Preload exactly the faces used above the fold. Header navigation and
            // the header CTA sit at the top of every page, so their weights are
            // always in the first viewport. Measured on the live site: preloading
            // a weight that is not used above the fold delays the ones that are,
            // which showed up as a 0.765 CLS on gold-investment.
            $criticalFonts = [
                'space-grotesk-variable.woff2',  // Space Grotesk Variable (headlines)
                'inter-v20-latin-regular.woff2', // Inter Regular (body)
                'inter-v20-latin-500.woff2',     // Inter Medium (navigation)
                'inter-v20-latin-600.woff2',     // Inter SemiBold (header CTA, h2-h4)
            ];

            foreach ($criticalFonts as $font) {
                $url = \WordpressStarter\Vite::getAssetUrl('resources/fonts/' . $font);

                printf(
                    '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin="anonymous">%s',
                    esc_url($url),
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
