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
    /** Upper bound for meta descriptions; search engines truncate well before this. */
    private const DESCRIPTION_MAX_LENGTH = 155;

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
        $this->setBreadcrumbSeparator();
        $this->addCanonicalUrl();
        $this->addOpenGraphTags();
        $this->addMetaDescription();
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

            // A crawler only obeys the most specific group matching its own
            // name and ignores `User-agent: *` entirely (RFC 9309), so each
            // named group below must repeat whatever restrictions core (and
            // Yoast, if active) already put in the default group — otherwise
            // it silently becomes a wider grant than `*`, not a narrower one.
            $defaultGroupRestrictions = $this->extractDefaultGroupRestrictions($output);

            $lines = ['', '# AI crawlers (managed by theme)'];
            foreach ($crawlers as $agent) {
                $agent = (string) $agent;
                if ($agent === '') {
                    continue;
                }
                $lines[] = 'User-agent: ' . $agent;
                foreach ($defaultGroupRestrictions as $restriction) {
                    $lines[] = $restriction;
                }
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
     * Extract the Disallow/Allow directives WordPress core (and, if active,
     * Yoast) placed in the default (`User-agent: *`) group of $output, so the
     * named crawler groups can repeat them instead of hardcoding a guess.
     *
     * @return array<int, string>
     */
    private function extractDefaultGroupRestrictions(string $output): array
    {
        $restrictions = [];
        $inDefaultGroup = false;

        foreach (preg_split('/\r\n|\r|\n/', $output) as $line) {
            $trimmed = trim( (string) $line);

            if (preg_match('/^User-agent:\s*\*$/i', $trimmed)) {
                $inDefaultGroup = true;
                continue;
            }

            if ($inDefaultGroup && preg_match('/^User-agent:/i', $trimmed)) {
                break;
            }

            if ($inDefaultGroup && preg_match('/^(Disallow|Allow):/i', $trimmed)) {
                $restrictions[] = $trimmed;
            }
        }

        return $restrictions;
    }

    /**
     * Add robots meta tag overrides for pages that must not be indexed.
     *
     * 404 pages: noindex, follow (broken URLs should not be indexed)
     * Password-protected pages: noindex, nofollow (only the gate is public)
     */
    private function addRobotsOverrides(): void
    {
        add_filter('wp_robots', function (array $robots): array {
            if (is_404()) {
                $robots['noindex'] = true;
                unset($robots['nofollow']);

                return $robots;
            }

            // Without this the password form itself gets indexed, which puts an
            // unlisted page into the search results under its own title.
            if (is_singular() && post_password_required()) {
                $robots['noindex'] = true;
                $robots['nofollow'] = true;
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
        if (function_exists('get_fields')) {
            // Batch load all option fields in one call instead of one
            // get_field() query per field. get_fields() returns false when
            // no options are saved — treat that as an empty set.
            $allOptions = get_fields('option');
            if (!is_array($allOptions)) {
                $allOptions = [];
            }

            $fieldNames = ['company_name', 'address', 'phone', 'email', 'site_logo', 'site_favicon', 'social_sharing_image'];
            foreach ($fieldNames as $fieldName) {
                $options[$fieldName] = $allOptions[$fieldName] ?? null;
            }
        }

        return $options;
    }

    /**
     * Feed the theme's contact details into Yoast's Organization node.
     *
     * When Yoast is active the theme yields the node to avoid two competing
     * Organization entities, so without this the telephone, email and address
     * would disappear from the structured data entirely.
     */
    private function enrichSeoPluginOrganization(): void
    {
        add_filter('wpseo_schema_organization', function (array $data): array {
            $themeOptions = $this->getThemeOptions();

            $phone = $themeOptions['phone'] ?? null;
            $email = $themeOptions['email'] ?? null;
            $address = $themeOptions['address'] ?? null;

            if ($phone && empty($data['telephone'])) {
                $data['telephone'] = $phone;
            }
            if ($email && empty($data['email'])) {
                $data['email'] = $email;
            }
            if ($address && empty($data['address'])) {
                $data['address'] = $this->buildPostalAddress($address);
            }

            return $data;
        });
    }

    /**
     * Add structured data (JSON-LD) for WebSite, Organization, and Article schemas
     */
    private function addStructuredData(): void
    {
        $this->enrichSeoPluginOrganization();

        add_action('wp_head', function (): void {
            $nonce = \WordpressStarter\Security::getNonce();

            // Yoast emits WebSite and Organization itself; two competing nodes
            // for the same entity are worse than one.
            $seoPluginOwnsSchema = defined('WPSEO_VERSION');

            // WebSite Schema (front page only)
            if (is_front_page() && !$seoPluginOwnsSchema) {
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

            if ($companyName && !$seoPluginOwnsSchema) {
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
                    $orgSchema['address'] = $this->buildPostalAddress($address);
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
     * Split a free-text address into PostalAddress parts.
     *
     * Expects "street\npostcode city". Anything that does not match that shape
     * is kept as a single streetAddress so no information is lost.
     *
     * @return array<string, string>
     */
    private function buildPostalAddress(string $address): array
    {
        $schema = ['@type' => 'PostalAddress'];

        $lines = array_values(array_filter(array_map('trim', (array) preg_split('/\r\n|\r|\n/', $address))));
        $lastLine = $lines ? end($lines) : '';

        if (count($lines) < 2 || !preg_match('/^(\d{4,5})\s+(.+)$/', $lastLine, $matches)) {
            $schema['streetAddress'] = $address;

            return $schema;
        }

        array_pop($lines);
        $schema['streetAddress'] = implode(', ', $lines);
        $schema['postalCode'] = $matches[1];
        $schema['addressLocality'] = $matches[2];

        return $schema;
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

            $nonce = \WordpressStarter\Security::getNonce();
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
     *
     * Self-references the actual paginated page (via get_pagenum_link()) from
     * page 2 onward, since Google dropped rel=prev/next and a page-1-only
     * canonical on a paginated archive/index just contradicts the real URL.
     */
    private function getCanonicalUrl(): ?string
    {
        $paged = (int) get_query_var('paged');

        if (is_singular()) {
            return get_permalink();
        }

        if (is_front_page()) {
            return home_url('/');
        }

        if (is_home() && get_option('page_for_posts')) {
            return $paged > 1 ? get_pagenum_link($paged) : get_permalink(get_option('page_for_posts'));
        }

        if (is_post_type_archive()) {
            return $paged > 1 ? get_pagenum_link($paged) : get_post_type_archive_link(get_queried_object()->name ?? '');
        }

        if (is_archive()) {
            // For date/author archives, use the current URL without query params
            global $wp;

            return $paged > 1 ? get_pagenum_link($paged) : home_url($wp->request);
        }

        if (is_search()) {
            return $paged > 1 ? get_pagenum_link($paged) : get_search_link();
        }

        return null;
    }

    /**
     * Emit <meta name="description"> when no SEO plugin is doing it.
     *
     * Yoast is listed as recommended, not required, so a site without it had no
     * meta description on any page at all — only og:description and
     * twitter:description, which search engines do not use for the snippet.
     */
    private function addMetaDescription(): void
    {
        add_action('wp_head', function (): void {
            // Yoast owns the description tag when active; two would conflict.
            if (defined('WPSEO_VERSION')) {
                return;
            }

            $description = $this->getMetaDescription();

            if ($description === '') {
                return;
            }

            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }, 1);
    }

    /**
     * Build a description for the current request.
     *
     * The excerpt alone is not enough in this theme: page content lives in ACF
     * Flexible Content, so post_content is usually empty and WordPress derives
     * no excerpt from it. Measured on a real install, half the pages had no
     * excerpt whatsoever. The flexible sections are therefore consulted before
     * falling back to the site tagline.
     */
    private function getMetaDescription(): string
    {
        if (!is_singular()) {
            if (is_search() || is_404()) {
                return '';
            }

            $archive = wp_strip_all_tags( (string) get_the_archive_description());

            return $this->normalizeDescription($archive !== '' ? $archive : (string) get_bloginfo('description'));
        }

        // Password-protected posts describe nothing. get_the_excerpt() returns
        // WordPress' "there is no excerpt because this is a protected post"
        // placeholder, and the ACF sections are readable even while the
        // password gate is up, because they bypass the_content(). Deriving a
        // description from either would publish protected content in a meta
        // tag that every crawler reads.
        if (post_password_required()) {
            return $this->normalizeDescription( (string) get_bloginfo('description'));
        }

        $excerpt = wp_strip_all_tags( (string) get_the_excerpt());

        if (trim($excerpt) !== '') {
            return $this->normalizeDescription($excerpt);
        }

        $fromSections = $this->descriptionFromSections( (int) get_the_ID());

        if ($fromSections !== '') {
            return $this->normalizeDescription($fromSections);
        }

        return $this->normalizeDescription( (string) get_bloginfo('description'));
    }

    /**
     * Pull prose out of the ACF Flexible Content sections, in page order.
     *
     * Only fields that actually carry sentences are considered — headlines and
     * labels make a poor snippet, and a description assembled from button
     * captions is worse than none.
     *
     * @param int $postId Post to read the sections from
     */
    private function descriptionFromSections(int $postId): string
    {
        if (!function_exists('get_field')) {
            return '';
        }

        $sections = get_field('page_sections', $postId);

        if (!is_array($sections)) {
            return '';
        }

        $proseFields = ['section_description', 'copy', 'content', 'column_1'];
        $collected = '';

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            foreach ($proseFields as $field) {
                $value = $section[$field] ?? null;

                if (!is_string($value)) {
                    continue;
                }

                $text = trim(wp_strip_all_tags($value));

                if ($text === '') {
                    continue;
                }

                $collected = trim($collected . ' ' . $text);

                if (mb_strlen($collected) >= self::DESCRIPTION_MAX_LENGTH) {
                    return $collected;
                }
            }
        }

        return $collected;
    }

    /**
     * Collapse whitespace and cut to length on a word boundary.
     */
    private function normalizeDescription(string $text): string
    {
        $text = trim( (string) preg_replace('/\s+/u', ' ', wp_strip_all_tags($text)));

        if ($text === '' || mb_strlen($text) <= self::DESCRIPTION_MAX_LENGTH) {
            return $text;
        }

        $cut = mb_substr($text, 0, self::DESCRIPTION_MAX_LENGTH);
        $lastSpace = mb_strrpos($cut, ' ');

        if ($lastSpace !== false && $lastSpace > 0) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, ' ,;:-') . '…';
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
            // Same source as <meta name="description">: on ACF-built pages the
            // excerpt alone is usually empty, so the sections are consulted too.
            $description = $this->getMetaDescription() ?: ( get_bloginfo('description') ?: get_bloginfo('name') );
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

        $nonce = \WordpressStarter\Security::getNonce();
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

        $nonce = \WordpressStarter\Security::getNonce();
        echo '<script type="application/ld+json" nonce="' . esc_attr($nonce) . '">'
            . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . '</script>' . "\n";
    }

    /**
     * Krumen mit » statt > trennen.
     *
     * Yoast nimmt das Trennzeichen aus seinen Einstellungen. Der Filter setzt
     * es unabhaengig davon, was dort gespeichert ist, damit alle Seiten
     * dasselbe Zeichen zeigen.
     */
    private function setBreadcrumbSeparator(): void
    {
        add_filter('wpseo_breadcrumb_separator', static fn (): string => '»');
    }
}
