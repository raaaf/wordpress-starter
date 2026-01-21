<?php

declare(strict_types=1);

namespace ForeignThemeA\Providers;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->setupThemeVersion();
    }

    public function boot(): void
    {
        $this->addThemeSupport();
        $this->disableGlobalStyles();
        $this->disableComments();
        $this->addTemplateFilter();
        $this->registerBladePageTemplates();
        $this->addStructuredData();
        $this->addOpenGraphTags();
        $this->addFaviconSupport();
        $this->addLoginLogoSupport();
        $this->disableTexturizeForAlpine();
        $this->syncAcfWithWordPress();
        // Note: Editor style overrides removed - all ACF blocks are in edit mode (no preview rendering)
    }

    /**
     * Fix wptexturize corruption in block comments only
     *
     * WordPress's wptexturize converts ASCII quotes and dashes to typographic
     * characters. This is great for content, but breaks JSON in block comments.
     *
     * Instead of disabling wptexturize globally, we fix block comments after
     * wptexturize runs, preserving nice typography for regular content.
     *
     * @see https://core.trac.wordpress.org/ticket/2647
     * @see https://support.advancedcustomfields.com/forums/topic/turn-off-smart-curly-quotes/
     */
    private function disableTexturizeForAlpine(): void
    {
        // Fix block comments AFTER wptexturize runs (priority 99, after default 10)
        add_filter('the_content', [$this, 'fixCorruptedBlockContent'], 99);

        // Fix content when editing (before it's displayed in editor)
        add_filter('content_edit_pre', [$this, 'fixCorruptedBlockContent'], 1);

        // Fix content BEFORE saving to database (prevents corruption)
        add_filter('content_save_pre', [$this, 'fixCorruptedBlockContent'], 1);
        add_filter('wp_insert_post_data', function ($data) {
            if (!empty($data['post_content'])) {
                $data['post_content'] = $this->fixCorruptedBlockContent($data['post_content']);
            }
            return $data;
        }, 99);

        // Fix content before block parsing (runs before ACF parses blocks)
        add_filter('the_posts', function ($posts) {
            foreach ($posts as $post) {
                if ($post->post_content && strpos($post->post_content, '<!-- wp:') !== false) {
                    $post->post_content = $this->fixCorruptedBlockContent($post->post_content);
                }
            }
            return $posts;
        }, 1);

        // Fix content when loaded via REST API (block editor uses REST)
        add_filter('rest_prepare_page', [$this, 'fixRestContent'], 10, 3);
        add_filter('rest_prepare_post', [$this, 'fixRestContent'], 10, 3);

        // Add admin action to fix all corrupted content (one-time fix)
        add_action('admin_init', [$this, 'fixCorruptedContentOnce']);
    }

    /**
     * One-time fix for all corrupted content in the database
     * Runs once when ?fix_block_quotes=1 is added to any admin URL
     */
    public function fixCorruptedContentOnce(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin-only one-time fix command
        if (!isset($_GET['fix_block_quotes']) || !current_user_can('manage_options')) {
            return;
        }

        global $wpdb;

        // Find all posts with block content
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time fix command
        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_content FROM {$wpdb->posts}
                 WHERE post_content LIKE %s
                 AND (post_content LIKE %s OR post_content LIKE %s)",
                '%<!-- wp:%',
                '%"%',
                '%"%'
            )
        );

        $fixed = 0;
        foreach ($posts as $post) {
            $fixedContent = $this->fixCorruptedBlockContent($post->post_content);
            if ($fixedContent !== $post->post_content) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time fix command
                $wpdb->update(
                    $wpdb->posts,
                    ['post_content' => $fixedContent],
                    ['ID' => $post->ID]
                );
                ++$fixed;
            }
        }

        add_action('admin_notices', function () use ($fixed): void {
            echo '<div class="notice notice-success"><p>Fixed ' . esc_html( $fixed ) . ' posts with corrupted block quotes.</p></div>';
        });
    }

    /**
     * Fix corrupted content in REST API responses
     */
    public function fixRestContent(\WP_REST_Response $response, \WP_Post $post, \WP_REST_Request $request): \WP_REST_Response
    {
        $data = $response->get_data();

        if (isset($data['content']['raw'])) {
            $data['content']['raw'] = $this->fixCorruptedBlockContent($data['content']['raw']);
            $response->set_data($data);
        }

        return $response;
    }

    /**
     * Fix block content corrupted by wptexturize
     *
     * Converts typographic quotes back to ASCII within block comments.
     */
    public function fixCorruptedBlockContent(string $content): string
    {
        // Only process if content has blocks
        if (strpos($content, '<!-- wp:') === false) {
            return $content;
        }

        // Fix corrupted quotes in block comments
        // Match block comments: <!-- wp:... -->
        // Use Unicode escape sequences to avoid encoding issues
        $typographicChars = [
            "\u{201C}", // " left double quote
            "\u{201D}", // " right double quote
            "\u{2018}", // ' left single quote
            "\u{2019}", // ' right single quote
            "\u{2013}", // – en dash
            "\u{2014}", // — em dash
        ];
        $asciiChars = ['"', '"', "'", "'", '-', '-'];

        return preg_replace_callback(
            '/<!-- wp:(.*?) -->/s',
            function ($matches) use ($typographicChars, $asciiChars) {
                return str_replace($typographicChars, $asciiChars, $matches[0]);
            },
            $content
        ) ?? $content;
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
            // Removed: wp-block-styles - we use Tailwind instead
            // Note: Editor styles removed - all ACF blocks use edit mode (no preview rendering)
            // add_theme_support('editor-styles') and add_editor_style() are not needed
            // ACF field UI styling is handled via inline CSS in Vite::enqueueEditorAssets()
        });
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

        // Only allow ACF blocks on root level (Core blocks still work in InnerBlocks)
        add_filter('allowed_block_types_all', [$this, 'filterAllowedBlocks'], 10, 2);
    }

    /**
     * Filter allowed blocks to only show ACF blocks on root level
     *
     * Core blocks (paragraph, heading, list) are still available inside
     * InnerBlocks of layout blocks - they're just hidden from the main inserter.
     *
     * @param bool|array<string> $allowedBlocks
     * @param \WP_Block_Editor_Context $context
     * @return array<string>
     */
    public function filterAllowedBlocks(bool|array $allowedBlocks, \WP_Block_Editor_Context $context): array
    {
        // Get all registered ACF blocks
        $acfBlocks = [];
        $blocksDir = get_template_directory() . '/blocks';

        if (is_dir($blocksDir)) {
            $blocks = glob($blocksDir . '/*/block.json');
            foreach ($blocks as $blockConfig) {
                $blockName = basename(dirname($blockConfig));
                $acfBlocks[] = 'acf/' . $blockName;
            }
        }

        // Core blocks allowed in InnerBlocks (text formatting essentials)
        // These won't appear in main inserter but work inside layout blocks
        $coreBlocksForInnerBlocks = [
            'core/paragraph',
            'core/heading',
            'core/list',
            'core/list-item',
            'core/quote',
            'core/separator',
        ];

        return array_merge($acfBlocks, $coreBlocksForInnerBlocks);
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
     */
    private function registerBladePageTemplates(): void
    {
        add_filter('theme_page_templates', function (array $templates): array {
            $templatesDir = get_template_directory() . '/templates';

            if (!is_dir($templatesDir)) {
                return $templates;
            }

            $bladeFiles = glob($templatesDir . '/page-*.blade.php');
            if (!$bladeFiles) {
                return $templates;
            }

            foreach ($bladeFiles as $file) {
                $filename = basename($file);
                // Skip if already registered
                if (isset($templates[$filename])) {
                    continue;
                }

                // Read file and extract Template Name from comment
                $content = file_get_contents($file);
                if ($content && preg_match('/Template Name:\s*(.+)/i', $content, $matches)) {
                    $templates[$filename] = trim($matches[1]);
                }
            }

            return $templates;
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

                    // Add logo if available (ACF first, then Customizer)
                    $logoUrl = null;
                    $acfLogo = get_field('site_logo', 'option');
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

                    echo '<script type="application/ld+json" nonce="' . esc_attr($nonce) . '">' . wp_json_encode($orgSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
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

                echo '<script type="application/ld+json" nonce="' . esc_attr($nonce) . '">' . wp_json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
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

            // Get image (post thumbnail, ACF logo, or Customizer logo)
            $imageUrl = '';
            if (is_singular() && has_post_thumbnail()) {
                $imageUrl = get_the_post_thumbnail_url(null, 'large');
            } else {
                // Try ACF logo first
                if (function_exists('get_field')) {
                    $acfLogo = get_field('site_logo', 'option');
                    if ($acfLogo && !empty($acfLogo['url'])) {
                        $imageUrl = $acfLogo['url'];
                    }
                }
                // Fallback to Customizer logo
                if (!$imageUrl) {
                    $customLogoId = get_theme_mod('custom_logo');
                    if ($customLogoId) {
                        $imageUrl = wp_get_attachment_image_url($customLogoId, 'full');
                    }
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
