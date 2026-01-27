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

    public static function configure(): void
    {
        if (!self::isPluginActive() || self::isConfigured()) {
            return;
        }

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

        // WP-Optimize stores cache settings in wpo_cache_config option
        $existingConfig = get_option('wpo_cache_config', []);
        $mergedConfig = array_merge($existingConfig, $cacheConfig);
        update_option('wpo_cache_config', $mergedConfig);
    }

    /**
     * Configure database cleanup schedule
     */
    private static function configureDatabaseCleanup(): void
    {
        // Enable scheduled optimization
        $scheduleSettings = [
            'schedule' => true,
            'schedule_type' => 'wpo_weekly',
        ];

        // What to clean
        $optimizationSettings = [
            'revisions' => true,
            'auto-drafts' => true,
            'trashed-posts' => true,
            'spam-comments' => true,
            'trashed-comments' => true,
            'expired-transients' => true,
            'orphaned-postmeta' => true,
            // Keep some data for safety
            'unapproved-comments' => false,
            'all-transients' => false,
        ];

        // Retention settings
        $retentionSettings = [
            'retention-enabled' => true,
            'retention-period' => 4, // 4 weeks
        ];

        // WP-Optimize uses individual options
        foreach ($scheduleSettings as $key => $value) {
            update_option('wpo_' . $key, $value);
        }

        // Store optimization settings
        update_option('wpo_optimization_options', $optimizationSettings);
        update_option('wpo_retention_settings', $retentionSettings);
    }

    /**
     * Configure image compression (if no other optimizer is active)
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

        $imageSettings = [
            'compression_level' => 'lossy',
            'compression_quality' => 85,
            'autocompress' => true,
            'preserve_exif' => false,
            'backup_original' => true,
        ];

        update_option('wpo_images_settings', $imageSettings);
    }

    public static function getConfigurationSummary(): string
    {
        return __('WP-Optimize: Page-Caching (12h), woechentliche DB-Bereinigung, Bildkompression (85%)', 'wp-starter');
    }
}
