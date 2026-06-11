<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

/**
 * Editor Integration Service Provider
 *
 * Disables Gutenberg and unused WordPress editing features (comments,
 * categories, tags) that are not part of the ACF Flexible Content workflow.
 * Also removes Global Styles output that conflicts with Tailwind CSS.
 */
class EditorIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No bindings required
    }

    public function boot(): void
    {
        // Disable Gutenberg completely - use Classic Editor with ACF Flexible Content
        \WordpressStarter\EditorConfig::init();

        $this->disableGlobalStyles();
        $this->disableComments();
        $this->disableCategoriesAndTags();
    }

    /**
     * Disable WordPress Global Styles on frontend
     *
     * WordPress generates inline CSS from theme.json that conflicts with Tailwind.
     * We keep theme.json for Block Editor presets (colors, spacing) but remove
     * the generated frontend styles.
     *
     * @see https://developer.wordpress.org/news/2023/07/how-to-disable-global-styles-and-block-supports/
     */
    private function disableGlobalStyles(): void
    {
        // Remove global styles actions after WordPress has registered them
        add_action('wp_enqueue_scripts', function (): void {
            // Remove the inline global-styles CSS
            wp_dequeue_style('global-styles');
            wp_deregister_style('global-styles');

            // Remove core block CSS - we style blocks with Tailwind
            wp_dequeue_style('wp-block-library');
            wp_deregister_style('wp-block-library');
        }, 100);

        // Prevent global styles from being enqueued in footer
        add_action('init', function (): void {
            remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
        });

        // Remove SVG filters for duotone (not needed with Tailwind)
        add_action('init', function (): void {
            remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
        });
    }

    private function disableComments(): void
    {
        // Redirect from comments page
        add_action('admin_init', function (): void {
            global $pagenow;
            if ($pagenow === 'edit-comments.php') {
                wp_safe_redirect(admin_url());
                exit;
            }
            remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
            foreach (get_post_types() as $post_type) {
                if (post_type_supports($post_type, 'comments')) {
                    remove_post_type_support($post_type, 'comments');
                    remove_post_type_support($post_type, 'trackbacks');
                }
            }
        });

        // Disable comment features
        /** @phpstan-ignore arguments.count */
        add_filter('comments_open', '__return_false', 20, 2);
        /** @phpstan-ignore arguments.count */
        add_filter('pings_open', '__return_false', 20, 2);
        /** @phpstan-ignore arguments.count */
        add_filter('comments_array', '__return_empty_array', 10, 2);

        // Remove from admin menu
        add_action('admin_menu', function (): void {
            remove_menu_page('edit-comments.php');
        });

        // Remove from admin bar
        add_action('init', function (): void {
            if (is_admin_bar_showing()) {
                remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
            }
        });
    }

    /**
     * Disable categories and tags for posts
     *
     * Removes the category and post_tag taxonomies from posts for a cleaner
     * content management experience focused on pages with ACF Flexible Content.
     */
    private function disableCategoriesAndTags(): void
    {
        add_action('init', function (): void {
            // Unregister category and tag taxonomies from posts
            unregister_taxonomy_for_object_type('category', 'post');
            unregister_taxonomy_for_object_type('post_tag', 'post');
        }, 99);

        // Remove from admin menu
        add_action('admin_menu', function (): void {
            remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=category');
            remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=post_tag');
        });
    }
}
