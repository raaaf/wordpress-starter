<?php

declare(strict_types=1);

namespace WordpressStarter\Acf;

class Options
{
    /**
     * Register ACF options pages
     */
    public static function register(): void
    {
        if (!function_exists('acf_add_options_page')) {
            return;
        }

        // Main theme options
        acf_add_options_page([
            'page_title' => __('Theme Options', 'wp-starter'),
            'menu_title' => __('Theme Options', 'wp-starter'),
            'menu_slug' => 'theme-options',
            'capability' => 'edit_posts',
            'redirect' => true,
            'icon_url' => 'dashicons-admin-generic',
            'position' => 2,
        ]);

        // Sub pages
        self::addSubPage('General', 'general');
        self::addSubPage('Header', 'header');
        self::addSubPage('Footer', 'footer');
        self::addSubPage('Social Media', 'social');
    }

    /**
     * Add options sub page
     */
    private static function addSubPage(string $title, string $slug): void
    {
        acf_add_options_sub_page([
            'page_title' => $title,
            'menu_title' => $title,
            'parent_slug' => 'theme-options',
            'menu_slug' => 'theme-options-' . $slug,
        ]);
    }

    /**
     * Clear options cache
     */
    public static function clearCache(): void
    {
        wp_cache_delete_group('theme');
    }

    /**
     * Hook to clear cache when options are updated
     */
    public static function initCacheClearing(): void
    {
        add_action('acf/save_post', function ($postId) {
            if ($postId === 'options') {
                self::clearCache();
            }
        });
    }
}