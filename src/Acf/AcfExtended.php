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

        // Configure thumbnail paths for layout previews
        add_filter('acfe/flexible/thumbnail/path', [self::class, 'getThumbnailPath']);
        add_filter('acfe/flexible/thumbnail/url', [self::class, 'getThumbnailUrl']);

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

            // Disable preview render (we use edit-only mode)
            'acfe_flexible_layouts_previews' => false,

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
     * Get filesystem path for layout thumbnails
     */
    public static function getThumbnailPath(): string
    {
        return get_template_directory() . '/resources/images/layouts/';
    }

    /**
     * Get URL for layout thumbnails
     */
    public static function getThumbnailUrl(): string
    {
        return get_template_directory_uri() . '/resources/images/layouts/';
    }
}
