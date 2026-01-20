<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->setupThemeVersion();
    }

    public function boot(): void
    {
        $this->addThemeSupport();
        $this->disableComments();
        $this->disableGutenberg();
        $this->addTemplateFilter();
        $this->addStructuredData();
    }

    private function setupThemeVersion(): void
    {
        if (!get_transient('theme_version')) {
            $theme_version = wp_get_theme()->get('Version');
            set_transient('theme_version', $theme_version, DAY_IN_SECONDS);
        }
    }

    private function addThemeSupport(): void
    {
        add_action('after_setup_theme', function (): void {
            add_theme_support('title-tag');
            add_theme_support('custom-logo');
            add_theme_support('html5', [
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
            ]);
            add_theme_support('post-thumbnails');
            add_theme_support('align-wide');
            add_theme_support('wp-block-styles');
            add_theme_support('editor-styles');
            add_editor_style('dist/editor-style.css');
            add_theme_support('editor-color-palette');
            add_theme_support('editor-font-sizes');
        });
    }

    private function disableComments(): void
    {
        // Redirect from comments page
        add_action('admin_init', function (): void {
            global $pagenow;
            if ($pagenow === 'edit-comments.php') {
                wp_redirect(admin_url());
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

    private function disableGutenberg(): void
    {
        // Disable Gutenberg for posts
        add_filter('use_block_editor_for_post', '__return_false', 10);
        
        // Disable Gutenberg for post types
        add_filter('use_block_editor_for_post_type', '__return_false', 10);
        
        // Remove Gutenberg CSS
        add_action('wp_enqueue_scripts', function (): void {
            wp_dequeue_style('wp-block-library');
            wp_dequeue_style('wp-block-library-theme');
            wp_dequeue_style('wc-block-style'); // Remove WooCommerce block CSS
            wp_dequeue_style('global-styles'); // Remove theme.json styles
        }, 100);
    }

    private function addTemplateFilter(): void
    {
        add_filter('template_include', function (string $template): string {
            $templateName = wp_basename($template, '.php');
            $templateName = wp_basename($templateName, '.blade');

            if ($GLOBALS['blade']->exists($templateName)) {
                $GLOBALS['template_name'] = $templateName;
                return get_template_directory() . '/config/index.php';
            }

            return $template;
        });
    }

    private function addStructuredData(): void
    {
        add_action('wp_head', function (): void {
            $json_ld = [
                "@context" => "https://schema.org",
                "@type" => "WebSite",
                "name" => get_bloginfo('name'),
                "url" => home_url(),
                "potentialAction" => [
                    "@type" => "SearchAction",
                    "target" => home_url() . "/?s={search_term_string}",
                    "query-input" => "required name=search_term_string"
                ]
            ];
            echo '<script type="application/ld+json">' . json_encode($json_ld) . '</script>';
        });
    }
}