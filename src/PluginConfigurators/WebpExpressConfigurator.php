<?php

declare(strict_types=1);

namespace WordpressStarter\PluginConfigurators;

/**
 * Configures WebP Express plugin
 *
 * Settings applied:
 * - Auto-conversion: Enabled
 * - Quality: 80%
 * - WebP serving via redirect
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

    public static function configure(): void
    {
        if (!self::isPluginActive() || self::isConfigured()) {
            return;
        }

        // WebP Express stores config in a JSON file and wp_options
        $config = [
            // Enable conversion
            'operation-mode' => 'varied-image-responses',

            // Quality settings
            'quality' => 80,
            'max-quality' => 85,
            'quality-specific' => [
                'jpeg' => 80,
                'png' => 85,
            ],

            // Conversion settings
            'converters' => [
                // Try multiple converters in order of preference
                ['converter' => 'cwebp', 'options' => []],
                ['converter' => 'gd', 'options' => []],
                ['converter' => 'imagick', 'options' => []],
            ],

            // Metadata handling
            'metadata' => 'none', // Strip metadata for smaller files

            // Enable features
            'enable-redirection-to-webp-realizer' => true,
            'enable-redirection-to-converter' => true,

            // Don't use cloud service
            'web-service' => false,

            // Destination folder
            'destination-folder' => 'separate',
            'destination-extension' => 'append',

            // Cache settings
            'cache-control' => 'set',
            'cache-control-max-age' => 31536000, // 1 year
        ];

        // Store in wp_options (WebP Express reads from here on some setups)
        update_option('webp-express-config', $config);

        // Note: WebP Express primarily uses JSON config files
        // The wp_options storage serves as initial configuration
        // that WebP Express will pick up on first admin visit

        self::markConfigured();
    }

    public static function getConfigurationSummary(): string
    {
        return __('WebP Express: Auto-Konvertierung aktiv, Qualitaet 80%', 'wp-starter');
    }
}
