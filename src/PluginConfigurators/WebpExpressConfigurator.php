<?php

declare(strict_types=1);

namespace WordpressStarter\PluginConfigurators;

/**
 * Configures WebP Express plugin
 *
 * WebP Express stores its configuration exclusively in a JSON file, not in
 * wp_options, so this configurator currently applies no settings. It stays
 * registered as a placeholder in case a supported write path is added later.
 *
 * @see https://wordpress.org/plugins/webp-express/
 */
class WebpExpressConfigurator extends AbstractPluginConfigurator
{
    public static function getPluginSlug(): string
    {
        return 'webp-express';
    }

    public static function isPluginActive(): bool
    {
        return class_exists('\\WebPExpress\\Config') || defined('WEBPEXPRESS_PLUGIN');
    }

    protected static function doConfigure(): void
    {
        // WebP Express reads its configuration exclusively from a JSON file
        // (see \WebPExpress\Config::loadConfig() / Paths::getConfigFileName()),
        // never from wp_options. There is no safe public API to write that
        // file from here (it requires running the plugin's own migration and
        // fix() pipeline), so this configurator intentionally does not write
        // any settings. Mark as configured anyway so this runs, and the
        // admin notice appears, only once instead of on every admin page load.
        static::markConfigured();
    }

    public static function getConfigurationSummary(): string
    {
        return __('WebP Express: behält seine eigenen Standardeinstellungen, bitte im Plugin selbst konfigurieren', 'wp-starter');
    }
}
