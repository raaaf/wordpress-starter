<?php

declare(strict_types=1);

namespace WordpressStarter\PluginConfigurators;

/**
 * Configures WP-Optimize plugin with optimal defaults
 *
 * Settings applied:
 * - Page Cache: Enabled with 12h lifespan
 * - Database Cleanup: Scheduled weekly
 * - Image Compression: Lossy at 85% quality (if no other optimizer active)
 *
 * @see https://wordpress.org/plugins/wp-optimize/
 */
class WpOptimizeConfigurator extends AbstractPluginConfigurator
{
    public static function getPluginSlug(): string
    {
        return 'wp-optimize';
    }

    public static function isPluginActive(): bool
    {
        return class_exists('WP_Optimize');
    }

    protected static function doConfigure(): void
    {
        self::configureCaching();
        self::configureDatabaseCleanup();
        self::configureImageCompression();

        self::markConfigured();
    }

    /**
     * Configure page caching settings
     */
    private static function configureCaching(): void
    {
        $cacheConfig = [
            'enable_page_caching' => true,
            'page_cache_length' => 43200, // 12 hours in seconds
            'enable_gzip_compression' => true,
            'enable_mobile_caching' => true,
            // Cache exclusions
            'cache_exception_urls' => [
                '/wp-admin/*',
                '/wp-login.php',
                '/warenkorb/*',
                '/cart/*',
                '/kasse/*',
                '/checkout/*',
                '/mein-konto/*',
                '/my-account/*',
            ],
        ];

        // WP-Optimize's own cache config API also writes the JSON file that
        // advanced-cache.php reads at runtime, and it handles the multisite
        // (get_site_option/update_site_option) split itself; writing the
        // option directly (mergeOption) leaves the running cache unaware of
        // the change, so cache_exception_urls never take effect.
        if (class_exists('\WPO_Cache_Config')) {
            $cacheConfigInstance = \WPO_Cache_Config::instance();
            $cacheConfigInstance->update(array_merge($cacheConfigInstance->get(), $cacheConfig));

            return;
        }

        // Fallback only: WPO_Cache_Config class unavailable (e.g. plugin
        // partially loaded). Writes the option but not the plugin's JSON
        // cache file, so the running cache will not pick up the change
        // until WP-Optimize itself re-syncs it.
        self::mergeOption('wpo_cache_config', $cacheConfig);
    }

    /**
     * Configure database cleanup schedule
     *
     * WP-Optimize reads these settings through WP_Optimize_Options, which
     * stores them as prefixed options (wp-optimize-<key>, or
     * wp-optimize-mu-<key> on multisite) rather than under the wpo_* names
     * used by this class previously. Scalar values are strings ('true' /
     * 'false'), matching the plugin's own convention (see
     * includes/class-wp-optimize-options.php and wp-optimize.php
     * cron_action()).
     */
    private static function configureDatabaseCleanup(): void
    {
        $options = \WP_Optimize()->get_options();

        $options->update_option('schedule', 'true');
        $options->update_option('schedule-type', 'wpo_weekly');
        $options->update_option('retention-enabled', 'true');
        $options->update_option('retention-period', '4'); // 4 weeks

        /*
         * What to clean automatically, keyed by each optimization's auto_id
         * (see plugins/wp-optimize/optimizations/*.php). Only optimizations
         * with available_for_auto = true can be scheduled at all.
         * - 'spams' covers both spam AND trashed comments in one
         *   optimization (WP_Optimization_spam::delete_comments_by_type()),
         *   so there is no separate trashed-comments key.
         * - 'transient' only auto-cleans expired transients; removing ALL
         *   transients is a manual-run-only parameter in the plugin, so
         *   there is no persisted auto option for it and it is not written
         *   here.
         * - Orphaned postmeta cleanup (WP_Optimization_postmeta) has
         *   available_for_auto = false, i.e. no auto_id at all, and can
         *   only be triggered by a manual run. Dropped, not migrated.
         */
        self::mergeWpOptimizeOption($options, 'auto', [
            'revisions' => 'true',
            'drafts' => 'true', // auto-drafts
            'trash' => 'true', // trashed posts
            'spams' => 'true', // spam comments + trashed comments
            'transient' => 'true', // expired transients
            // Keep unapproved comments for safety (not auto-deleted)
            'unapproved' => 'false',
        ]);
    }

    /**
     * Configure image compression (if no other optimizer is active)
     *
     * WP-Optimize's built-in image compression ("Smush") stores its
     * settings as individual prefixed options through WP_Optimize_Options,
     * not as a single wpo_images_settings array (see
     * includes/class-updraft-smush-manager.php get_smush_options()).
     */
    private static function configureImageCompression(): void
    {
        // Skip if another image optimizer is active
        if (
            class_exists('WP_Smush') ||
            class_exists('ShortPixel') ||
            class_exists('Imagify')
        ) {
            return;
        }

        $options = \WP_Optimize()->get_options();

        $options->update_option('lossy_compression', true);
        $options->update_option('image_quality', 85);
        $options->update_option('autosmush', true);

        // EXIF is dropped deliberately (2026-09-05): none of this theme's
        // frontend code reads image EXIF, so keeping it only costs bytes.
        // Originals stay recoverable via back_up_original, so this is not
        // a one-way loss if that decision changes later.
        $options->update_option('preserve_exif', false);
        $options->update_option('back_up_original', true);
    }

    /**
     * Merge values into an array-shaped WP-Optimize option
     *
     * Mirrors AbstractPluginConfigurator::mergeOption(), but reads/writes
     * through WP_Optimize_Options so the wp-optimize-<key> prefixing and
     * multisite handling stay consistent with the plugin's own accessor.
     *
     * @param array<string, mixed> $values
     */
    private static function mergeWpOptimizeOption(\WP_Optimize_Options $options, string $key, array $values): void
    {
        $current = $options->get_option($key, []);
        if (!is_array($current)) {
            $current = [];
        }

        $options->update_option($key, array_merge($current, $values));
    }

    public static function getConfigurationSummary(): string
    {
        return __('WP-Optimize: Page-Caching (12h), woechentliche DB-Bereinigung, Bildkompression (85%)', 'wp-starter');
    }
}
