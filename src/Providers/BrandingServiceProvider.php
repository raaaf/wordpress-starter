<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

/**
 * Branding Service Provider
 *
 * Manages site branding assets: exposes the ACF favicon on the frontend and
 * login screen, renders a custom login logo, and keeps ACF option values in
 * sync with the WordPress core custom_logo theme mod and site_icon option so
 * SEO plugins and social-sharing integrations use the correct images.
 */
class BrandingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No bindings required
    }

    public function boot(): void
    {
        $this->addFaviconSupport();
        $this->addLoginLogoSupport();
        $this->syncAcfWithWordPress();
    }

    /**
     * Add favicon support using ACF options
     */
    private function addFaviconSupport(): void
    {
        // Override WordPress site icon with ACF favicon
        add_filter('get_site_icon_url', function (string $url, int $size, int $blogId): string {
            if (!function_exists('get_field')) {
                return $url;
            }

            $faviconId = \WordpressStarter\Acf\Fields::option('site_favicon');
            if (!$faviconId) {
                return $url;
            }

            $faviconUrl = wp_get_attachment_image_url($faviconId, [$size, $size]);

            return $faviconUrl ?: $url;
        }, 10, 3);

        // Add favicon meta tags if ACF favicon is set
        add_action('wp_head', function (): void {
            if (!function_exists('get_field')) {
                return;
            }

            $faviconId = \WordpressStarter\Acf\Fields::option('site_favicon');
            if (!$faviconId) {
                return;
            }

            // Don't output if WordPress already has a site icon
            if (has_site_icon()) {
                return;
            }

            $favicon16 = wp_get_attachment_image_url($faviconId, [16, 16]);
            $favicon32 = wp_get_attachment_image_url($faviconId, [32, 32]);
            $favicon180 = wp_get_attachment_image_url($faviconId, [180, 180]);
            $favicon192 = wp_get_attachment_image_url($faviconId, [192, 192]);
            $favicon512 = wp_get_attachment_image_url($faviconId, [512, 512]);

            if ($favicon32) {
                echo '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url($favicon32) . '">' . "\n";
            }
            if ($favicon16) {
                echo '<link rel="icon" type="image/png" sizes="16x16" href="' . esc_url($favicon16) . '">' . "\n";
            }
            if ($favicon180) {
                echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url($favicon180) . '">' . "\n";
            }
            if ($favicon192) {
                echo '<link rel="icon" type="image/png" sizes="192x192" href="' . esc_url($favicon192) . '">' . "\n";
            }
            if ($favicon512) {
                echo '<link rel="icon" type="image/png" sizes="512x512" href="' . esc_url($favicon512) . '">' . "\n";
            }
        }, 1);
    }

    /**
     * Add custom login page logo using ACF options
     */
    private function addLoginLogoSupport(): void
    {
        // Custom login logo
        add_action('login_enqueue_scripts', function (): void {
            $logoUrl = $this->getLogoUrl();
            if (!$logoUrl) {
                return;
            }
            ?>
            <style type="text/css">
                #login h1 a, .login h1 a {
                    background-image: url('<?php echo esc_url($logoUrl); ?>');
                    background-size: contain;
                    background-repeat: no-repeat;
                    background-position: center;
                    width: 100%;
                    height: 80px;
                }
            </style>
            <?php
        });

        // Custom login logo URL
        add_filter('login_headerurl', function (): string {
            return home_url();
        });

        // Custom login logo title
        add_filter('login_headertext', function (): string {
            return get_bloginfo('name');
        });
    }

    /**
     * Get logo URL from ACF options or Customizer
     */
    private function getLogoUrl(): ?string
    {
        return \WordpressStarter\Acf\Fields::siteLogoUrl();
    }

    /**
     * Sync ACF options with WordPress core settings
     *
     * When logo/favicon are set in ACF Theme Options, this syncs them to
     * WordPress core settings so SEO plugins, social sharing, and other
     * WordPress features use the correct images.
     */
    private function syncAcfWithWordPress(): void
    {
        // Sync on ACF options save
        add_action('acf/save_post', function ($postId): void {
            if ($postId !== 'options') {
                return;
            }

            $this->syncLogoToWordPress();
            $this->syncFaviconToWordPress();
        }, 20);

        // Also sync on init if values exist but aren't synced
        add_action('init', function (): void {
            if (!function_exists('get_field')) {
                return;
            }

            // Only sync if ACF values exist but WordPress values don't match
            $this->maybeInitialSync();
        }, 20);
    }

    /**
     * Sync ACF logo to WordPress custom_logo theme mod
     */
    private function syncLogoToWordPress(): void
    {
        if (!function_exists('get_field')) {
            return;
        }

        $acfLogo = get_field('site_logo', 'option');

        if ($acfLogo && !empty($acfLogo['ID'])) {
            // Set the WordPress custom_logo to the ACF logo
            set_theme_mod('custom_logo', $acfLogo['ID']);
        }
    }

    /**
     * Sync ACF favicon to WordPress site_icon option
     */
    private function syncFaviconToWordPress(): void
    {
        if (!function_exists('get_field')) {
            return;
        }

        $faviconId = \WordpressStarter\Acf\Fields::option('site_favicon');

        if ($faviconId) {
            // Set the WordPress site_icon to the ACF favicon
            update_option('site_icon', $faviconId);
        }
    }

    /**
     * Initial sync if ACF values exist but WordPress values don't
     */
    private function maybeInitialSync(): void
    {
        // Only run once — bail if already synced in a previous request
        $transientKey = \WordpressStarter\ThemeContext::prefix() . '_initial_sync_done';
        if (get_transient($transientKey)) {
            return;
        }

        // Sync logo if ACF has one but WordPress doesn't
        $acfLogo = get_field('site_logo', 'option');
        $wpLogo = get_theme_mod('custom_logo');

        if ($acfLogo && !empty($acfLogo['ID']) && !$wpLogo) {
            set_theme_mod('custom_logo', $acfLogo['ID']);
        }

        // Sync favicon if ACF has one but WordPress doesn't
        $acfFavicon = \WordpressStarter\Acf\Fields::option('site_favicon');
        $wpSiteIcon = get_option('site_icon');

        if ($acfFavicon && !$wpSiteIcon) {
            update_option('site_icon', $acfFavicon);
        }

        set_transient($transientKey, true, DAY_IN_SECONDS);
    }
}
