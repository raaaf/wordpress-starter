<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

/**
 * Welcome Service Provider
 *
 * Shows a welcome notice after theme activation and offers to create
 * a styleguide reference page with ACF Flexible Content layouts.
 */
class WelcomeServiceProvider extends ServiceProvider
{
    private const OPTION_ACTIVATED = 'wp_starter_theme_activated';
    private const OPTION_DISMISSED = 'wp_starter_welcome_dismissed';
    private const OPTION_PAGE_ID = 'wp_starter_styleguide_page_id';
    private const OPTION_IMAGES = 'wp_starter_styleguide_images';
    private const OPTION_ACF_PREFILL_PENDING = 'wp_starter_acf_prefill_pending';
    private const NONCE_CREATE = 'wp-starter-create-styleguide';
    private const NONCE_DISMISS = 'wp-starter-dismiss-welcome';
    private const NONCE_IMPORT_OPTIONS = 'wp-starter-import-options';

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
        update_option(self::OPTION_ACTIVATED, true);
        delete_option(self::OPTION_DISMISSED);

        // Check if we have ACF options config to import
        $configPath = get_stylesheet_directory() . '/config/acf-options.php';
        if (file_exists($configPath)) {
            // Mark that prefill is pending (ACF may not be active yet)
            update_option(self::OPTION_ACF_PREFILL_PENDING, true);

            // Try to prefill now if ACF is already active
            if (function_exists('update_field')) {
                $this->prefillAcfOptions();
            }
        }
    }

    /**
     * Try to prefill ACF options when ACF initializes
     * This handles the case where ACF is activated after theme activation
     */
    public function maybePrefillAcfOptions(): void
    {
        // Only run if prefill is pending
        if (!get_option(self::OPTION_ACF_PREFILL_PENDING)) {
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
            // Config already processed or doesn't exist, clear flag
            delete_option(self::OPTION_ACF_PREFILL_PENDING);
            return;
        }

        // Only run if ACF is active
        if (!function_exists('update_field')) {
            return;
        }

        /** @var array<string, mixed> $options */
        $options = include $configPath;

        if (!is_array($options) || empty($options)) {
            delete_option(self::OPTION_ACF_PREFILL_PENDING);
            return;
        }

        // Map config keys to ACF field keys
        $fieldMapping = [
            'company_name' => 'field_options_company_name',
            'address' => 'field_options_address',
            'phone' => 'field_options_phone',
            'email' => 'field_options_email',
            'color_scheme' => 'field_options_color_scheme',
            'copyright_text' => 'field_options_copyright',
            'pirsch_code' => 'field_options_pirsch_code',
        ];

        // Update simple fields
        foreach ($fieldMapping as $configKey => $fieldKey) {
            if (isset($options[$configKey]) && $options[$configKey] !== '') {
                update_field($fieldKey, $options[$configKey], 'options');
            }
        }

        // Handle social_links repeater field
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

        // Mark config as processed and clear pending flag
        rename($configPath, $configPath . '.processed');
        delete_option(self::OPTION_ACF_PREFILL_PENDING);
    }

    /**
     * Display welcome notice in admin
     */
    public function displayWelcomeNotice(): void
    {
        // Skip if dismissed
        if (get_option(self::OPTION_DISMISSED)) {
            return;
        }

        // Skip if styleguide page already exists
        $existingPageId = get_option(self::OPTION_PAGE_ID);
        if ($existingPageId && get_post($existingPageId)) {
            return;
        }

        // Check if theme was activated (either via normal activation or via setup script)
        $themeActivated = get_option(self::OPTION_ACTIVATED);
        $setupComplete = get_option('wp_starter_setup_complete') || get_option('wp_starter_content_setup_complete');

        // Only show if theme was activated or setup was completed
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
            add_query_arg('wp-starter-create-styleguide', '1'),
            self::NONCE_CREATE
        );

        $dismissUrl = wp_nonce_url(
            add_query_arg('wp-starter-dismiss-welcome', '1'),
            self::NONCE_DISMISS
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
            esc_html__('Nein, danke', 'wp-starter')
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
        if (!isset($_GET['wp-starter-import-options'])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, self::NONCE_IMPORT_OPTIONS)) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Sie haben keine Berechtigung für diese Aktion.', 'wp-starter'));
        }

        // Force the prefill by setting the pending flag
        update_option(self::OPTION_ACF_PREFILL_PENDING, true);
        $this->prefillAcfOptions();

        // Redirect to dashboard with success message
        $redirectUrl = admin_url('index.php?options-imported=1');
        wp_safe_redirect($redirectUrl);
        exit;
    }

    /**
     * Display notice when ACF options config exists but hasn't been imported
     */
    public function displayImportOptionsNotice(): void
    {
        // Only show to admins
        if (!current_user_can('manage_options')) {
            return;
        }

        // Show success message if just imported
        if (isset($_GET['options-imported']) && $_GET['options-imported'] === '1') {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong><?php esc_html_e('Theme-Einstellungen wurden aus der Setup-Konfiguration importiert!', 'wp-starter'); ?></strong></p>
            </div>
            <?php
            return;
        }

        // Check if config file exists (not yet processed)
        $configPath = get_stylesheet_directory() . '/config/acf-options.php';
        if (!file_exists($configPath)) {
            return;
        }

        // Show on dashboard and theme-related pages
        $screen = get_current_screen();
        $relevantPages = [
            'dashboard',
            'themes',
            'plugins',
        ];
        $isRelevantPage = $screen && (
            in_array($screen->id, $relevantPages, true) ||
            str_contains($screen->id, 'theme-settings') ||
            str_contains($screen->id, 'options')
        );

        if (!$isRelevantPage) {
            return;
        }

        $importUrl = wp_nonce_url(
            admin_url('admin.php?wp-starter-import-options=1'),
            self::NONCE_IMPORT_OPTIONS
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
        if (!isset($_GET['wp-starter-create-styleguide'])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, self::NONCE_CREATE)) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('publish_pages')) {
            wp_die(esc_html__('Sie haben keine Berechtigung, Seiten zu erstellen.', 'wp-starter'));
        }

        $pageId = $this->createStyleguidePage();

        if ($pageId) {
            update_option(self::OPTION_PAGE_ID, $pageId);
            update_option(self::OPTION_DISMISSED, true);

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
        if (!isset($_GET['wp-starter-dismiss-welcome'])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, self::NONCE_DISMISS)) {
            return;
        }

        update_option(self::OPTION_DISMISSED, true);

        wp_safe_redirect(remove_query_arg(['wp-starter-dismiss-welcome', '_wpnonce']));
        exit;
    }

    /**
     * Handle styleguide regeneration (from Tools page)
     */
    private function handleRegenerateStyleguide(): void
    {
        if (!isset($_GET['wp-starter-regenerate-styleguide'])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, 'wp-starter-regenerate-styleguide')) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('publish_pages')) {
            wp_die(esc_html__('Sie haben keine Berechtigung, Seiten zu erstellen.', 'wp-starter'));
        }

        // Delete existing styleguide page if it exists
        $existingPageId = get_option(self::OPTION_PAGE_ID);
        if ($existingPageId && get_post($existingPageId)) {
            wp_delete_post( (int) $existingPageId, true);
        }

        // Clear cached images so they get re-uploaded (including SVGs)
        delete_option(self::OPTION_IMAGES);
        $this->imageIds = [];

        // Create new styleguide page
        $pageId = $this->createStyleguidePage();

        if ($pageId) {
            update_option(self::OPTION_PAGE_ID, $pageId);
            update_option(self::OPTION_DISMISSED, true);

            $editUrl = get_edit_post_link($pageId, 'url');
            if ($editUrl) {
                wp_safe_redirect($editUrl);
                exit;
            }
        }

        // Redirect back to tools page
        wp_safe_redirect(admin_url('admin.php?page=theme-options-tools'));
        exit;
    }

    /**
     * Handle styleguide restoration from trash
     */
    private function handleRestoreStyleguide(): void
    {
        if (!isset($_GET['wp-starter-restore-styleguide'])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, 'wp-starter-restore-styleguide')) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('publish_pages')) {
            wp_die(esc_html__('Sie haben keine Berechtigung, Seiten zu bearbeiten.', 'wp-starter'));
        }

        $existingPageId = get_option(self::OPTION_PAGE_ID);
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
        if (!isset($_GET['wp-starter-delete-styleguide'])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, 'wp-starter-delete-styleguide')) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('delete_pages')) {
            wp_die(esc_html__('Sie haben keine Berechtigung, Seiten zu löschen.', 'wp-starter'));
        }

        $existingPageId = get_option(self::OPTION_PAGE_ID);
        if ($existingPageId) {
            wp_delete_post( (int) $existingPageId, true); // true = force delete (bypass trash)
            delete_option(self::OPTION_PAGE_ID);
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
        // Import placeholder images first
        $this->importPlaceholderImages();

        // Create page with empty content (ACF handles the content)
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

        // Set the page template to Flexible Content
        update_post_meta($pageId, '_wp_page_template', 'page-flexible.blade.php');

        // Generate and save the flexible content layouts
        $layouts = $this->generateStyleguideLayouts();

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
        // Check if images were already imported
        $existingImages = get_option(self::OPTION_IMAGES, []);
        if (!empty($existingImages) && is_array($existingImages)) {
            // Verify images still exist
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

        // Import regular placeholder images
        for ($i = 1; $i <= 6; $i++) {
            $file = $assetsDir . "placeholder-{$i}.jpg";
            if (file_exists($file)) {
                $attachmentId = $this->importImage($file, "Styleguide Placeholder {$i}");
                if ($attachmentId) {
                    $this->imageIds["placeholder_{$i}"] = $attachmentId;
                }
            }
        }

        // Import logo placeholder SVGs
        for ($i = 1; $i <= 6; $i++) {
            $file = $assetsDir . "logo-placeholder-{$i}.svg";
            if (file_exists($file)) {
                $attachmentId = $this->importImage($file, "Styleguide Logo {$i}");
                if ($attachmentId) {
                    $this->imageIds["logo_{$i}"] = $attachmentId;
                }
            }
        }

        // Store image IDs for reuse
        update_option(self::OPTION_IMAGES, $this->imageIds);
    }

    /**
     * Import a single image file into the media library
     *
     * @param string $filePath Path to the image file
     * @param string $title Attachment title
     * @return int|null Attachment ID or null on failure
     */
    private function importImage(string $filePath, string $title): ?int
    {
        $uploadDir = wp_upload_dir();
        $filename = basename($filePath);
        $newFilePath = $uploadDir['path'] . '/' . $filename;

        // Copy file to uploads
        if (!copy($filePath, $newFilePath)) {
            return null;
        }

        // Get mime type - handle SVGs explicitly
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

        // Generate metadata (skip for SVGs - they don't have standard image dimensions)
        if ($ext !== 'svg') {
            $attachData = wp_generate_attachment_metadata($attachmentId, $newFilePath);
            wp_update_attachment_metadata($attachmentId, $attachData);
        }

        return $attachmentId;
    }

    /**
     * Get a placeholder image ID
     *
     * @param int $index Image index (1-6)
     * @return int|null Attachment ID or null
     */
    private function getImageId(int $index = 1): ?int
    {
        return $this->imageIds["placeholder_{$index}"] ?? null;
    }

    /**
     * Get a logo image ID
     *
     * @param int $index Logo index (1-6)
     * @return int|null Attachment ID or null
     */
    private function getLogoId(int $index = 1): ?int
    {
        return $this->imageIds["logo_{$index}"] ?? null;
    }

    /**
     * Generate a Flexible Content layout array
     *
     * @param string $layoutName Layout name (e.g., 'hero', 'one_column')
     * @param array<string, mixed> $data Layout field data
     * @return array<string, mixed> Layout array for ACF Flexible Content
     */
    private function generateLayout(string $layoutName, array $data): array
    {
        return array_merge(['acf_fc_layout' => $layoutName], $data);
    }

    /**
     * Generate all styleguide layouts for ACF Flexible Content
     *
     * @return array<int, array<string, mixed>> Array of layout arrays
     */
    private function generateStyleguideLayouts(): array
    {
        $layouts = [];

        // =====================================================================
        // TEIL 1: DESIGN SYSTEM - GRUNDLAGEN
        // =====================================================================

        // Intro
        $layouts[] = $this->generateLayout('one_column', [
            'label' => '',
            'content' => '<h2>Design System</h2><p>Alle grundlegenden Design-Tokens und Stilregeln des Themes.</p>',
            'background_color' => 'primary',
        ]);

        // 1.1 Typografie
        $layouts[] = $this->generateLayout('one_column', [
            'label' => 'Typografie',
            'content' => '<h3>Typografie</h3><p>Alle verfügbaren Typografie-Klassen des Design Systems.</p>' . $this->generateTypographyHtml(),
            'background_color' => 'secondary',
        ]);

        // 1.2 Farben
        $layouts[] = $this->generateLayout('one_column', [
            'label' => 'Farben',
            'content' => '<h3>Farben</h3><p>Die semantischen Farbklassen für Hintergründe, Text und Rahmen.</p>' . $this->generateColorsHtml(),
            'background_color' => 'primary',
        ]);

        // 1.3 Schatten
        $layouts[] = $this->generateLayout('one_column', [
            'label' => 'Schatten',
            'content' => '<h3>Schatten</h3><p>Definierte Schatten-Tokens für verschiedene UI-Elemente.</p>' . $this->generateShadowsHtml(),
            'background_color' => 'secondary',
        ]);

        // 1.4 Verläufe
        $layouts[] = $this->generateLayout('one_column', [
            'label' => 'Verläufe',
            'content' => '<h3>Verläufe (Gradients)</h3><p>Farbverläufe für Buttons und Hintergründe.</p>' . $this->generateGradientsHtml(),
            'background_color' => 'primary',
        ]);

        // 1.5 Abstände & Radien
        $layouts[] = $this->generateLayout('one_column', [
            'label' => 'Abstände',
            'content' => '<h3>Abstände &amp; Radien</h3><p>Spacing-Scale und Border-Radius-Werte für konsistente Layouts.</p>' . $this->generateSpacingHtml(),
            'background_color' => 'secondary',
        ]);

        // =====================================================================
        // TEIL 2: DESIGN SYSTEM - KOMPONENTEN
        // =====================================================================

        // UI Components
        $layouts[] = $this->generateLayout('one_column', [
            'label' => 'UI-Komponenten',
            'content' => '<h2>UI-Komponenten</h2><p>Wiederverwendbare Blade-Komponenten für die Gestaltung.</p>' . $this->generateComponentsHtml(),
            'background_color' => 'primary',
        ]);

        // Layout Helpers
        $layouts[] = $this->generateLayout('one_column', [
            'label' => 'Layout-Helfer',
            'content' => '<h3>Layout-Helfer</h3><p>Grid, Section und Prose Komponenten für strukturierte Layouts.</p>' . $this->generateLayoutHelpersHtml(),
            'background_color' => 'secondary',
        ]);

        // =====================================================================
        // TEIL 3: FLEXIBLE CONTENT LAYOUTS - HERO
        // =====================================================================

        // Intro for layouts section
        $layouts[] = $this->generateLayout('one_column', [
            'label' => '',
            'content' => '<h2>Flexible Content Layouts</h2><p>Alle 28 verfügbaren Layouts für den Seitenaufbau.</p>',
            'background_color' => 'primary',
        ]);

        // Hero
        $layouts[] = $this->getHeroLayoutData();

        // =====================================================================
        // TEIL 4: LAYOUT & TEXT
        // =====================================================================

        // Section intro
        $layouts[] = $this->generateLayout('one_column', [
            'label' => '',
            'content' => '<h2>Layout &amp; Text</h2><p>Verschiedene Spalten-Layouts für die Inhaltsstrukturierung.</p>',
            'background_color' => 'secondary',
        ]);

        // Column layouts
        $layouts[] = $this->getOneColumnLayoutData();
        $layouts[] = $this->getTwoColumnsLayoutData();
        $layouts[] = $this->getThreeColumnsLayoutData();
        $layouts[] = $this->getFourColumnsLayoutData();
        $layouts[] = $this->getOneThirdTwoThirdsLayoutData();
        $layouts[] = $this->getTwoThirdsOneThirdLayoutData();
        $layouts[] = $this->getTwoColumnsImagesLayoutData();
        $layouts[] = $this->getDividerLayoutData();

        // =====================================================================
        // TEIL 5: INTERAKTIVE ELEMENTE
        // =====================================================================

        $layouts[] = $this->generateLayout('one_column', [
            'label' => '',
            'content' => '<h2>Interaktive Elemente</h2><p>Layouts mit Benutzerinteraktion wie Akkordeons und Tabs.</p>',
            'background_color' => 'primary',
        ]);

        $layouts[] = $this->getAccordionLayoutData();
        $layouts[] = $this->getTabsLayoutData();

        // =====================================================================
        // TEIL 6: KARTEN & INHALTE
        // =====================================================================

        $layouts[] = $this->generateLayout('one_column', [
            'label' => '',
            'content' => '<h2>Karten &amp; Inhalte</h2><p>Layouts zur Darstellung von Features, Team, Preisen und mehr.</p>',
            'background_color' => 'secondary',
        ]);

        $layouts[] = $this->getCardsLayoutData();
        $layouts[] = $this->getTestimonialsLayoutData();
        $layouts[] = $this->getTeamLayoutData();
        $layouts[] = $this->getStatsLayoutData();
        $layouts[] = $this->getPricingLayoutData();
        $layouts[] = $this->getTimelineLayoutData();
        $layouts[] = $this->getPostsLayoutData();

        // =====================================================================
        // TEIL 7: MEDIEN
        // =====================================================================

        $layouts[] = $this->generateLayout('one_column', [
            'label' => '',
            'content' => '<h2>Medien</h2><p>Layouts für Bilder, Videos und Galerien.</p>',
            'background_color' => 'primary',
        ]);

        $layouts[] = $this->getImageLayoutData();
        $layouts[] = $this->getGalleryLayoutData();
        $layouts[] = $this->getBeforeAfterLayoutData();
        $layouts[] = $this->getVideoLayoutData();
        $layouts[] = $this->getLogoSliderLayoutData();

        // =====================================================================
        // TEIL 8: KONTAKT & STANDORT
        // =====================================================================

        $layouts[] = $this->generateLayout('one_column', [
            'label' => '',
            'content' => '<h2>Kontakt &amp; Standort</h2><p>Layouts für Kontaktformulare und Kartenansichten.</p>',
            'background_color' => 'secondary',
        ]);

        $layouts[] = $this->getContactFormLayoutData();
        $layouts[] = $this->getMapLayoutData();

        // =====================================================================
        // TEIL 9: CALL-TO-ACTION
        // =====================================================================

        $layouts[] = $this->generateLayout('one_column', [
            'label' => '',
            'content' => '<h2>Call-to-Action</h2><p>Auffällige Handlungsaufforderungen für wichtige Konversionen.</p>',
            'background_color' => 'primary',
        ]);

        $layouts[] = $this->getCtaLayoutData();

        // =====================================================================
        // TEIL 10: DATEN & TABELLEN
        // =====================================================================

        $layouts[] = $this->generateLayout('one_column', [
            'label' => '',
            'content' => '<h2>Daten &amp; Tabellen</h2><p>Strukturierte Darstellung von tabellarischen Daten.</p>',
            'background_color' => 'secondary',
        ]);

        $layouts[] = $this->getTableLayoutData();

        return $layouts;
    }

    // =========================================================================
    // LAYOUT DATA GENERATORS
    // =========================================================================

    /**
     * Get Hero layout data
     *
     * @return array<string, mixed>
     */
    private function getHeroLayoutData(): array
    {
        $imageId = $this->getImageId(1);

        return $this->generateLayout('hero', [
            'variant' => 'background',
            'badge' => 'Design System',
            'title' => 'Willkommen auf unserer Website',
            'copy' => 'Wir bieten Ihnen maßgeschneiderte Lösungen für Ihre individuellen Anforderungen. Mit langjähriger Erfahrung und einem engagierten Team stehen wir Ihnen zur Seite.',
            'background_image' => $imageId,
            'overlay_opacity' => 70,
            'cta_primary' => [
                'title' => 'Mehr erfahren',
                'url' => '#features',
                'target' => '',
            ],
            'cta_secondary' => [
                'title' => 'Kontakt aufnehmen',
                'url' => '#kontakt',
                'target' => '',
            ],
        ]);
    }

    /**
     * Get One Column layout data
     *
     * @return array<string, mixed>
     */
    private function getOneColumnLayoutData(): array
    {
        return $this->generateLayout('one_column', [
            'label' => 'Über uns',
            'content' => '<h3>Einspaltiger Inhalt</h3><p>Dies ist ein Beispiel für einen einspaltigen Textblock. Hier können Sie längere Texte, Überschriften und andere Inhalte platzieren. Der Text fließt über die gesamte verfügbare Breite.</p><p>Nutzen Sie dieses Layout für Einleitungstexte, ausführliche Beschreibungen oder wichtige Mitteilungen, die die volle Aufmerksamkeit des Lesers erfordern.</p>',
            'background_color' => 'primary',
        ]);
    }

    /**
     * Get Two Columns layout data
     *
     * @return array<string, mixed>
     */
    private function getTwoColumnsLayoutData(): array
    {
        return $this->generateLayout('two_columns', [
            'column_1' => '<h4>Linke Spalte</h4><p>Dies ist der Inhalt der linken Spalte. Beide Spalten haben die gleiche Breite (50/50). Ideal für vergleichende Darstellungen oder parallele Informationen.</p>',
            'column_2' => '<h4>Rechte Spalte</h4><p>Dies ist der Inhalt der rechten Spalte. Die Spalten passen sich automatisch an die Bildschirmgröße an und werden auf mobilen Geräten untereinander angezeigt.</p>',
            'background_color' => 'secondary',
        ]);
    }

    /**
     * Get Three Columns layout data
     *
     * @return array<string, mixed>
     */
    private function getThreeColumnsLayoutData(): array
    {
        return $this->generateLayout('three_columns', [
            'column_1' => '<h4>Spalte 1</h4><p>Erste von drei gleichmäßig verteilten Spalten. Perfekt für die Darstellung von drei Hauptthemen oder Produkten.</p>',
            'column_2' => '<h4>Spalte 2</h4><p>Die mittlere Spalte eignet sich gut für das wichtigste Element, da sie automatisch im Fokus des Betrachters liegt.</p>',
            'column_3' => '<h4>Spalte 3</h4><p>Die dritte Spalte rundet das Layout ab. Auf kleineren Bildschirmen stapeln sich die Spalten vertikal.</p>',
            'background_color' => 'primary',
        ]);
    }

    /**
     * Get Four Columns layout data
     *
     * @return array<string, mixed>
     */
    private function getFourColumnsLayoutData(): array
    {
        return $this->generateLayout('four_columns', [
            'column_1' => '<h4>Spalte 1</h4><p>Erste von vier Spalten für kompakte Inhalte.</p>',
            'column_2' => '<h4>Spalte 2</h4><p>Zweite Spalte mit kurzem Inhalt.</p>',
            'column_3' => '<h4>Spalte 3</h4><p>Dritte Spalte für weitere Infos.</p>',
            'column_4' => '<h4>Spalte 4</h4><p>Vierte Spalte zum Abschluss.</p>',
            'background_color' => 'tertiary',
        ]);
    }

    /**
     * Get One Third Two Thirds layout data
     *
     * @return array<string, mixed>
     */
    private function getOneThirdTwoThirdsLayoutData(): array
    {
        return $this->generateLayout('one_third_two_thirds', [
            'column_1' => '<h4>Schmal</h4><p>Diese schmale Spalte (1/3) eignet sich für Nebensachen, Navigationen oder ergänzende Informationen.</p>',
            'column_2' => '<h4>Breit</h4><p>Die breite Spalte (2/3) nimmt den Hauptinhalt auf. Dieses asymmetrische Layout lenkt die Aufmerksamkeit auf den wichtigeren Teil und eignet sich gut für Artikel mit Seitenleiste.</p>',
            'background_color' => 'primary',
        ]);
    }

    /**
     * Get Two Thirds One Third layout data
     *
     * @return array<string, mixed>
     */
    private function getTwoThirdsOneThirdLayoutData(): array
    {
        return $this->generateLayout('two_thirds_one_third', [
            'column_1' => '<h4>Hauptinhalt</h4><p>Die breite linke Spalte (2/3) enthält den Hauptinhalt. Dieses Layout ist das Gegenstück zum vorherigen Block und bietet Flexibilität bei der Seitengestaltung.</p>',
            'column_2' => '<h4>Sidebar</h4><p>Die schmalere rechte Spalte (1/3) kann für Zusatzinformationen genutzt werden.</p>',
            'background_color' => 'secondary',
        ]);
    }

    /**
     * Get Two Columns with Images layout data
     *
     * @return array<string, mixed>
     */
    private function getTwoColumnsImagesLayoutData(): array
    {
        $image1 = $this->getImageId(2);
        $image2 = $this->getImageId(3);

        return $this->generateLayout('two_columns_images', [
            'image_1' => $image1,
            'column_1' => '<h4>Projekt A</h4><p>Beschreibung des ersten Projekts mit Bild. Die Karte kombiniert visuelle und textliche Elemente.</p>',
            'image_2' => $image2,
            'column_2' => '<h4>Projekt B</h4><p>Beschreibung des zweiten Projekts. Beide Karten sind gleich groß und wirken ausgewogen.</p>',
            'background_color' => 'primary',
        ]);
    }

    /**
     * Get Accordion layout data
     *
     * @return array<string, mixed>
     */
    private function getAccordionLayoutData(): array
    {
        return $this->generateLayout('accordion', [
            'accordion' => [
                [
                    'title' => 'Was bieten Sie an?',
                    'content' => '<p>Wir bieten ein breites Spektrum an Dienstleistungen, von der Beratung über die Umsetzung bis hin zur langfristigen Betreuung. Unser Fokus liegt auf maßgeschneiderten Lösungen für Ihre spezifischen Anforderungen.</p>',
                ],
                [
                    'title' => 'Wie lange dauert ein typisches Projekt?',
                    'content' => '<p>Die Projektdauer hängt vom Umfang ab. Kleinere Projekte können innerhalb weniger Wochen abgeschlossen werden, während umfangreichere Vorhaben mehrere Monate in Anspruch nehmen können. Wir erstellen immer einen realistischen Zeitplan.</p>',
                ],
                [
                    'title' => 'Wie kann ich Sie kontaktieren?',
                    'content' => '<p>Sie können uns telefonisch, per E-Mail oder über das Kontaktformular auf unserer Website erreichen. Wir melden uns in der Regel innerhalb von 24 Stunden bei Ihnen.</p>',
                ],
                [
                    'title' => 'Gibt es eine Mindestvertragslaufzeit?',
                    'content' => '<p>Nein, wir bieten flexible Vertragsmodelle ohne lange Bindungszeiten. Sie können unsere Dienste auch projektbasiert in Anspruch nehmen.</p>',
                ],
            ],
            'background_color' => 'primary',
        ]);
    }

    /**
     * Get Tabs layout data
     *
     * @return array<string, mixed>
     */
    private function getTabsLayoutData(): array
    {
        return $this->generateLayout('tabs', [
            'title' => '',
            'tabs' => [
                [
                    'title' => 'Übersicht',
                    'icon' => 'eye',
                    'content' => '<h4>Allgemeine Informationen</h4><p>Dies ist der Inhalt des ersten Tabs. Tabs eignen sich hervorragend, um zusammengehörige Informationen zu strukturieren und übersichtlich darzustellen, ohne die Seite mit zu viel Text zu überladen.</p>',
                ],
                [
                    'title' => 'Funktionen',
                    'icon' => 'check',
                    'content' => '<h4>Unsere Funktionen</h4><ul><li>Automatische Anpassung an alle Geräte</li><li>Schnelle Ladezeiten</li><li>Benutzerfreundliche Oberfläche</li><li>Regelmäßige Updates</li></ul>',
                ],
                [
                    'title' => 'Preise',
                    'icon' => 'calendar',
                    'content' => '<h4>Preisgestaltung</h4><p>Unsere Preise richten sich nach dem Umfang Ihrer Anforderungen. Kontaktieren Sie uns für ein individuelles Angebot.</p>',
                ],
            ],
            'background_color' => 'secondary',
        ]);
    }

    /**
     * Get Cards layout data
     *
     * @return array<string, mixed>
     */
    private function getCardsLayoutData(): array
    {
        return $this->generateLayout('cards', [
            'title' => 'Unsere Leistungen',
            'cards' => [
                [
                    'icon' => 'user',
                    'title' => 'Beratung',
                    'content' => 'Professionelle Beratung für Ihre individuellen Anforderungen und Ziele.',
                    'link' => ['title' => 'Mehr erfahren', 'url' => '#', 'target' => ''],
                ],
                [
                    'icon' => 'check',
                    'title' => 'Umsetzung',
                    'content' => 'Zuverlässige Umsetzung Ihrer Projekte mit modernsten Technologien.',
                    'link' => ['title' => 'Details ansehen', 'url' => '#', 'target' => ''],
                ],
                [
                    'icon' => 'phone',
                    'title' => 'Support',
                    'content' => 'Langfristige Betreuung und schneller Support für Ihren Erfolg.',
                    'link' => ['title' => 'Kontakt', 'url' => '#', 'target' => ''],
                ],
            ],
            'columns' => '3',
            'background_color' => 'primary',
        ]);
    }

    /**
     * Get Testimonials layout data
     *
     * @return array<string, mixed>
     */
    private function getTestimonialsLayoutData(): array
    {
        $image1 = $this->getImageId(1);
        $image2 = $this->getImageId(2);

        return $this->generateLayout('testimonials', [
            'title' => 'Das sagen unsere Kunden',
            'testimonials' => [
                [
                    'quote' => 'Die Zusammenarbeit war von Anfang an professionell und unkompliziert. Das Ergebnis hat unsere Erwartungen übertroffen.',
                    'author' => 'Maria Müller',
                    'role' => 'Geschäftsführerin, Beispiel GmbH',
                    'image' => $image1,
                ],
                [
                    'quote' => 'Schnelle Reaktionszeiten und kompetente Beratung. Wir können das Team uneingeschränkt empfehlen.',
                    'author' => 'Thomas Schmidt',
                    'role' => 'Projektleiter, Muster AG',
                    'image' => $image2,
                ],
            ],
            'columns' => '2',
            'background_color' => 'brand-subtle',
        ]);
    }

    /**
     * Get Team layout data
     *
     * @return array<string, mixed>
     */
    private function getTeamLayoutData(): array
    {
        $image1 = $this->getImageId(3);
        $image2 = $this->getImageId(4);
        $image3 = $this->getImageId(5);

        return $this->generateLayout('team', [
            'title' => 'Unser Team',
            'members' => [
                [
                    'image' => $image1,
                    'name' => 'Anna Weber',
                    'position' => 'Geschäftsführerin',
                    'bio' => 'Seit 2015 führt Anna das Unternehmen mit Leidenschaft.',
                    'email' => 'anna@beispiel.de',
                    'linkedin' => 'https://linkedin.com/in/beispiel',
                ],
                [
                    'image' => $image2,
                    'name' => 'Michael Braun',
                    'position' => 'Technischer Leiter',
                    'bio' => 'Michael verantwortet alle technischen Entwicklungen.',
                    'email' => 'michael@beispiel.de',
                    'linkedin' => '',
                ],
                [
                    'image' => $image3,
                    'name' => 'Sarah Klein',
                    'position' => 'Marketing Managerin',
                    'bio' => 'Sarah sorgt für die Sichtbarkeit unserer Projekte.',
                    'email' => 'sarah@beispiel.de',
                    'linkedin' => 'https://linkedin.com/in/beispiel',
                ],
            ],
            'columns' => '3',
            'background_color' => 'primary',
        ]);
    }

    /**
     * Get Stats layout data
     *
     * @return array<string, mixed>
     */
    private function getStatsLayoutData(): array
    {
        return $this->generateLayout('stats', [
            'title' => 'Zahlen & Fakten',
            'stats' => [
                [
                    'number' => 250,
                    'suffix' => '+',
                    'label' => 'Zufriedene Kunden',
                    'icon' => '',
                ],
                [
                    'number' => 15,
                    'suffix' => '',
                    'label' => 'Jahre Erfahrung',
                    'icon' => '',
                ],
                [
                    'number' => 500,
                    'suffix' => '+',
                    'label' => 'Projekte abgeschlossen',
                    'icon' => '',
                ],
                [
                    'number' => 98,
                    'suffix' => '%',
                    'label' => 'Kundenzufriedenheit',
                    'icon' => '',
                ],
            ],
            'background_color' => 'secondary',
        ]);
    }

    /**
     * Get Pricing layout data
     *
     * @return array<string, mixed>
     */
    private function getPricingLayoutData(): array
    {
        return $this->generateLayout('pricing_table', [
            'title' => 'Unsere Pakete',
            'plans' => [
                [
                    'name' => 'Starter',
                    'price' => '49 EUR',
                    'period' => 'Monat',
                    'features' => '<ul><li>Grundfunktionen</li><li>E-Mail Support</li><li>5 Projekte</li><li>1 Benutzer</li></ul>',
                    'cta' => ['title' => 'Auswählen', 'url' => '#', 'target' => ''],
                    'is_featured' => false,
                ],
                [
                    'name' => 'Professional',
                    'price' => '99 EUR',
                    'period' => 'Monat',
                    'features' => '<ul><li>Alle Funktionen</li><li>Prioritäts-Support</li><li>Unbegrenzte Projekte</li><li>5 Benutzer</li><li>API-Zugang</li></ul>',
                    'cta' => ['title' => 'Auswählen', 'url' => '#', 'target' => ''],
                    'is_featured' => true,
                ],
                [
                    'name' => 'Enterprise',
                    'price' => 'Auf Anfrage',
                    'period' => '',
                    'features' => '<ul><li>Individuelle Lösungen</li><li>Dedicated Support</li><li>On-Premise Option</li><li>Unbegrenzte Benutzer</li><li>SLA-Garantie</li></ul>',
                    'cta' => ['title' => 'Kontakt', 'url' => '#', 'target' => ''],
                    'is_featured' => false,
                ],
            ],
            'background_color' => 'secondary',
        ]);
    }

    /**
     * Get Timeline layout data
     *
     * @return array<string, mixed>
     */
    private function getTimelineLayoutData(): array
    {
        $image1 = $this->getImageId(1);
        $image2 = $this->getImageId(2);

        return $this->generateLayout('timeline', [
            'title' => 'Unsere Geschichte',
            'events' => [
                [
                    'year' => '2010',
                    'title' => 'Gründung',
                    'content' => '<p>Unser Unternehmen wurde mit einer Vision gegründet: innovative Lösungen für unsere Kunden zu entwickeln.</p>',
                    'image' => $image1,
                ],
                [
                    'year' => '2015',
                    'title' => 'Expansion',
                    'content' => '<p>Wir haben unser Team erweitert und neue Standorte eröffnet, um näher an unseren Kunden zu sein.</p>',
                    'image' => null,
                ],
                [
                    'year' => '2020',
                    'title' => 'Digitale Transformation',
                    'content' => '<p>Mit der Einführung neuer digitaler Dienste haben wir unseren Service auf ein neues Level gehoben.</p>',
                    'image' => $image2,
                ],
                [
                    'year' => 'Heute',
                    'title' => 'Marktführer',
                    'content' => '<p>Heute sind wir stolz darauf, einer der führenden Anbieter in unserer Branche zu sein.</p>',
                    'image' => null,
                ],
            ],
            'background_color' => 'primary',
        ]);
    }

    /**
     * Get Image layout data
     *
     * @return array<string, mixed>
     */
    private function getImageLayoutData(): array
    {
        $imageId = $this->getImageId(1);

        return $this->generateLayout('image', [
            'image' => $imageId,
            'show_border' => false,
            'show_caption' => true,
            'background_color' => 'primary',
        ]);
    }

    /**
     * Get Gallery layout data
     *
     * @return array<string, mixed>
     */
    private function getGalleryLayoutData(): array
    {
        $images = [];
        for ($i = 1; $i <= 6; $i++) {
            $id = $this->getImageId($i);
            if ($id) {
                $images[] = $id;
            }
        }

        return $this->generateLayout('gallery', [
            'title' => 'Bildergalerie',
            'images' => $images,
            'columns' => '3',
            'background_color' => 'secondary',
        ]);
    }

    /**
     * Get Before/After layout data
     *
     * @return array<string, mixed>
     */
    private function getBeforeAfterLayoutData(): array
    {
        $imageBefore = $this->getImageId(1);
        $imageAfter = $this->getImageId(2);

        return $this->generateLayout('before_after', [
            'title' => 'Vorher vs. Nachher',
            'image_before' => $imageBefore,
            'image_after' => $imageAfter,
            'label_before' => 'Vorher',
            'label_after' => 'Nachher',
            'background_color' => 'primary',
        ]);
    }

    /**
     * Get Video layout data
     *
     * @return array<string, mixed>
     */
    private function getVideoLayoutData(): array
    {
        return $this->generateLayout('video', [
            'source' => 'external',
            'video' => '',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'background_color' => 'primary',
        ]);
    }

    /**
     * Get Logo Slider layout data
     *
     * @return array<string, mixed>
     */
    private function getLogoSliderLayoutData(): array
    {
        $logos = [];
        for ($i = 1; $i <= 6; $i++) {
            $logoId = $this->getLogoId($i);
            if ($logoId) {
                $logos[] = [
                    'logo' => $logoId,
                    'name' => "Partner {$i}",
                    'link' => '',
                ];
            }
        }

        // Fallback to regular images if no logos
        if (empty($logos)) {
            for ($i = 1; $i <= 4; $i++) {
                $imgId = $this->getImageId($i);
                if ($imgId) {
                    $logos[] = [
                        'logo' => $imgId,
                        'name' => "Partner {$i}",
                        'link' => '',
                    ];
                }
            }
        }

        return $this->generateLayout('logo_slider', [
            'title' => 'Unsere Partner',
            'logos' => $logos,
            'autoplay' => true,
            'background_color' => 'secondary',
        ]);
    }

    /**
     * Get CTA layout data
     *
     * @return array<string, mixed>
     */
    private function getCtaLayoutData(): array
    {
        return $this->generateLayout('cta', [
            'title' => 'Bereit loszulegen?',
            'content' => 'Kontaktieren Sie uns noch heute für ein unverbindliches Beratungsgespräch. Wir freuen uns darauf, gemeinsam mit Ihnen Ihre Ziele zu erreichen.',
            'button' => [
                'title' => 'Jetzt Kontakt aufnehmen',
                'url' => '#kontakt',
                'target' => '',
            ],
        ]);
    }

    /**
     * Get Divider layout data
     *
     * @return array<string, mixed>
     */
    private function getDividerLayoutData(): array
    {
        return $this->generateLayout('divider', [
            'background_color' => 'brand-subtle',
        ]);
    }

    /**
     * Get Table layout data
     *
     * @return array<string, mixed>
     */
    private function getTableLayoutData(): array
    {
        return $this->generateLayout('table', [
            'title' => 'Preisübersicht',
            'headers' => [
                ['label' => 'Leistung'],
                ['label' => 'Starter'],
                ['label' => 'Professional'],
            ],
            'rows' => [
                [
                    'cells' => [
                        ['content' => 'Beratung'],
                        ['content' => '2 Std./Monat'],
                        ['content' => 'Unbegrenzt'],
                    ],
                ],
                [
                    'cells' => [
                        ['content' => 'Support'],
                        ['content' => 'E-Mail'],
                        ['content' => 'Telefon & E-Mail'],
                    ],
                ],
                [
                    'cells' => [
                        ['content' => 'Projekte'],
                        ['content' => '5'],
                        ['content' => 'Unbegrenzt'],
                    ],
                ],
                [
                    'cells' => [
                        ['content' => 'Speicherplatz'],
                        ['content' => '10 GB'],
                        ['content' => '100 GB'],
                    ],
                ],
            ],
            'striped' => true,
            'bordered' => false,
            'background_color' => 'primary',
        ]);
    }

    // =========================================================================
    // DESIGN SYSTEM SECTIONS
    // =========================================================================

    /**
     * Generate Typography HTML for styleguide
     */
    private function generateTypographyHtml(): string
    {
        $html = '<div class="space-y-6 p-8 bg-surface-secondary rounded-xl">';

        $typography = [
            ['class' => 'text-display', 'label' => 'Display', 'desc' => '60px / Bold'],
            ['class' => 'text-h1', 'label' => 'Heading 1', 'desc' => '36px / Bold'],
            ['class' => 'text-h2', 'label' => 'Heading 2', 'desc' => '30px / Semibold'],
            ['class' => 'text-h3', 'label' => 'Heading 3', 'desc' => '24px / Semibold'],
            ['class' => 'text-h4', 'label' => 'Heading 4', 'desc' => '20px / Semibold'],
            ['class' => 'text-h5', 'label' => 'Heading 5', 'desc' => '18px / Medium'],
            ['class' => 'text-body-large', 'label' => 'Body Large', 'desc' => '18px / Regular'],
            ['class' => 'text-body', 'label' => 'Body', 'desc' => '16px / Regular'],
            ['class' => 'text-body-small', 'label' => 'Body Small', 'desc' => '14px / Regular'],
            ['class' => 'text-caption', 'label' => 'Caption', 'desc' => '12px / Regular'],
            ['class' => 'text-overline', 'label' => 'Overline', 'desc' => '12px / Semibold / Uppercase'],
            ['class' => 'text-code', 'label' => 'Code', 'desc' => '14px / Mono'],
        ];

        foreach ($typography as $item) {
            $html .= sprintf(
                '<div class="flex flex-col gap-1 pb-4 border-b border-line last:border-b-0 last:pb-0">
                    <span class="text-caption text-content-secondary">.%s — %s</span>
                    <span class="%s text-content">%s</span>
                </div>',
                esc_html($item['class']),
                esc_html($item['desc']),
                esc_attr($item['class']),
                esc_html($item['label'])
            );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate Colors HTML for styleguide
     */
    private function generateColorsHtml(): string
    {
        $html = '<div class="space-y-8">';

        // Surface colors
        $html .= '<div><h4 class="text-h4 mb-4 text-content">Hintergründe (surface-*)</h4>';
        $html .= '<div class="grid grid-cols-2 md:grid-cols-4 gap-4">';
        $surfaces = [
            ['bg-surface', 'surface', 'Standard'],
            ['bg-surface-secondary', 'surface-secondary', 'Sekundär'],
            ['bg-surface-tertiary', 'surface-tertiary', 'Tertiär'],
            ['bg-surface-inverse', 'surface-inverse', 'Invers'],
            ['bg-surface-brand', 'surface-brand', 'Marke'],
            ['bg-surface-brand-subtle', 'surface-brand-subtle', 'Marke Dezent'],
            ['bg-surface-accent', 'surface-accent', 'Akzent'],
            ['bg-surface-accent-subtle', 'surface-accent-subtle', 'Akzent Dezent'],
        ];
        foreach ($surfaces as $item) {
            $textClass = in_array($item[1], ['surface-inverse', 'surface-brand', 'surface-accent'], true) ? 'text-content-inverse' : 'text-content';
            $html .= sprintf(
                '<div class="p-4 rounded-lg %s"><span class="text-caption %s">.%s</span><br><span class="text-body-small %s">%s</span></div>',
                esc_attr($item[0]),
                $textClass,
                esc_html($item[1]),
                $textClass,
                esc_html($item[2])
            );
        }
        $html .= '</div></div>';

        // Text colors
        $html .= '<div><h4 class="text-h4 mb-4 text-content">Text (content-*)</h4>';
        $html .= '<div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-surface-secondary rounded-lg">';
        $texts = [
            ['text-content', 'content', 'Standard'],
            ['text-content-secondary', 'content-secondary', 'Sekundär'],
            ['text-content-tertiary', 'content-tertiary', 'Tertiär'],
            ['text-content-brand', 'content-brand', 'Marke'],
            ['text-content-accent', 'content-accent', 'Akzent'],
            ['text-content-link', 'content-link', 'Link'],
            ['text-content-success', 'content-success', 'Erfolg'],
            ['text-content-error', 'content-error', 'Fehler'],
        ];
        foreach ($texts as $item) {
            $html .= sprintf(
                '<div><span class="text-caption text-content-tertiary">.%s</span><br><span class="text-body %s">%s</span></div>',
                esc_html($item[1]),
                esc_attr($item[0]),
                esc_html($item[2])
            );
        }
        $html .= '</div></div>';

        // Border colors
        $html .= '<div><h4 class="text-h4 mb-4 text-content">Rahmen (line-*)</h4>';
        $html .= '<div class="grid grid-cols-2 md:grid-cols-4 gap-4">';
        $borders = [
            ['border-line', 'line', 'Standard'],
            ['border-line-strong', 'line-strong', 'Stark'],
            ['border-line-subtle', 'line-subtle', 'Dezent'],
            ['border-line-brand', 'line-brand', 'Marke'],
            ['border-line-accent', 'line-accent', 'Akzent'],
            ['border-line-focus', 'line-focus', 'Fokus'],
            ['border-line-success', 'line-success', 'Erfolg'],
            ['border-line-error', 'line-error', 'Fehler'],
        ];
        foreach ($borders as $item) {
            $html .= sprintf(
                '<div class="p-4 rounded-lg bg-surface border-2 %s"><span class="text-caption text-content-secondary">.%s</span><br><span class="text-body-small text-content">%s</span></div>',
                esc_attr($item[0]),
                esc_html($item[1]),
                esc_html($item[2])
            );
        }
        $html .= '</div></div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate Shadows HTML for styleguide
     */
    private function generateShadowsHtml(): string
    {
        $html = '<div class="space-y-6">';
        $html .= '<div class="grid grid-cols-2 md:grid-cols-4 gap-6">';

        $shadows = [
            ['shadow-[var(--shadow-button)]', 'Button', 'Subtiler Schatten für Buttons'],
            ['shadow-[var(--shadow-card)]', 'Card', 'Standard Karten-Schatten'],
            ['shadow-[var(--shadow-card-hover)]', 'Card Hover', 'Erhöhter Schatten bei Hover'],
            ['shadow-[var(--shadow-input)]', 'Input', 'Subtiler Schatten für Eingabefelder'],
            ['shadow-[var(--shadow-dropdown)]', 'Dropdown', 'Schatten für Dropdown-Menüs'],
            ['shadow-[var(--shadow-modal)]', 'Modal', 'Prominenter Schatten für Modals'],
            ['shadow-[var(--shadow-focus-ring)]', 'Focus Ring', 'Fokus-Indikator für Accessibility'],
            ['shadow-[var(--shadow-focus-ring-error)]', 'Focus Error', 'Fokus-Ring bei Fehlerzustand'],
        ];

        foreach ($shadows as $item) {
            $html .= sprintf(
                '<div class="p-6 bg-surface rounded-lg %s"><span class="text-caption text-content-secondary block mb-2">%s</span><span class="text-body-small text-content">%s</span></div>',
                esc_attr($item[0]),
                esc_html($item[1]),
                esc_html($item[2])
            );
        }

        $html .= '</div></div>';

        return $html;
    }

    /**
     * Generate Gradients HTML for styleguide
     */
    private function generateGradientsHtml(): string
    {
        $html = '<div class="space-y-6">';
        $html .= '<div class="grid grid-cols-2 md:grid-cols-3 gap-6">';

        $gradients = [
            ['bg-gradient-to-b from-[var(--gradient-primary-start)] to-[var(--gradient-primary-end)]', 'Primary Button', 'Standard Gradient für primäre Buttons'],
            ['bg-gradient-to-b from-[var(--gradient-primary-hover-start)] to-[var(--gradient-primary-hover-end)]', 'Primary Hover', 'Hover-Zustand für primäre Buttons'],
            ['bg-gradient-to-b from-surface to-surface-secondary', 'Surface', 'Subtiler Übergang zwischen Flächen'],
            ['bg-gradient-to-r from-surface-brand to-surface-accent', 'Brand to Accent', 'Horizontaler Marken-Gradient'],
        ];

        foreach ($gradients as $item) {
            $html .= sprintf(
                '<div class="p-6 rounded-lg %s"><span class="text-caption text-content-inverse block mb-2 drop-shadow">%s</span><span class="text-body-small text-content-inverse drop-shadow">%s</span></div>',
                esc_attr($item[0]),
                esc_html($item[1]),
                esc_html($item[2])
            );
        }

        $html .= '</div></div>';

        return $html;
    }

    /**
     * Generate Spacing HTML for styleguide
     */
    private function generateSpacingHtml(): string
    {
        $html = '<div class="space-y-8">';

        // Spacing scale
        $html .= '<div><h5 class="text-h5 mb-4 text-content">Abstände (Spacing Scale)</h5>';
        $html .= '<div class="flex flex-wrap items-end gap-4 p-6 bg-surface-secondary rounded-lg">';

        $spacings = [
            ['0.5', '2px'],
            ['1', '4px'],
            ['1.5', '6px'],
            ['2', '8px'],
            ['2.5', '10px'],
            ['3', '12px'],
            ['4', '16px'],
            ['5', '20px'],
            ['6', '24px'],
            ['8', '32px'],
            ['10', '40px'],
            ['12', '48px'],
            ['16', '64px'],
        ];

        foreach ($spacings as $item) {
            $html .= sprintf(
                '<div class="flex flex-col items-center"><div class="w-8 bg-surface-brand rounded" style="height: %s;"></div><span class="mt-2 text-caption text-content-secondary">%s</span><span class="text-caption text-content-tertiary">%s</span></div>',
                esc_attr($item[1]),
                esc_html($item[0]),
                esc_html($item[1])
            );
        }
        $html .= '</div></div>';

        // Border radius
        $html .= '<div><h5 class="text-h5 mb-4 text-content">Eckenradien (Border Radius)</h5>';
        $html .= '<div class="flex flex-wrap gap-4 p-6 bg-surface-secondary rounded-lg">';

        $radii = [
            ['rounded-none', 'none', '0px'],
            ['rounded-sm', 'sm', '4px'],
            ['rounded', 'default', '6px'],
            ['rounded-md', 'md', '8px'],
            ['rounded-lg', 'lg', '12px'],
            ['rounded-xl', 'xl', '16px'],
            ['rounded-2xl', '2xl', '20px'],
            ['rounded-full', 'full', '9999px'],
        ];

        foreach ($radii as $item) {
            $html .= sprintf(
                '<div class="flex flex-col items-center"><div class="w-16 h-16 bg-surface-brand %s"></div><span class="mt-2 text-caption text-content-secondary">%s</span><span class="text-caption text-content-tertiary">%s</span></div>',
                esc_attr($item[0]),
                esc_html($item[1]),
                esc_html($item[2])
            );
        }
        $html .= '</div></div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate Components HTML for styleguide
     */
    private function generateComponentsHtml(): string
    {
        $html = '<div class="space-y-12">';

        // Buttons
        $html .= '<div><h4 class="text-h4 mb-4 text-content">Buttons</h4>';
        $html .= '<div class="flex flex-wrap gap-4 p-6 bg-surface-secondary rounded-lg">';
        $html .= $this->renderButton('Primary', 'primary', 'md');
        $html .= $this->renderButton('Secondary', 'secondary', 'md');
        $html .= $this->renderButton('Ghost', 'ghost', 'md');
        $html .= $this->renderButton('Danger', 'danger', 'md');
        $html .= '</div>';
        $html .= '<div class="flex flex-wrap gap-4 p-6 mt-4 bg-surface-secondary rounded-lg">';
        $html .= $this->renderButton('Small', 'primary', 'sm');
        $html .= $this->renderButton('Medium', 'primary', 'md');
        $html .= $this->renderButton('Large', 'primary', 'lg');
        $html .= '</div></div>';

        // Badges
        $html .= '<div><h4 class="text-h4 mb-4 text-content">Badges</h4>';
        $html .= '<div class="flex flex-wrap gap-4 p-6 bg-surface-secondary rounded-lg">';
        $html .= $this->renderBadge('Default', 'gray', 'solid');
        $html .= $this->renderBadge('Brand', 'brand', 'solid');
        $html .= $this->renderBadge('Accent', 'accent', 'solid');
        $html .= $this->renderBadge('Success', 'success', 'solid');
        $html .= $this->renderBadge('Warning', 'warning', 'solid');
        $html .= $this->renderBadge('Error', 'error', 'solid');
        $html .= '</div>';
        $html .= '<div class="flex flex-wrap gap-4 p-6 mt-4 bg-surface-secondary rounded-lg">';
        $html .= $this->renderBadge('Outline', 'brand', 'outline');
        $html .= $this->renderBadge('Subtle', 'brand', 'subtle');
        $html .= '</div></div>';

        // Form Elements
        $html .= '<div><h4 class="text-h4 mb-4 text-content">Formular-Elemente</h4>';
        $html .= '<div class="grid md:grid-cols-2 gap-6 p-6 bg-surface-secondary rounded-lg">';
        $html .= '<div><label class="block text-body-small font-medium text-content mb-2">Text Input</label>';
        $html .= '<input type="text" class="w-full px-4 py-2.5 rounded-lg border border-line bg-surface text-content placeholder:text-content-tertiary focus:outline-none focus:ring-2 focus:ring-line-focus" placeholder="Beispieltext"></div>';
        $html .= '<div><label class="block text-body-small font-medium text-content mb-2">Select</label>';
        $html .= '<select class="w-full px-4 py-2.5 rounded-lg border border-line bg-surface text-content focus:outline-none focus:ring-2 focus:ring-line-focus"><option>Option 1</option><option>Option 2</option></select></div>';
        $html .= '<div><label class="block text-body-small font-medium text-content mb-2">Textarea</label>';
        $html .= '<textarea class="w-full px-4 py-2.5 rounded-lg border border-line bg-surface text-content placeholder:text-content-tertiary focus:outline-none focus:ring-2 focus:ring-line-focus" rows="3" placeholder="Mehrzeiliger Text..."></textarea></div>';
        $html .= '<div class="space-y-4">';
        $html .= '<label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" class="w-5 h-5 rounded border-line text-surface-brand focus:ring-line-focus"><span class="text-body text-content">Checkbox Option</span></label>';
        $html .= '<label class="flex items-center gap-3 cursor-pointer"><input type="radio" name="radio-demo" class="w-5 h-5 border-line text-surface-brand focus:ring-line-focus"><span class="text-body text-content">Radio Option 1</span></label>';
        $html .= '<label class="flex items-center gap-3 cursor-pointer"><input type="radio" name="radio-demo" class="w-5 h-5 border-line text-surface-brand focus:ring-line-focus"><span class="text-body text-content">Radio Option 2</span></label>';
        $html .= '</div>';
        $html .= '</div></div>';

        // Cards
        $html .= '<div><h4 class="text-h4 mb-4 text-content">Cards</h4>';
        $html .= '<div class="grid md:grid-cols-3 gap-6">';
        $html .= '<div class="p-6 bg-surface rounded-xl border border-line shadow-sm"><h5 class="text-h5 text-content mb-2">Card Title</h5><p class="text-body-small text-content-secondary">Eine einfache Karte mit Rahmen und leichtem Schatten.</p></div>';
        $html .= '<div class="p-6 bg-surface-secondary rounded-xl"><h5 class="text-h5 text-content mb-2">Filled Card</h5><p class="text-body-small text-content-secondary">Eine Karte mit Hintergrundfarbe ohne Rahmen.</p></div>';
        $html .= '<div class="p-6 bg-surface-brand rounded-xl text-content-inverse"><h5 class="text-h5 mb-2">Brand Card</h5><p class="text-body-small opacity-90">Eine Karte in Markenfarbe mit invertiertem Text.</p></div>';
        $html .= '</div></div>';

        // Links
        $html .= '<div><h4 class="text-h4 mb-4 text-content">Links</h4>';
        $html .= '<div class="flex flex-wrap items-center gap-6 p-6 bg-surface-secondary rounded-lg">';
        $html .= '<a href="#" class="text-content-link hover:text-content-link-hover underline underline-offset-2 transition-colors">Accent Link</a>';
        $html .= '<a href="#" class="text-content hover:text-content-secondary underline underline-offset-2 transition-colors">Dark Link</a>';
        $html .= '<a href="#" class="text-sm text-content-link hover:text-content-link-hover underline underline-offset-2 transition-colors">Small Link</a>';
        $html .= '<a href="#" class="text-lg text-content-link hover:text-content-link-hover underline underline-offset-2 transition-colors">Large Link</a>';
        $html .= '<span class="text-content-disabled underline underline-offset-2 cursor-not-allowed">Disabled Link</span>';
        $html .= '</div></div>';

        // Icons - using inline SVGs for reliable rendering
        $html .= '<div><h4 class="text-h4 mb-4 text-content">Icons</h4>';
        $html .= '<p class="text-body-small text-content-secondary mb-4">Verfügbare Icons aus <code class="text-code bg-surface-tertiary px-1 rounded">resources/icons/</code>. Icons erben die Textfarbe via <code class="text-code bg-surface-tertiary px-1 rounded">currentColor</code>.</p>';
        $html .= '<div class="grid grid-cols-4 md:grid-cols-8 gap-4 p-6 bg-surface-secondary rounded-lg">';

        $icons = [
            'calendar', 'check', 'chevron', 'chevron-up', 'chevron-down', 'chevron-left', 'chevron-right',
            'close', 'eye', 'lock', 'mail', 'minus', 'phone', 'plus', 'search', 'user', 'warning',
            'facebook', 'instagram', 'linkedin', 'x', 'xing', 'youtube',
        ];

        $iconDir = get_template_directory() . '/resources/icons/';
        foreach ($icons as $name) {
            $iconPath = $iconDir . $name . '.svg';
            $iconSvg = '';
            if (file_exists($iconPath)) {
                $iconSvg = file_get_contents($iconPath);
                // Remove width/height and add classes
                $iconSvg = preg_replace('/\s*(width|height)="[^"]*"/', '', $iconSvg);
                $iconSvg = preg_replace(
                    '/<svg/',
                    '<svg class="w-6 h-6 inline-block shrink-0" aria-hidden="true"',
                    $iconSvg,
                    1
                );
            }
            $html .= sprintf(
                '<div class="flex flex-col items-center gap-2 p-3 text-icon-primary">%s<span class="text-caption text-content-secondary">%s</span></div>',
                $iconSvg,
                esc_html($name)
            );
        }
        $html .= '</div>';

        // Icon color variants
        $html .= '<div class="flex flex-wrap items-center gap-6 p-6 mt-4 bg-surface-secondary rounded-lg">';

        // Helper to get inline SVG
        $getIcon = function ($name, $class = 'w-5 h-5') use ($iconDir) {
            $path = $iconDir . $name . '.svg';
            if (!file_exists($path)) {
				return '';
            }
            $svg = file_get_contents($path);
            $svg = preg_replace('/\s*(width|height)="[^"]*"/', '', $svg);
            return preg_replace('/<svg/', '<svg class="' . $class . ' inline-block shrink-0" aria-hidden="true"', $svg, 1);
        };

        $html .= '<span class="flex items-center gap-2 text-icon-primary">' . $getIcon('check', 'w-4 h-4') . ' Icon mit Text</span>';
        $html .= '<span class="flex items-center gap-2 text-icon-success">' . $getIcon('check') . ' Success</span>';
        $html .= '<span class="flex items-center gap-2 text-icon-error">' . $getIcon('warning') . ' Error</span>';
        $html .= '<span class="flex items-center gap-2 text-icon-brand">' . $getIcon('mail', 'w-6 h-6') . ' Brand</span>';
        $html .= '</div></div>';

        // Toggle
        $html .= '<div><h4 class="text-h4 mb-4 text-content">Toggle / Switch</h4>';
        $html .= '<div class="flex flex-wrap items-center gap-8 p-6 bg-surface-secondary rounded-lg">';

        // Toggle Off
        $html .= '<label class="inline-flex items-center gap-3 cursor-pointer">';
        $html .= '<span class="relative"><input type="checkbox" class="peer sr-only"><span class="block w-11 h-6 rounded-full transition-all duration-200 bg-surface-tertiary peer-checked:bg-surface-accent"></span><span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-surface-on-color shadow-md transition-all duration-200 peer-checked:translate-x-5"></span></span>';
        $html .= '<span class="text-base text-content">Toggle Off</span></label>';

        // Toggle On
        $html .= '<label class="inline-flex items-center gap-3 cursor-pointer">';
        $html .= '<span class="relative"><input type="checkbox" checked class="peer sr-only"><span class="block w-11 h-6 rounded-full transition-all duration-200 bg-surface-tertiary peer-checked:bg-surface-accent"></span><span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-surface-on-color shadow-md transition-all duration-200 peer-checked:translate-x-5"></span></span>';
        $html .= '<span class="text-base text-content">Toggle On</span></label>';

        // Toggle Disabled
        $html .= '<label class="inline-flex items-center gap-3 cursor-not-allowed">';
        $html .= '<span class="relative"><input type="checkbox" disabled class="peer sr-only"><span class="block w-11 h-6 rounded-full bg-surface-disabled"></span><span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-surface-secondary shadow-md"></span></span>';
        $html .= '<span class="text-base text-content-disabled">Disabled</span></label>';

        $html .= '</div></div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate Layout Helpers HTML for styleguide
     */
    private function generateLayoutHelpersHtml(): string
    {
        $html = '<div class="space-y-8">';

        // Grid Component
        $html .= '<div><h4 class="text-h4 mb-4 text-content">Grid Komponente</h4>';
        $html .= '<p class="text-body-small text-content-secondary mb-4">Flexible Spalten-Layouts mit <code class="text-code bg-surface-tertiary px-1 rounded">&lt;x-grid&gt;</code></p>';
        $html .= '<div class="space-y-4">';

        // 2 columns demo
        $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
        $html .= '<div class="p-4 bg-surface-brand-subtle rounded-lg text-center text-content-brand">Spalte 1</div>';
        $html .= '<div class="p-4 bg-surface-brand-subtle rounded-lg text-center text-content-brand">Spalte 2</div>';
        $html .= '</div>';

        // 3 columns demo
        $html .= '<div class="grid grid-cols-1 md:grid-cols-3 gap-4">';
        $html .= '<div class="p-4 bg-surface-accent-subtle rounded-lg text-center text-content-accent">1/3</div>';
        $html .= '<div class="p-4 bg-surface-accent-subtle rounded-lg text-center text-content-accent">1/3</div>';
        $html .= '<div class="p-4 bg-surface-accent-subtle rounded-lg text-center text-content-accent">1/3</div>';
        $html .= '</div>';

        // 1/3 + 2/3 demo
        $html .= '<div class="grid grid-cols-1 md:grid-cols-3 gap-4">';
        $html .= '<div class="p-4 bg-surface-success rounded-lg text-center text-content-success">1/3</div>';
        $html .= '<div class="md:col-span-2 p-4 bg-surface-success rounded-lg text-center text-content-success">2/3</div>';
        $html .= '</div>';

        $html .= '</div></div>';

        // Section Component
        $html .= '<div><h4 class="text-h4 mb-4 text-content">Section Komponente</h4>';
        $html .= '<p class="text-body-small text-content-secondary mb-4">Wrapper für Inhaltsabschnitte mit <code class="text-code bg-surface-tertiary px-1 rounded">&lt;x-section&gt;</code></p>';
        $html .= '<div class="border border-line rounded-lg overflow-hidden">';

        $sectionBgs = [
            ['primary', 'bg-surface', 'Primary (Standard)'],
            ['secondary', 'bg-surface-secondary', 'Secondary'],
            ['tertiary', 'bg-surface-tertiary', 'Tertiary'],
            ['brand', 'bg-surface-brand text-content-inverse', 'Brand'],
            ['brand-subtle', 'bg-surface-brand-subtle', 'Brand Subtle'],
        ];

        foreach ($sectionBgs as $section) {
            $textClass = $section[0] === 'brand' ? 'text-content-inverse' : 'text-content';
            $html .= sprintf(
                '<div class="p-4 %s"><span class="%s text-body-small">background="%s"</span></div>',
                esc_attr($section[1]),
                $textClass,
                esc_html($section[0])
            );
        }

        $html .= '</div></div>';

        // Prose Component
        $html .= '<div><h4 class="text-h4 mb-4 text-content">Prose Komponente</h4>';
        $html .= '<p class="text-body-small text-content-secondary mb-4">Typography-Wrapper für WYSIWYG-Inhalte mit <code class="text-code bg-surface-tertiary px-1 rounded">&lt;x-prose&gt;</code></p>';
        $html .= '<div class="p-6 bg-surface-secondary rounded-lg prose prose-lg max-w-none">';
        $html .= '<h3>Beispiel-Überschrift</h3>';
        $html .= '<p>Dies ist ein Absatz innerhalb der Prose-Komponente. Die Typografie wird automatisch formatiert, inklusive <strong>Fettdruck</strong>, <em>Kursiv</em> und <a href="#">Links</a>.</p>';
        $html .= '<ul><li>Aufzählungspunkt 1</li><li>Aufzählungspunkt 2</li><li>Aufzählungspunkt 3</li></ul>';
        $html .= '<blockquote>Ein Zitat wird ebenfalls automatisch gestylt.</blockquote>';
        $html .= '</div></div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Render a button for the styleguide
     */
    private function renderButton(string $label, string $variant, string $size): string
    {
        $variants = [
            'primary' => 'bg-gradient-to-b from-[var(--gradient-primary-start)] to-[var(--gradient-primary-end)] text-content-inverse border border-line shadow-[var(--shadow-button)] hover:from-[var(--gradient-primary-hover-start)] hover:to-[var(--gradient-primary-hover-end)]',
            'secondary' => 'bg-surface-secondary text-content border border-line shadow-[var(--shadow-button)] hover:border-line-strong',
            'ghost' => 'bg-transparent text-content border border-transparent hover:bg-surface-tertiary',
            'danger' => 'bg-surface-error-strong text-content-on-color border border-transparent shadow-[var(--shadow-button)]',
        ];

        $sizes = [
            'sm' => 'px-3 py-1.5 text-xs min-h-[28px] gap-1 rounded-md',
            'md' => 'px-4 py-2 text-sm min-h-[36px] gap-1.5 rounded-lg',
            'lg' => 'px-6 py-3 text-base min-h-[44px] gap-2 rounded-lg',
        ];

        $variantClass = $variants[$variant] ?? $variants['primary'];
        $sizeClass = $sizes[$size] ?? $sizes['md'];

        return sprintf(
            '<button class="inline-flex items-center justify-center font-semibold transition-all duration-200 no-underline cursor-pointer select-none focus-visible:outline-none %s %s">%s</button>',
            esc_attr($variantClass),
            esc_attr($sizeClass),
            esc_html($label)
        );
    }

    /**
     * Render a badge for the styleguide
     */
    private function renderBadge(string $label, string $variant, string $style): string
    {
        $colors = [
            'gray' => ['solid' => 'bg-surface-secondary text-content', 'outline' => 'border-line text-content', 'subtle' => 'bg-surface-tertiary text-content'],
            'brand' => ['solid' => 'bg-surface-brand text-content-inverse', 'outline' => 'border-line-brand text-content-brand', 'subtle' => 'bg-surface-brand-subtle text-content-brand'],
            'accent' => ['solid' => 'bg-surface-accent text-content-inverse', 'outline' => 'border-line-accent text-content-accent', 'subtle' => 'bg-surface-accent-subtle text-content-accent'],
            'success' => ['solid' => 'bg-surface-success-strong text-content-on-color', 'outline' => 'border-line-success text-content-success', 'subtle' => 'bg-surface-success text-content-success'],
            'warning' => ['solid' => 'bg-surface-warning-strong text-content-on-color', 'outline' => 'border-line-warning text-content-warning', 'subtle' => 'bg-surface-warning text-content-warning'],
            'error' => ['solid' => 'bg-surface-error-strong text-content-on-color', 'outline' => 'border-line-error text-content-error', 'subtle' => 'bg-surface-error text-content-error'],
        ];

        $colorClass = $colors[$variant][$style] ?? $colors['gray']['solid'];
        $borderClass = $style === 'outline' ? 'border' : '';

        return sprintf(
            '<span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full %s %s">%s</span>',
            esc_attr($colorClass),
            $borderClass,
            esc_html($label)
        );
    }

    // =========================================================================
    // ADDITIONAL LAYOUT DATA GENERATORS
    // =========================================================================

    /**
     * Get Contact Form layout data
     *
     * @return array<string, mixed>
     */
    private function getContactFormLayoutData(): array
    {
        return $this->generateLayout('contact_form', [
            'title' => 'Kontaktieren Sie uns',
            'content' => '<p>Haben Sie Fragen oder möchten Sie mehr erfahren? Füllen Sie einfach das Formular aus und wir melden uns schnellstmöglich bei Ihnen.</p>',
            'form_id' => $this->getFirstContactForm7Id(),
            'show_contact_info' => true,
            'background_color' => 'secondary',
        ]);
    }

    /**
     * Get the first Contact Form 7 form ID
     *
     * @return int|string Empty string if CF7 not installed or no forms exist
     */
    private function getFirstContactForm7Id(): int|string
    {
        // Check if CF7 is active
        if (!class_exists('WPCF7')) {
            return '';
        }

        // Query for CF7 forms (they're stored as 'wpcf7_contact_form' post type)
        $forms = get_posts([
            'post_type' => 'wpcf7_contact_form',
            'posts_per_page' => 1,
            'orderby' => 'ID',
            'order' => 'ASC',
            'post_status' => 'publish',
        ]);

        if (!empty($forms)) {
            return $forms[0]->ID;
        }

        return '';
    }

    /**
     * Get Map layout data
     *
     * @return array<string, mixed>
     */
    private function getMapLayoutData(): array
    {
        return $this->generateLayout('map', [
            'title' => 'So finden Sie uns',
            'address' => 'Musterstraße 123, 12345 Berlin, Deutschland',
            'embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2427.924165409515!2d13.404954!3d52.520008!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47a84e373f035901%3A0x42120465b5e3b70!2sBerlin!5e0!3m2!1sde!2sde!4v1234567890',
            'height' => 400,
            'show_directions_link' => true,
            'background_color' => 'primary',
        ]);
    }

    /**
     * Get Posts layout data
     *
     * @return array<string, mixed>
     */
    private function getPostsLayoutData(): array
    {
        return $this->generateLayout('posts', [
            'title' => 'Aktuelle Beiträge',
            'post_type' => 'post',
            'posts_per_page' => 3,
            'category' => '',
            'show_excerpt' => true,
            'show_date' => true,
            'show_author' => false,
            'columns' => 3,
            'background_color' => 'secondary',
        ]);
    }
}
