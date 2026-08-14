<?php

declare(strict_types=1);

namespace WordpressStarter\Acf;

/**
 * ACF Extended Configuration
 *
 * Configures ACF Extended (FREE) for optimal Flexible Content UX:
 * - Modal Selection: Choose layouts in a visual grid modal
 * - Modal Edit: Edit layouts in a large modal instead of inline
 * - Copy/Paste: Copy layouts between pages
 * - Layout Thumbnails: Visual previews in the modal
 * - Performance Mode: Optimized database queries
 *
 * @see https://www.acf-extended.com/features/fields/flexible-content
 */
class AcfExtended
{
    /**
     * Initialize ACF Extended configuration
     */
    public static function init(): void
    {
        // Only run if ACF Extended is active
        if (!class_exists('ACFE')) {
            return;
        }

        // Configure Flexible Content defaults
        add_filter('acfe/flexible/defaults', [self::class, 'flexibleDefaults']);

        // Enable Performance Mode for optimized queries
        add_filter('acfe/modules/performance', '__return_true');

        // Enable Developer Mode only in debug environment
        add_filter('acfe/modules/dev_mode', [self::class, 'shouldEnableDevMode']);

        // Vorschaubild je Layout aufloesen.
        //
        // Vorher hingen hier zwei Filter, die es in ACF Extended nicht gibt:
        // acfe/flexible/thumbnail/path und .../url. Sie liefen ins Leere, und im
        // Auswahldialog blieb es bei Textkacheln. Der echte Hook heisst
        // acfe/flexible/thumbnail und erwartet eine Anhang-ID oder eine URL.
        add_filter('acfe/flexible/thumbnail', [self::class, 'resolveThumbnail'], 10, 3);

        // Disable modules we don't need
        add_filter('acfe/modules/block_types', '__return_false'); // Using Flexible Content, not blocks
        add_filter('acfe/modules/forms', '__return_false'); // Not using ACFE forms
    }

    /**
     * Default settings for all Flexible Content fields
     *
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    public static function flexibleDefaults(array $defaults): array
    {
        // Achtung, doppelte Quelle: die Page-Builder-Gruppe in FlexibleContent.php
        // setzt dieselben acfe_flexible_*-Schluessel noch einmal direkt am Feld,
        // und Feldwerte schlagen diese Defaults. Wer hier etwas aendert und sich
        // wundert, warum nichts passiert, schaut dort nach.
        //
        // Dieser Filter bleibt, weil er fuer jedes weitere Flexible-Content-Feld
        // gilt, das ein Kundentheme anlegt, ohne alles zu wiederholen.
        return array_merge($defaults, [
            // Modal for adding layouts (visual grid selection)
            'acfe_flexible_modal' => [
                'acfe_flexible_modal_enabled' => true,
                'acfe_flexible_modal_col' => '4',           // 4 columns grid
                'acfe_flexible_modal_categories' => true,   // Show categories
            ],

            // Modal for editing layouts (better UX than inline)
            'acfe_flexible_modal_edit' => [
                'acfe_flexible_modal_edit_enabled' => true,
                'acfe_flexible_modal_edit_size' => 'large',
            ],

            // Copy/Paste layouts between pages
            'acfe_flexible_copy_paste' => true,

            // Layouts collapsed by default (cleaner overview)
            'acfe_flexible_layouts_state' => 'collapse',

            // Stylized "Add Section" button
            'acfe_flexible_stylised_button' => true,

            // Allow editing layout titles from field values
            'acfe_flexible_title_edition' => true,

            // Remove layout from available options when max reached
            'acfe_flexible_remove_button' => [],

            // Show layout count in admin
            'acfe_flexible_layouts_templates' => false,

            // Serverseitige Layout-Vorschau bleibt aus: sie rendert jedes Layout
            // im Editor nach und kostet entsprechend.
            'acfe_flexible_layouts_previews' => false,

            // Vorschaubilder im Auswahldialog. Ohne diese Einstellung ignoriert
            // ACFE acfe_flexible_thumbnail vollstaendig.
            'acfe_flexible_layouts_thumbnails' => true,

            // Empty message when no layouts
            'acfe_flexible_empty_message' => '',

            // Hide clone/copy buttons (simplify UI)
            'acfe_flexible_hide_empty_message' => false,
        ]);
    }

    /**
     * Enable Developer Mode only when WP_DEBUG is true
     */
    public static function shouldEnableDevMode(): bool
    {
        return defined('WP_DEBUG') && WP_DEBUG;
    }

    /**
     * Dateinamen eines Layout-Vorschaubildes zu einer URL aufloesen.
     *
     * Die Layouts tragen nur den Dateinamen, etwa "hero.png". Das haelt
     * FlexibleContent.php lesbar und die Bilder austauschbar. Existiert die
     * Datei nicht, bleibt es bei der Textkachel statt eines toten Bildes.
     *
     * @param mixed                $thumbnail Wert aus acfe_flexible_thumbnail
     * @param array<string, mixed> $field     Das Flexible-Content-Feld
     * @param array<string, mixed> $layout    Das Layout
     */
    public static function resolveThumbnail(mixed $thumbnail, array $field, array $layout): mixed
    {
        if (!is_string($thumbnail) || $thumbnail === '' || str_contains($thumbnail, '://')) {
            return $thumbnail;
        }

        $datei = basename($thumbnail);
        if (!file_exists(get_template_directory() . '/resources/images/layouts/' . $datei)) {
            return false;
        }

        return get_template_directory_uri() . '/resources/images/layouts/' . $datei;
    }
}
