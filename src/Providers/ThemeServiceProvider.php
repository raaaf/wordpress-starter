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
        $this->addTemplateFilter();
        $this->addStructuredData();
        $this->addOpenGraphTags();
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
            // WebSite Schema
            $websiteSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => get_bloginfo('name'),
                'url' => home_url(),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => home_url() . '/?s={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ];
            echo '<script type="application/ld+json">' . wp_json_encode($websiteSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";

            // Organization Schema (from theme options)
            if (function_exists('get_field')) {
                $companyName = get_field('company_name', 'option');
                $address = get_field('address', 'option');
                $phone = get_field('phone', 'option');
                $email = get_field('email', 'option');

                if ($companyName) {
                    $orgSchema = [
                        '@context' => 'https://schema.org',
                        '@type' => 'Organization',
                        'name' => $companyName,
                        'url' => home_url(),
                    ];

                    // Add logo if available
                    $customLogoId = get_theme_mod('custom_logo');
                    if ($customLogoId) {
                        $logoUrl = wp_get_attachment_image_url($customLogoId, 'full');
                        if ($logoUrl) {
                            $orgSchema['logo'] = $logoUrl;
                        }
                    }

                    // Add contact info
                    if ($phone) {
                        $orgSchema['telephone'] = $phone;
                    }
                    if ($email) {
                        $orgSchema['email'] = $email;
                    }
                    if ($address) {
                        $orgSchema['address'] = [
                            '@type' => 'PostalAddress',
                            'streetAddress' => $address,
                        ];
                    }

                    echo '<script type="application/ld+json">' . wp_json_encode($orgSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
                }
            }

            // Article Schema for single posts
            if (is_singular('post')) {
                global $post;
                $articleSchema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => get_the_title(),
                    'url' => get_permalink(),
                    'datePublished' => get_the_date('c'),
                    'dateModified' => get_the_modified_date('c'),
                    'author' => [
                        '@type' => 'Person',
                        'name' => get_the_author(),
                    ],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => get_bloginfo('name'),
                        'url' => home_url(),
                    ],
                ];

                // Add featured image
                if (has_post_thumbnail()) {
                    $articleSchema['image'] = get_the_post_thumbnail_url($post, 'large');
                }

                // Add excerpt as description
                $excerpt = get_the_excerpt();
                if ($excerpt) {
                    $articleSchema['description'] = wp_strip_all_tags($excerpt);
                }

                echo '<script type="application/ld+json">' . wp_json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
            }
        });
    }

    private function addOpenGraphTags(): void
    {
        add_action('wp_head', function (): void {
            $title = is_singular() ? get_the_title() : get_bloginfo('name');
            $description = is_singular() ? wp_strip_all_tags(get_the_excerpt()) : get_bloginfo('description');
            $url = is_singular() ? get_permalink() : home_url();
            $siteName = get_bloginfo('name');

            // Get image
            $imageUrl = '';
            if (is_singular() && has_post_thumbnail()) {
                $imageUrl = get_the_post_thumbnail_url(null, 'large');
            } else {
                $customLogoId = get_theme_mod('custom_logo');
                if ($customLogoId) {
                    $imageUrl = wp_get_attachment_image_url($customLogoId, 'full');
                }
            }

            // Open Graph Tags
            echo '<meta property="og:type" content="' . ( is_singular('post') ? 'article' : 'website' ) . '">' . "\n";
            echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
            if ($description) {
                echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
            }
            echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
            echo '<meta property="og:site_name" content="' . esc_attr($siteName) . '">' . "\n";
            echo '<meta property="og:locale" content="' . esc_attr(get_locale()) . '">' . "\n";

            if ($imageUrl) {
                echo '<meta property="og:image" content="' . esc_url($imageUrl) . '">' . "\n";
                echo '<meta property="og:image:alt" content="' . esc_attr($title) . '">' . "\n";
            }

            // Twitter Card Tags
            echo '<meta name="twitter:card" content="' . ( $imageUrl ? 'summary_large_image' : 'summary' ) . '">' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
            if ($description) {
                echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
            }
            if ($imageUrl) {
                echo '<meta name="twitter:image" content="' . esc_url($imageUrl) . '">' . "\n";
            }

            // Article-specific Open Graph
            if (is_singular('post')) {
                echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c')) . '">' . "\n";
                echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c')) . '">' . "\n";
                echo '<meta property="article:author" content="' . esc_attr(get_the_author()) . '">' . "\n";
            }
        }, 5); // Priority 5 to run before wp_head outputs other meta
    }
}
