<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\Content\StyleguideLayoutData;
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
    private static function optActivated(): string
    {
        return ThemeContext::optionKey('theme_activated');
    }

    private static function optDismissed(): string
    {
        return ThemeContext::optionKey('welcome_dismissed');
    }

    private static function optPageId(): string
    {
        return ThemeContext::optionKey('styleguide_page_id');
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

        $existingPageId = get_option(self::optPageId());
        if ($existingPageId && get_post($existingPageId)) {
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
            esc_html__('Möchten Sie eine Styleguide-Seite erstellen? Diese enthält alle verfügbaren Farben, Typografie, Abstände und Block-Beispiele als visuelle Referenz.', 'wp-starter'),
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
            wp_die(esc_html__('Sie haben keine Berechtigung für diese Aktion.', 'wp-starter'));
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
            wp_die(esc_html__('Sie haben keine Berechtigung, Seiten zu erstellen.', 'wp-starter'));
        }

        $pageId = $this->createStyleguidePage();

        if ($pageId) {
            update_option(self::optPageId(), $pageId);
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
            wp_die(esc_html__('Sie haben keine Berechtigung, Seiten zu erstellen.', 'wp-starter'));
        }

        $existingPageId = get_option(self::optPageId());
        if ($existingPageId && get_post($existingPageId)) {
            wp_delete_post( (int) $existingPageId, true);
        }

        $pageId = $this->createStyleguidePage();

        if ($pageId) {
            update_option(self::optPageId(), $pageId);
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
            wp_die(esc_html__('Sie haben keine Berechtigung, Seiten zu bearbeiten.', 'wp-starter'));
        }

        $existingPageId = get_option(self::optPageId());
        if ($existingPageId) {
            wp_untrash_post( (int) $existingPageId);

            $editUrl = get_edit_post_link( (int) $existingPageId, 'url');
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
            wp_die(esc_html__('Sie haben keine Berechtigung, Seiten zu löschen.', 'wp-starter'));
        }

        $existingPageId = get_option(self::optPageId());
        if ($existingPageId) {
            wp_delete_post( (int) $existingPageId, true);
            delete_option(self::optPageId());
        }

        wp_safe_redirect(admin_url('admin.php?page=theme-options-tools'));
        exit;
    }

    /**
     * Create the styleguide page with ACF Flexible Content layouts
     *
     * @return int Post ID on success, 0 on failure
     */
    private function createStyleguidePage(): int
    {
        $this->importPlaceholderImages();

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

        update_post_meta($pageId, '_wp_page_template', 'page-flexible.blade.php');

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
}
