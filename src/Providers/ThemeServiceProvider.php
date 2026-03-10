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
        $transientKey = \WordpressStarter\ThemeContext::prefix() . '_theme_version';
        if (!get_transient($transientKey)) {
            $theme_version = wp_get_theme()->get('Version');
            set_transient($transientKey, $theme_version, DAY_IN_SECONDS);
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
                'wp-hooks',         // Required by wp-i18n (defines wp.hooks)
                'wp-i18n',          // Required by inline translation scripts (Contact Form 7, etc.)
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
                $cacheKey = 'critical_css_' . get_template_directory();
                $criticalCss = wp_cache_get($cacheKey, 'theme');
                if ($criticalCss === false) {
                    $criticalCss = file_get_contents($criticalCssPath);
                    wp_cache_set($cacheKey, $criticalCss, 'theme', DAY_IN_SECONDS);
                }
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
            $cssUrl = \WordpressStarter\Vite::getAssetUrl('resources/css/app.css');
            if (!$cssUrl) {
                return;
            }

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
     * Add favicon support using ACF options
     */
    private function addFaviconSupport(): void
    {
        // Override WordPress site icon with ACF favicon
        add_filter('get_site_icon_url', function (string $url, int $size, int $blogId): string {
            if (!function_exists('get_field')) {
                return $url;
            }

            $faviconId = \WordpressStarter\Acf\Fields::option('site_favicon');
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

            $faviconId = \WordpressStarter\Acf\Fields::option('site_favicon');
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
            $acfLogo = \WordpressStarter\Acf\Fields::option('site_logo');
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

        $faviconId = \WordpressStarter\Acf\Fields::option('site_favicon');

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
        // Only run once — bail if already synced in a previous request
        $transientKey = \WordpressStarter\ThemeContext::prefix() . '_initial_sync_done';
        if (get_transient($transientKey)) {
            return;
        }

        // Sync logo if ACF has one but WordPress doesn't
        $acfLogo = get_field('site_logo', 'option');
        $wpLogo = get_theme_mod('custom_logo');

        if ($acfLogo && !empty($acfLogo['ID']) && !$wpLogo) {
            set_theme_mod('custom_logo', $acfLogo['ID']);
        }

        // Sync favicon if ACF has one but WordPress doesn't
        $acfFavicon = \WordpressStarter\Acf\Fields::option('site_favicon');
        $wpSiteIcon = get_option('site_icon');

        if ($acfFavicon && !$wpSiteIcon) {
            update_option('site_icon', $acfFavicon);
        }

        set_transient($transientKey, true, DAY_IN_SECONDS);
    }
}
