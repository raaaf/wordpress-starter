<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\PluginInstaller;

/**
 * Plugin Service Provider
 *
 * Manages plugin recommendations and requirements for the theme.
 * Provides a setup page for bulk plugin installation.
 */
class PluginServiceProvider extends ServiceProvider
{
    private const SETUP_PAGE_SLUG = 'wp-starter-setup';
    private const NONCE_ACTION = 'wp_starter_install_plugins';

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

    /**
     * Selected plugins from setup script
     *
     * @var array<string>
     */
    private array $selectedPlugins = [];

    /**
     * Content setup options from setup script
     *
     * @var array<string, mixed>
     */
    private array $setupOptions = [];

    public function boot(): void
    {
        $this->loadSetupConfig();

        add_action('admin_menu', [$this, 'addSetupPage']);
        add_action('admin_notices', [$this, 'displayPluginNotices']);
        add_action('admin_init', [$this, 'handleDismissal']);
        add_action('after_switch_theme', [$this, 'onThemeActivation']);
        add_action('admin_init', [$this, 'maybeRedirectToSetup']);
        add_action('admin_init', [$this, 'runContentSetup']);

        // AJAX handlers
        add_action('wp_ajax_wp_starter_install_plugin', [$this, 'ajaxInstallPlugin']);
        add_action('wp_ajax_wp_starter_install_all_plugins', [$this, 'ajaxInstallAllPlugins']);
    }

    /**
     * Load configuration from setup script
     */
    private function loadSetupConfig(): void
    {
        $pluginsConfigPath = get_template_directory() . '/config/plugins-to-install.php';
        $setupOptionsPath = get_template_directory() . '/config/setup-options.php';

        if (file_exists($pluginsConfigPath)) {
            $this->selectedPlugins = include $pluginsConfigPath;
        }

        if (file_exists($setupOptionsPath)) {
            $this->setupOptions = include $setupOptionsPath;
        }
    }

    /**
     * Run content setup on first theme activation
     */
    public function runContentSetup(): void
    {
        // Only run once
        if (get_option('wp_starter_content_setup_complete')) {
            return;
        }

        // Only run if we have setup options
        if (empty($this->setupOptions)) {
            return;
        }

        // Delete default WordPress content
        if (!empty($this->setupOptions['delete_default_content'])) {
            $this->deleteDefaultContent();
        }

        // Set permalink structure
        if (!empty($this->setupOptions['set_permalink_structure'])) {
            $this->setPermalinkStructure();
        }

        // Create default pages
        if (!empty($this->setupOptions['create_pages']) && !empty($this->setupOptions['pages'])) {
            $this->createDefaultPages($this->setupOptions['pages']);
        }

        // Note: color_scheme is handled by WelcomeServiceProvider via ACF's update_field()
        // to ensure proper field validation and formatting

        // Mark as complete
        update_option('wp_starter_content_setup_complete', true);
    }

    /**
     * Delete default WordPress content
     */
    private function deleteDefaultContent(): void
    {
        // Delete "Hello World" post (ID 1 is always the default post)
        // Works for all languages (en: hello-world, de: hallo-welt)
        $default_post = get_post(1);
        if ($default_post && $default_post->post_type === 'post') {
            wp_delete_post(1, true);
        }

        // Delete sample page (ID 2 is always the default page)
        // Works for all languages (en: sample-page, de: beispiel-seite)
        $sample_page = get_post(2);
        if ($sample_page && $sample_page->post_type === 'page') {
            wp_delete_post(2, true);
        }

        // Delete default comment
        $comment = get_comment(1);
        if ($comment) {
            wp_delete_comment(1, true);
        }
    }

    /**
     * Set permalink structure to /%postname%/
     */
    private function setPermalinkStructure(): void
    {
        global $wp_rewrite;

        $wp_rewrite->set_permalink_structure('/%postname%/');
        $wp_rewrite->flush_rules();
    }

    /**
     * Create default pages
     *
     * @param array<string, array{title: string, template: string, status?: string}> $pages
     */
    private function createDefaultPages(array $pages): void
    {
        $homePageId = null;

        foreach ($pages as $slug => $pageData) {
            // Check if page already exists
            $existing = get_page_by_path($slug);
            if ($existing) {
                // If styleguide exists, store its ID
                if ($slug === 'styleguide') {
                    update_option('wp_starter_styleguide_page_id', $existing->ID);
                }
                continue;
            }

            // Determine post status (default: publish, styleguide: private)
            $postStatus = $pageData['status'] ?? 'publish';

            $pageId = wp_insert_post([
                'post_title' => $pageData['title'],
                'post_name' => $slug,
                'post_status' => $postStatus,
                'post_type' => 'page',
                'post_content' => '',
            ]);

            if ($pageId && !is_wp_error($pageId)) {
                // Set page template if specified
                if (!empty($pageData['template'])) {
                    update_post_meta($pageId, '_wp_page_template', $pageData['template'] . '.blade.php');
                }

                // Track home page
                if ($slug === 'home') {
                    $homePageId = $pageId;
                }

                // Track styleguide page
                if ($slug === 'styleguide') {
                    update_option('wp_starter_styleguide_page_id', $pageId);
                    // Dismiss the welcome notice since styleguide is created
                    update_option('wp_starter_welcome_dismissed', true);
                }

                // Set legal pages in theme options (if ACF is active)
                if (function_exists('update_field')) {
                    if ($slug === 'privacy') {
                        update_field('datenschutz_seite', $pageId, 'option');
                        update_option('wp_page_for_privacy_policy', $pageId);
                    } elseif ($slug === 'imprint') {
                        update_field('impressum_seite', $pageId, 'option');
                    }
                }
            }
        }

        // Set homepage as front page
        if ($homePageId) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $homePageId);
        }
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

            // === RECOMMENDED: Security & Backup (Premium) ===
            'solid-security' => [
                'name' => 'Solid Security',
                'slug' => 'solid-security',
                'required' => false,
                'description' => 'Umfassende WordPress-Sicherheit (ehem. iThemes Security).',
                'check' => fn() => defined('ITSEC_CORE_VER') || class_exists('ITSEC_Core'),
                'external' => 'https://developer.wordpress.org/plugins/solid-security/',
            ],
            'solid-backups' => [
                'name' => 'Solid Backups',
                'slug' => 'solid-backups',
                'required' => false,
                'description' => 'Automatische Backups und Migration (ehem. BackupBuddy).',
                'check' => fn() => class_exists('pb_backupbuddy') || defined('JETRAIL_BUDDY_VER'),
                'external' => 'https://developer.wordpress.org/plugins/backup-backup/',
            ],
        ];
    }

    /**
     * Get plugins organized by category
     *
     * @return array<string, array<string, array{name: string, slug: string, required: bool, description: string, check: callable, external?: string}>>
     */
    private function getPluginsByCategory(): array
    {
        return [
            'Erforderlich' => array_filter($this->plugins, fn($p) => $p['required']),
            'SEO & Content' => [
                'wordpress-seo' => $this->plugins['wordpress-seo'],
                'acf-content-analysis-for-yoast-seo' => $this->plugins['acf-content-analysis-for-yoast-seo'],
                'acf-extended' => $this->plugins['acf-extended'],
            ],
            'Formulare & Kommunikation' => [
                'contact-form-7' => $this->plugins['contact-form-7'],
                'wp-mail-smtp' => $this->plugins['wp-mail-smtp'],
            ],
            'Performance & Analytics' => [
                'wp-optimize' => $this->plugins['wp-optimize'],
                'pirsch-analytics' => $this->plugins['pirsch-analytics'],
            ],
            'Sicherheit & Backup' => [
                'solid-security' => $this->plugins['solid-security'],
                'solid-backups' => $this->plugins['solid-backups'],
            ],
            'Admin' => [
                'admin-site-enhancements' => $this->plugins['admin-site-enhancements'],
            ],
        ];
    }

    /**
     * Add setup page to admin menu
     */
    public function addSetupPage(): void
    {
        add_theme_page(
            __('Theme Setup', 'wp-starter'),
            __('Theme Setup', 'wp-starter'),
            'install_plugins',
            self::SETUP_PAGE_SLUG,
            [$this, 'renderSetupPage']
        );
    }

    /**
     * Handle theme activation
     */
    public function onThemeActivation(): void
    {
        // Set transient to redirect to setup page
        set_transient('wp_starter_activation_redirect', true, 60);
    }

    /**
     * Redirect to setup page after theme activation
     */
    public function maybeRedirectToSetup(): void
    {
        if (!get_transient('wp_starter_activation_redirect')) {
            return;
        }

        delete_transient('wp_starter_activation_redirect');

        // Don't redirect on bulk activation or AJAX
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Core WordPress bulk activation parameter
        if (wp_doing_ajax() || isset($_GET['activate-multi'])) {
            return;
        }

        wp_safe_redirect(admin_url('themes.php?page=' . self::SETUP_PAGE_SLUG));
        exit;
    }

    /**
     * Render the setup page
     */
    public function renderSetupPage(): void
    {
        // Dismiss the styleguide welcome notice - user is already doing setup
        update_option('wp_starter_welcome_dismissed', true);

        $categories = $this->getPluginsByCategory();
        $selectedPlugins = $this->getSelectedPlugins();
        $missingSelectedPlugins = array_filter($selectedPlugins, fn($p) => !( $p['check'] )());
        $hasConfig = $this->hasSetupConfig();

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP-Starter Theme Setup', 'wp-starter'); ?></h1>

            <div class="wp-starter-setup-header" style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h2 style="margin-top: 0;"><?php esc_html_e('Willkommen beim WP-Starter Theme!', 'wp-starter'); ?></h2>
                <?php if ($hasConfig) : ?>
                    <p><?php esc_html_e('Ihre vorkonfigurierten Plugins werden jetzt installiert.', 'wp-starter'); ?></p>
                <?php else : ?>
                    <p><?php esc_html_e('Installieren Sie die empfohlenen Plugins für die beste Erfahrung mit diesem Theme.', 'wp-starter'); ?></p>
                <?php endif; ?>

                <?php if (!empty($missingSelectedPlugins)) : ?>
                    <p>
                        <button type="button" id="wp-starter-install-all" class="button button-primary button-hero">
                            <?php
                            printf(
                                $hasConfig
                                    // translators: %d is the number of preconfigured plugins to install
                                    ? esc_html__('Vorkonfigurierte Plugins installieren (%d)', 'wp-starter')
                                    // translators: %d is the number of free plugins to install
                                    : esc_html__('Alle kostenlosen Plugins installieren (%d)', 'wp-starter'),
                                count($missingSelectedPlugins)
                            );
                            ?>
                        </button>
                    </p>
                <?php else : ?>
                    <p style="color: #00a32a; font-weight: 600;">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Alle ausgewählten Plugins sind installiert!', 'wp-starter'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <div id="wp-starter-install-progress" style="display: none; background: #fff; padding: 20px; margin: 20px 0; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-left: 4px solid #2271b1;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0;"><?php esc_html_e('Installation läuft...', 'wp-starter'); ?></h3>
                    <div style="text-align: right;">
                        <span class="wp-starter-progress-counter" style="font-size: 14px; color: #50575e;"></span>
                        <span class="wp-starter-elapsed-time" style="font-size: 12px; color: #787c82; display: block;"></span>
                    </div>
                </div>
                <div class="wp-starter-progress-bar" style="background: #ddd; height: 24px; border-radius: 4px; overflow: hidden; position: relative;">
                    <div class="wp-starter-progress-fill" style="background: linear-gradient(90deg, #2271b1 0%, #135e96 100%); height: 100%; width: 0%; transition: width 0.3s;"></div>
                    <span class="wp-starter-progress-percent" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px; font-weight: 600; color: #1d2327;"></span>
                </div>
                <div class="wp-starter-current-plugin" style="margin-top: 15px; padding: 12px; background: #f0f6fc; border-radius: 4px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="wp-starter-spinner" style="display: inline-block; width: 20px; height: 20px; border: 2px solid #2271b1; border-top-color: transparent; border-radius: 50%; animation: wp-starter-spin 1s linear infinite;"></span>
                        <div>
                            <strong class="wp-starter-plugin-name" style="display: block; color: #1d2327;"></strong>
                            <span class="wp-starter-plugin-step" style="font-size: 12px; color: #50575e;"></span>
                        </div>
                    </div>
                </div>
                <div class="wp-starter-install-log" style="max-height: 200px; overflow-y: auto; margin-top: 15px; padding: 12px; background: #f6f7f7; border-radius: 4px; font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace; font-size: 12px; line-height: 1.6;"></div>
            </div>
            <style>
                @keyframes wp-starter-spin {
                    to { transform: rotate(360deg); }
                }
            </style>

            <?php foreach ($categories as $categoryName => $categoryPlugins) : ?>
                <div class="wp-starter-plugin-category" style="background: #fff; padding: 20px; margin: 20px 0; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                    <h2><?php echo esc_html($categoryName); ?></h2>
                    <table class="wp-list-table widefat plugins">
                        <tbody>
                            <?php foreach ($categoryPlugins as $key => $plugin) : ?>
                                <?php
                                $isActive = ( $plugin['check'] )();
                                $isExternal = !empty($plugin['external']);
                                $isInstalled = !$isExternal && PluginInstaller::isInstalled($plugin['slug']);
                                ?>
                                <tr class="<?php echo $isActive ? 'active' : 'inactive'; ?>" data-slug="<?php echo esc_attr($plugin['slug']); ?>">
                                    <td class="plugin-title column-primary" style="padding: 15px;">
                                        <strong><?php echo esc_html($plugin['name']); ?></strong>
                                        <?php if ($isExternal) : ?>
                                            <span style="background: #d63638; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-left: 5px;">Premium</span>
                                        <?php endif; ?>
                                        <?php if ($plugin['required']) : ?>
                                            <span style="background: #dba617; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-left: 5px;">Erforderlich</span>
                                        <?php endif; ?>
                                        <p class="description" style="margin: 5px 0 0;"><?php echo esc_html($plugin['description']); ?></p>
                                    </td>
                                    <td class="column-status" style="padding: 15px; text-align: right; white-space: nowrap;">
                                        <?php if ($isActive) : ?>
                                            <span style="color: #00a32a;"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Aktiv', 'wp-starter'); ?></span>
                                        <?php elseif ($isExternal) : ?>
                                            <a href="<?php echo esc_url($plugin['external']); ?>" class="button" target="_blank" rel="noopener">
                                                <?php esc_html_e('Website besuchen', 'wp-starter'); ?>
                                                <span class="dashicons dashicons-external" style="line-height: 1.4;"></span>
                                            </a>
                                        <?php elseif ($isInstalled) : ?>
                                            <button type="button" class="button button-primary wp-starter-activate-plugin" data-slug="<?php echo esc_attr($plugin['slug']); ?>">
                                                <?php esc_html_e('Aktivieren', 'wp-starter'); ?>
                                            </button>
                                        <?php else : ?>
                                            <button type="button" class="button button-primary wp-starter-install-plugin" data-slug="<?php echo esc_attr($plugin['slug']); ?>">
                                                <?php esc_html_e('Installieren & Aktivieren', 'wp-starter'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>

            <div style="margin-top: 30px; text-align: center;">
                <a href="<?php echo esc_url(admin_url()); ?>" class="button button-secondary button-hero">
                    <?php esc_html_e('Zum Dashboard', 'wp-starter'); ?>
                </a>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var nonce = '<?php echo esc_attr( wp_create_nonce( self::NONCE_ACTION ) ); ?>';

            // Single plugin install
            $('.wp-starter-install-plugin, .wp-starter-activate-plugin').on('click', function() {
                var $button = $(this);
                var slug = $button.data('slug');
                var $row = $button.closest('tr');
                var originalText = $button.text();

                $button.prop('disabled', true).text('<?php esc_html_e('Wird installiert...', 'wp-starter'); ?>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    timeout: 120000, // 2 minutes timeout for plugin installation
                    data: {
                        action: 'wp_starter_install_plugin',
                        slug: slug,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $row.removeClass('inactive').addClass('active');
                            $button.replaceWith('<span style="color: #00a32a;"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Aktiv', 'wp-starter'); ?></span>');
                        } else {
                            $button.prop('disabled', false).text('<?php esc_html_e('Fehler - Erneut versuchen', 'wp-starter'); ?>');
                            var errorMsg = response.data && response.data.message ? response.data.message : '<?php esc_html_e('Installation fehlgeschlagen.', 'wp-starter'); ?>';
                            console.error('Plugin install error:', errorMsg);
                            alert(errorMsg);
                        }
                    },
                    error: function(xhr, status, error) {
                        $button.prop('disabled', false).text('<?php esc_html_e('Fehler - Erneut versuchen', 'wp-starter'); ?>');
                        console.error('Plugin install AJAX error:', status, error, xhr.responseText);
                        if (status === 'timeout') {
                            alert('<?php esc_html_e('Die Installation hat zu lange gedauert. Bitte versuchen Sie es erneut.', 'wp-starter'); ?>');
                        } else {
                            alert('<?php esc_html_e('Netzwerkfehler bei der Installation. Bitte versuchen Sie es erneut.', 'wp-starter'); ?>');
                        }
                    }
                });
            });

            // Install all plugins
            $('#wp-starter-install-all').on('click', function() {
                var $button = $(this);
                var $progress = $('#wp-starter-install-progress');
                var $progressBar = $progress.find('.wp-starter-progress-fill');
                var $progressPercent = $progress.find('.wp-starter-progress-percent');
                var $progressCounter = $progress.find('.wp-starter-progress-counter');
                var $elapsedTime = $progress.find('.wp-starter-elapsed-time');
                var $pluginName = $progress.find('.wp-starter-plugin-name');
                var $pluginStep = $progress.find('.wp-starter-plugin-step');
                var $currentPlugin = $progress.find('.wp-starter-current-plugin');
                var $log = $progress.find('.wp-starter-install-log');

                // Build plugin list with names
                var plugins = [];
                var pluginNames = {};
                $('.wp-starter-install-plugin').each(function() {
                    var slug = $(this).data('slug');
                    var name = $(this).closest('tr').find('strong').first().text();
                    plugins.push(slug);
                    pluginNames[slug] = name || slug;
                });

                if (plugins.length === 0) {
                    alert('<?php esc_html_e('Alle Plugins sind bereits installiert!', 'wp-starter'); ?>');
                    return;
                }

                $button.prop('disabled', true);
                $progress.show();
                $log.empty();

                var total = plugins.length;
                var completed = 0;
                var startTime = Date.now();
                var timerInterval;

                // Update elapsed time every second
                function updateElapsedTime() {
                    var elapsed = Math.floor((Date.now() - startTime) / 1000);
                    var minutes = Math.floor(elapsed / 60);
                    var seconds = elapsed % 60;
                    $elapsedTime.text('<?php esc_html_e('Verstrichene Zeit:', 'wp-starter'); ?> ' +
                        (minutes > 0 ? minutes + ' min ' : '') + seconds + ' s');
                }
                timerInterval = setInterval(updateElapsedTime, 1000);
                updateElapsedTime();

                function updateProgress(current, total, percent) {
                    $progressBar.css('width', percent + '%');
                    $progressPercent.text(percent + '%');
                    $progressCounter.text('Plugin ' + current + ' / ' + total);
                }

                function logMessage(type, slug, message, details) {
                    var name = pluginNames[slug] || slug;
                    var timestamp = new Date().toLocaleTimeString('de-DE');
                    var icon = type === 'success' ? '✓' : (type === 'error' ? '✗' : '○');
                    var color = type === 'success' ? '#00a32a' : (type === 'error' ? '#d63638' : '#50575e');
                    var html = '<div style="color: ' + color + '; padding: 4px 0; border-bottom: 1px solid #e0e0e0;">' +
                        '<span style="color: #787c82; margin-right: 8px;">[' + timestamp + ']</span>' +
                        '<strong>' + icon + ' ' + name + '</strong>';
                    if (message) {
                        html += ' <span style="color: #50575e;">— ' + message + '</span>';
                    }
                    if (details) {
                        html += '<div style="font-size: 11px; color: #787c82; margin-left: 20px;">' + details + '</div>';
                    }
                    html += '</div>';
                    $log.append(html);
                    $log.scrollTop($log[0].scrollHeight);
                }

                function installNext() {
                    if (plugins.length === 0) {
                        clearInterval(timerInterval);
                        $currentPlugin.html('<div style="display: flex; align-items: center; gap: 10px; color: #00a32a;">' +
                            '<span class="dashicons dashicons-yes-alt" style="font-size: 24px;"></span>' +
                            '<strong><?php esc_html_e('Alle Plugins wurden erfolgreich installiert!', 'wp-starter'); ?></strong></div>');
                        $progress.find('h3').text('<?php esc_html_e('Installation abgeschlossen', 'wp-starter'); ?>');
                        $progress.css('border-left-color', '#00a32a');
                        $button.hide();
                        logMessage('success', '', '<?php esc_html_e('Installation abgeschlossen', 'wp-starter'); ?>',
                            '<?php esc_html_e('Seite wird neu geladen...', 'wp-starter'); ?>');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                        return;
                    }

                    var slug = plugins.shift();
                    var currentNum = completed + 1;
                    var percent = Math.round((completed / total) * 100);

                    updateProgress(currentNum, total, percent);
                    $pluginName.text(pluginNames[slug]);
                    $pluginStep.text('<?php esc_html_e('Lade Plugin von WordPress.org herunter...', 'wp-starter'); ?>');
                    logMessage('info', slug, '<?php esc_html_e('Installation gestartet', 'wp-starter'); ?>');

                    // Simulate step updates (since we can't get real-time feedback from WP)
                    var stepTimeout = setTimeout(function() {
                        $pluginStep.text('<?php esc_html_e('Entpacke und installiere...', 'wp-starter'); ?>');
                    }, 2000);
                    var stepTimeout2 = setTimeout(function() {
                        $pluginStep.text('<?php esc_html_e('Aktiviere Plugin...', 'wp-starter'); ?>');
                    }, 4000);

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        timeout: 120000, // 2 minutes timeout for plugin installation
                        data: {
                            action: 'wp_starter_install_plugin',
                            slug: slug,
                            nonce: nonce
                        },
                        success: function(response) {
                            clearTimeout(stepTimeout);
                            clearTimeout(stepTimeout2);
                            completed++;
                            var percent = Math.round((completed / total) * 100);
                            updateProgress(completed, total, percent);

                            if (response.success) {
                                var details = response.data && response.data.installed ?
                                    '<?php esc_html_e('Neu installiert und aktiviert', 'wp-starter'); ?>' :
                                    '<?php esc_html_e('Aktiviert', 'wp-starter'); ?>';
                                logMessage('success', slug, '<?php esc_html_e('Erfolgreich', 'wp-starter'); ?>', details);
                            } else {
                                var errorMsg = response.data && response.data.message ? response.data.message : '<?php esc_html_e('Unbekannter Fehler', 'wp-starter'); ?>';
                                logMessage('error', slug, '<?php esc_html_e('Fehlgeschlagen', 'wp-starter'); ?>', errorMsg);
                            }

                            installNext();
                        },
                        error: function(xhr, status, error) {
                            clearTimeout(stepTimeout);
                            clearTimeout(stepTimeout2);
                            completed++;
                            var percent = Math.round((completed / total) * 100);
                            updateProgress(completed, total, percent);
                            var errorDetail = status === 'timeout' ?
                                '<?php esc_html_e('Zeitüberschreitung', 'wp-starter'); ?>' :
                                (status + (error ? ': ' + error : ''));
                            logMessage('error', slug, '<?php esc_html_e('Netzwerkfehler', 'wp-starter'); ?>', errorDetail);

                            installNext();
                        }
                    });
                }

                installNext();
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for single plugin installation
     */
    public function ajaxInstallPlugin(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        if (!current_user_can('install_plugins')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'wp-starter')]);
        }

        $slug = isset($_POST['slug']) ? sanitize_text_field(wp_unslash($_POST['slug'])) : '';

        if (empty($slug)) {
            wp_send_json_error(['message' => __('Kein Plugin angegeben.', 'wp-starter')]);
        }

        $result = PluginInstaller::installAndActivate($slug);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX handler for bulk plugin installation
     */
    public function ajaxInstallAllPlugins(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        if (!current_user_can('install_plugins')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'wp-starter')]);
        }

        $selectedPlugins = $this->getSelectedPlugins();
        $missingPlugins = array_filter($selectedPlugins, fn($p) => !( $p['check'] )());
        $slugs = array_column($missingPlugins, 'slug');

        $results = PluginInstaller::bulkInstallAndActivate($slugs);

        wp_send_json_success(['results' => $results]);
    }

    /**
     * Get only free (non-external) plugins
     *
     * @return array<string, array{name: string, slug: string, required: bool, description: string, check: callable}>
     */
    private function getFreePlugins(): array
    {
        return array_filter($this->plugins, fn($p) => empty($p['external']));
    }

    /**
     * Get plugins selected in setup script (or all free plugins if no config)
     *
     * @return array<string, array{name: string, slug: string, required: bool, description: string, check: callable}>
     */
    private function getSelectedPlugins(): array
    {
        $freePlugins = $this->getFreePlugins();

        // If no setup config, return all free plugins
        if (empty($this->selectedPlugins)) {
            return $freePlugins;
        }

        // Filter to only selected plugins
        return array_filter(
            $freePlugins,
            fn($plugin, $key) => in_array($plugin['slug'], $this->selectedPlugins, true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Check if setup config exists
     */
    private function hasSetupConfig(): bool
    {
        return !empty($this->selectedPlugins) || !empty($this->setupOptions);
    }

    /**
     * Display admin notices for missing plugins
     */
    public function displayPluginNotices(): void
    {
        // Don't show on plugin install page or setup page
        global $pagenow;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading page parameter for display logic only
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';

        if ($pagenow === 'update.php' || $pagenow === 'plugins.php' || $page === self::SETUP_PAGE_SLUG) {
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

        // Show recommended plugins notice (link to setup page)
        if (!empty($missingRecommended) && !$this->isDismissed('recommended')) {
            $this->renderRecommendedNotice(count($missingRecommended));
        }
    }

    /**
     * Render admin notice for required plugins
     *
     * @param array<string, array{name: string, slug: string, required: bool, description: string, check: callable, external?: string}> $plugins
     */
    private function renderNotice(array $plugins, bool $isRequired): void
    {
        $setupUrl = admin_url('themes.php?page=' . self::SETUP_PAGE_SLUG);

        $pluginNames = array_map(fn($p) => $p['name'], $plugins);

        printf(
            '<div class="notice notice-error"><p><strong>%s</strong></p><p>%s: %s</p><p><a href="%s" class="button button-primary">%s</a></p></div>',
            esc_html__('Erforderliche Plugins fehlen', 'wp-starter'),
            esc_html__('Das Theme benötigt folgende Plugins', 'wp-starter'),
            esc_html(implode(', ', $pluginNames)),
            esc_url($setupUrl),
            esc_html__('Zum Theme Setup', 'wp-starter')
        );
    }

    /**
     * Render notice for recommended plugins
     */
    private function renderRecommendedNotice(int $count): void
    {
        $setupUrl = admin_url('themes.php?page=' . self::SETUP_PAGE_SLUG);
        $dismissUrl = wp_nonce_url(
            add_query_arg('wp-starter-dismiss-plugins', '1'),
            'wp-starter-dismiss-plugins'
        );

        printf(
            '<div class="notice notice-info is-dismissible"><p>%s</p><p><a href="%s" class="button button-primary">%s</a> <a href="%s" style="margin-left: 10px;">%s</a></p></div>',
            sprintf(
                // translators: %d is the number of recommended plugins available
                esc_html__('Es sind %d empfohlene Plugins für das WP-Starter Theme verfügbar.', 'wp-starter'),
                esc_html( $count )
            ),
            esc_url($setupUrl),
            esc_html__('Plugins anzeigen & installieren', 'wp-starter'),
            esc_url($dismissUrl),
            esc_html__('Ausblenden', 'wp-starter')
        );
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
