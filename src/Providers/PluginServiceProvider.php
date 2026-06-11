<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\PluginInstaller;
use WordpressStarter\Services\ContentSetupService;
use WordpressStarter\ThemeContext;

/**
 * Plugin Service Provider
 *
 * Manages plugin recommendations and requirements for the theme.
 * Provides a setup page for bulk plugin installation and handles
 * content scaffolding via ContentSetupService.
 */
class PluginServiceProvider extends ServiceProvider
{
    private static function setupPageSlug(): string
    {
        return ThemeContext::kebabPrefix() . '-setup';
    }

    private static function nonceInstall(): string
    {
        return ThemeContext::prefix() . '_install_plugins';
    }

    private static function ajaxActionInstallPlugin(): string
    {
        return ThemeContext::prefix() . '_install_plugin';
    }

    private static function ajaxActionInstallAllPlugins(): string
    {
        return ThemeContext::prefix() . '_install_all_plugins';
    }

    private static function paramRerunContentSetup(): string
    {
        return ThemeContext::kebabPrefix() . '-rerun-content-setup';
    }

    private static function paramGenerateDemoPosts(): string
    {
        return ThemeContext::kebabPrefix() . '-generate-demo-posts';
    }

    private static function paramDeleteDemoPosts(): string
    {
        return ThemeContext::kebabPrefix() . '-delete-demo-posts';
    }

    private static function paramDismissPlugins(): string
    {
        return ThemeContext::kebabPrefix() . '-dismiss-plugins';
    }

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

    private ContentSetupService $contentSetupService;

    public function register(): void
    {
        $this->definePlugins();
        $this->contentSetupService = new ContentSetupService();
    }

    public function boot(): void
    {
        $this->loadSetupConfig();

        add_action('admin_menu', [$this, 'addSetupPage']);
        add_action('admin_notices', [$this, 'displayPluginNotices']);
        add_action('admin_init', [$this, 'handleDismissal']);
        add_action('after_switch_theme', [$this, 'onThemeActivation']);
        add_action('admin_init', [$this, 'maybeRedirectToSetup']);
        add_action('admin_init', [$this, 'runContentSetup']);
        add_action('admin_init', [$this, 'handleRerunContentSetup']);
        add_action('admin_init', [$this, 'handleGenerateDemoPosts']);
        add_action('admin_init', [$this, 'handleDeleteDemoPosts']);

        // AJAX handlers
        add_action('wp_ajax_' . self::ajaxActionInstallPlugin(), [$this, 'ajaxInstallPlugin']);
        add_action('wp_ajax_' . self::ajaxActionInstallAllPlugins(), [$this, 'ajaxInstallAllPlugins']);
    }

    /**
     * Load configuration from setup script
     */
    private function loadSetupConfig(): void
    {
        $composerPath = get_template_directory() . '/composer.json';
        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            if (isset($composer['require'])) {
                foreach ($composer['require'] as $package => $version) {
                    if (str_starts_with($package, 'wpackagist-plugin/')) {
                        $this->selectedPlugins[] = str_replace('wpackagist-plugin/', '', $package);
                    }
                }
            }
        }

        $setupOptionsPath = get_template_directory() . '/config/setup-options.php';
        if (file_exists($setupOptionsPath)) {
            $this->setupOptions = include $setupOptionsPath;
        }
    }

    /**
     * Run content setup on first theme activation — delegates to ContentSetupService.
     */
    public function runContentSetup(): void
    {
        if (!ThemeContext::isActiveOnCurrentSite()) {
            return;
        }

        $this->contentSetupService->runOnce($this->setupOptions);
    }

    /**
     * Handle manual re-run of content setup from Tools page — delegates to ContentSetupService.
     */
    public function handleRerunContentSetup(): void
    {
        if (!ThemeContext::isActiveOnCurrentSite()) {
            return;
        }

        if (!isset($_GET[self::paramRerunContentSetup()])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce, self::paramRerunContentSetup())) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'wp-starter'));
        }

        $this->loadSetupConfig();

        $this->contentSetupService->rerun($this->setupOptions);

        wp_safe_redirect(admin_url('admin.php?page=theme-options-tools&content-setup=success'));
        exit;
    }

    /**
     * Handle demo posts generation from Tools page
     */
    public function handleGenerateDemoPosts(): void
    {
        if (!ThemeContext::isActiveOnCurrentSite()) {
            return;
        }

        if (!isset($_GET[self::paramGenerateDemoPosts()])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce, self::paramGenerateDemoPosts())) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'wp-starter'));
        }

        // German sample blog posts
        $samplePosts = [
            [
                'title' => 'Willkommen auf unserem neuen Blog',
                'content' => '<p>Wir freuen uns, Sie auf unserem neuen Blog begrüßen zu dürfen! Hier werden wir regelmäßig spannende Einblicke, hilfreiche Tipps und aktuelle Neuigkeiten aus unserer Branche teilen.</p><p>Bleiben Sie dran für interessante Artikel und lassen Sie uns wissen, welche Themen Sie besonders interessieren.</p>',
                'excerpt' => 'Wir freuen uns, Sie auf unserem neuen Blog begrüßen zu dürfen! Hier finden Sie regelmäßig spannende Einblicke und hilfreiche Tipps.',
            ],
            [
                'title' => '5 Tipps für mehr Produktivität im Arbeitsalltag',
                'content' => '<p>In der heutigen schnelllebigen Arbeitswelt ist es wichtiger denn je, produktiv zu bleiben. Hier sind fünf bewährte Tipps:</p><ol><li><strong>Priorisieren Sie Ihre Aufgaben</strong> - Beginnen Sie jeden Tag mit einer klaren To-Do-Liste.</li><li><strong>Minimieren Sie Ablenkungen</strong> - Schalten Sie unnötige Benachrichtigungen aus.</li><li><strong>Nutzen Sie die Pomodoro-Technik</strong> - Arbeiten Sie in fokussierten 25-Minuten-Blöcken.</li><li><strong>Machen Sie regelmäßig Pausen</strong> - Kurze Pausen steigern die Konzentration.</li><li><strong>Reflektieren Sie am Ende des Tages</strong> - Was hat gut funktioniert, was nicht?</li></ol>',
                'excerpt' => 'Entdecken Sie fünf bewährte Strategien, die Ihnen helfen, im Arbeitsalltag produktiver zu sein.',
            ],
            [
                'title' => 'Die Zukunft der digitalen Transformation',
                'content' => '<p>Die digitale Transformation verändert grundlegend, wie Unternehmen arbeiten und mit ihren Kunden interagieren. Von künstlicher Intelligenz bis hin zu Cloud-Computing – die technologischen Möglichkeiten scheinen grenzenlos.</p><p>Unternehmen, die heute in innovative Technologien investieren, werden morgen die Nase vorn haben. Dabei geht es nicht nur um die Einführung neuer Tools, sondern um einen kulturellen Wandel in der gesamten Organisation.</p>',
                'excerpt' => 'Erfahren Sie, wie die digitale Transformation die Geschäftswelt verändert und was das für Ihr Unternehmen bedeutet.',
            ],
            [
                'title' => 'Nachhaltigkeit im Unternehmen: Mehr als nur ein Trend',
                'content' => '<p>Nachhaltigkeit ist längst kein Nischenthema mehr – sie ist zu einem zentralen Faktor für den Unternehmenserfolg geworden. Kunden, Mitarbeiter und Investoren erwarten zunehmend, dass Unternehmen Verantwortung für ihre ökologischen und sozialen Auswirkungen übernehmen.</p><p>Von der Reduzierung des CO2-Fußabdrucks über nachhaltige Lieferketten bis hin zu sozialen Initiativen – die Möglichkeiten sind vielfältig. Der Schlüssel liegt darin, Nachhaltigkeit authentisch in die Unternehmensstrategie zu integrieren.</p>',
                'excerpt' => 'Warum Nachhaltigkeit mehr als ein Trend ist und wie Unternehmen davon profitieren können.',
            ],
            [
                'title' => 'Remote Work: Best Practices für verteilte Teams',
                'content' => '<p>Die Arbeit im Home-Office hat sich für viele Unternehmen vom Notfall-Modus zur neuen Normalität entwickelt. Doch verteilte Teams erfolgreich zu führen, erfordert neue Ansätze und Tools.</p><p>Regelmäßige Video-Calls, klare Kommunikationsrichtlinien und die richtigen Kollaborationstools sind dabei nur der Anfang. Ebenso wichtig sind Vertrauen, Flexibilität und ein starker Teamgeist – auch über die Distanz hinweg.</p>',
                'excerpt' => 'Praktische Tipps und Best Practices für die erfolgreiche Zusammenarbeit in verteilten Teams.',
            ],
        ];

        $baseTime = time();
        $dayOffset = 0;
        $imageIndex = 0;

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        foreach ($samplePosts as $postData) {
            $postDate = gmdate('Y-m-d H:i:s', $baseTime - ( $dayOffset * DAY_IN_SECONDS ));
            $dayOffset += rand(2, 4);

            $postId = wp_insert_post([
                'post_title' => $postData['title'],
                'post_content' => $postData['content'],
                'post_excerpt' => $postData['excerpt'],
                'post_status' => 'publish',
                'post_type' => 'post',
                'post_date' => $postDate,
                'post_date_gmt' => $postDate,
            ]);

            if ($postId && !is_wp_error($postId)) {
                ++$imageIndex;
                $imageId = $this->downloadAndAttachImage(
                    "https://picsum.photos/seed/blog{$imageIndex}/1200/800",
                    "Blog Beitragsbild {$imageIndex}",
                    $postId,
                );

                if ($imageId) {
                    set_post_thumbnail($postId, $imageId);
                }
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=theme-options-tools&demo-posts=created'));
        exit;
    }

    /**
     * Handle demo posts deletion from Tools page
     */
    public function handleDeleteDemoPosts(): void
    {
        if (!ThemeContext::isActiveOnCurrentSite()) {
            return;
        }

        if (!isset($_GET[self::paramDeleteDemoPosts()])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce, self::paramDeleteDemoPosts())) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'wp-starter'));
        }

        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'any',
            'numberposts' => -1,
        ]);

        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }

        wp_safe_redirect(admin_url('admin.php?page=theme-options-tools&demo-posts=deleted'));
        exit;
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
                'check' => fn () => class_exists('ACF') && function_exists('acf_add_local_field_group'),
                'external' => 'https://www.advancedcustomfields.com/pro/',
            ],
            // === RECOMMENDED: SEO & Content ===
            'wordpress-seo' => [
                'name' => 'Yoast SEO',
                'slug' => 'wordpress-seo',
                'required' => false,
                'description' => 'SEO-Optimierung für alle Inhalte.',
                'check' => fn () => defined('WPSEO_VERSION'),
            ],
            'acf-content-analysis-for-yoast-seo' => [
                'name' => 'ACF Content Analysis for Yoast SEO',
                'slug' => 'acf-content-analysis-for-yoast-seo',
                'required' => false,
                'description' => 'Integriert ACF-Felder in die Yoast SEO-Analyse.',
                'check' => fn () => defined('AC_YOAST_ACF_ANALYSIS_FILE'),
            ],
            'acf-extended' => [
                'name' => 'ACF Extended',
                'slug' => 'acf-extended',
                'required' => false,
                'description' => 'Erweitert ACF um zusätzliche Feldtypen und Funktionen.',
                'check' => fn () => class_exists('ACFE'),
            ],

            // === RECOMMENDED: Forms & Communication ===
            'contact-form-7' => [
                'name' => 'Contact Form 7',
                'slug' => 'contact-form-7',
                'required' => false,
                'description' => 'Empfohlen für den Kontaktformular-Block.',
                'check' => fn () => class_exists('WPCF7'),
            ],
            'wp-mail-smtp' => [
                'name' => 'WP Mail SMTP',
                'slug' => 'wp-mail-smtp',
                'required' => false,
                'description' => 'Zuverlässiger E-Mail-Versand über SMTP.',
                'check' => fn () => defined('WPMS_PLUGIN_VER'),
            ],

            // === RECOMMENDED: Performance & Analytics ===
            'wp-optimize' => [
                'name' => 'WP-Optimize',
                'slug' => 'wp-optimize',
                'required' => false,
                'description' => 'Datenbank-Optimierung und Caching.',
                'check' => fn () => class_exists('WP_Optimize'),
            ],
            'integrate-rybbit' => [
                'name' => 'Rybbit Analytics',
                'slug' => 'integrate-rybbit',
                'required' => false,
                'description' => 'Datenschutzfreundliche Website-Analyse.',
                'check' => fn () => self::isPluginActive('integrate-rybbit/integrate-rybbit.php'),
            ],

            // === RECOMMENDED: Admin ===
            'admin-site-enhancements' => [
                'name' => 'Admin and Site Enhancements',
                'slug' => 'admin-site-enhancements',
                'required' => false,
                'description' => 'Über 60 Admin-Verbesserungen inkl. SVG-Upload.',
                'check' => fn () => defined('ASENHA_VERSION'),
            ],

            // === RECOMMENDED: Security & Backup (Premium) ===
            'solid-security' => [
                'name' => 'Solid Security',
                'slug' => 'solid-security',
                'required' => false,
                'description' => 'Umfassende WordPress-Sicherheit (ehem. iThemes Security).',
                'check' => fn () => defined('ITSEC_CORE_VER') || class_exists('ITSEC_Core'),
                'external' => 'https://developer.wordpress.org/plugins/solid-security/',
            ],
            'solid-backups' => [
                'name' => 'Solid Backups',
                'slug' => 'solid-backups',
                'required' => false,
                'description' => 'Automatische Backups und Migration (ehem. BackupBuddy).',
                'check' => fn () => class_exists('pb_backupbuddy') || defined('JETRAIL_BUDDY_VER'),
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
            'Erforderlich' => array_filter($this->plugins, fn ($p) => $p['required']),
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
                'integrate-rybbit' => $this->plugins['integrate-rybbit'],
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
            self::setupPageSlug(),
            [$this, 'renderSetupPage'],
        );
    }

    /**
     * Handle theme activation
     */
    public function onThemeActivation(): void
    {
        set_transient(ThemeContext::optionKey('activation_redirect'), true, 60);
    }

    /**
     * Redirect to setup page after theme activation
     */
    public function maybeRedirectToSetup(): void
    {
        if (!get_transient(ThemeContext::optionKey('activation_redirect'))) {
            return;
        }

        delete_transient(ThemeContext::optionKey('activation_redirect'));

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Core WordPress bulk activation parameter
        if (wp_doing_ajax() || isset($_GET['activate-multi'])) {
            return;
        }

        wp_safe_redirect(admin_url('themes.php?page=' . self::setupPageSlug()));
        exit;
    }

    /**
     * Render the setup page via Blade view (templates/admin/setup-page.blade.php).
     */
    public function renderSetupPage(): void
    {
        update_option(ThemeContext::optionKey('welcome_dismissed'), true);

        $categories = $this->getPluginsByCategory();
        $selectedPlugins = $this->getSelectedPlugins();
        $missingSelectedPlugins = array_filter($selectedPlugins, fn ($p) => !( $p['check'] )());
        $hasConfig = $this->hasSetupConfig();

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaping happens inside the Blade view
        echo blade('admin.setup-page', [
            'categories' => $categories,
            'selectedPlugins' => $selectedPlugins,
            'missingSelectedPlugins' => $missingSelectedPlugins,
            'hasConfig' => $hasConfig,
            'nonce' => wp_create_nonce(self::nonceInstall()),
            'ajaxActionInstallPlugin' => self::ajaxActionInstallPlugin(),
            'ajaxActionInstallAll' => self::ajaxActionInstallAllPlugins(),
        ]);
    }

    /**
     * AJAX handler for single plugin installation
     */
    public function ajaxInstallPlugin(): void
    {
        // Rate limit: 20 plugin installs per minute
        \WordpressStarter\RateLimiter::enforce('plugin_install', 20, 60);

        check_ajax_referer(self::nonceInstall(), 'nonce');

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
        // Rate limit: 5 bulk installs per minute
        \WordpressStarter\RateLimiter::enforce('plugin_bulk_install', 5, 60);

        check_ajax_referer(self::nonceInstall(), 'nonce');

        if (!current_user_can('install_plugins')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'wp-starter')]);
        }

        $selectedPlugins = $this->getSelectedPlugins();
        $missingPlugins = array_filter($selectedPlugins, fn ($p) => !( $p['check'] )());
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
        return array_filter($this->plugins, fn ($p) => empty($p['external']));
    }

    /**
     * Get plugins selected in setup script (or all free plugins if no config)
     *
     * @return array<string, array{name: string, slug: string, required: bool, description: string, check: callable}>
     */
    private function getSelectedPlugins(): array
    {
        $freePlugins = $this->getFreePlugins();

        if (empty($this->selectedPlugins)) {
            return $freePlugins;
        }

        return array_filter(
            $freePlugins,
            fn ($plugin, $key) => in_array($plugin['slug'], $this->selectedPlugins, true),
            ARRAY_FILTER_USE_BOTH,
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
        global $pagenow;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading page parameter for display logic only
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';

        if ($pagenow === 'update.php' || $pagenow === 'plugins.php' || $page === self::setupPageSlug()) {
            return;
        }

        $missingRequired = [];
        $missingRecommended = [];

        foreach ($this->plugins as $key => $plugin) {
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

        if (!empty($missingRequired)) {
            $this->renderNotice($missingRequired, true);
        }

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
        $setupUrl = admin_url('themes.php?page=' . self::setupPageSlug());
        $pluginNames = array_map(fn ($p) => $p['name'], $plugins);

        printf(
            '<div class="notice notice-error"><p><strong>%s</strong></p><p>%s: %s</p><p><a href="%s" class="button button-primary">%s</a></p></div>',
            esc_html__('Erforderliche Plugins fehlen', 'wp-starter'),
            esc_html__('Das Theme benötigt folgende Plugins', 'wp-starter'),
            esc_html(implode(', ', $pluginNames)),
            esc_url($setupUrl),
            esc_html__('Zum Theme Setup', 'wp-starter'),
        );
    }

    /**
     * Render notice for recommended plugins
     */
    private function renderRecommendedNotice(int $count): void
    {
        $setupUrl = admin_url('themes.php?page=' . self::setupPageSlug());
        $dismissUrl = wp_nonce_url(
            add_query_arg(self::paramDismissPlugins(), '1'),
            self::paramDismissPlugins(),
        );

        printf(
            '<div class="notice notice-info is-dismissible"><p>%s</p><p><a href="%s" class="button button-primary">%s</a> <a href="%s" style="margin-left: 10px;">%s</a></p></div>',
            sprintf(
                // translators: %d is the number of recommended plugins available
                esc_html__('Es sind %d empfohlene Plugins für das WP-Starter Theme verfügbar.', 'wp-starter'),
                (int) $count,
            ),
            esc_url($setupUrl),
            esc_html__('Plugins anzeigen & installieren', 'wp-starter'),
            esc_url($dismissUrl),
            esc_html__('Ausblenden', 'wp-starter'),
        );
    }

    /**
     * Handle notice dismissal
     */
    public function handleDismissal(): void
    {
        if (!isset($_GET[self::paramDismissPlugins()])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (wp_verify_nonce($nonce, self::paramDismissPlugins())) {
            update_option(ThemeContext::optionKey('dismissed_plugin_notice'), true);
            wp_safe_redirect(remove_query_arg([self::paramDismissPlugins(), '_wpnonce']));
            exit;
        }
    }

    /**
     * Check if notice is dismissed
     */
    private function isDismissed(string $pluginKey): bool
    {
        return (bool) get_option(ThemeContext::optionKey('dismissed_plugin_notice'), false);
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

    /**
     * Download an image from URL and attach it to a post
     *
     * @param string $url Image URL to download
     * @param string $title Title for the attachment
     * @param int $postId Post ID to attach the image to
     *
     * @return int|false Attachment ID or false on failure
     */
    private function downloadAndAttachImage(string $url, string $title, int $postId): int|false
    {
        $response = wp_remote_get($url, [
            'timeout' => 30,
            'redirection' => 5,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $imageData = wp_remote_retrieve_body($response);
        if (empty($imageData)) {
            return false;
        }

        $filename = sanitize_file_name($title) . '-' . time() . '.jpg';
        $upload = wp_upload_bits($filename, null, $imageData);

        if (!empty($upload['error'])) {
            return false;
        }

        $attachment = [
            'post_mime_type' => 'image/jpeg',
            'post_title' => $title,
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => $postId,
        ];

        $attachmentId = wp_insert_attachment($attachment, $upload['file'], $postId);

        if (is_wp_error($attachmentId)) {
            return false;
        }

        $attachmentData = wp_generate_attachment_metadata($attachmentId, $upload['file']);
        wp_update_attachment_metadata($attachmentId, $attachmentData);

        return $attachmentId;
    }
}
