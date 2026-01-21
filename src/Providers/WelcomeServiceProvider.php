<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

/**
 * Welcome Service Provider
 *
 * Shows a welcome notice after theme activation and offers to create
 * a styleguide reference page with real ACF blocks.
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
     * Create the styleguide page with real ACF Gutenberg blocks
     *
     * @return int Post ID on success, 0 on failure
     */
    private function createStyleguidePage(): int
    {
        // Import placeholder images first
        $this->importPlaceholderImages();

        // Generate the block content
        $content = $this->generateStyleguideContent();

        $pageId = wp_insert_post([
            'post_title' => __('Styleguide', 'wp-starter'),
            'post_content' => $content,
            'post_status' => 'private',
            'post_type' => 'page',
            'post_author' => get_current_user_id(),
        ]);

        if (!$pageId || is_wp_error($pageId)) {
            return 0;
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

        // Import logo placeholder images (SVG may not work, use JPG fallback)
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

        $filetype = wp_check_filetype($filename);
        $attachment = [
            'post_mime_type' => $filetype['type'],
            'post_title' => $title,
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attachmentId = wp_insert_attachment($attachment, $newFilePath);
        if (is_wp_error($attachmentId)) {
            return null;
        }

        $attachData = wp_generate_attachment_metadata($attachmentId, $newFilePath);
        wp_update_attachment_metadata($attachmentId, $attachData);

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
     * Generate unique block ID
     *
     * @return string Unique block ID
     */
    private function generateBlockId(): string
    {
        return 'block_' . substr(md5( (string) microtime(true) . wp_rand()), 0, 13);
    }

    /**
     * Generate ACF block markup
     *
     * @param string $blockName Block name without acf/ prefix
     * @param array<string, mixed> $data Block data
     * @param array<string, mixed> $attrs Additional attributes
     * @return string Block markup
     */
    private function generateAcfBlock(string $blockName, array $data, array $attrs = []): string
    {
        $blockAttrs = array_merge([
            'id' => $this->generateBlockId(),
            'name' => "acf/{$blockName}",
            'data' => $data,
            'mode' => 'preview',
        ], $attrs);

        $json = wp_json_encode($blockAttrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return "<!-- wp:acf/{$blockName} {$json} /-->";
    }

    /**
     * Generate a WordPress heading block
     *
     * @param string $text Heading text
     * @param int $level Heading level (1-6)
     * @return string Block markup
     */
    private function generateHeading(string $text, int $level = 2): string
    {
        $tag = "h{$level}";
        return "<!-- wp:heading {\"level\":{$level}} -->\n<{$tag} class=\"wp-block-heading\">{$text}</{$tag}>\n<!-- /wp:heading -->";
    }

    /**
     * Generate a WordPress paragraph block
     *
     * @param string $text Paragraph text
     * @return string Block markup
     */
    private function generateParagraph(string $text): string
    {
        return "<!-- wp:paragraph -->\n<p>{$text}</p>\n<!-- /wp:paragraph -->";
    }

    /**
     * Generate a WordPress separator block
     *
     * @return string Block markup
     */
    private function generateSeparator(): string
    {
        return "<!-- wp:separator {\"className\":\"is-style-wide\"} -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity is-style-wide\"/>\n<!-- /wp:separator -->";
    }

    /**
     * Generate a WordPress spacer block
     *
     * @param int $height Height in pixels
     * @return string Block markup
     */
    private function generateSpacer(int $height = 40): string
    {
        return "<!-- wp:spacer {\"height\":\"{$height}px\"} -->\n<div style=\"height:{$height}px\" aria-hidden=\"true\" class=\"wp-block-spacer\"></div>\n<!-- /wp:spacer -->";
    }

    /**
     * Generate all styleguide content
     *
     * @return string Complete page content with all blocks
     */
    private function generateStyleguideContent(): string
    {
        $blocks = [];

        // =====================================================================
        // TEIL 1: DESIGN SYSTEM - GRUNDLAGEN
        // =====================================================================

        $blocks[] = $this->generateHeading('Design System');
        $blocks[] = $this->generateParagraph('Alle grundlegenden Design-Tokens und Stilregeln des Themes.');
        $blocks[] = $this->generateSeparator();

        // 1.1 Typografie
        $blocks[] = $this->generateHeading('Typografie', 3);
        $blocks[] = $this->generateParagraph('Alle verfügbaren Typografie-Klassen des Design Systems.');
        $blocks[] = $this->generateTypographySection();
        $blocks[] = $this->generateSpacer(60);

        // 1.2 Farben
        $blocks[] = $this->generateHeading('Farben', 3);
        $blocks[] = $this->generateParagraph('Die semantischen Farbklassen für Hintergründe, Text und Rahmen.');
        $blocks[] = $this->generateColorsSection();
        $blocks[] = $this->generateSpacer(60);

        // 1.3 Schatten
        $blocks[] = $this->generateHeading('Schatten', 3);
        $blocks[] = $this->generateParagraph('Definierte Schatten-Tokens für verschiedene UI-Elemente.');
        $blocks[] = $this->generateShadowsSection();
        $blocks[] = $this->generateSpacer(60);

        // 1.4 Verläufe
        $blocks[] = $this->generateHeading('Verläufe (Gradients)', 3);
        $blocks[] = $this->generateParagraph('Farbverläufe für Buttons und Hintergründe.');
        $blocks[] = $this->generateGradientsSection();
        $blocks[] = $this->generateSpacer(60);

        // 1.5 Abstände & Radien
        $blocks[] = $this->generateHeading('Abstände & Radien', 3);
        $blocks[] = $this->generateParagraph('Spacing-Scale und Border-Radius-Werte für konsistente Layouts.');
        $blocks[] = $this->generateSpacingSection();
        $blocks[] = $this->generateSpacer(60);

        // =====================================================================
        // TEIL 2: DESIGN SYSTEM - KOMPONENTEN
        // =====================================================================

        $blocks[] = $this->generateSeparator();
        $blocks[] = $this->generateHeading('UI-Komponenten');
        $blocks[] = $this->generateParagraph('Wiederverwendbare Blade-Komponenten für die Gestaltung.');

        // 2.1 Buttons, Badges, Links, etc.
        $blocks[] = $this->generateComponentsSection();
        $blocks[] = $this->generateSpacer(60);

        // 2.2 Layout-Helfer
        $blocks[] = $this->generateHeading('Layout-Helfer', 3);
        $blocks[] = $this->generateParagraph('Grid, Section und Prose Komponenten für strukturierte Layouts.');
        $blocks[] = $this->generateLayoutHelpersSection();
        $blocks[] = $this->generateSpacer(60);

        // =====================================================================
        // TEIL 3: ACF BLÖCKE - HERO & EINFÜHRUNG
        // =====================================================================

        $blocks[] = $this->generateSeparator();
        $blocks[] = $this->generateHeading('ACF Blöcke');
        $blocks[] = $this->generateParagraph('Alle 28 verfügbaren Gutenberg-Blöcke des Themes.');

        // 3.1 Hero
        $blocks[] = $this->generateHeading('Hero-Bereich', 3);
        $blocks[] = $this->generateParagraph('Ein großer Kopfbereich für den Seitenstart mit Hintergrundbild.');
        $blocks[] = $this->generateHeroBlock();
        $blocks[] = $this->generateSpacer(60);

        // =====================================================================
        // TEIL 4: ACF BLÖCKE - LAYOUT & TEXT
        // =====================================================================

        $blocks[] = $this->generateSeparator();
        $blocks[] = $this->generateHeading('Layout & Text');
        $blocks[] = $this->generateParagraph('Verschiedene Spalten-Layouts für die Inhaltsstrukturierung.');

        // 4.1 Eine Spalte
        $blocks[] = $this->generateHeading('Eine Spalte', 4);
        $blocks[] = $this->generateOneColumnBlock();
        $blocks[] = $this->generateSpacer();

        // 4.2 Zwei Spalten
        $blocks[] = $this->generateHeading('Zwei Spalten', 4);
        $blocks[] = $this->generateTwoColumnsBlock();
        $blocks[] = $this->generateSpacer();

        // 4.3 Drei Spalten
        $blocks[] = $this->generateHeading('Drei Spalten', 4);
        $blocks[] = $this->generateThreeColumnsBlock();
        $blocks[] = $this->generateSpacer();

        // 4.4 Vier Spalten
        $blocks[] = $this->generateHeading('Vier Spalten', 4);
        $blocks[] = $this->generateFourColumnsBlock();
        $blocks[] = $this->generateSpacer();

        // 4.5 Ein Drittel + Zwei Drittel
        $blocks[] = $this->generateHeading('Ein Drittel + Zwei Drittel', 4);
        $blocks[] = $this->generateOneThirdTwoThirdsBlock();
        $blocks[] = $this->generateSpacer();

        // 4.6 Zwei Drittel + Ein Drittel
        $blocks[] = $this->generateHeading('Zwei Drittel + Ein Drittel', 4);
        $blocks[] = $this->generateTwoThirdsOneThirdBlock();
        $blocks[] = $this->generateSpacer();

        // 4.7 Zwei Spalten mit Bildern
        $blocks[] = $this->generateHeading('Zwei Spalten mit Bildern', 4);
        $blocks[] = $this->generateTwoColumnsImagesBlock();
        $blocks[] = $this->generateSpacer();

        // 4.8 Trenner
        $blocks[] = $this->generateHeading('Trenner', 4);
        $blocks[] = $this->generateParagraph('Ein einfacher Trennbereich mit Hintergrundfarbe.');
        $blocks[] = $this->generateDividerBlock();
        $blocks[] = $this->generateSpacer(60);

        // =====================================================================
        // TEIL 5: ACF BLÖCKE - INTERAKTIVE ELEMENTE
        // =====================================================================

        $blocks[] = $this->generateSeparator();
        $blocks[] = $this->generateHeading('Interaktive Elemente');
        $blocks[] = $this->generateParagraph('Blöcke mit Benutzerinteraktion wie Akkordeons, Tabs und Buttons.');

        // 5.1 Button Block
        $blocks[] = $this->generateHeading('Button', 4);
        $blocks[] = $this->generateParagraph('Standalone Button-Block für flexible Platzierung.');
        $blocks[] = $this->generateButtonBlock();
        $blocks[] = $this->generateSpacer();

        // 5.2 Akkordeon
        $blocks[] = $this->generateHeading('Akkordeon (FAQ)', 4);
        $blocks[] = $this->generateAccordionBlock();
        $blocks[] = $this->generateSpacer();

        // 5.3 Tabs
        $blocks[] = $this->generateHeading('Tabs', 4);
        $blocks[] = $this->generateTabsBlock();
        $blocks[] = $this->generateSpacer(60);

        // =====================================================================
        // TEIL 6: ACF BLÖCKE - KARTEN & INHALTE
        // =====================================================================

        $blocks[] = $this->generateSeparator();
        $blocks[] = $this->generateHeading('Karten & Inhalte');
        $blocks[] = $this->generateParagraph('Blöcke zur Darstellung von Features, Team, Preisen und mehr.');

        // 6.1 Karten / Features
        $blocks[] = $this->generateHeading('Karten / Features', 4);
        $blocks[] = $this->generateCardsBlock();
        $blocks[] = $this->generateSpacer();

        // 6.2 Kundenstimmen
        $blocks[] = $this->generateHeading('Kundenstimmen', 4);
        $blocks[] = $this->generateTestimonialsBlock();
        $blocks[] = $this->generateSpacer();

        // 6.3 Team
        $blocks[] = $this->generateHeading('Team', 4);
        $blocks[] = $this->generateTeamBlock();
        $blocks[] = $this->generateSpacer();

        // 6.4 Statistiken
        $blocks[] = $this->generateHeading('Statistiken', 4);
        $blocks[] = $this->generateStatsBlock();
        $blocks[] = $this->generateSpacer();

        // 6.5 Preistabelle
        $blocks[] = $this->generateHeading('Preistabelle', 4);
        $blocks[] = $this->generatePricingBlock();
        $blocks[] = $this->generateSpacer();

        // 6.6 Zeitstrahl
        $blocks[] = $this->generateHeading('Zeitstrahl', 4);
        $blocks[] = $this->generateTimelineBlock();
        $blocks[] = $this->generateSpacer();

        // 6.7 Beiträge
        $blocks[] = $this->generateHeading('Beiträge / Blog', 4);
        $blocks[] = $this->generateParagraph('Zeigt automatisch die neuesten Beiträge an.');
        $blocks[] = $this->generatePostsBlock();
        $blocks[] = $this->generateSpacer(60);

        // =====================================================================
        // TEIL 7: ACF BLÖCKE - MEDIEN
        // =====================================================================

        $blocks[] = $this->generateSeparator();
        $blocks[] = $this->generateHeading('Medien');
        $blocks[] = $this->generateParagraph('Blöcke für Bilder, Videos und Galerien.');

        // 7.1 Bild
        $blocks[] = $this->generateHeading('Bild', 4);
        $blocks[] = $this->generateImageBlock();
        $blocks[] = $this->generateSpacer();

        // 7.2 Galerie
        $blocks[] = $this->generateHeading('Galerie', 4);
        $blocks[] = $this->generateGalleryBlock();
        $blocks[] = $this->generateSpacer();

        // 7.3 Vorher/Nachher
        $blocks[] = $this->generateHeading('Vorher/Nachher', 4);
        $blocks[] = $this->generateBeforeAfterBlock();
        $blocks[] = $this->generateSpacer();

        // 7.4 Video
        $blocks[] = $this->generateHeading('Video', 4);
        $blocks[] = $this->generateVideoBlock();
        $blocks[] = $this->generateSpacer();

        // 7.5 Logo-Slider
        $blocks[] = $this->generateHeading('Logo-Slider', 4);
        $blocks[] = $this->generateLogoSliderBlock();
        $blocks[] = $this->generateSpacer(60);

        // =====================================================================
        // TEIL 8: ACF BLÖCKE - KONTAKT & STANDORT
        // =====================================================================

        $blocks[] = $this->generateSeparator();
        $blocks[] = $this->generateHeading('Kontakt & Standort');
        $blocks[] = $this->generateParagraph('Blöcke für Kontaktformulare und Kartenansichten.');

        // 8.1 Kontaktformular
        $blocks[] = $this->generateHeading('Kontaktformular', 4);
        $blocks[] = $this->generateParagraph('Integration mit Contact Form 7. Zeigt Kontaktdaten aus den Theme-Optionen.');
        $blocks[] = $this->generateContactFormBlock();
        $blocks[] = $this->generateSpacer();

        // 8.2 Karte
        $blocks[] = $this->generateHeading('Google Maps Karte', 4);
        $blocks[] = $this->generateParagraph('DSGVO-konform: Karte wird erst nach Zustimmung geladen.');
        $blocks[] = $this->generateMapBlock();
        $blocks[] = $this->generateSpacer(60);

        // =====================================================================
        // TEIL 9: ACF BLÖCKE - CALL-TO-ACTION
        // =====================================================================

        $blocks[] = $this->generateSeparator();
        $blocks[] = $this->generateHeading('Call-to-Action');
        $blocks[] = $this->generateParagraph('Auffällige Handlungsaufforderungen für wichtige Konversionen.');

        // 9.1 CTA Block
        $blocks[] = $this->generateCtaBlock();
        $blocks[] = $this->generateSpacer(60);

        // =====================================================================
        // TEIL 10: ACF BLÖCKE - DATEN & TABELLEN
        // =====================================================================

        $blocks[] = $this->generateSeparator();
        $blocks[] = $this->generateHeading('Daten & Tabellen');
        $blocks[] = $this->generateParagraph('Strukturierte Darstellung von tabellarischen Daten.');

        // 10.1 Tabelle
        $blocks[] = $this->generateTableBlock();

        return implode("\n\n", $blocks);
    }

    // =========================================================================
    // BLOCK GENERATORS
    // =========================================================================

    /**
     * Generate Hero block
     */
    private function generateHeroBlock(): string
    {
        $imageId = $this->getImageId(1);

        return $this->generateAcfBlock('hero', [
            'title' => 'Willkommen auf unserer Website',
            'subtitle' => 'Ihr Partner für innovative Lösungen',
            'content' => '<p>Wir bieten Ihnen maßgeschneiderte Lösungen für Ihre individuellen Anforderungen. Mit langjähriger Erfahrung und einem engagierten Team stehen wir Ihnen zur Seite.</p>',
            'background_image' => $imageId,
            'cta' => [
                'title' => 'Mehr erfahren',
                'url' => '#',
                'target' => '',
            ],
            'background_color' => 'primary',
        ], ['align' => 'full']);
    }

    /**
     * Generate One Column block
     */
    private function generateOneColumnBlock(): string
    {
        return $this->generateAcfBlock('one-column', [
            'label' => 'Über uns',
            'content' => '<h3>Einspaltiger Inhalt</h3><p>Dies ist ein Beispiel für einen einspaltigen Textblock. Hier können Sie längere Texte, Überschriften und andere Inhalte platzieren. Der Text fließt über die gesamte verfügbare Breite.</p><p>Nutzen Sie dieses Layout für Einleitungstexte, ausführliche Beschreibungen oder wichtige Mitteilungen, die die volle Aufmerksamkeit des Lesers erfordern.</p>',
            'background_color' => 'primary',
        ]);
    }

    /**
     * Generate Two Columns block
     */
    private function generateTwoColumnsBlock(): string
    {
        return $this->generateAcfBlock('two-columns', [
            'column_1' => '<h4>Linke Spalte</h4><p>Dies ist der Inhalt der linken Spalte. Beide Spalten haben die gleiche Breite (50/50). Ideal für vergleichende Darstellungen oder parallele Informationen.</p>',
            'column_2' => '<h4>Rechte Spalte</h4><p>Dies ist der Inhalt der rechten Spalte. Die Spalten passen sich automatisch an die Bildschirmgröße an und werden auf mobilen Geräten untereinander angezeigt.</p>',
            'background_color' => 'secondary',
        ]);
    }

    /**
     * Generate Three Columns block
     */
    private function generateThreeColumnsBlock(): string
    {
        return $this->generateAcfBlock('three-columns', [
            'column_1' => '<h4>Spalte 1</h4><p>Erste von drei gleichmäßig verteilten Spalten. Perfekt für die Darstellung von drei Hauptthemen oder Produkten.</p>',
            'column_2' => '<h4>Spalte 2</h4><p>Die mittlere Spalte eignet sich gut für das wichtigste Element, da sie automatisch im Fokus des Betrachters liegt.</p>',
            'column_3' => '<h4>Spalte 3</h4><p>Die dritte Spalte rundet das Layout ab. Auf kleineren Bildschirmen stapeln sich die Spalten vertikal.</p>',
            'background_color' => 'primary',
        ]);
    }

    /**
     * Generate Four Columns block
     */
    private function generateFourColumnsBlock(): string
    {
        return $this->generateAcfBlock('four-columns', [
            'column_1' => '<h4>Spalte 1</h4><p>Erste von vier Spalten für kompakte Inhalte.</p>',
            'column_2' => '<h4>Spalte 2</h4><p>Zweite Spalte mit kurzem Inhalt.</p>',
            'column_3' => '<h4>Spalte 3</h4><p>Dritte Spalte für weitere Infos.</p>',
            'column_4' => '<h4>Spalte 4</h4><p>Vierte Spalte zum Abschluss.</p>',
            'background_color' => 'tertiary',
        ]);
    }

    /**
     * Generate One Third Two Thirds block
     */
    private function generateOneThirdTwoThirdsBlock(): string
    {
        return $this->generateAcfBlock('one-third-two-thirds', [
            'column_1' => '<h4>Schmal</h4><p>Diese schmale Spalte (1/3) eignet sich für Nebensachen, Navigationen oder ergänzende Informationen.</p>',
            'column_2' => '<h4>Breit</h4><p>Die breite Spalte (2/3) nimmt den Hauptinhalt auf. Dieses asymmetrische Layout lenkt die Aufmerksamkeit auf den wichtigeren Teil und eignet sich gut für Artikel mit Seitenleiste.</p>',
            'background_color' => 'primary',
        ]);
    }

    /**
     * Generate Two Thirds One Third block
     */
    private function generateTwoThirdsOneThirdBlock(): string
    {
        return $this->generateAcfBlock('two-thirds-one-third', [
            'column_1' => '<h4>Hauptinhalt</h4><p>Die breite linke Spalte (2/3) enthält den Hauptinhalt. Dieses Layout ist das Gegenstück zum vorherigen Block und bietet Flexibilität bei der Seitengestaltung.</p>',
            'column_2' => '<h4>Sidebar</h4><p>Die schmalere rechte Spalte (1/3) kann für Zusatzinformationen genutzt werden.</p>',
            'background_color' => 'secondary',
        ]);
    }

    /**
     * Generate Two Columns with Images block
     */
    private function generateTwoColumnsImagesBlock(): string
    {
        $image1 = $this->getImageId(2);
        $image2 = $this->getImageId(3);

        return $this->generateAcfBlock('two-columns-images', [
            'image_1' => $image1,
            'column_1' => '<h4>Projekt A</h4><p>Beschreibung des ersten Projekts mit Bild. Die Karte kombiniert visuelle und textliche Elemente.</p>',
            'image_2' => $image2,
            'column_2' => '<h4>Projekt B</h4><p>Beschreibung des zweiten Projekts. Beide Karten sind gleich groß und wirken ausgewogen.</p>',
            'background_color' => 'primary',
        ]);
    }

    /**
     * Generate Accordion block
     */
    private function generateAccordionBlock(): string
    {
        return $this->generateAcfBlock('accordion', [
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
     * Generate Tabs block
     */
    private function generateTabsBlock(): string
    {
        return $this->generateAcfBlock('tabs', [
            'title' => '',
            'tabs' => [
                [
                    'title' => 'Übersicht',
                    'content' => '<h4>Allgemeine Informationen</h4><p>Dies ist der Inhalt des ersten Tabs. Tabs eignen sich hervorragend, um zusammengehörige Informationen zu strukturieren und übersichtlich darzustellen, ohne die Seite mit zu viel Text zu überladen.</p>',
                ],
                [
                    'title' => 'Funktionen',
                    'content' => '<h4>Unsere Funktionen</h4><ul><li>Automatische Anpassung an alle Geräte</li><li>Schnelle Ladezeiten</li><li>Benutzerfreundliche Oberfläche</li><li>Regelmäßige Updates</li></ul>',
                ],
                [
                    'title' => 'Preise',
                    'content' => '<h4>Preisgestaltung</h4><p>Unsere Preise richten sich nach dem Umfang Ihrer Anforderungen. Kontaktieren Sie uns für ein individuelles Angebot.</p>',
                ],
            ],
            'background_color' => 'secondary',
        ]);
    }

    /**
     * Generate Cards block
     */
    private function generateCardsBlock(): string
    {
        $icon1 = $this->getImageId(4);
        $icon2 = $this->getImageId(5);
        $icon3 = $this->getImageId(6);

        return $this->generateAcfBlock('cards', [
            'title' => 'Unsere Leistungen',
            'cards' => [
                [
                    'icon' => $icon1,
                    'title' => 'Beratung',
                    'content' => 'Professionelle Beratung für Ihre individuellen Anforderungen und Ziele.',
                    'link' => ['title' => 'Mehr erfahren', 'url' => '#', 'target' => ''],
                ],
                [
                    'icon' => $icon2,
                    'title' => 'Umsetzung',
                    'content' => 'Zuverlässige Umsetzung Ihrer Projekte mit modernsten Technologien.',
                    'link' => ['title' => 'Details ansehen', 'url' => '#', 'target' => ''],
                ],
                [
                    'icon' => $icon3,
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
     * Generate Testimonials block
     */
    private function generateTestimonialsBlock(): string
    {
        $image1 = $this->getImageId(1);
        $image2 = $this->getImageId(2);

        return $this->generateAcfBlock('testimonials', [
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
     * Generate Team block
     */
    private function generateTeamBlock(): string
    {
        $image1 = $this->getImageId(3);
        $image2 = $this->getImageId(4);
        $image3 = $this->getImageId(5);

        return $this->generateAcfBlock('team', [
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
     * Generate Stats block
     */
    private function generateStatsBlock(): string
    {
        return $this->generateAcfBlock('stats', [
            'title' => 'Zahlen & Fakten',
            'stats' => [
                [
                    'number' => 250,
                    'suffix' => '+',
                    'label' => 'Zufriedene Kunden',
                    'icon' => '👥',
                ],
                [
                    'number' => 15,
                    'suffix' => '',
                    'label' => 'Jahre Erfahrung',
                    'icon' => '📅',
                ],
                [
                    'number' => 500,
                    'suffix' => '+',
                    'label' => 'Projekte abgeschlossen',
                    'icon' => '✅',
                ],
                [
                    'number' => 98,
                    'suffix' => '%',
                    'label' => 'Kundenzufriedenheit',
                    'icon' => '⭐',
                ],
            ],
            'background_color' => 'brand',
        ]);
    }

    /**
     * Generate Pricing block
     */
    private function generatePricingBlock(): string
    {
        return $this->generateAcfBlock('pricing-table', [
            'title' => 'Unsere Pakete',
            'plans' => [
                [
                    'name' => 'Starter',
                    'price' => '49€',
                    'period' => 'Monat',
                    'features' => '<ul><li>Grundfunktionen</li><li>E-Mail Support</li><li>5 Projekte</li><li>1 Benutzer</li></ul>',
                    'cta' => ['title' => 'Auswählen', 'url' => '#', 'target' => ''],
                    'is_featured' => false,
                ],
                [
                    'name' => 'Professional',
                    'price' => '99€',
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
     * Generate Timeline block
     */
    private function generateTimelineBlock(): string
    {
        $image1 = $this->getImageId(1);
        $image2 = $this->getImageId(2);

        return $this->generateAcfBlock('timeline', [
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
     * Generate Image block
     */
    private function generateImageBlock(): string
    {
        $imageId = $this->getImageId(1);

        return $this->generateAcfBlock('image', [
            'image' => $imageId,
            'show_border' => true,
            'show_caption' => true,
            'background_color' => 'primary',
        ]);
    }

    /**
     * Generate Gallery block
     */
    private function generateGalleryBlock(): string
    {
        $images = [];
        for ($i = 1; $i <= 6; $i++) {
            $id = $this->getImageId($i);
            if ($id) {
                $images[] = $id;
            }
        }

        return $this->generateAcfBlock('gallery', [
            'title' => 'Bildergalerie',
            'images' => $images,
            'columns' => '3',
            'background_color' => 'secondary',
        ]);
    }

    /**
     * Generate Before/After block
     */
    private function generateBeforeAfterBlock(): string
    {
        $imageBefore = $this->getImageId(1);
        $imageAfter = $this->getImageId(2);

        return $this->generateAcfBlock('before-after', [
            'title' => 'Vorher vs. Nachher',
            'image_before' => $imageBefore,
            'image_after' => $imageAfter,
            'label_before' => 'Vorher',
            'label_after' => 'Nachher',
            'background_color' => 'primary',
        ]);
    }

    /**
     * Generate Video block
     */
    private function generateVideoBlock(): string
    {
        $poster = $this->getImageId(3);

        return $this->generateAcfBlock('video', [
            'source' => 'external',
            'video' => '',
            'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'poster' => $poster,
            'background_color' => 'primary',
        ]);
    }

    /**
     * Generate Logo Slider block
     */
    private function generateLogoSliderBlock(): string
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

        return $this->generateAcfBlock('logo-slider', [
            'title' => 'Unsere Partner',
            'logos' => $logos,
            'autoplay' => true,
            'background_color' => 'secondary',
        ]);
    }

    /**
     * Generate CTA block
     */
    private function generateCtaBlock(): string
    {
        return $this->generateAcfBlock('cta', [
            'title' => 'Bereit loszulegen?',
            'content' => '<p>Kontaktieren Sie uns noch heute für ein unverbindliches Beratungsgespräch. Wir freuen uns darauf, gemeinsam mit Ihnen Ihre Ziele zu erreichen.</p>',
            'cta' => [
                'title' => 'Jetzt Kontakt aufnehmen',
                'url' => '#',
                'target' => '',
            ],
            'background_color' => 'brand',
        ]);
    }

    /**
     * Generate Divider block
     */
    private function generateDividerBlock(): string
    {
        return $this->generateAcfBlock('divider', [
            'background_color' => 'brand-subtle',
        ]);
    }

    /**
     * Generate Table block
     */
    private function generateTableBlock(): string
    {
        return $this->generateAcfBlock('table', [
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
     * Generate Typography section
     */
    private function generateTypographySection(): string
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

        return "<!-- wp:html -->\n{$html}\n<!-- /wp:html -->";
    }

    /**
     * Generate Colors section
     */
    private function generateColorsSection(): string
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

        return "<!-- wp:html -->\n{$html}\n<!-- /wp:html -->";
    }

    /**
     * Generate Shadows section
     */
    private function generateShadowsSection(): string
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

        return "<!-- wp:html -->\n{$html}\n<!-- /wp:html -->";
    }

    /**
     * Generate Gradients section
     */
    private function generateGradientsSection(): string
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

        return "<!-- wp:html -->\n{$html}\n<!-- /wp:html -->";
    }

    /**
     * Generate Spacing section
     */
    private function generateSpacingSection(): string
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

        return "<!-- wp:html -->\n{$html}\n<!-- /wp:html -->";
    }

    /**
     * Generate Components section
     */
    private function generateComponentsSection(): string
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

        // Icons
        $html .= '<div><h4 class="text-h4 mb-4 text-content">Icons</h4>';
        $html .= '<p class="text-body-small text-content-secondary mb-4">Verfügbare Icons aus <code class="text-code bg-surface-tertiary px-1 rounded">resources/icons/</code>. Icons erben die Textfarbe via <code class="text-code bg-surface-tertiary px-1 rounded">currentColor</code>.</p>';
        $html .= '<div class="grid grid-cols-4 md:grid-cols-8 gap-4 p-6 bg-surface-secondary rounded-lg">';

        $icons = [
            'calendar' => 'Kalender',
            'check' => 'Häkchen',
            'chevron' => 'Pfeil',
            'close' => 'Schließen',
            'eye' => 'Auge',
            'lock' => 'Schloss',
            'mail' => 'E-Mail',
            'minus' => 'Minus',
            'phone' => 'Telefon',
            'plus' => 'Plus',
            'search' => 'Suche',
            'user' => 'Benutzer',
            'warning' => 'Warnung',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'linkedin' => 'LinkedIn',
            'x' => 'X (Twitter)',
            'xing' => 'Xing',
            'youtube' => 'YouTube',
        ];

        foreach ($icons as $name => $label) {
            $html .= sprintf(
                '<div class="flex flex-col items-center gap-2 p-3"><svg class="w-6 h-6 text-content" aria-hidden="true"><use href="#icon-%s"></use></svg><span class="text-caption text-content-secondary">%s</span></div>',
                esc_attr($name),
                esc_html($name)
            );
        }
        $html .= '</div>';
        $html .= '<div class="flex flex-wrap items-center gap-6 p-6 mt-4 bg-surface-secondary rounded-lg">';
        $html .= '<span class="flex items-center gap-2 text-content"><svg class="w-4 h-4"><use href="#icon-check"></use></svg> Icon mit Text</span>';
        $html .= '<span class="flex items-center gap-2 text-content-success"><svg class="w-5 h-5"><use href="#icon-check"></use></svg> Success</span>';
        $html .= '<span class="flex items-center gap-2 text-content-error"><svg class="w-5 h-5"><use href="#icon-warning"></use></svg> Error</span>';
        $html .= '<span class="flex items-center gap-2 text-content-brand"><svg class="w-6 h-6"><use href="#icon-mail"></use></svg> Brand</span>';
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

        return "<!-- wp:html -->\n{$html}\n<!-- /wp:html -->";
    }

    /**
     * Generate Layout Helpers section
     */
    private function generateLayoutHelpersSection(): string
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

        return "<!-- wp:html -->\n{$html}\n<!-- /wp:html -->";
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
    // ADDITIONAL BLOCK GENERATORS
    // =========================================================================

    /**
     * Generate Button block
     */
    private function generateButtonBlock(): string
    {
        return $this->generateAcfBlock('button', [
            'button' => [
                'title' => 'Jetzt starten',
                'url' => '#',
                'target' => '',
            ],
            'variant' => 'primary',
            'size' => 'md',
            'full_width' => false,
        ]);
    }

    /**
     * Generate Contact Form block
     */
    private function generateContactFormBlock(): string
    {
        return $this->generateAcfBlock('contact-form', [
            'title' => 'Kontaktieren Sie uns',
            'content' => '<p>Haben Sie Fragen oder möchten Sie mehr erfahren? Füllen Sie einfach das Formular aus und wir melden uns schnellstmöglich bei Ihnen.</p>',
            'form_id' => '',
            'show_contact_info' => true,
            'background_color' => 'secondary',
        ]);
    }

    /**
     * Generate Map block
     */
    private function generateMapBlock(): string
    {
        return $this->generateAcfBlock('map', [
            'title' => 'So finden Sie uns',
            'address' => 'Musterstraße 123, 12345 Berlin, Deutschland',
            'embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2427.924165409515!2d13.404954!3d52.520008!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47a84e373f035901%3A0x42120465b5e3b70!2sBerlin!5e0!3m2!1sde!2sde!4v1234567890',
            'height' => 400,
            'show_directions_link' => true,
            'background_color' => 'primary',
        ]);
    }

    /**
     * Generate Posts block
     */
    private function generatePostsBlock(): string
    {
        return $this->generateAcfBlock('posts', [
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
