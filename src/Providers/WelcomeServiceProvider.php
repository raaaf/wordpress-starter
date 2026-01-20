<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

/**
 * Welcome Service Provider
 *
 * Shows a welcome notice after theme activation and offers to create
 * a styleguide reference page with all design tokens and block examples.
 */
class WelcomeServiceProvider extends ServiceProvider
{
    private const OPTION_ACTIVATED = 'wp_starter_theme_activated';
    private const OPTION_DISMISSED = 'wp_starter_welcome_dismissed';
    private const OPTION_PAGE_ID = 'wp_starter_styleguide_page_id';
    private const NONCE_CREATE = 'wp-starter-create-styleguide';
    private const NONCE_DISMISS = 'wp-starter-dismiss-welcome';

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
        if (!get_option(self::OPTION_ACTIVATED)) {
            return;
        }

        if (get_option(self::OPTION_DISMISSED)) {
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
     * Create the styleguide page
     */
    private function createStyleguidePage(): int|false
    {
        $content = $this->buildStyleguideContent();

        $pageId = wp_insert_post([
            'post_title' => __('Styleguide', 'wp-starter'),
            'post_content' => $content,
            'post_status' => 'draft',
            'post_type' => 'page',
            'post_author' => get_current_user_id(),
        ]);

        return is_wp_error($pageId) ? false : $pageId;
    }

    /**
     * Build the styleguide page content with Gutenberg blocks
     */
    private function buildStyleguideContent(): string
    {
        $themeJson = $this->loadThemeJson();
        $sections = [];

        // Introduction
        $sections[] = $this->buildIntroSection();

        // Colors
        $sections[] = $this->buildColorsSection($themeJson);

        // Typography
        $sections[] = $this->buildTypographySection($themeJson);

        // Spacing
        $sections[] = $this->buildSpacingSection($themeJson);

        // Components
        $sections[] = $this->buildComponentsSection();

        // Block Overview
        $sections[] = $this->buildBlockOverviewSection();

        return implode("\n\n", array_filter($sections));
    }

    /**
     * Load theme.json data
     *
     * @return array<string, mixed>
     */
    private function loadThemeJson(): array
    {
        $themeJsonPath = get_template_directory() . '/theme.json';

        if (!file_exists($themeJsonPath)) {
            return [];
        }

        $content = file_get_contents($themeJsonPath);
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Build introduction section
     */
    private function buildIntroSection(): string
    {
        $title = __('Styleguide', 'wp-starter');
        $description = __('Diese Seite zeigt alle verfügbaren Design-Elemente des Themes. Nutzen Sie sie als Referenz beim Erstellen von Inhalten.', 'wp-starter');

        return sprintf(
            '<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">%s</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>%s</p>
<!-- /wp:paragraph -->',
            esc_html($title),
            esc_html($description)
        );
    }

    /**
     * Build colors section
     *
     * @param array<string, mixed> $themeJson
     */
    private function buildColorsSection(array $themeJson): string
    {
        $colors = $themeJson['settings']['color']['palette'] ?? [];

        if (empty($colors)) {
            return '';
        }

        $title = __('Farbpalette', 'wp-starter');
        $description = __('Die verfügbaren Farben basieren auf den Design-Tokens des Themes.', 'wp-starter');

        $output = sprintf(
            '<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">%s</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>%s</p>
<!-- /wp:paragraph -->',
            esc_html($title),
            esc_html($description)
        );

        // Group colors
        $backgroundColors = [];
        $textColors = [];
        $statusColors = [];

        foreach ($colors as $color) {
            $slug = $color['slug'] ?? '';
            if (str_starts_with($slug, 'text-')) {
                $textColors[] = $color;
            } elseif (in_array($slug, ['success', 'warning', 'error'], true)) {
                $statusColors[] = $color;
            } else {
                $backgroundColors[] = $color;
            }
        }

        // Background colors
        if (!empty($backgroundColors)) {
            $output .= $this->buildColorGroup(__('Hintergrundfarben', 'wp-starter'), $backgroundColors);
        }

        // Text colors
        if (!empty($textColors)) {
            $output .= $this->buildColorGroup(__('Textfarben', 'wp-starter'), $textColors);
        }

        // Status colors
        if (!empty($statusColors)) {
            $output .= $this->buildColorGroup(__('Status-Farben', 'wp-starter'), $statusColors);
        }

        return $output;
    }

    /**
     * Build a color group
     *
     * @param array<int, array{slug: string, color: string, name: string}> $colors
     */
    private function buildColorGroup(string $title, array $colors): string
    {
        $output = sprintf(
            '

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">%s</h3>
<!-- /wp:heading -->

<!-- wp:html -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 32px;">',
            esc_html($title)
        );

        foreach ($colors as $color) {
            $colorValue = $color['color'] ?? '#ccc';
            $colorName = $color['name'] ?? '';
            $colorSlug = $color['slug'] ?? '';

            // Determine text color based on background
            $isLight = $this->isLightColor($colorSlug);
            $textColor = $isLight ? '#171717' : '#ffffff';

            $output .= sprintf(
                '
    <div style="background: %s; padding: 20px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.1);">
        <div style="color: %s; font-weight: 600;">%s</div>
        <div style="color: %s; font-size: 12px; font-family: monospace; margin-top: 4px;">%s</div>
    </div>',
                esc_attr($colorValue),
                esc_attr($textColor),
                esc_html($colorName),
                esc_attr($textColor),
                esc_html($colorSlug)
            );
        }

        $output .= '
</div>
<!-- /wp:html -->';

        return $output;
    }

    /**
     * Check if a color slug represents a light color
     */
    private function isLightColor(string $slug): bool
    {
        $darkSlugs = ['inverse', 'brand', 'accent', 'text-primary', 'text-secondary', 'text-brand'];
        return !in_array($slug, $darkSlugs, true);
    }

    /**
     * Build typography section
     *
     * @param array<string, mixed> $themeJson
     */
    private function buildTypographySection(array $themeJson): string
    {
        $title = __('Typografie', 'wp-starter');
        $sampleText = __('Der schnelle braune Fuchs springt über den faulen Hund', 'wp-starter');

        $output = sprintf(
            '<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">%s</h2>
<!-- /wp:heading -->',
            esc_html($title)
        );

        // Headings
        $output .= sprintf(
            '

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">%s</h3>
<!-- /wp:heading -->

<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">H1 - %s</h1>
<!-- /wp:heading -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">H2 - %s</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">H3 - %s</h3>
<!-- /wp:heading -->

<!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">H4 - %s</h4>
<!-- /wp:heading -->

<!-- wp:heading {"level":5} -->
<h5 class="wp-block-heading">H5 - %s</h5>
<!-- /wp:heading -->

<!-- wp:heading {"level":6} -->
<h6 class="wp-block-heading">H6 - %s</h6>
<!-- /wp:heading -->',
            esc_html__('Überschriften', 'wp-starter'),
            esc_html($sampleText),
            esc_html($sampleText),
            esc_html($sampleText),
            esc_html($sampleText),
            esc_html($sampleText),
            esc_html($sampleText)
        );

        // Font sizes
        $fontSizes = $themeJson['settings']['typography']['fontSizes'] ?? [];
        if (!empty($fontSizes)) {
            $output .= sprintf(
                '

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">%s</h3>
<!-- /wp:heading -->

<!-- wp:html -->
<div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px;">',
                esc_html__('Schriftgrößen', 'wp-starter')
            );

            foreach ($fontSizes as $size) {
                $sizeName = $size['name'] ?? '';
                $sizeValue = $size['size'] ?? '1rem';
                $sizeSlug = $size['slug'] ?? '';

                $output .= sprintf(
                    '
    <div style="display: flex; align-items: baseline; gap: 16px; padding: 12px; background: #f9f9f9; border-radius: 6px;">
        <code style="min-width: 100px; font-size: 12px;">%s</code>
        <span style="font-size: %s;">%s</span>
    </div>',
                    esc_html($sizeSlug),
                    esc_attr($sizeValue),
                    esc_html($sizeName)
                );
            }

            $output .= '
</div>
<!-- /wp:html -->';
        }

        // Font families
        $fontFamilies = $themeJson['settings']['typography']['fontFamilies'] ?? [];
        if (!empty($fontFamilies)) {
            $output .= sprintf(
                '

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">%s</h3>
<!-- /wp:heading -->

<!-- wp:html -->
<div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px;">',
                esc_html__('Schriftfamilien', 'wp-starter')
            );

            foreach ($fontFamilies as $family) {
                $familyName = $family['name'] ?? '';
                $familyFont = $family['fontFamily'] ?? 'sans-serif';
                $familySlug = $family['slug'] ?? '';

                $output .= sprintf(
                    '
    <div style="padding: 16px; background: #f9f9f9; border-radius: 6px;">
        <div style="font-size: 12px; color: #666; margin-bottom: 8px;"><code>%s</code> - %s</div>
        <div style="font-family: %s; font-size: 24px;">ABCDEFGHIJKLMNOPQRSTUVWXYZ</div>
        <div style="font-family: %s; font-size: 24px;">abcdefghijklmnopqrstuvwxyz</div>
        <div style="font-family: %s; font-size: 24px;">0123456789</div>
    </div>',
                    esc_html($familySlug),
                    esc_html($familyName),
                    esc_attr($familyFont),
                    esc_attr($familyFont),
                    esc_attr($familyFont)
                );
            }

            $output .= '
</div>
<!-- /wp:html -->';
        }

        return $output;
    }

    /**
     * Build spacing section
     *
     * @param array<string, mixed> $themeJson
     */
    private function buildSpacingSection(array $themeJson): string
    {
        $spacings = $themeJson['settings']['spacing']['spacingSizes'] ?? [];

        if (empty($spacings)) {
            return '';
        }

        $title = __('Abstände (Spacing)', 'wp-starter');
        $description = __('Die folgenden Abstandswerte stehen zur Verfügung.', 'wp-starter');

        $output = sprintf(
            '<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">%s</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>%s</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 32px;">',
            esc_html($title),
            esc_html($description)
        );

        foreach ($spacings as $spacing) {
            $spacingName = $spacing['name'] ?? '';
            $spacingSize = $spacing['size'] ?? '0';
            $spacingSlug = $spacing['slug'] ?? '';

            $output .= sprintf(
                '
    <div style="display: flex; align-items: center; gap: 16px;">
        <code style="min-width: 80px; font-size: 12px;">%s</code>
        <span style="min-width: 100px; font-size: 14px;">%s</span>
        <div style="background: #FF6B35; height: 24px; width: %s; border-radius: 4px;"></div>
    </div>',
                esc_html($spacingSlug),
                esc_html($spacingName),
                esc_attr($spacingSize)
            );
        }

        $output .= '
</div>
<!-- /wp:html -->';

        return $output;
    }

    /**
     * Build components section
     */
    private function buildComponentsSection(): string
    {
        $title = __('Komponenten', 'wp-starter');

        $output = sprintf(
            '<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">%s</h2>
<!-- /wp:heading -->',
            esc_html($title)
        );

        // Buttons
        $output .= sprintf(
            '

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">%s</h3>
<!-- /wp:heading -->

<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Primary Button</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">Outline Button</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

<!-- wp:html -->
<div style="margin-top: 24px; margin-bottom: 32px;">
    <p style="font-weight: 600; margin-bottom: 12px;">%s</p>
    <div style="display: flex; flex-wrap: wrap; gap: 12px; padding: 20px; background: #f9f9f9; border-radius: 8px;">
        <button style="display: inline-flex; align-items: center; justify-content: center; font-weight: 600; border-radius: 6px; background: var(--bg-brand, #FF6B35); color: white; padding: 8px 16px; border: none; cursor: pointer;">Primary</button>
        <button style="display: inline-flex; align-items: center; justify-content: center; font-weight: 600; border-radius: 6px; background: white; color: #171717; padding: 8px 16px; border: 1px solid #e5e5e5; cursor: pointer;">Secondary</button>
        <button style="display: inline-flex; align-items: center; justify-content: center; font-weight: 600; border-radius: 6px; background: transparent; color: #171717; padding: 8px 16px; border: none; cursor: pointer;">Ghost</button>
        <button style="display: inline-flex; align-items: center; justify-content: center; font-weight: 600; border-radius: 6px; background: #dc2626; color: white; padding: 8px 16px; border: none; cursor: pointer;">Danger</button>
    </div>
    <p style="font-weight: 600; margin-top: 24px; margin-bottom: 12px;">%s</p>
    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 20px; background: #f9f9f9; border-radius: 8px;">
        <button style="display: inline-flex; align-items: center; justify-content: center; font-weight: 600; border-radius: 6px; background: var(--bg-brand, #FF6B35); color: white; padding: 6px 12px; font-size: 14px; border: none; cursor: pointer;">Small</button>
        <button style="display: inline-flex; align-items: center; justify-content: center; font-weight: 600; border-radius: 6px; background: var(--bg-brand, #FF6B35); color: white; padding: 8px 16px; font-size: 16px; border: none; cursor: pointer;">Medium</button>
        <button style="display: inline-flex; align-items: center; justify-content: center; font-weight: 600; border-radius: 6px; background: var(--bg-brand, #FF6B35); color: white; padding: 12px 24px; font-size: 18px; border: none; cursor: pointer;">Large</button>
    </div>
</div>
<!-- /wp:html -->',
            esc_html__('Buttons', 'wp-starter'),
            esc_html__('Button-Varianten (Blade-Komponente):', 'wp-starter'),
            esc_html__('Button-Größen:', 'wp-starter')
        );

        // Cards
        $output .= sprintf(
            '

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">%s</h3>
<!-- /wp:heading -->

<!-- wp:html -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);">
        <h4 style="margin: 0 0 8px 0; font-size: 18px;">Card Elevated</h4>
        <p style="margin: 0; color: #666;">Beispielinhalt für eine Card mit Schatten.</p>
    </div>
    <div style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e5e5e5;">
        <h4 style="margin: 0 0 8px 0; font-size: 18px;">Card Outlined</h4>
        <p style="margin: 0; color: #666;">Beispielinhalt für eine Card mit Rahmen.</p>
    </div>
    <div style="background: #f5f5f5; border-radius: 12px; padding: 20px;">
        <h4 style="margin: 0 0 8px 0; font-size: 18px;">Card Filled</h4>
        <p style="margin: 0; color: #666;">Beispielinhalt für eine gefüllte Card.</p>
    </div>
</div>
<!-- /wp:html -->',
            esc_html__('Karten (Cards)', 'wp-starter')
        );

        // Badges
        $output .= sprintf(
            '

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">%s</h3>
<!-- /wp:heading -->

<!-- wp:html -->
<div style="display: flex; flex-wrap: wrap; gap: 12px; padding: 20px; background: #f9f9f9; border-radius: 8px; margin-bottom: 32px;">
    <span style="display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; background: var(--bg-brand, #FF6B35); color: white;">Brand</span>
    <span style="display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; background: #dcfce7; color: #166534;">Erfolg</span>
    <span style="display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; background: #fef3c7; color: #92400e;">Warnung</span>
    <span style="display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; background: #fee2e2; color: #dc2626;">Fehler</span>
    <span style="display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; background: #f5f5f5; color: #525252; border: 1px solid #e5e5e5;">Neutral</span>
</div>
<!-- /wp:html -->',
            esc_html__('Badges', 'wp-starter')
        );

        return $output;
    }

    /**
     * Build block overview section
     */
    private function buildBlockOverviewSection(): string
    {
        $title = __('ACF-Blöcke', 'wp-starter');
        $description = __('Die folgenden ACF-Blöcke stehen im Editor unter "Theme Blocks" zur Verfügung.', 'wp-starter');

        $blocks = [
            ['acf/hero', 'Hero-Bereich', 'Großer Kopfbereich mit Titel und Hintergrundbild'],
            ['acf/cta', 'Handlungsaufforderung', 'Auffälliger Bereich mit Button zum Klicken'],
            ['acf/accordion', 'Akkordeon (FAQ)', 'Auf- und zuklappbare Elemente'],
            ['acf/cards', 'Karten / Features', 'Wiederholbare Karten mit Icon und Text'],
            ['acf/testimonials', 'Kundenstimmen', 'Zitate und Bewertungen'],
            ['acf/one-column', 'Eine Spalte', 'Zentrierter Inhaltsbereich'],
            ['acf/two-columns', 'Zwei Spalten', 'Zwei gleichbreite Spalten'],
            ['acf/two-columns-images', 'Zwei Spalten mit Bildern', 'Layout mit Bildern und Text'],
            ['acf/three-columns', 'Drei Spalten', 'Drei gleichbreite Spalten'],
            ['acf/four-columns', 'Vier Spalten', 'Vier gleichbreite Spalten'],
            ['acf/one-third-two-thirds', '1/3 + 2/3 Spalten', 'Schmale linke, breite rechte Spalte'],
            ['acf/two-thirds-one-third', '2/3 + 1/3 Spalten', 'Breite linke, schmale rechte Spalte'],
            ['acf/gallery', 'Bildergalerie', 'Galerie mit Lightbox'],
            ['acf/image', 'Bild', 'Einzelbild mit Bildunterschrift'],
            ['acf/video', 'Video', 'Video aus Mediathek oder YouTube/Vimeo'],
            ['acf/divider', 'Trenner / Abstand', 'Optische Gliederung'],
            ['acf/stats', 'Statistiken', 'Animierte Zahlenwerte'],
            ['acf/timeline', 'Zeitstrahl', 'Chronologische Darstellung'],
            ['acf/pricing-table', 'Preistabelle', 'Tarif-Übersicht'],
            ['acf/team', 'Teammitglieder', 'Team-Darstellung mit Fotos'],
            ['acf/tabs', 'Tabs', 'Tab-basierte Inhalte'],
            ['acf/table', 'Datentabelle', 'Tabelle mit Zeilen und Spalten'],
            ['acf/posts', 'Beiträge', 'Dynamische Blog-Beiträge'],
            ['acf/logo-slider', 'Logo-Slider', 'Partner- und Kundenlogos'],
            ['acf/map', 'Google Maps', 'Karte mit DSGVO-Consent'],
            ['acf/contact-form', 'Kontaktformular', 'Contact Form 7 Integration'],
            ['acf/before-after', 'Vorher/Nachher', 'Bildvergleichs-Slider'],
        ];

        $output = sprintf(
            '<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">%s</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>%s</p>
<!-- /wp:paragraph -->

<!-- wp:table {"hasFixedLayout":true} -->
<figure class="wp-block-table"><table class="has-fixed-layout"><thead><tr><th>%s</th><th>%s</th><th>%s</th></tr></thead><tbody>',
            esc_html($title),
            esc_html($description),
            esc_html__('Block', 'wp-starter'),
            esc_html__('Name', 'wp-starter'),
            esc_html__('Beschreibung', 'wp-starter')
        );

        foreach ($blocks as [$slug, $name, $desc]) {
            $output .= sprintf(
                '<tr><td><code>%s</code></td><td>%s</td><td>%s</td></tr>',
                esc_html($slug),
                esc_html($name),
                esc_html($desc)
            );
        }

        $output .= '</tbody></table></figure>
<!-- /wp:table -->';

        return $output;
    }
}
