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
    private const NONCE_CREATE = 'wp-starter-create-styleguide';
    private const NONCE_DISMISS = 'wp-starter-dismiss-welcome';

    /** @var array<string, int> Imported placeholder image IDs */
    private array $imageIds = [];

    public function register(): void
    {
        add_action('after_switch_theme', [$this, 'onThemeActivation']);
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

        // Section: Design System - Typography
        $blocks[] = $this->generateHeading('Design System');
        $blocks[] = $this->generateSeparator();

        $blocks[] = $this->generateHeading('Typografie', 3);
        $blocks[] = $this->generateParagraph('Alle verfügbaren Typografie-Klassen des Design Systems.');
        $blocks[] = $this->generateTypographySection();
        $blocks[] = $this->generateSpacer(60);

        // Section: Design System - Colors
        $blocks[] = $this->generateHeading('Farben', 3);
        $blocks[] = $this->generateParagraph('Die semantischen Farbklassen des Design Systems.');
        $blocks[] = $this->generateColorsSection();
        $blocks[] = $this->generateSpacer(60);

        // Section: Design System - Components
        $blocks[] = $this->generateHeading('Komponenten', 3);
        $blocks[] = $this->generateParagraph('Alle verfügbaren UI-Komponenten.');
        $blocks[] = $this->generateComponentsSection();
        $blocks[] = $this->generateSpacer(60);

        // Section: Blocks
        $blocks[] = $this->generateSeparator();
        $blocks[] = $this->generateHeading('Blöcke');

        // Section: Hero Block
        $blocks[] = $this->generateHeading('Hero-Bereich', 3);
        $blocks[] = $this->generateParagraph('Ein großer Kopfbereich für den Seitenstart mit Hintergrundbild.');
        $blocks[] = $this->generateHeroBlock();
        $blocks[] = $this->generateSpacer(60);

        // Section: Text & Layout Blocks
        $blocks[] = $this->generateHeading('Text & Layout', 3);
        $blocks[] = $this->generateParagraph('Verschiedene Spalten-Layouts für die Inhaltsstrukturierung.');

        // One Column
        $blocks[] = $this->generateHeading('Eine Spalte', 3);
        $blocks[] = $this->generateOneColumnBlock();
        $blocks[] = $this->generateSpacer();

        // Two Columns
        $blocks[] = $this->generateHeading('Zwei Spalten', 3);
        $blocks[] = $this->generateTwoColumnsBlock();
        $blocks[] = $this->generateSpacer();

        // Three Columns
        $blocks[] = $this->generateHeading('Drei Spalten', 3);
        $blocks[] = $this->generateThreeColumnsBlock();
        $blocks[] = $this->generateSpacer();

        // Four Columns
        $blocks[] = $this->generateHeading('Vier Spalten', 3);
        $blocks[] = $this->generateFourColumnsBlock();
        $blocks[] = $this->generateSpacer();

        // 1/3 + 2/3
        $blocks[] = $this->generateHeading('Ein Drittel + Zwei Drittel', 3);
        $blocks[] = $this->generateOneThirdTwoThirdsBlock();
        $blocks[] = $this->generateSpacer();

        // 2/3 + 1/3
        $blocks[] = $this->generateHeading('Zwei Drittel + Ein Drittel', 3);
        $blocks[] = $this->generateTwoThirdsOneThirdBlock();
        $blocks[] = $this->generateSpacer();

        // Two Columns with Images
        $blocks[] = $this->generateHeading('Zwei Spalten mit Bildern', 3);
        $blocks[] = $this->generateTwoColumnsImagesBlock();
        $blocks[] = $this->generateSpacer(60);

        // Section: Interactive Blocks
        $blocks[] = $this->generateSeparator();
        $blocks[] = $this->generateHeading('Interaktive Elemente');

        // Accordion
        $blocks[] = $this->generateHeading('Akkordeon (FAQ)', 3);
        $blocks[] = $this->generateAccordionBlock();
        $blocks[] = $this->generateSpacer();

        // Tabs
        $blocks[] = $this->generateHeading('Tabs', 3);
        $blocks[] = $this->generateTabsBlock();
        $blocks[] = $this->generateSpacer(60);

        // Section: Cards & Content
        $blocks[] = $this->generateSeparator();
        $blocks[] = $this->generateHeading('Karten & Inhalte');

        // Cards
        $blocks[] = $this->generateHeading('Karten / Features', 3);
        $blocks[] = $this->generateCardsBlock();
        $blocks[] = $this->generateSpacer();

        // Testimonials
        $blocks[] = $this->generateHeading('Kundenstimmen', 3);
        $blocks[] = $this->generateTestimonialsBlock();
        $blocks[] = $this->generateSpacer();

        // Team
        $blocks[] = $this->generateHeading('Team', 3);
        $blocks[] = $this->generateTeamBlock();
        $blocks[] = $this->generateSpacer();

        // Stats
        $blocks[] = $this->generateHeading('Statistiken', 3);
        $blocks[] = $this->generateStatsBlock();
        $blocks[] = $this->generateSpacer();

        // Pricing
        $blocks[] = $this->generateHeading('Preistabelle', 3);
        $blocks[] = $this->generatePricingBlock();
        $blocks[] = $this->generateSpacer();

        // Timeline
        $blocks[] = $this->generateHeading('Zeitstrahl', 3);
        $blocks[] = $this->generateTimelineBlock();
        $blocks[] = $this->generateSpacer(60);

        // Section: Media
        $blocks[] = $this->generateSeparator();
        $blocks[] = $this->generateHeading('Medien');

        // Image
        $blocks[] = $this->generateHeading('Bild', 3);
        $blocks[] = $this->generateImageBlock();
        $blocks[] = $this->generateSpacer();

        // Gallery
        $blocks[] = $this->generateHeading('Galerie', 3);
        $blocks[] = $this->generateGalleryBlock();
        $blocks[] = $this->generateSpacer();

        // Before/After
        $blocks[] = $this->generateHeading('Vorher/Nachher', 3);
        $blocks[] = $this->generateBeforeAfterBlock();
        $blocks[] = $this->generateSpacer();

        // Video
        $blocks[] = $this->generateHeading('Video', 3);
        $blocks[] = $this->generateVideoBlock();
        $blocks[] = $this->generateSpacer();

        // Logo Slider
        $blocks[] = $this->generateHeading('Logo-Slider', 3);
        $blocks[] = $this->generateLogoSliderBlock();
        $blocks[] = $this->generateSpacer(60);

        // Section: CTA & Actions
        $blocks[] = $this->generateSeparator();
        $blocks[] = $this->generateHeading('Call-to-Action');

        // CTA
        $blocks[] = $this->generateCtaBlock();
        $blocks[] = $this->generateSpacer(60);

        // Section: Divider
        $blocks[] = $this->generateSeparator();
        $blocks[] = $this->generateHeading('Trenner');
        $blocks[] = $this->generateParagraph('Ein einfacher Trennbereich mit Hintergrundfarbe.');
        $blocks[] = $this->generateDividerBlock();
        $blocks[] = $this->generateSpacer(60);

        // Section: Table
        $blocks[] = $this->generateSeparator();
        $blocks[] = $this->generateHeading('Tabelle');
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
}
