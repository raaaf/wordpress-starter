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
        self::disableUnusedFeatures();

        self::markConfigured();
    }

    /**
     * Configure general SEO settings
     */
    private static function configureGeneralSettings(): void
    {
        $options = get_option('wpseo', []);

        // Enable XML sitemaps
        $options['enable_xml_sitemap'] = true;

        // Keep analysis features active
        $options['keyword_analysis_active'] = true;
        $options['content_analysis_active'] = true;

        // Enable link suggestions
        $options['enable_link_suggestions'] = true;

        // Disable admin bar menu (cleaner admin UI)
        $options['enable_admin_bar_menu'] = false;

        // Disable enhanced slack sharing
        $options['enable_enhanced_slack_sharing'] = false;

        update_option('wpseo', $options);
    }

    /**
     * Configure breadcrumbs (German labels) and schema in a single read-modify-write
     */
    private static function configureTitles(): void
    {
        $options = get_option('wpseo_titles', []);

        // --- Breadcrumbs ---
        $options['breadcrumbs-enable'] = true;
        $options['breadcrumbs-sep'] = ' > ';
        $options['breadcrumbs-home'] = __('Startseite', 'wp-starter');
        $options['breadcrumbs-prefix'] = '';
        $options['breadcrumbs-archiveprefix'] = __('Archiv:', 'wp-starter');
        $options['breadcrumbs-searchprefix'] = __('Suche:', 'wp-starter');
        $options['breadcrumbs-404crumb'] = __('Seite nicht gefunden', 'wp-starter');
        $options['breadcrumbs-display-blog-page'] = true;

        // --- Schema ---
        $options['company_or_person'] = 'company';

        if (function_exists('get_field')) {
            $companyName = get_field('company_name', 'option');
            if ($companyName) {
                $options['company_name'] = $companyName;
            }

            $logo = get_field('site_logo', 'option');
            if ($logo && isset($logo['url'])) {
                $options['company_logo'] = $logo['url'];
            }
        }

        update_option('wpseo_titles', $options);
    }

    /**
     * Disable features that are not needed or privacy-invasive
     */
    private static function disableUnusedFeatures(): void
    {
        $options = get_option('wpseo', []);

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

        update_option('wpseo', $options);
    }

    public static function getConfigurationSummary(): string
    {
        return __('Yoast SEO: Breadcrumbs aktiv, XML-Sitemap aktiv, Schema auf Organisation', 'wp-starter');
    }
}
