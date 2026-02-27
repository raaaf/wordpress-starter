<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\PluginConfigurators\AbstractPluginConfigurator;
use WordpressStarter\PluginConfigurators\AdminSiteEnhancementsConfigurator;
use WordpressStarter\PluginConfigurators\ContactForm7Configurator;
use WordpressStarter\PluginConfigurators\IThemesSecurityConfigurator;
use WordpressStarter\PluginConfigurators\WebpExpressConfigurator;
use WordpressStarter\PluginConfigurators\WpOptimizeConfigurator;
use WordpressStarter\PluginConfigurators\YoastSeoConfigurator;

/**
 * Service Provider for automatic plugin configuration
 *
 * Automatically configures plugins with optimal defaults:
 * - On plugin activation (via activated_plugin hook)
 * - On admin_init as fallback (idempotent check)
 *
 * Users can disable auto-configuration via filter:
 * add_filter('wp_starter_auto_configure_plugins', '__return_false');
 */
class PluginConfiguratorServiceProvider extends ServiceProvider
{
    /**
     * List of configurator classes
     *
     * @var array<class-string<AbstractPluginConfigurator>>
     */
    private array $configurators = [
        WpOptimizeConfigurator::class,
        YoastSeoConfigurator::class,
        AdminSiteEnhancementsConfigurator::class,
        IThemesSecurityConfigurator::class,
        WebpExpressConfigurator::class,
        ContactForm7Configurator::class,
    ];

    public function register(): void
    {
        // Allow themes/plugins to add custom configurators
        $this->configurators = apply_filters('wp_starter_plugin_configurators', $this->configurators);
    }

    public function boot(): void
    {
        // Check if auto-configuration is enabled
        if (!apply_filters('wp_starter_auto_configure_plugins', true)) {
            return;
        }

        // Hook into plugin activation
        add_action('activated_plugin', [$this, 'onPluginActivated'], 10, 2);

        // Fallback: Check on admin_init for unconfigured plugins
        add_action('admin_init', [$this, 'configureUnconfiguredPlugins'], 20);

        // Display admin notice after configuration
        add_action('admin_notices', [$this, 'displayConfigurationNotice']);

        // Register permanent filters for plugins that need them
        $this->registerPluginFilters();
    }

    /**
     * Called when any plugin is activated
     *
     * @param string $plugin Plugin path (e.g., "wp-optimize/wp-optimize.php")
     * @param bool   $networkWide Whether activated network-wide
     */
    public function onPluginActivated(string $plugin, bool $networkWide): void
    {
        // Extract plugin slug from path
        $slug = dirname($plugin);
        if ($slug === '.') {
            $slug = basename($plugin, '.php');
        }

        foreach ($this->configurators as $configuratorClass) {
            $configuratorSlug = $configuratorClass::getPluginSlug();

            // Match by slug or partial match (e.g., "wordpress-seo" matches "wordpress-seo-premium")
            if ($configuratorSlug === $slug || strpos($slug, $configuratorSlug) === 0) {
                $this->configurePlugin($configuratorClass);
                break;
            }
        }
    }

    /**
     * Check and configure any unconfigured plugins
     *
     * Runs on admin_init as fallback for plugins that were
     * activated before this theme or via other means.
     */
    public function configureUnconfiguredPlugins(): void
    {
        foreach ($this->configurators as $configuratorClass) {
            if (!$configuratorClass::isConfigured()) {
                $this->configurePlugin($configuratorClass);
            }
        }
    }

    /**
     * Configure a single plugin and store notice transient
     *
     * @param class-string<AbstractPluginConfigurator> $configuratorClass
     */
    private function configurePlugin(string $configuratorClass): void
    {
        // Allow disabling individual plugin configuration
        $slug = $configuratorClass::getPluginSlug();
        if (!apply_filters("wp_starter_configure_{$slug}", true)) {
            return;
        }

        $configuratorClass::configure();

        // Store summary for admin notice
        set_transient(
            'wp_starter_plugin_configured_' . $slug,
            $configuratorClass::getConfigurationSummary(),
            300 // 5 minutes
        );
    }

    /**
     * Register filters that need to run on every request
     *
     * Some plugins need filters registered on every page load,
     * not just during configuration.
     */
    private function registerPluginFilters(): void
    {
        ContactForm7Configurator::registerFilters();
    }

    /**
     * Display admin notice about configured plugins
     */
    public function displayConfigurationNotice(): void
    {
        $messages = [];

        foreach ($this->configurators as $configuratorClass) {
            $slug = $configuratorClass::getPluginSlug();
            $message = get_transient('wp_starter_plugin_configured_' . $slug);

            if ($message) {
                $messages[] = $message;
                delete_transient('wp_starter_plugin_configured_' . $slug);
            }
        }

        if (empty($messages)) {
            return;
        }

        printf(
            '<div class="notice notice-success is-dismissible">
                <p><strong>%s</strong></p>
                <ul style="margin: 0.5em 0 0.5em 1.5em; list-style: disc;">%s</ul>
                <p><em>%s</em></p>
            </div>',
            esc_html__('WP-Starter: Plugins wurden automatisch konfiguriert', 'wp-starter'),
            '<li>' . implode('</li><li>', array_map('esc_html', $messages)) . '</li>',
            esc_html__('Sie koennen die Einstellungen jederzeit in den jeweiligen Plugin-Optionen aendern.', 'wp-starter')
        );
    }

    /**
     * Get all registered configurators
     *
     * @return array<class-string<AbstractPluginConfigurator>>
     */
    public function getConfigurators(): array
    {
        return $this->configurators;
    }
}
