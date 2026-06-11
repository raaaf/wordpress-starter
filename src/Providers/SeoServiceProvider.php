<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WP_Post;
use WP_Post_Type;

/**
 * SEO Service Provider
 *
 * Handles all SEO-related functionality:
 * - JSON-LD structured data (WebSite, Organization, Article, BreadcrumbList)
 * - Open Graph and Twitter Card meta tags
 * - Canonical URLs
 * - Robots meta tag overrides (noindex for 404 pages)
 */
class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No registration needed
    }

    public function boot(): void
    {
        $this->addRobotsOverrides();
        $this->addAiCrawlerPolicy();
        $this->addStructuredData();
        $this->addBreadcrumbSchema();
        $this->addCanonicalUrl();
        $this->addOpenGraphTags();
    }

    /**
     * Explicitly allow known AI crawlers in the WordPress virtual robots.txt.
     *
     * WordPress serves a virtual robots.txt unless a static file exists in the
     * web root. Yoast SEO appends its own rules via the same filter. We append
     * a clearly labelled block so crawlers that respect robots.txt see an
     * explicit allow, even if a parent directive (`User-agent: *`) would have
     * covered them. Clients can disable the whole block by returning an empty
     * string from the `wp_starter_ai_crawler_policy` filter, or rewrite it
     * entirely via `wp_starter_ai_crawlers`.
     */
    private function addAiCrawlerPolicy(): void
    {
        add_filter('robots_txt', function (string $output, bool $public): string {
            if (!$public) {
                return $output;
            }

            $crawlers = apply_filters('wp_starter_ai_crawlers', [
                'GPTBot',           // ChatGPT (training + browsing)
                'OAI-SearchBot',    // ChatGPT Search surfaces
                'ChatGPT-User',     // ChatGPT browsing on behalf of a user
                'ClaudeBot',        // Anthropic crawler
                'Claude-Web',       // Legacy Anthropic crawler
                'anthropic-ai',     // Legacy Anthropic user agent
                'PerplexityBot',    // Perplexity AI
                'Perplexity-User',  // Perplexity browsing on behalf of a user
                'Google-Extended',  // Gemini + AI Overviews opt-in signal
                'CCBot',            // Common Crawl (feeds many LLMs)
                'Applebot-Extended',
                'Bytespider',
                'DuckAssistBot',
                'Meta-ExternalAgent',
                'cohere-ai',
                'Diffbot',
            ]);

            if (!is_array($crawlers) || $crawlers === []) {
                return $output;
            }

            $lines = ['', '# AI crawlers (managed by theme)'];
            foreach ($crawlers as $agent) {
                $agent = (string) $agent;
                if ($agent === '') {
                    continue;
                }
                $lines[] = 'User-agent: ' . $agent;
                $lines[] = 'Allow: /';
                $lines[] = '';
            }

            $block = implode("\n", $lines);

            /**
             * Filter the final AI crawler block. Return an empty string to omit it entirely.
             */
            $block = (string) apply_filters('wp_starter_ai_crawler_policy', $block, $crawlers);

            return rtrim($output) . "\n" . $block;
        }, 20, 2);
    }

    /**
     * Add robots meta tag overrides for pages that must not be indexed.
     *
     * 404 pages: noindex, follow (broken URLs should not be indexed)
     */
    private function addRobotsOverrides(): void
    {
        add_filter('wp_robots', function (array $robots): array {
            if (is_404()) {
                $robots['noindex'] = true;
                unset($robots['nofollow']);
            }

            return $robots;
        });
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
            $fieldNames = ['company_name', 'address', 'phone', 'email', 'site_logo', 'site_favicon', 'social_sharing_image'];
            foreach ($fieldNames as $fieldName) {
                $options[$fieldName] = get_field($fieldName, 'option');
            }
        }

        return $options;
    }

    /**
     * Add structured data (JSON-LD) for WebSite, Organization, and Article schemas
     */
    private function addStructuredData(): void
    {
        add_action('wp_head', function (): void {
            $nonce = $GLOBALS['csp_nonce'] ?? '';

            // WebSite Schema (front page only)
            if (is_front_page()) {
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
                echo '<script type="application/ld+json" nonce="' . esc_attr($nonce) . '">' . wp_json_encode($websiteSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
            }

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
                $logoUrl = \WordpressStarter\Acf\Fields::siteLogoUrl();
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
                    'publisher' => $this->buildPublisherSchema(),
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
                $listItem = [
                    '@type' => 'ListItem',
                    'position' => $position + 1,
                    'name' => $item['name'],
                ];

                if (!empty($item['url'])) {
                    $listItem['item'] = [
                        '@type' => 'Thing',
                        '@id' => $item['url'],
                        'name' => $item['name'],
                    ];
                }

                $listItems[] = $listItem;
            }

            $json = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $listItems,
            ];

            $nonce = $GLOBALS['csp_nonce'] ?? '';
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
            if (!$post instanceof WP_Post) {
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
                if ($postType instanceof WP_Post_Type) {
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

    /**
     * Add Open Graph and Twitter Card meta tags
     */
    private function addOpenGraphTags(): void
    {
        add_action('wp_head', function (): void {
            // Skip if Yoast SEO is active - it handles Open Graph tags
            if (defined('WPSEO_VERSION')) {
                return;
            }

            $title = is_singular() ? get_the_title() : get_bloginfo('name');
            $description = is_singular()
                ? wp_strip_all_tags(get_the_excerpt())
                : ( get_bloginfo('description') ?: get_the_archive_title() ?: get_bloginfo('name') );
            if (is_singular()) {
                $url = get_permalink() ?: home_url('/');
            } elseif (is_search()) {
                $url = home_url('/?s=' . urlencode(get_search_query()));
            } else {
                global $wp;
                $url = home_url($wp->request);
            }
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
                return $this->getImageMetadata( (int) $thumbnailId, 'full');
            }
        }

        $themeOptions = $this->getThemeOptions();

        // 2. Try dedicated social sharing image
        $socialImageId = $themeOptions['social_sharing_image'] ?? null;
        if ($socialImageId) {
            return $this->getImageMetadata( (int) $socialImageId, 'full');
        }

        // 3. Try site logo
        $acfLogo = $themeOptions['site_logo'] ?? null;
        if ($acfLogo && !empty($acfLogo['id'])) {
            return $this->getImageMetadata( (int) $acfLogo['id'], 'full');
        }

        // 4. Fallback to Customizer logo
        $customLogoId = get_theme_mod('custom_logo');
        if ($customLogoId) {
            return $this->getImageMetadata( (int) $customLogoId, 'full');
        }

        return null;
    }

    /**
     * Build the publisher schema for Article structured data.
     *
     * Includes the logo ImageObject only when a logo URL is available.
     *
     * @return array<string, mixed>
     */
    private function buildPublisherSchema(): array
    {
        $publisher = [
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url(),
        ];

        $logoUrl = $this->getOrganizationLogoUrl();
        if ($logoUrl) {
            $publisher['logo'] = [
                '@type' => 'ImageObject',
                'url' => $logoUrl,
            ];
        }

        return $publisher;
    }

    /**
     * Get the organization logo URL for use in structured data.
     *
     * Fallback order: ACF site_logo → Customizer custom_logo → empty string.
     */
    private function getOrganizationLogoUrl(): string
    {
        return \WordpressStarter\Acf\Fields::siteLogoUrl() ?? '';
    }

    /**
     * Get image URL and metadata from attachment ID
     *
     * @param int $attachmentId
     * @param string $size
     *
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
     * Render a FAQPage JSON-LD block for a list of question/answer pairs.
     *
     * Intended for opt-in use inside flexible layouts (e.g. accordion.blade.php).
     * Skips rendering when the list is empty or all entries are invalid.
     *
     * @param array<int, array{question: string, answer: string}> $items
     */
    public static function emitFaqSchema(array $items): void
    {
        $mainEntity = [];

        foreach ($items as $item) {
            $question = isset($item['question']) ? trim(wp_strip_all_tags( (string) $item['question'])) : '';
            $answer = isset($item['answer']) ? trim(wp_kses_post( (string) $item['answer'])) : '';

            if ($question === '' || $answer === '') {
                continue;
            }

            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        if ($mainEntity === []) {
            return;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity,
        ];

        $nonce = $GLOBALS['csp_nonce'] ?? '';
        echo '<script type="application/ld+json" nonce="' . esc_attr($nonce) . '">'
            . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . '</script>' . "\n";
    }

    /**
     * Render a Person JSON-LD block. Useful for team pages, author bios and
     * E-E-A-T signals on regulated pages (finance, health, legal).
     *
     * @param array{
     *     name: string,
     *     jobTitle?: string,
     *     description?: string,
     *     image?: string,
     *     url?: string,
     *     email?: string,
     *     telephone?: string,
     *     sameAs?: array<int, string>,
     *     worksFor?: string
     * } $person
     */
    public static function emitPersonSchema(array $person): void
    {
        $name = isset($person['name']) ? trim( (string) $person['name']) : '';
        if ($name === '') {
            return;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $name,
        ];

        foreach (['jobTitle', 'description', 'image', 'url', 'email', 'telephone'] as $key) {
            if (!empty($person[$key])) {
                $schema[$key] = $person[$key];
            }
        }

        if (!empty($person['sameAs']) && is_array($person['sameAs'])) {
            $schema['sameAs'] = array_values(array_filter(array_map('strval', $person['sameAs'])));
            if ($schema['sameAs'] === []) {
                unset($schema['sameAs']);
            }
        }

        if (!empty($person['worksFor'])) {
            $schema['worksFor'] = [
                '@type' => 'Organization',
                'name' => (string) $person['worksFor'],
            ];
        }

        $nonce = $GLOBALS['csp_nonce'] ?? '';
        echo '<script type="application/ld+json" nonce="' . esc_attr($nonce) . '">'
            . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . '</script>' . "\n";
    }
}
