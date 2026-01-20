<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

/**
 * Plugin Service Provider
 *
 * Manages plugin recommendations and requirements for the theme.
 * Shows admin notices for missing plugins and provides easy installation links.
 */
class PluginServiceProvider extends ServiceProvider
{
    /**
     * Plugin definitions
     *
     * @var array<string, array{
     *   name: string,
     *   slug: string,
     *   required: bool,
     *   description: string,
     *   check: callable,
     *   external?: string
     * }>
     */
    private array $plugins = [];

    public function register(): void
    {
        $this->definePlugins();
    }

    public function boot(): void
    {
        add_action('admin_notices', [$this, 'displayPluginNotices']);
        add_action('admin_init', [$this, 'handleDismissal']);
    }

    /**
     * Define required and recommended plugins
     */
    private function definePlugins(): void
    {
        $this->plugins = [
            // === REQUIRED PLUGINS ===
            'advanced-custom-fields-pro' => [
                'name' => 'Advanced Custom Fields PRO',
                'slug' => 'advanced-custom-fields-pro',
                'required' => true,
                'description' => 'Erforderlich für alle ACF-Blöcke und Theme Options.',
                'check' => fn() => class_exists('ACF') && function_exists('acf_add_local_field_group'),
                'external' => 'https://www.advancedcustomfields.com/pro/',
            ],
            // === RECOMMENDED: SEO & Content ===
            'wordpress-seo' => [
                'name' => 'Yoast SEO',
                'slug' => 'wordpress-seo',
                'required' => false,
                'description' => 'SEO-Optimierung für alle Inhalte.',
                'check' => fn() => defined('WPSEO_VERSION'),
            ],
            'acf-content-analysis-for-yoast-seo' => [
                'name' => 'ACF Content Analysis for Yoast SEO',
                'slug' => 'acf-content-analysis-for-yoast-seo',
                'required' => false,
                'description' => 'Integriert ACF-Felder in die Yoast SEO-Analyse.',
                'check' => fn() => defined('AC_YOAST_ACF_ANALYSIS_FILE'),
            ],
            'acf-extended' => [
                'name' => 'ACF Extended',
                'slug' => 'acf-extended',
                'required' => false,
                'description' => 'Erweitert ACF um zusätzliche Feldtypen und Funktionen.',
                'check' => fn() => class_exists('ACFE'),
            ],

            // === RECOMMENDED: Forms & Communication ===
            'contact-form-7' => [
                'name' => 'Contact Form 7',
                'slug' => 'contact-form-7',
                'required' => false,
                'description' => 'Empfohlen für den Kontaktformular-Block.',
                'check' => fn() => class_exists('WPCF7'),
            ],
            'wp-mail-smtp' => [
                'name' => 'WP Mail SMTP',
                'slug' => 'wp-mail-smtp',
                'required' => false,
                'description' => 'Zuverlässiger E-Mail-Versand über SMTP.',
                'check' => fn() => defined('WPMS_PLUGIN_VER'),
            ],

            // === RECOMMENDED: Security & Backup ===
            'solid-security' => [
                'name' => 'Solid Security (ehem. iThemes)',
                'slug' => 'solid-security',
                'required' => false,
                'description' => 'Umfassender Sicherheitsschutz für WordPress.',
                'check' => fn() => class_exists('ITSEC_Core'),
                'external' => 'https://developer.liquidweb.com/solid-security/',
            ],
            'solid-backups' => [
                'name' => 'Solid Backups (ehem. BackupBuddy)',
                'slug' => 'solid-backups',
                'required' => false,
                'description' => 'Automatische Backups und Migration.',
                'check' => fn() => class_exists('pb_backupbuddy') || class_exists('SolidBackups'),
                'external' => 'https://developer.liquidweb.com/solid-backups/',
            ],

            // === RECOMMENDED: Performance & Analytics ===
            'wp-optimize' => [
                'name' => 'WP-Optimize',
                'slug' => 'wp-optimize',
                'required' => false,
                'description' => 'Datenbank-Optimierung und Caching.',
                'check' => fn() => class_exists('WP_Optimize'),
            ],
            'pirsch-analytics' => [
                'name' => 'Pirsch Analytics',
                'slug' => 'pirsch-analytics',
                'required' => false,
                'description' => 'Datenschutzfreundliche Website-Analyse.',
                'check' => fn() => self::isPluginActive('pirsch-analytics/pirsch-analytics.php'),
            ],

            // === RECOMMENDED: Admin ===
            'admin-site-enhancements' => [
                'name' => 'Admin and Site Enhancements',
                'slug' => 'admin-site-enhancements',
                'required' => false,
                'description' => 'Über 60 Admin-Verbesserungen inkl. SVG-Upload.',
                'check' => fn() => defined('ASENHA_VERSION'),
            ],
        ];
    }

    /**
     * Display admin notices for missing plugins
     */
    public function displayPluginNotices(): void
    {
        // Don't show on plugin install page
        global $pagenow;
        if ($pagenow === 'update.php' || $pagenow === 'plugins.php') {
            return;
        }

        $missingRequired = [];
        $missingRecommended = [];

        foreach ($this->plugins as $key => $plugin) {
            // Skip if already dismissed (for recommended plugins)
            if (!$plugin['required'] && $this->isDismissed($key)) {
                continue;
            }

            if (!( $plugin['check'] )()) {
                if ($plugin['required']) {
                    $missingRequired[$key] = $plugin;
                } else {
                    $missingRecommended[$key] = $plugin;
                }
            }
        }

        // Show required plugins notice
        if (!empty($missingRequired)) {
            $this->renderNotice($missingRequired, true);
        }

        // Show recommended plugins notice
        if (!empty($missingRecommended)) {
            $this->renderNotice($missingRecommended, false);
        }
    }

    /**
     * Render admin notice
     *
     * @param array<string, array{name: string, slug: string, required: bool, description: string, check: callable, external?: string}> $plugins
     */
    private function renderNotice(array $plugins, bool $isRequired): void
    {
        $type = $isRequired ? 'error' : 'warning';
        $title = $isRequired
            ? __('Erforderliche Plugins fehlen', 'wp-starter')
            : __('Empfohlene Plugins', 'wp-starter');
        $intro = $isRequired
            ? __('Das Theme benötigt folgende Plugins für volle Funktionalität:', 'wp-starter')
            : __('Folgende Plugins werden für optimale Funktionalität empfohlen:', 'wp-starter');

        $pluginListHtml = '';
        foreach ($plugins as $key => $plugin) {
            $isExternal = !empty($plugin['external']);
            $url = $isExternal ? $plugin['external'] : $this->getInstallUrl($plugin['slug']);
            $buttonText = $isExternal
                ? __('Website besuchen', 'wp-starter')
                : __('Installieren', 'wp-starter');
            $buttonClass = $isExternal ? 'button button-small' : 'button button-small button-primary';
            $target = $isExternal ? ' target="_blank" rel="noopener"' : '';
            $premiumBadge = $isExternal ? ' <span style="background:#d63638;color:#fff;padding:2px 6px;border-radius:3px;font-size:11px;">Premium</span>' : '';

            $pluginListHtml .= sprintf(
                '<li><strong>%s</strong>%s - %s <a href="%s" class="%s"%s>%s</a></li>',
                esc_html($plugin['name']),
                $premiumBadge,
                esc_html($plugin['description']),
                esc_url($url),
                esc_attr($buttonClass),
                $target,
                esc_html($buttonText)
            );
        }

        $dismissHtml = '';
        if (!$isRequired) {
            $dismissUrl = wp_nonce_url(
                add_query_arg('wp-starter-dismiss-plugins', '1'),
                'wp-starter-dismiss-plugins'
            );
            $dismissHtml = sprintf(
                '<p><a href="%s">%s</a></p>',
                esc_url($dismissUrl),
                esc_html__('Diese Empfehlung ausblenden', 'wp-starter')
            );
        }

        printf(
            '<div class="notice notice-%s"><p><strong>%s</strong></p><p>%s</p><ul style="list-style: disc; padding-left: 20px;">%s</ul>%s</div>',
            esc_attr($type),
            esc_html($title),
            esc_html($intro),
            wp_kses_post($pluginListHtml),
            wp_kses_post($dismissHtml)
        );
    }

    /**
     * Get plugin installation URL
     */
    private function getInstallUrl(string $slug): string
    {
        // For plugins in WordPress.org repository
        return admin_url('plugin-install.php?s=' . urlencode($slug) . '&tab=search&type=term');
    }

    /**
     * Handle notice dismissal
     */
    public function handleDismissal(): void
    {
        if (!isset($_GET['wp-starter-dismiss-plugins'])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (wp_verify_nonce($nonce, 'wp-starter-dismiss-plugins')) {
            update_option('wp_starter_dismissed_plugin_notice', true);
            wp_safe_redirect(remove_query_arg(['wp-starter-dismiss-plugins', '_wpnonce']));
            exit;
        }
    }

    /**
     * Check if notice is dismissed
     */
    private function isDismissed(string $pluginKey): bool
    {
        return (bool) get_option('wp_starter_dismissed_plugin_notice', false);
    }

    /**
     * Get missing required plugins (for use in other parts of the theme)
     *
     * @return array<string, array{name: string, slug: string, required: bool, description: string}>
     */
    public function getMissingRequiredPlugins(): array
    {
        $missing = [];
        foreach ($this->plugins as $key => $plugin) {
            if ($plugin['required'] && !( $plugin['check'] )()) {
                $missing[$key] = $plugin;
            }
        }
        return $missing;
    }

    /**
     * Check if all required plugins are active
     */
    public function allRequiredPluginsActive(): bool
    {
        return empty($this->getMissingRequiredPlugins());
    }

    /**
     * Check if a plugin is active by its path
     */
    private static function isPluginActive(string $plugin): bool
    {
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active($plugin);
    }
}
