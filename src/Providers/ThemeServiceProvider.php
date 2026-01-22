<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use Spatie\SchemaOrg\Schema;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->setupThemeVersion();
    }

    public function boot(): void
    {
        // Disable Gutenberg completely - use Classic Editor with ACF Flexible Content
        \WordpressStarter\EditorConfig::init();

        $this->addThemeSupport();
        $this->allowSvgUploads();
        $this->optimizeScriptLoading();
        $this->addResourcePreloading();
        $this->disableGlobalStyles();
        $this->disableComments();
        $this->addTemplateFilter();
        $this->registerBladePageTemplates();
        $this->disableCategoriesAndTags();
        // SEO (structured data, canonical, OG tags) now handled by SeoServiceProvider
        $this->addFaviconSupport();
        $this->addLoginLogoSupport();
        $this->syncAcfWithWordPress();
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
        });
    }

    /**
     * Optimize script loading with defer attribute
     *
     * Adds defer attribute to non-critical scripts to prevent blocking
     * the initial page render. This improves First Contentful Paint (FCP)
     * and Largest Contentful Paint (LCP) metrics.
     *
     * Note: Scripts with type="module" are already deferred by browsers.
     */
    private function optimizeScriptLoading(): void
    {
        add_filter('script_loader_tag', function (string $tag, string $handle, string $src): string {
            // Skip admin scripts
            if (is_admin()) {
                return $tag;
            }

            // Skip scripts that already have defer, async, or type="module"
            if (
                str_contains($tag, ' defer')
                || str_contains($tag, ' async')
                || str_contains($tag, 'type="module"')
            ) {
                return $tag;
            }

            // Scripts that should NOT be deferred (critical for page functionality)
            $noDeferHandles = [
                'jquery-core',      // jQuery must load synchronously for inline scripts
                'jquery-migrate',
                'wp-polyfill',      // Polyfills must load first
            ];

            if (in_array($handle, $noDeferHandles, true)) {
                return $tag;
            }

            // Add defer to all other scripts
            return str_replace('<script ', '<script defer ', $tag);
        }, 20, 3);
    }

    /**
     * Add resource preloading for critical assets
     *
     * Preloads fonts and optionally inlines critical CSS to improve
     * Largest Contentful Paint (LCP) and reduce render-blocking.
     */
    private function addResourcePreloading(): void
    {
        // Add preload links early in head
        add_action('wp_head', function (): void {
            // Skip in admin
            if (is_admin()) {
                return;
            }

            $fontsDir = get_theme_file_uri('resources/fonts/');

            // Preload critical fonts (headline and body, most used weights)
            $criticalFonts = [
                'colabthi-webfont.woff2',      // ColaborateLight (headlines)
                'inter-v20-latin-regular.woff2', // Inter Regular (body)
                'inter-v20-latin-700.woff2',    // Inter Bold (body emphasis)
            ];

            foreach ($criticalFonts as $font) {
                printf(
                    '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin="anonymous">%s',
                    esc_url($fontsDir . $font),
                    "\n"
                );
            }
        }, 1); // Priority 1 = very early in head

        // Inline critical CSS if file exists
        add_action('wp_head', function (): void {
            if (is_admin()) {
                return;
            }

            $criticalCssPath = get_theme_file_path('resources/css/critical.css');

            if (file_exists($criticalCssPath)) {
                $criticalCss = file_get_contents($criticalCssPath);
                if ($criticalCss) {
                    $nonce = $GLOBALS['csp_nonce'] ?? '';
                    printf(
                        '<style id="critical-css"%s>%s</style>%s',
                        $nonce ? ' nonce="' . esc_attr($nonce) . '"' : '',
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted internal CSS
                        $criticalCss,
                        "\n"
                    );
                }
            }
        }, 2); // Priority 2 = right after preloads

        // Preload main stylesheet for faster loading
        add_action('wp_head', function (): void {
            if (is_admin()) {
                return;
            }

            // Get CSS URL from Vite manifest in production
            $manifestPath = get_theme_file_path('dist/.vite/manifest.json');
            if (!file_exists($manifestPath)) {
                return;
            }

            $manifest = json_decode(file_get_contents($manifestPath) ?: '', true);
            if (!isset($manifest['resources/css/app.css']['file'])) {
                return;
            }

            $cssUrl = get_theme_file_uri('dist/' . $manifest['resources/css/app.css']['file']);
            printf(
                '<link rel="preload" href="%s" as="style">%s',
                esc_url($cssUrl),
                "\n"
            );
        }, 1);
    }

    /**
     * Allow SVG uploads for admin users
     *
     * SVGs are used for logo placeholders in the styleguide and can be
     * uploaded by administrators. Basic sanitization is applied.
     */
    private function allowSvgUploads(): void
    {
        // Add SVG to allowed mime types
        add_filter('upload_mimes', function (array $mimes): array {
            $mimes['svg'] = 'image/svg+xml';
            $mimes['svgz'] = 'image/svg+xml';
            return $mimes;
        });

        // Fix SVG file type detection
        add_filter('wp_check_filetype_and_ext', function (array $data, string $file, string $filename, ?array $mimes): array {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            if ($ext === 'svg') {
                $data['ext'] = 'svg';
                $data['type'] = 'image/svg+xml';
            }

            return $data;
        }, 10, 4);

        // Basic SVG sanitization on upload
        add_filter('wp_handle_upload_prefilter', function (array $file): array {
            if ($file['type'] !== 'image/svg+xml') {
                return $file;
            }

            // Only allow admins to upload SVGs
            if (!current_user_can('manage_options')) {
                $file['error'] = __('SVG uploads are only allowed for administrators.', 'wp-starter');
                return $file;
            }

            // Read and sanitize SVG content
            $content = file_get_contents($file['tmp_name']);
            if ($content === false) {
                return $file;
            }

            // Remove potentially dangerous elements and attributes
            $content = $this->sanitizeSvg($content);

            // Write sanitized content back
            file_put_contents($file['tmp_name'], $content);

            return $file;
        });
    }

    /**
     * SVG sanitization using enshrined/svg-sanitize library
     *
     * Properly sanitizes SVG files by parsing the XML and removing
     * dangerous elements and attributes, rather than using regex.
     *
     * @see https://github.com/darylldoyle/svg-sanitizer
     */
    private function sanitizeSvg(string $content): string
    {
        // Use the proper SVG sanitizer library
        $sanitizer = new \enshrined\svgSanitize\Sanitizer();

        // Configure allowed tags and attributes for strict sanitization
        $sanitizer->removeRemoteReferences(true);
        $sanitizer->removeXMLTag(false); // Keep the XML declaration

        $sanitized = $sanitizer->sanitize($content);

        // Return original if sanitization failed (shouldn't happen with valid SVG)
        return $sanitized ?: $content;
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

    private function addTemplateFilter(): void
    {
        add_filter('template_include', function (string $template): string {
            $blade = $GLOBALS['blade'] ?? null;

            if (!$blade) {
                return $template;
            }

            // Try to determine the best template based on WordPress context
            $templateName = $this->getBladeTemplateName($template);

            if ($blade->exists($templateName)) {
                $GLOBALS['template_name'] = $templateName;
                return get_template_directory() . '/config/index.php';
            }

            return $template;
        });
    }

    /**
     * Register Blade page templates with WordPress
     *
     * Scans templates directory for .blade.php files with "Template Name:" comment
     * and registers them so they appear in the page template dropdown.
     * Uses transient caching to avoid filesystem operations on every request.
     */
    private function registerBladePageTemplates(): void
    {
        add_filter('theme_page_templates', function (array $templates): array {
            // Try to get cached template list first
            $cacheKey = 'blade_page_templates_' . wp_get_theme()->get('Version');
            $cachedTemplates = get_transient($cacheKey);

            if ($cachedTemplates !== false && is_array($cachedTemplates)) {
                return array_merge($templates, $cachedTemplates);
            }

            $templatesDir = get_template_directory() . '/templates';

            if (!is_dir($templatesDir)) {
                return $templates;
            }

            $bladeFiles = glob($templatesDir . '/page-*.blade.php');
            if (!$bladeFiles) {
                return $templates;
            }

            $foundTemplates = [];
            foreach ($bladeFiles as $file) {
                $filename = basename($file);
                // Skip if already registered
                if (isset($templates[$filename])) {
                    continue;
                }

                // Read file and extract Template Name from comment
                $content = file_get_contents($file);
                if ($content && preg_match('/Template Name:\s*(.+)/i', $content, $matches)) {
                    $foundTemplates[$filename] = trim($matches[1]);
                }
            }

            // Cache for 7 days (invalidated by theme version in cache key)
            set_transient($cacheKey, $foundTemplates, WEEK_IN_SECONDS);

            return array_merge($templates, $foundTemplates);
        });
    }

    /**
     * Determine the Blade template name based on WordPress context
     */
    private function getBladeTemplateName(string $wpTemplate): string
    {
        $blade = $GLOBALS['blade'] ?? null;

        if (!$blade) {
            return wp_basename($wpTemplate, '.php');
        }

        // Check for page templates (custom template, page-{slug}, page-{id}, page)
        if (is_page()) {
            $pageId = get_queried_object_id();
            $pageSlug = get_queried_object()->post_name ?? '';

            // Check for custom page template (set via _wp_page_template meta)
            $customTemplate = get_page_template_slug($pageId);
            if ($customTemplate) {
                // Remove .blade.php or .php extension to get template name
                $templateName = wp_basename($customTemplate, '.blade.php');
                $templateName = wp_basename($templateName, '.php');
                if ($blade->exists($templateName)) {
                    return $templateName;
                }
            }

            // Check for page-{slug}.blade.php
            if ($pageSlug && $blade->exists("page-{$pageSlug}")) {
                return "page-{$pageSlug}";
            }
            // Check for page-{id}.blade.php
            if ($pageId && $blade->exists("page-{$pageId}")) {
                return "page-{$pageId}";
            }
            // Check for page.blade.php
            if ($blade->exists('page')) {
                return 'page';
            }
        }

        // Check for single post templates (single-{post_type}, single)
        if (is_single()) {
            $postType = get_post_type();

            // Check for single-{post_type}.blade.php
            if ($postType && $blade->exists("single-{$postType}")) {
                return "single-{$postType}";
            }
            // Check for single.blade.php
            if ($blade->exists('single')) {
                return 'single';
            }
        }

        // Check for archive templates
        if (is_archive()) {
            if (is_category() && $blade->exists('category')) {
                return 'category';
            }
            if (is_tag() && $blade->exists('tag')) {
                return 'tag';
            }
            if (is_author() && $blade->exists('author')) {
                return 'author';
            }
            if ($blade->exists('archive')) {
                return 'archive';
            }
        }

        // Check for search template
        if (is_search() && $blade->exists('search')) {
            return 'search';
        }

        // Check for 404 template
        if (is_404() && $blade->exists('404')) {
            return '404';
        }

        // Check for front page template
        if (is_front_page() && $blade->exists('front-page')) {
            return 'front-page';
        }

        // Check for home (blog) template
        if (is_home() && $blade->exists('home')) {
            return 'home';
        }

        // Fall back to extracting name from WordPress template
        $templateName = wp_basename($wpTemplate, '.php');
        $templateName = wp_basename($templateName, '.blade');

        return $templateName;
    }

    /**
     * Get all theme options in a single batch for performance.
     * Uses static caching to avoid multiple database queries per request.
     *
     * @return array<string, mixed>
     */
    private function getThemeOptions(): array
    {
        static $options = null;

        if ($options !== null) {
            return $options;
        }

        $options = [];
        if (function_exists('get_field')) {
            // Batch load all commonly used theme options
            $fieldNames = ['company_name', 'address', 'phone', 'email', 'site_logo', 'site_favicon'];
            foreach ($fieldNames as $fieldName) {
                $options[$fieldName] = get_field($fieldName, 'option');
            }
        }

        return $options;
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
            $nonce = $GLOBALS['csp_nonce'] ?? '';
            echo '<script type="application/ld+json" nonce="' . esc_attr($nonce) . '">' . wp_json_encode($websiteSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";

            // Organization Schema (from batched theme options)
            $themeOptions = $this->getThemeOptions();
            $companyName = $themeOptions['company_name'] ?? null;

            if ($companyName) {
                $orgSchema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => $companyName,
                    'url' => home_url(),
                ];

                // Add logo if available (ACF first, then Customizer)
                $logoUrl = null;
                $acfLogo = $themeOptions['site_logo'] ?? null;
                if ($acfLogo && !empty($acfLogo['url'])) {
                    $logoUrl = $acfLogo['url'];
                } else {
                    $customLogoId = get_theme_mod('custom_logo');
                    if ($customLogoId) {
                        $logoUrl = wp_get_attachment_image_url($customLogoId, 'full');
                    }
                }
                if ($logoUrl) {
                    $orgSchema['logo'] = $logoUrl;
                }

                // Add contact info from batched options
                $phone = $themeOptions['phone'] ?? null;
                $email = $themeOptions['email'] ?? null;
                $address = $themeOptions['address'] ?? null;

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

                echo '<script type="application/ld+json" nonce="' . esc_attr($nonce) . '">' . wp_json_encode($orgSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
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

                echo '<script type="application/ld+json" nonce="' . esc_attr($nonce) . '">' . wp_json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
            }
        });
    }

    /**
     * Add BreadcrumbList JSON-LD schema for better SEO
     *
     * Generates structured data for breadcrumbs when Yoast SEO is not handling it,
     * or provides an enhanced schema even when Yoast is active.
     */
    private function addBreadcrumbSchema(): void
    {
        add_action('wp_head', function (): void {
            // Skip on front page - no breadcrumbs needed
            if (is_front_page()) {
                return;
            }

            $breadcrumbItems = $this->getBreadcrumbItems();

            if (empty($breadcrumbItems)) {
                return;
            }

            $listItems = [];
            foreach ($breadcrumbItems as $position => $item) {
                $listItem = Schema::listItem()
                    ->position($position + 1)
                    ->name($item['name']);

                // Only add item URL if not the current page (last item)
                // item() expects a Thing object with @id, not a plain string
                if (!empty($item['url'])) {
                    $listItem->item(
                        Schema::thing()
                            ->setProperty('@id', $item['url'])
                            ->name($item['name'])
                    );
                }

                $listItems[] = $listItem;
            }

            $breadcrumbSchema = Schema::breadcrumbList()
                ->itemListElement($listItems);

            $nonce = $GLOBALS['csp_nonce'] ?? '';
            // Use custom script output to include nonce
            $json = $breadcrumbSchema->toArray();
            echo '<script type="application/ld+json" nonce="' . esc_attr($nonce) . '">' . wp_json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }, 15);
    }

    /**
     * Build breadcrumb items array for schema generation
     *
     * @return array<int, array{name: string, url: string}>
     */
    private function getBreadcrumbItems(): array
    {
        $items = [];

        // Home is always first
        $items[] = [
            'name' => __('Startseite', 'wp-starter'),
            'url' => home_url('/'),
        ];

        if (is_singular()) {
            $post = get_queried_object();
            if (!$post instanceof \WP_Post) {
                return $items;
            }

            // For pages, add ancestors
            if (is_page() && $post->post_parent) {
                $ancestors = get_post_ancestors($post->ID);
                $ancestors = array_reverse($ancestors);

                foreach ($ancestors as $ancestorId) {
                    $ancestor = get_post($ancestorId);
                    if ($ancestor) {
                        $items[] = [
                            'name' => get_the_title($ancestor),
                            'url' => get_permalink($ancestor),
                        ];
                    }
                }
            }

            // For posts, add blog page if set
            if (is_single() && get_option('page_for_posts')) {
                $blogPageId = (int) get_option('page_for_posts');
                $items[] = [
                    'name' => get_the_title($blogPageId),
                    'url' => get_permalink($blogPageId),
                ];
            }

            // Current page (no URL - it's the current page)
            $items[] = [
                'name' => get_the_title($post),
                'url' => '', // Empty URL for current page
            ];
        } elseif (is_archive()) {
            if (is_post_type_archive()) {
                $postType = get_queried_object();
                if ($postType instanceof \WP_Post_Type) {
                    $items[] = [
                        'name' => $postType->labels->name ?? $postType->name,
                        'url' => '',
                    ];
                }
            } elseif (is_date()) {
                if (is_year()) {
                    $items[] = [
                        'name' => get_the_date('Y'),
                        'url' => '',
                    ];
                } elseif (is_month()) {
                    $items[] = [
                        'name' => get_the_date('F Y'),
                        'url' => '',
                    ];
                } elseif (is_day()) {
                    $items[] = [
                        'name' => get_the_date(),
                        'url' => '',
                    ];
                }
            }
        } elseif (is_search()) {
            $items[] = [
                // translators: %s is the search query term.
                'name' => sprintf(__('Suchergebnisse für: %s', 'wp-starter'), get_search_query()),
                'url' => '',
            ];
        } elseif (is_404()) {
            $items[] = [
                'name' => __('Seite nicht gefunden', 'wp-starter'),
                'url' => '',
            ];
        }

        return $items;
    }

    /**
     * Add canonical URL fallback for sites without Yoast SEO
     *
     * Outputs canonical link tag if Yoast SEO is not active.
     */
    private function addCanonicalUrl(): void
    {
        add_action('wp_head', function (): void {
            // Skip if Yoast SEO is active - it handles canonical URLs
            if (defined('WPSEO_VERSION')) {
                return;
            }

            // Skip if another SEO plugin has already output canonical
            if (has_action('wp_head', 'rel_canonical')) {
                return;
            }

            $canonicalUrl = $this->getCanonicalUrl();

            if ($canonicalUrl) {
                echo '<link rel="canonical" href="' . esc_url($canonicalUrl) . '" />' . "\n";
            }
        }, 1);
    }

    /**
     * Get the canonical URL for the current page
     */
    private function getCanonicalUrl(): ?string
    {
        if (is_singular()) {
            return get_permalink();
        }

        if (is_front_page()) {
            return home_url('/');
        }

        if (is_home() && get_option('page_for_posts')) {
            return get_permalink(get_option('page_for_posts'));
        }

        if (is_post_type_archive()) {
            return get_post_type_archive_link(get_queried_object()->name ?? '');
        }

        if (is_archive()) {
            // For date/author archives, use the current URL without query params
            global $wp;
            return home_url($wp->request);
        }

        if (is_search()) {
            return get_search_link();
        }

        return null;
    }

    private function addOpenGraphTags(): void
    {
        add_action('wp_head', function (): void {
            $title = is_singular() ? get_the_title() : get_bloginfo('name');
            $description = is_singular() ? wp_strip_all_tags(get_the_excerpt()) : get_bloginfo('description');
            $url = is_singular() ? get_permalink() : home_url();
            $siteName = get_bloginfo('name');

            // Get image with metadata (URL, width, height, mime type)
            $imageData = $this->getSocialShareImage();

            // Open Graph Tags
            echo '<meta property="og:type" content="' . ( is_singular('post') ? 'article' : 'website' ) . '">' . "\n";
            echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
            if ($description) {
                echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
            }
            echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
            echo '<meta property="og:site_name" content="' . esc_attr($siteName) . '">' . "\n";
            echo '<meta property="og:locale" content="' . esc_attr(get_locale()) . '">' . "\n";

            if ($imageData) {
                echo '<meta property="og:image" content="' . esc_url($imageData['url']) . '">' . "\n";
                echo '<meta property="og:image:secure_url" content="' . esc_url($imageData['url']) . '">' . "\n";
                echo '<meta property="og:image:alt" content="' . esc_attr($title) . '">' . "\n";
                if (!empty($imageData['width'])) {
                    echo '<meta property="og:image:width" content="' . esc_attr( (string) $imageData['width']) . '">' . "\n";
                }
                if (!empty($imageData['height'])) {
                    echo '<meta property="og:image:height" content="' . esc_attr( (string) $imageData['height']) . '">' . "\n";
                }
                if (!empty($imageData['mime'])) {
                    echo '<meta property="og:image:type" content="' . esc_attr($imageData['mime']) . '">' . "\n";
                }
            }

            // Twitter Card Tags
            echo '<meta name="twitter:card" content="' . ( $imageData ? 'summary_large_image' : 'summary' ) . '">' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
            if ($description) {
                echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
            }
            if ($imageData) {
                echo '<meta name="twitter:image" content="' . esc_url($imageData['url']) . '">' . "\n";
                echo '<meta name="twitter:image:alt" content="' . esc_attr($title) . '">' . "\n";
            }

            // Article-specific Open Graph
            if (is_singular('post')) {
                echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c')) . '">' . "\n";
                echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c')) . '">' . "\n";
                echo '<meta property="article:author" content="' . esc_attr(get_the_author()) . '">' . "\n";
            }
        }, 5); // Priority 5 to run before wp_head outputs other meta
    }

    /**
     * Get social share image with full metadata
     *
     * Fallback order:
     * 1. Post featured image (for singular pages)
     * 2. Dedicated social sharing image from theme options
     * 3. Site logo from theme options
     * 4. Customizer logo
     *
     * @return array{url: string, width: int, height: int, mime: string}|null
     */
    private function getSocialShareImage(): ?array
    {
        // 1. Try post featured image first
        if (is_singular() && has_post_thumbnail()) {
            $thumbnailId = get_post_thumbnail_id();
            if ($thumbnailId) {
                return $this->getImageMetadata( (int) $thumbnailId, 'large');
            }
        }

        if (function_exists('get_field')) {
            // 2. Try dedicated social sharing image
            $socialImageId = get_field('social_sharing_image', 'option');
            if ($socialImageId) {
                return $this->getImageMetadata( (int) $socialImageId, 'full');
            }

            // 3. Try site logo
            $acfLogo = get_field('site_logo', 'option');
            if ($acfLogo && !empty($acfLogo['id'])) {
                return $this->getImageMetadata( (int) $acfLogo['id'], 'full');
            }
        }

        // 4. Fallback to Customizer logo
        $customLogoId = get_theme_mod('custom_logo');
        if ($customLogoId) {
            return $this->getImageMetadata( (int) $customLogoId, 'full');
        }

        return null;
    }

    /**
     * Get image URL and metadata from attachment ID
     *
     * @param int $attachmentId
     * @param string $size
     * @return array{url: string, width: int, height: int, mime: string}|null
     */
    private function getImageMetadata(int $attachmentId, string $size = 'full'): ?array
    {
        $imageSrc = wp_get_attachment_image_src($attachmentId, $size);
        if (!$imageSrc) {
            return null;
        }

        $mime = get_post_mime_type($attachmentId) ?: '';

        return [
            'url' => $imageSrc[0],
            'width' => (int) $imageSrc[1],
            'height' => (int) $imageSrc[2],
            'mime' => $mime,
        ];
    }

    /**
     * Add favicon support using ACF options
     */
    private function addFaviconSupport(): void
    {
        // Override WordPress site icon with ACF favicon
        add_filter('get_site_icon_url', function (string $url, int $size, int $blogId): string {
            if (!function_exists('get_field')) {
                return $url;
            }

            $faviconId = get_field('site_favicon', 'option');
            if (!$faviconId) {
                return $url;
            }

            $faviconUrl = wp_get_attachment_image_url($faviconId, [$size, $size]);
            return $faviconUrl ?: $url;
        }, 10, 3);

        // Add favicon meta tags if ACF favicon is set
        add_action('wp_head', function (): void {
            if (!function_exists('get_field')) {
                return;
            }

            $faviconId = get_field('site_favicon', 'option');
            if (!$faviconId) {
                return;
            }

            // Don't output if WordPress already has a site icon
            if (has_site_icon()) {
                return;
            }

            $favicon16 = wp_get_attachment_image_url($faviconId, [16, 16]);
            $favicon32 = wp_get_attachment_image_url($faviconId, [32, 32]);
            $favicon180 = wp_get_attachment_image_url($faviconId, [180, 180]);
            $favicon192 = wp_get_attachment_image_url($faviconId, [192, 192]);
            $favicon512 = wp_get_attachment_image_url($faviconId, [512, 512]);

            if ($favicon32) {
                echo '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url($favicon32) . '">' . "\n";
            }
            if ($favicon16) {
                echo '<link rel="icon" type="image/png" sizes="16x16" href="' . esc_url($favicon16) . '">' . "\n";
            }
            if ($favicon180) {
                echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url($favicon180) . '">' . "\n";
            }
            if ($favicon192) {
                echo '<link rel="icon" type="image/png" sizes="192x192" href="' . esc_url($favicon192) . '">' . "\n";
            }
            if ($favicon512) {
                echo '<link rel="icon" type="image/png" sizes="512x512" href="' . esc_url($favicon512) . '">' . "\n";
            }
        }, 1);
    }

    /**
     * Add custom login page logo using ACF options
     */
    private function addLoginLogoSupport(): void
    {
        // Custom login logo
        add_action('login_enqueue_scripts', function (): void {
            $logoUrl = $this->getLogoUrl();
            if (!$logoUrl) {
                return;
            }
            ?>
            <style type="text/css">
                #login h1 a, .login h1 a {
                    background-image: url('<?php echo esc_url($logoUrl); ?>');
                    background-size: contain;
                    background-repeat: no-repeat;
                    background-position: center;
                    width: 100%;
                    height: 80px;
                }
            </style>
            <?php
        });

        // Custom login logo URL
        add_filter('login_headerurl', function (): string {
            return home_url();
        });

        // Custom login logo title
        add_filter('login_headertext', function (): string {
            return get_bloginfo('name');
        });
    }

    /**
     * Get logo URL from ACF options or Customizer
     */
    private function getLogoUrl(): ?string
    {
        // Try ACF option first
        if (function_exists('get_field')) {
            $acfLogo = get_field('site_logo', 'option');
            if ($acfLogo && !empty($acfLogo['url'])) {
                return $acfLogo['url'];
            }
        }

        // Fallback to Customizer
        $customLogoId = get_theme_mod('custom_logo');
        if ($customLogoId) {
            return wp_get_attachment_image_url($customLogoId, 'full');
        }

        return null;
    }

    /**
     * Sync ACF options with WordPress core settings
     *
     * When logo/favicon are set in ACF Theme Options, this syncs them to
     * WordPress core settings so SEO plugins, social sharing, and other
     * WordPress features use the correct images.
     */
    private function syncAcfWithWordPress(): void
    {
        // Sync on ACF options save
        add_action('acf/save_post', function ($postId): void {
            if ($postId !== 'options') {
                return;
            }

            $this->syncLogoToWordPress();
            $this->syncFaviconToWordPress();
        }, 20);

        // Also sync on init if values exist but aren't synced
        add_action('init', function (): void {
            if (!function_exists('get_field')) {
                return;
            }

            // Only sync if ACF values exist but WordPress values don't match
            $this->maybeInitialSync();
        }, 20);
    }

    /**
     * Sync ACF logo to WordPress custom_logo theme mod
     */
    private function syncLogoToWordPress(): void
    {
        if (!function_exists('get_field')) {
            return;
        }

        $acfLogo = get_field('site_logo', 'option');

        if ($acfLogo && !empty($acfLogo['ID'])) {
            // Set the WordPress custom_logo to the ACF logo
            set_theme_mod('custom_logo', $acfLogo['ID']);
        }
    }

    /**
     * Sync ACF favicon to WordPress site_icon option
     */
    private function syncFaviconToWordPress(): void
    {
        if (!function_exists('get_field')) {
            return;
        }

        $faviconId = get_field('site_favicon', 'option');

        if ($faviconId) {
            // Set the WordPress site_icon to the ACF favicon
            update_option('site_icon', $faviconId);
        }
    }

    /**
     * Initial sync if ACF values exist but WordPress values don't
     */
    private function maybeInitialSync(): void
    {
        // Sync logo if ACF has one but WordPress doesn't
        $acfLogo = get_field('site_logo', 'option');
        $wpLogo = get_theme_mod('custom_logo');

        if ($acfLogo && !empty($acfLogo['ID']) && !$wpLogo) {
            set_theme_mod('custom_logo', $acfLogo['ID']);
        }

        // Sync favicon if ACF has one but WordPress doesn't
        $acfFavicon = get_field('site_favicon', 'option');
        $wpSiteIcon = get_option('site_icon');

        if ($acfFavicon && !$wpSiteIcon) {
            update_option('site_icon', $acfFavicon);
        }
    }
}
