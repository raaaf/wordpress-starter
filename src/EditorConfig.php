<?php

declare(strict_types=1);

namespace WordpressStarter;

/**
 * Disable Gutenberg and configure Classic Editor
 *
 * This class completely disables the WordPress Block Editor (Gutenberg)
 * in favor of the Classic Editor with ACF Flexible Content.
 */
class EditorConfig
{
    /**
     * Initialize all editor configuration
     */
    public static function init(): void
    {
        // Disable Block Editor for all post types
        add_filter('use_block_editor_for_post', '__return_false', 100);
        add_filter('use_block_editor_for_post_type', '__return_false', 100);

        // Disable Block-based Widgets Editor
        add_filter('use_widgets_block_editor', '__return_false');

        // Remove Block Patterns support
        add_action('after_setup_theme', [self::class, 'removeBlockPatterns'], 100);

        // Remove Block Editor assets from frontend
        add_action('wp_enqueue_scripts', [self::class, 'dequeueBlockAssets'], 100);

        // Remove Gutenberg-related admin menus
        add_action('admin_menu', [self::class, 'removeGutenbergMenuItems'], 999);

        // Remove Full Site Editing features
        add_action('init', [self::class, 'disableFullSiteEditing'], 100);
    }

    /**
     * Remove Block Patterns support
     */
    public static function removeBlockPatterns(): void
    {
        remove_theme_support('core-block-patterns');
    }

    /**
     * Dequeue Block Editor assets from frontend
     */
    public static function dequeueBlockAssets(): void
    {
        // Remove Block Library CSS
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');

        // Remove WooCommerce Blocks CSS if present
        wp_dequeue_style('wc-blocks-style');

        // Remove Global Styles (theme.json generated)
        wp_dequeue_style('global-styles');
    }

    /**
     * Remove Gutenberg-related admin menu items
     */
    public static function removeGutenbergMenuItems(): void
    {
        // Remove "Edit Site" menu (Full Site Editing)
        remove_submenu_page('themes.php', 'site-editor.php');
    }

    /**
     * Disable Full Site Editing features
     */
    public static function disableFullSiteEditing(): void
    {
        // Remove FSE template loading
        remove_action('wp_footer', 'wp_enqueue_global_styles', 1);

        // Remove SVG filters for duotone
        remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
    }
}
