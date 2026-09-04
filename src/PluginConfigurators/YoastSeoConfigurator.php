<?php

declare(strict_types=1);

namespace WordpressStarter\PluginConfigurators;

/**
 * Configures Yoast SEO plugin with optimal defaults
 *
 * Settings applied:
 * - Breadcrumbs: Enabled with German labels
 * - XML Sitemaps: Enabled
 * - Schema: Organization type (uses theme company name)
 * - Disabled: Admin bar menu, IndexNow, AI features, usage tracking
 *
 * @see https://wordpress.org/plugins/wordpress-seo/
 */
class YoastSeoConfigurator extends AbstractPluginConfigurator
{
    public static function getPluginSlug(): string
    {
        return 'wordpress-seo';
    }

    public static function isPluginActive(): bool
    {
        return defined('WPSEO_VERSION');
    }

    protected static function doConfigure(): void
    {
        self::configureGeneralSettings();
        self::configureTitles();

        self::markConfigured();
    }

    /**
     * Configure general SEO settings and disable unused/privacy-invasive
     * features in a single read-modify-write pass on the 'wpseo' option.
     */
    private static function configureGeneralSettings(): void
    {
        $options = [
            // Enable XML sitemaps
            'enable_xml_sitemap' => true,

            // Keep analysis features active
            'keyword_analysis_active' => true,
            'content_analysis_active' => true,

            // Enable link suggestions
            'enable_link_suggestions' => true,

            // Disable admin bar menu (cleaner admin UI)
            'enable_admin_bar_menu' => false,

            // Disable enhanced slack sharing
            'enable_enhanced_slack_sharing' => false,
        ];

        self::disableUnusedFeatures($options);

        self::mergeOption('wpseo', $options);
    }

    /**
     * Configure breadcrumbs (German labels) and schema in a single read-modify-write
     */
    private static function configureTitles(): void
    {
        $options = [
            // --- Breadcrumbs ---
            'breadcrumbs-enable' => true,
            'breadcrumbs-sep' => ' » ',
            'breadcrumbs-home' => __('Startseite', 'wp-starter'),
            'breadcrumbs-prefix' => '',
            'breadcrumbs-archiveprefix' => __('Archiv:', 'wp-starter'),
            'breadcrumbs-searchprefix' => __('Suche:', 'wp-starter'),
            'breadcrumbs-404crumb' => __('Seite nicht gefunden', 'wp-starter'),
            'breadcrumbs-display-blog-page' => true,

            // --- Schema ---
            'company_or_person' => 'company',
        ];

        if (function_exists('get_field')) {
            $companyName = get_field('company_name', 'option');
            if (is_string($companyName) && $companyName !== '') {
                $options['company_name'] = sanitize_text_field($companyName);
            }

            $logo = get_field('site_logo', 'option');
            if (is_array($logo) && isset($logo['url']) && is_string($logo['url'])) {
                $options['company_logo'] = esc_url_raw($logo['url']);
            }
        }

        self::mergeOption('wpseo_titles', $options);
    }

    /**
     * Disable features that are not needed or privacy-invasive.
     *
     * Mutates the 'wpseo' options array passed in by configureGeneralSettings()
     * instead of doing its own get_option()/update_option() round trip.
     *
     * @param array<string, mixed> $options
     */
    private static function disableUnusedFeatures(array &$options): void
    {
        // Disable IndexNow (privacy)
        $options['enable_index_now'] = false;

        // Disable AI features
        $options['enable_ai_generator'] = false;

        // Disable usage tracking
        $options['tracking'] = false;

        // Disable Wincher integration
        $options['wincher_integration_active'] = false;

        // Disable Semrush integration
        $options['semrush_integration_active'] = false;
    }

    public static function getConfigurationSummary(): string
    {
        return __('Yoast SEO: Breadcrumbs aktiv, XML-Sitemap aktiv, Schema auf Organisation', 'wp-starter');
    }
}
