<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\Content\StyleguideLayoutData;
use WordpressStarter\Services\StyleguidePage;
use WordpressStarter\ThemeContext;

/**
 * Welcome Service Provider
 *
 * Shows a welcome notice after theme activation and offers to create
 * a styleguide reference page with ACF Flexible Content layouts.
 * Layout data generation is delegated to StyleguideLayoutData.
 */
class WelcomeServiceProvider extends ServiceProvider
{
    /**
     * Post meta flag marking an attachment as one this importer created.
     *
     * Lets ContentSetupService::rerun() safely delete only the styleguide
     * placeholder attachments it is responsible for, never an unrelated
     * attachment that happens to share a cached ID.
     */
    public const STYLEGUIDE_IMAGE_META_KEY = '_wp_starter_styleguide_image';

    /**
     * Marker fuer Demo-Eintraege in den Custom Post Types.
     *
     * Die Layouts "Team" und "Kundenstimmen" koennen ihre Daten aus der
     * jeweiligen Verwaltung ziehen. Ohne Eintraege dort rendert dieser Pfad
     * nichts, er blieb deshalb in jeder visuellen Abnahme ungeprueft. Der Marker
     * grenzt die vom Styleguide erzeugten Eintraege ab, damit das Regenerieren
     * genau sie wieder entfernt und keine echten Inhalte anfasst.
     */
    public const STYLEGUIDE_DEMO_POST_META_KEY = '_wp_starter_styleguide_demo';

    private static function optActivated(): string
    {
        return ThemeContext::optionKey('theme_activated');
    }

    private static function optDismissed(): string
    {
        return ThemeContext::optionKey('welcome_dismissed');
    }

    /**
     * Resolve the cached styleguide page ID, tolerating a stale value.
     *
     * The cached option can point at a page that was deleted outright, or
     * at a post ID a user has since re-purposed. Both must be treated as
     * "no styleguide page" rather than trusted blindly.
     *
     * @return int Page ID if it still resolves to a page, 0 otherwise.
     */
    private static function resolveStyleguidePageId(): int
    {
        $pageId = StyleguidePage::find();

        // AMBIGUOUS means several pages could be it. Treat that as "none" here:
        // every caller of this method either deletes or overwrites the result.
        return $pageId > 0 ? $pageId : 0;
    }

    private static function optImages(): string
    {
        return ThemeContext::optionKey('styleguide_images');
    }

    private static function optAcfPrefillPending(): string
    {
        return ThemeContext::optionKey('acf_prefill_pending');
    }

    private static function nonceCreate(): string
    {
        return ThemeContext::kebabPrefix() . '-create-styleguide';
    }

    private static function nonceDismiss(): string
    {
        return ThemeContext::kebabPrefix() . '-dismiss-welcome';
    }

    private static function nonceImportOptions(): string
    {
        return ThemeContext::kebabPrefix() . '-import-options';
    }

    private static function nonceRegenerateStyleguide(): string
    {
        return ThemeContext::kebabPrefix() . '-regenerate-styleguide';
    }

    private static function nonceRestoreStyleguide(): string
    {
        return ThemeContext::kebabPrefix() . '-restore-styleguide';
    }

    private static function nonceDeleteStyleguide(): string
    {
        return ThemeContext::kebabPrefix() . '-delete-styleguide';
    }

    private static function nonceMigrateStyleguide(): string
    {
        return ThemeContext::kebabPrefix() . '-migrate-styleguide';
    }

    private static function paramMigrateStyleguide(): string
    {
        return ThemeContext::kebabPrefix() . '-migrate-styleguide';
    }

    private static function paramCreateStyleguide(): string
    {
        return ThemeContext::kebabPrefix() . '-create-styleguide';
    }

    private static function paramDismissWelcome(): string
    {
        return ThemeContext::kebabPrefix() . '-dismiss-welcome';
    }

    private static function paramImportOptions(): string
    {
        return ThemeContext::kebabPrefix() . '-import-options';
    }

    private static function paramRegenerateStyleguide(): string
    {
        return ThemeContext::kebabPrefix() . '-regenerate-styleguide';
    }

    private static function paramRestoreStyleguide(): string
    {
        return ThemeContext::kebabPrefix() . '-restore-styleguide';
    }

    private static function paramDeleteStyleguide(): string
    {
        return ThemeContext::kebabPrefix() . '-delete-styleguide';
    }

    /** @var array<string, int> Imported placeholder image IDs */
    private array $imageIds = [];

    public function register(): void
    {
        add_action('after_switch_theme', [$this, 'onThemeActivation']);

        // Hook into ACF init to prefill options when ACF becomes available
        add_action('acf/init', [$this, 'maybePrefillAcfOptions'], 20);
    }

    public function boot(): void
    {
        add_action('admin_notices', [$this, 'displayWelcomeNotice']);
        add_action('admin_notices', [$this, 'displayImportOptionsNotice']);
        add_action('admin_notices', [$this, 'displayStyleguideMigrationNotice']);
        add_action('admin_init', [$this, 'handleNoticeActions']);
    }

    /**
     * Handle theme activation
     */
    public function onThemeActivation(): void
    {
        if (!ThemeContext::isActiveOnCurrentSite()) {
            return;
        }

        update_option(self::optActivated(), true);
        delete_option(self::optDismissed());

        $configPath = get_stylesheet_directory() . '/config/acf-options.php';
        if (file_exists($configPath)) {
            update_option(self::optAcfPrefillPending(), true);

            if (function_exists('update_field')) {
                $this->prefillAcfOptions();
            }
        }
    }

    /**
     * Try to prefill ACF options when ACF initializes.
     * Handles the case where ACF is activated after theme activation.
     */
    public function maybePrefillAcfOptions(): void
    {
        if (!ThemeContext::isActiveOnCurrentSite()) {
            return;
        }

        if (!get_option(self::optAcfPrefillPending())) {
            return;
        }

        $this->prefillAcfOptions();
    }

    /**
     * Pre-fill ACF options from config file created by setup script
     */
    private function prefillAcfOptions(): void
    {
        $configPath = get_stylesheet_directory() . '/config/acf-options.php';

        if (!file_exists($configPath)) {
            delete_option(self::optAcfPrefillPending());

            return;
        }

        if (!function_exists('update_field')) {
            return;
        }

        /** @var array<string, mixed> $options */
        $options = include $configPath;

        if (!is_array($options) || empty($options)) {
            delete_option(self::optAcfPrefillPending());

            return;
        }

        $fieldMapping = [
            'company_name' => 'field_options_company_name',
            'address' => 'field_options_address',
            'phone' => 'field_options_phone',
            'email' => 'field_options_email',
            'color_scheme' => 'field_options_color_scheme',
            'copyright_text' => 'field_options_copyright',
        ];

        foreach ($fieldMapping as $configKey => $fieldKey) {
            if (isset($options[$configKey]) && $options[$configKey] !== '') {
                update_field($fieldKey, $options[$configKey], 'options');
            }
        }

        if (!empty($options['social_links']) && is_array($options['social_links'])) {
            $socialRows = [];
            foreach ($options['social_links'] as $link) {
                if (isset($link['platform'], $link['url']) && $link['url'] !== '') {
                    $socialRows[] = [
                        'field_options_social_platform' => $link['platform'],
                        'field_options_social_url' => $link['url'],
                    ];
                }
            }
            if (!empty($socialRows)) {
                update_field('field_options_social_links', $socialRows, 'options');
            }
        }

        rename($configPath, $configPath . '.processed');
        delete_option(self::optAcfPrefillPending());
    }

    /**
     * Display welcome notice in admin
     */
    public function displayWelcomeNotice(): void
    {
        if (get_option(self::optDismissed())) {
            return;
        }

        if (self::resolveStyleguidePageId() > 0) {
            return;
        }

        $themeActivated = get_option(self::optActivated());
        $setupComplete = get_option(ThemeContext::optionKey('setup_complete'))
                        || get_option(ThemeContext::optionKey('content_setup_complete'));

        if (!$themeActivated && !$setupComplete) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        global $pagenow;
        if (in_array($pagenow, ['update.php', 'themes.php'], true)) {
            return;
        }

        $this->renderNotice();
    }

    /**
     * Render the welcome notice HTML
     */
    private function renderNotice(): void
    {
        $createUrl = wp_nonce_url(
            add_query_arg(self::paramCreateStyleguide(), '1'),
            self::nonceCreate(),
        );

        $dismissUrl = wp_nonce_url(
            add_query_arg(self::paramDismissWelcome(), '1'),
            self::nonceDismiss(),
        );

        printf(
            '<div class="notice notice-info" style="padding: 15px;">
                <p><strong>%s</strong></p>
                <p>%s</p>
                <p style="margin-top: 15px;">
                    <a href="%s" class="button button-primary">%s</a>
                    <a href="%s" class="button" style="margin-left: 10px;">%s</a>
                </p>
            </div>',
            esc_html__('Willkommen beim WP-Starter Theme!', 'wp-starter'),
            esc_html__('Möchtest du eine Styleguide-Seite erstellen? Sie enthält alle verfügbaren Farben, Typografie, Abstände und Block-Beispiele als visuelle Referenz.', 'wp-starter'),
            esc_url($createUrl),
            esc_html__('Styleguide-Seite erstellen', 'wp-starter'),
            esc_url($dismissUrl),
            esc_html__('Nein, danke', 'wp-starter'),
        );
    }

    /**
     * Handle notice action buttons
     */
    public function handleNoticeActions(): void
    {
        $this->handleCreateStyleguide();
        $this->handleRegenerateStyleguide();
        $this->handleRestoreStyleguide();
        $this->handleDeleteStyleguide();
        $this->handleMigrateStyleguide();
        $this->handleDismiss();
        $this->handleImportOptions();
    }

    /**
     * Handle manual import of ACF options from config file
     */
    private function handleImportOptions(): void
    {
        if (!isset($_GET[self::paramImportOptions()])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, self::nonceImportOptions())) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Du hast keine Berechtigung für diese Aktion.', 'wp-starter'));
        }

        update_option(self::optAcfPrefillPending(), true);
        $this->prefillAcfOptions();

        $redirectUrl = add_query_arg([
            'options-imported' => '1',
            '_wpnonce' => wp_create_nonce('options_imported_notice'),
        ], admin_url('index.php'));
        wp_safe_redirect($redirectUrl);
        exit;
    }

    /**
     * Display notice when ACF options config exists but hasn't been imported
     */
    public function displayImportOptionsNotice(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below
        if (isset($_GET['options-imported'], $_GET['_wpnonce'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification
            if (wp_verify_nonce(wp_unslash($_GET['_wpnonce']), 'options_imported_notice')) {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php esc_html_e('Theme-Einstellungen wurden aus der Setup-Konfiguration importiert!', 'wp-starter'); ?></strong></p>
                </div>
                <?php
                return;
            }
        }

        $configPath = get_stylesheet_directory() . '/config/acf-options.php';
        if (!file_exists($configPath)) {
            return;
        }

        $screen = get_current_screen();
        $relevantPages = ['dashboard', 'themes', 'plugins'];
        $isRelevantPage = $screen && (
            in_array($screen->id, $relevantPages, true) ||
            str_contains($screen->id, 'theme-settings') ||
            str_contains($screen->id, 'options')
        );

        if (!$isRelevantPage) {
            return;
        }

        $importUrl = wp_nonce_url(
            admin_url('admin.php?' . self::paramImportOptions() . '=1'),
            self::nonceImportOptions(),
        );
        ?>
        <div class="notice notice-info">
            <p>
                <strong><?php esc_html_e('Setup-Konfiguration gefunden!', 'wp-starter'); ?></strong>
                <?php esc_html_e('Die Theme-Einstellungen aus dem Setup-Wizard wurden noch nicht importiert.', 'wp-starter'); ?>
            </p>
            <p>
                <a href="<?php echo esc_url($importUrl); ?>" class="button button-primary">
                    <?php esc_html_e('Einstellungen jetzt importieren', 'wp-starter'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Handle styleguide creation
     */
    private function handleCreateStyleguide(): void
    {
        if (!isset($_GET[self::paramCreateStyleguide()])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, self::nonceCreate())) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('publish_pages')) {
            wp_die(esc_html__('Du hast keine Berechtigung, Seiten zu erstellen.', 'wp-starter'));
        }

        $pageId = $this->createStyleguidePage();

        if ($pageId) {
            update_option(self::optDismissed(), true);

            $editUrl = get_edit_post_link($pageId, 'url');
            if ($editUrl) {
                wp_safe_redirect($editUrl);
                exit;
            }
        }

        wp_safe_redirect(admin_url());
        exit;
    }

    /**
     * Handle notice dismissal
     */
    private function handleDismiss(): void
    {
        if (!isset($_GET[self::paramDismissWelcome()])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, self::nonceDismiss())) {
            return;
        }

        update_option(self::optDismissed(), true);

        wp_safe_redirect(remove_query_arg([self::paramDismissWelcome(), '_wpnonce']));
        exit;
    }

    /**
     * Handle styleguide regeneration (from Tools page)
     */
    private function handleRegenerateStyleguide(): void
    {
        if (!isset($_GET[self::paramRegenerateStyleguide()])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, self::nonceRegenerateStyleguide())) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('publish_pages')) {
            wp_die(esc_html__('Du hast keine Berechtigung, Seiten zu erstellen.', 'wp-starter'));
        }

        $existingPageId = self::resolveStyleguidePageId();
        if ($existingPageId > 0) {
            wp_delete_post($existingPageId, true);
        }

        $this->deleteDemoMedia();

        $pageId = $this->createStyleguidePage();

        if ($pageId) {
            update_option(self::optDismissed(), true);

            $editUrl = get_edit_post_link($pageId, 'url');
            if ($editUrl) {
                wp_safe_redirect($editUrl);
                exit;
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=theme-options-tools'));
        exit;
    }

    /**
     * Handle styleguide restoration from trash
     */
    private function handleRestoreStyleguide(): void
    {
        if (!isset($_GET[self::paramRestoreStyleguide()])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, self::nonceRestoreStyleguide())) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('publish_pages')) {
            wp_die(esc_html__('Du hast keine Berechtigung, Seiten zu bearbeiten.', 'wp-starter'));
        }

        $existingPageId = self::resolveStyleguidePageId();
        if ($existingPageId > 0) {
            wp_untrash_post($existingPageId);

            $editUrl = get_edit_post_link($existingPageId, 'url');
            if ($editUrl) {
                wp_safe_redirect($editUrl);
                exit;
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=theme-options-tools'));
        exit;
    }

    /**
     * Handle permanent styleguide deletion
     */
    private function handleDeleteStyleguide(): void
    {
        if (!isset($_GET[self::paramDeleteStyleguide()])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, self::nonceDeleteStyleguide())) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('delete_pages')) {
            wp_die(esc_html__('Du hast keine Berechtigung, Seiten zu löschen.', 'wp-starter'));
        }

        $existingPageId = self::resolveStyleguidePageId();
        if ($existingPageId > 0) {
            wp_delete_post($existingPageId, true);
        }
        StyleguidePage::forget();

        wp_safe_redirect(admin_url('admin.php?page=theme-options-tools'));
        exit;
    }

    /**
     * Create the styleguide page with ACF Flexible Content layouts
     *
     * @return int Post ID on success, 0 on failure
     */
    /**
     * Offer the switch to the component-rendered styleguide.
     *
     * Deliberately a notice and not an automatic migration: this writes over a page
     * on a site we know nothing about. The user clicks, or nothing happens.
     *
     * When several pages could be the styleguide, say so instead of picking one.
     */
    public function displayStyleguideMigrationNotice(): void
    {
        if (!current_user_can('publish_pages') || !ThemeContext::isActiveOnCurrentSite()) {
            return;
        }

        $pageId = StyleguidePage::find();

        if ($pageId === StyleguidePage::AMBIGUOUS) {
            printf(
                '<div class="notice notice-warning"><p>%s</p></div>',
                esc_html__(
                    'Mehrere Seiten kommen als Styleguide in Frage. Bitte die richtige Seite oeffnen und dort das Template "Styleguide" auswaehlen; automatisch wird hier nichts geaendert.',
                    'wp-starter'
                )
            );

            return;
        }

        if ($pageId <= 0 || !StyleguidePage::needsMigration($pageId)) {
            return;
        }

        $url = wp_nonce_url(
            add_query_arg(self::paramMigrateStyleguide(), '1', admin_url()),
            self::nonceMigrateStyleguide()
        );

        printf(
            '<div class="notice notice-info"><p>%s</p><p><a href="%s" class="button button-primary">%s</a> <a href="%s">%s</a></p></div>',
            esc_html__(
                'Der Styleguide zeigt die Design-System-Referenz noch als kopiertes HTML. Die neue Fassung rendert sie aus den echten Komponenten, bleibt damit automatisch aktuell und behaelt die Layout-Galerie.',
                'wp-starter'
            ),
            esc_url($url),
            esc_html__('Styleguide umstellen', 'wp-starter'),
            esc_url( (string) get_edit_post_link($pageId, 'url') ),
            esc_html__('Seite vorher ansehen', 'wp-starter')
        );
    }

    /**
     * Switch the styleguide page over: new template, layout gallery rebuilt.
     *
     * The gallery is regenerated rather than filtered, because the old content mixed
     * reference sections and gallery sections in one flat list with no marker to tell
     * them apart. Regenerating is safe here and only here: the page is generated
     * content that nobody edits by hand, and it is identified by its own marker.
     */
    private function handleMigrateStyleguide(): void
    {
        if (!isset($_GET[self::paramMigrateStyleguide()])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, self::nonceMigrateStyleguide())) {
            wp_die(esc_html__('Sicherheitsueberpruefung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('publish_pages')) {
            wp_die(esc_html__('Du hast keine Berechtigung fuer diese Aktion.', 'wp-starter'));
        }

        if (!function_exists('update_field')) {
            wp_die(esc_html__('ACF Pro ist nicht aktiv. Ohne ACF kann der Styleguide nicht umgestellt werden.', 'wp-starter'));
        }

        $pageId = StyleguidePage::find();

        if ($pageId <= 0) {
            wp_die(esc_html__('Es wurde keine eindeutige Styleguide-Seite gefunden.', 'wp-starter'));
        }

        $this->importPlaceholderImages();

        update_post_meta($pageId, '_wp_page_template', StyleguidePage::TEMPLATE);
        StyleguidePage::adopt($pageId);

        $factory = new StyleguideLayoutData($this->imageIds);
        update_field('page_sections', $factory->build(), $pageId);

        $viewUrl = get_permalink($pageId);

        if ($viewUrl) {
            wp_safe_redirect($viewUrl);
            exit;
        }
    }

    private function createStyleguidePage(): int
    {
        $this->importPlaceholderImages();
        $this->importDemoVideo();
        $this->createDemoCptEntries();

        $pageId = wp_insert_post([
            'post_title' => __('Styleguide', 'wp-starter'),
            'post_content' => '',
            'post_status' => 'private',
            'post_type' => 'page',
            'post_author' => get_current_user_id(),
        ]);

        if (!$pageId || is_wp_error($pageId)) {
            return 0;
        }

        update_post_meta($pageId, '_wp_page_template', StyleguidePage::TEMPLATE);
        StyleguidePage::adopt($pageId);

        $factory = new StyleguideLayoutData($this->imageIds);
        $layouts = $factory->build();

        if (function_exists('update_field')) {
            update_field('page_sections', $layouts, $pageId);
        }

        return $pageId;
    }

    /**
     * Import placeholder images from theme assets into media library
     */
    private function importPlaceholderImages(): void
    {
        $existingImages = get_option(self::optImages(), []);
        if (!empty($existingImages) && is_array($existingImages)) {
            $allExist = true;
            foreach ($existingImages as $id) {
                if (!wp_get_attachment_url( (int) $id)) {
                    $allExist = false;
                    break;
                }
            }
            if ($allExist) {
                $this->imageIds = $existingImages;

                return;
            }
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $themeDir = get_stylesheet_directory();
        $assetsDir = $themeDir . '/assets/images/';

        for ($i = 1; $i <= 6; $i++) {
            $file = $assetsDir . "placeholder-{$i}.jpg";
            if (file_exists($file)) {
                $attachmentId = $this->importImage($file, "Styleguide Placeholder {$i}");
                if ($attachmentId) {
                    $this->imageIds["placeholder_{$i}"] = $attachmentId;
                }
            }
        }

        // Ein echtes Hochformat, damit die Teamportraits zeigen, was sie auf
        // Kundenseiten zeigen: 4:5 statt eines quer beschnittenen Querformats.
        $portrait = $assetsDir . 'placeholder-portrait.jpg';
        if (file_exists($portrait)) {
            $portraitId = $this->importImage($portrait, __('Styleguide Portrait', 'wp-starter'));
            if ($portraitId) {
                $this->imageIds['portrait'] = $portraitId;
            }
        }

        for ($i = 1; $i <= 6; $i++) {
            $file = $assetsDir . "logo-placeholder-{$i}.svg";
            if (file_exists($file)) {
                $attachmentId = $this->importImage($file, "Styleguide Logo {$i}");
                if ($attachmentId) {
                    $this->imageIds["logo_{$i}"] = $attachmentId;
                }
            }
        }

        update_option(self::optImages(), $this->imageIds);
    }

    /**
     * Import a single image file into the media library
     *
     * @param string $filePath Path to the image file
     * @param string $title Attachment title
     *
     * @return int|null Attachment ID or null on failure
     */
    private function importImage(string $filePath, string $title): ?int
    {
        $uploadDir = wp_upload_dir();
        $filename = basename($filePath);
        $newFilePath = $uploadDir['path'] . '/' . $filename;

        if (!copy($filePath, $newFilePath)) {
            return null;
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext === 'svg') {
            $mimeType = 'image/svg+xml';
        } else {
            $filetype = wp_check_filetype($filename);
            $mimeType = $filetype['type'];
        }

        if (empty($mimeType)) {
            return null;
        }

        $attachment = [
            'post_mime_type' => $mimeType,
            'post_title' => $title,
            'post_content' => '',
            'post_status' => 'inherit',
            'meta_input' => [
                self::STYLEGUIDE_IMAGE_META_KEY => 1,
                // Ohne Alt-Text meldet Text::imageAlt bei jedem Aufruf eine
                // Luecke, und zwar zu Recht: 44 Bilder der Styleguide-Seite
                // waren fuer Screenreader stumm. Der Text benennt, was das Bild
                // ist, statt so zu tun, als zeige es etwas.
                '_wp_attachment_image_alt' => $title,
            ],
        ];

        $attachmentId = wp_insert_attachment($attachment, $newFilePath);
        if (is_wp_error($attachmentId)) {
            return null;
        }

        if ($ext !== 'svg') {
            $attachData = wp_generate_attachment_metadata($attachmentId, $newFilePath);
            wp_update_attachment_metadata($attachmentId, $attachData);
        }

        return $attachmentId;
    }

    /**
     * Demo-Eintraege fuer die Custom Post Types anlegen.
     *
     * Die Layouts "Team" und "Kundenstimmen" bieten die Quelle "aus der
     * Verwaltung" an. Ohne Eintraege dort rendert das Layout nur seine
     * Ueberschrift, weshalb dieser Renderpfad nie visuell abgenommen wurde.
     *
     * Bestehende Demo-Eintraege werden vorher entfernt, damit ein erneutes
     * Generieren keine Duplikate hinterlaesst. Eintraege ohne Marker bleiben
     * unangetastet, echte Inhalte sind also sicher.
     */
    private function createDemoCptEntries(): void
    {
        $this->deleteDemoCptEntries();

        $testimonials = [
            ['Sabine Vogel', 'Bereichsleiterin, Nordwind AG', 'Die Zusammenarbeit war strukturiert und termintreu. Wir haben jede Woche gesehen, woran gearbeitet wurde.'],
            ['Jonas Hartmann', 'Inhaber, Hartmann Werkstoffe', 'Aus einer alten Seite wurde ein Werkzeug, mit dem unser Team selbst arbeiten kann.'],
            ['Elif Demir', 'Marketing, Stadtwerke Süd', 'Besonders geholfen hat die klare Struktur der Inhalte. Pflege dauert heute Minuten statt Stunden.'],
        ];

        foreach ($testimonials as $index => [$name, $role, $quote]) {
            $postId = wp_insert_post([
                'post_title' => $name,
                'post_type' => 'testimonial',
                'post_status' => 'publish',
                'meta_input' => [self::STYLEGUIDE_DEMO_POST_META_KEY => 1],
            ]);

            if (!$postId || is_wp_error($postId)) {
                continue;
            }

            if (function_exists('update_field')) {
                update_field('author_name', $name, $postId);
                update_field('author_position', $role, $postId);
                update_field('content', $quote, $postId);
            }

            $imageId = $this->imageIds['placeholder_' . ( ( $index % 6 ) + 1 )] ?? null;
            if ($imageId) {
                set_post_thumbnail($postId, (int) $imageId);
            }
        }

        $members = [
            ['Anna Weber', 'Geschäftsführerin', 'Seit 2015 verantwortet Anna Strategie und Kundenbeziehungen.', 'anna@beispiel.de', 'https://www.linkedin.com/in/beispiel-weber'],
            ['Michael Braun', 'Technischer Leiter', 'Michael verantwortet Architektur und Entwicklung.', 'michael@beispiel.de', ''],
            ['Sarah Klein', 'Marketing Managerin', 'Sarah bringt die Projekte zu ihrer Zielgruppe.', '', 'https://www.linkedin.com/in/beispiel-klein'],
        ];

        foreach ($members as $index => [$name, $position, $bio, $email, $linkedin]) {
            $postId = wp_insert_post([
                'post_title' => $name,
                'post_type' => 'team_member',
                'post_status' => 'publish',
                'meta_input' => [self::STYLEGUIDE_DEMO_POST_META_KEY => 1],
            ]);

            if (!$postId || is_wp_error($postId)) {
                continue;
            }

            if (function_exists('update_field')) {
                update_field('position', $position, $postId);
                update_field('bio', $bio, $postId);
                update_field('display_order', $index + 1, $postId);
                // Bewusst unterschiedlich befuellt: eine Person mit beiden
                // Kontaktwegen, eine mit einem, eine ohne. So zeigt der
                // Styleguide auch die Faelle, in denen Icons fehlen.
                update_field('email', $email, $postId);
                update_field('linkedin', $linkedin, $postId);
                update_field('phone', '', $postId);
                update_field('xing', '', $postId);
            }

            // Teamportraits nutzen das Hochformat, sonst zeigt der Styleguide
            // einen quer beschnittenen Schnappschuss statt eines Portraits.
            $imageId = $this->imageIds['portrait'] ?? ( $this->imageIds['placeholder_' . ( ( $index % 6 ) + 1 )] ?? null );
            if ($imageId) {
                set_post_thumbnail($postId, (int) $imageId);
            }
        }
    }

    /**
     * Vom Styleguide erzeugte Demo-Eintraege wieder entfernen.
     */
    private function deleteDemoCptEntries(): void
    {
        $posts = get_posts([
            'post_type' => ['testimonial', 'team_member'],
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_key' => self::STYLEGUIDE_DEMO_POST_META_KEY,
        ]);

        foreach ($posts as $postId) {
            wp_delete_post( (int) $postId, true);
        }
    }

    /**
     * Demo-Video in die Mediathek importieren.
     *
     * Eigener Schritt und nicht Teil von importPlaceholderImages(): jene Methode
     * kehrt frueh zurueck, sobald die Bilder schon im Option-Cache stehen, und
     * haette das Video auf jeder bereits eingerichteten Site uebersprungen.
     *
     * Ohne diese Datei zeigten die Video-Varianten "Mediathek" und "Externer
     * Link" nur den Redakteurshinweis, ihr Renderpfad blieb ungeprueft.
     */
    private function importDemoVideo(): void
    {
        $existing = $this->imageIds['video_demo'] ?? null;
        if ($existing && wp_get_attachment_url( (int) $existing)) {
            return;
        }

        $file = get_stylesheet_directory() . '/assets/videos/styleguide-demo.mp4';
        if (!file_exists($file)) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachmentId = $this->importImage($file, __('Styleguide Demo-Video', 'wp-starter'));
        if (!$attachmentId) {
            return;
        }

        $this->imageIds['video_demo'] = $attachmentId;
        update_option(self::optImages(), $this->imageIds);
    }

    /**
     * Vom Styleguide importierte Medien entfernen.
     *
     * Ohne diesen Schritt bleiben einmal importierte Platzhalter fuer immer
     * liegen: importPlaceholderImages() kehrt frueh zurueck, sobald die IDs im
     * Option-Cache stehen. Wer die Dateien im Theme austauscht, bekommt sonst
     * weiter die alten, samt der Zuschnitte, die es damals gab.
     */
    private function deleteDemoMedia(): void
    {
        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_key' => self::STYLEGUIDE_IMAGE_META_KEY,
        ]);

        foreach ($attachments as $id) {
            wp_delete_attachment( (int) $id, true);
        }

        delete_option(self::optImages());
        $this->imageIds = [];
    }
}
