<?php
declare(strict_types=1);

namespace WordpressStarter;

/**
 * Theme Context
 *
 * Provides theme-specific option keys and site isolation for WordPress Multisite.
 * All option keys are prefixed with the theme slug to prevent collisions when
 * switching themes on a site or when multiple themes run on the same network.
 */
final class ThemeContext
{
    private function __construct() {}

    private static ?string $slug = null;

    /**
     * Own theme slug — set once on first reset() or first slug() call.
     * Never cleared by reset() so isActiveOnCurrentSite() always compares
     * against the theme that was active when this context was initialised.
     */
    private static ?string $ownSlug = null;

    /**
     * Theme slug from get_template() — always the parent theme slug,
     * even when a WP child theme is active.
     */
    public static function slug(): string
    {
        if (self::$slug === null) {
            self::$slug = get_template();
            if (self::$ownSlug === null) {
                self::$ownSlug = self::$slug;
            }
        }
        return self::$slug;
    }

    /**
     * Prefix for option keys: dashes replaced with underscores.
     * 'wordpress-starter-theme' → 'wordpress_starter_theme'
     */
    public static function prefix(): string
    {
        return str_replace('-', '_', self::slug());
    }

    /**
     * Kebab-case prefix — same as slug(), kept as named alias for clarity.
     * 'wordpress-starter-theme' → 'wordpress-starter-theme'
     */
    public static function kebabPrefix(): string
    {
        return self::slug();
    }

    /**
     * CamelCase JS variable name derived from the theme slug.
     * 'wordpress-starter-theme' → 'wordpressStarterTheme'
     */
    public static function jsPrefix(): string
    {
        $parts = explode('-', self::slug());
        $camel = array_shift($parts);
        foreach ($parts as $part) {
            $camel .= ucfirst($part);
        }
        return $camel;
    }

    /**
     * Human-readable title-case prefix for error_log messages.
     * 'wordpress-starter-theme' → 'WordPress Starter Theme'
     */
    public static function logPrefix(): string
    {
        return implode(' ', array_map('ucfirst', explode('-', self::slug())));
    }

    /**
     * Theme-specific option key.
     * ThemeContext::optionKey('content_setup_complete')
     * → 'wordpress_starter_theme_content_setup_complete'
     */
    public static function optionKey(string $key): string
    {
        return self::prefix() . '_' . $key;
    }

    /**
     * Returns true if this theme (or a child of it) is active on the current site.
     * Guards content-generating admin_init handlers against cross-site execution
     * by super-admins in WP Multisite.
     * Compares the current get_template() against the slug that was active when
     * this context was initialised — not the potentially re-read dynamic slug.
     */
    public static function isActiveOnCurrentSite(): bool
    {
        $own = self::$ownSlug ?? self::slug();
        return get_template() === $own;
    }

    /**
     * One-time migration of legacy wp_starter_* option keys to theme-specific keys.
     * Runs on first boot() after a theme update. Old keys are preserved for rollback safety.
     */
    public static function migrate(): void
    {
        $migrationKey = self::optionKey('migration_done');

        if (get_option($migrationKey)) {
            return;
        }

        $migrations = [
            'wp_starter_content_setup_complete'  => self::optionKey('content_setup_complete'),
            'wp_starter_setup_complete'          => self::optionKey('setup_complete'),
            'wp_starter_theme_activated'         => self::optionKey('theme_activated'),
            'wp_starter_welcome_dismissed'       => self::optionKey('welcome_dismissed'),
            'wp_starter_styleguide_page_id'      => self::optionKey('styleguide_page_id'),
            'wp_starter_styleguide_images'       => self::optionKey('styleguide_images'),
            'wp_starter_acf_prefill_pending'     => self::optionKey('acf_prefill_pending'),
            'wp_starter_dismissed_plugin_notice' => self::optionKey('dismissed_plugin_notice'),
        ];

        foreach ($migrations as $oldKey => $newKey) {
            $oldValue = get_option($oldKey);
            if ($oldValue !== false && get_option($newKey) === false) {
                update_option($newKey, $oldValue);
            }
        }

        self::migratePluginConfiguratorKeys();

        update_option($migrationKey, true);
    }

    /**
     * Migrates wp_starter_configured_{slug} keys for all known plugin slugs.
     */
    private static function migratePluginConfiguratorKeys(): void
    {
        $pluginSlugs = [
            'wp-optimize',
            'wordpress-seo',
            'admin-site-enhancements',
            'ithemes-security',
            'webp-express',
            'contact-form-7',
        ];

        foreach ($pluginSlugs as $slug) {
            $oldKey = 'wp_starter_configured_' . $slug;
            $newKey = self::optionKey('configured_' . $slug);
            $oldValue = get_option($oldKey);
            if ($oldValue !== false && get_option($newKey) === false) {
                update_option($newKey, $oldValue);
            }
        }
    }

    /**
     * Resets the dynamic slug cache — for tests only.
     * Also seeds $ownSlug from the currently active template when it is still unset,
     * so the first reset() in a test setUp() pins the "own" slug for the entire test run.
     *
     * @internal
     */
    public static function reset(): void
    {
        if (self::$ownSlug === null) {
            self::$ownSlug = get_template();
        }
        self::$slug = null;
    }
}
