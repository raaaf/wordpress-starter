<?php

declare(strict_types=1);

namespace WordpressStarter\PluginConfigurators;

/**
 * Configures Contact Form 7 plugin
 *
 * Settings applied:
 * - Auto-P in forms: Disabled (cleaner HTML)
 *
 * Note: CF7 has limited global settings. Most configuration is per-form.
 * This configurator primarily sets up sensible defaults.
 *
 * @see https://wordpress.org/plugins/contact-form-7/
 */
class ContactForm7Configurator extends AbstractPluginConfigurator
{
    public static function getPluginSlug(): string
    {
        return 'contact-form-7';
    }

    public static function isPluginActive(): bool
    {
        return defined('WPCF7_VERSION');
    }

    public static function configure(): void
    {
        if (!self::isPluginActive() || self::isConfigured()) {
            return;
        }

        // CF7 has very limited global options
        // Most settings are stored per-form as post meta

        // Set global settings
        $settings = [
            // Don't load CF7 assets everywhere (theme handles conditional loading)
            'load_js' => 0,
            'load_css' => 0,
        ];

        // CF7 stores settings via WPCF7 class
        if (class_exists('WPCF7')) {
            foreach ($settings as $key => $value) {
                \WPCF7::update_option($key, $value);
            }
        }

        self::markConfigured();
    }

    /**
     * Register filters that need to run on every page load
     *
     * Called from PluginConfiguratorServiceProvider::boot()
     * These filters run regardless of configuration state.
     */
    public static function registerFilters(): void
    {
        if (!self::isPluginActive()) {
            return;
        }

        // Disable auto-p in forms (produces cleaner HTML)
        add_filter('wpcf7_autop_or_not', '__return_false');

        // Use custom validation messages in German
        add_filter('wpcf7_default_validation_error_message', function (): string {
            return __('Bitte korrigieren Sie die markierten Felder.', 'wp-starter');
        });
    }

    public static function getConfigurationSummary(): string
    {
        return __('Contact Form 7: Auto-Formatierung deaktiviert, Assets-Laden optimiert', 'wp-starter');
    }
}
