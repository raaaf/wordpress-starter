<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

/**
 * Llms.txt provider.
 *
 * Serves a curated plain-text overview for LLM crawlers and AI search engines
 * at /llms.txt (short index) and /llms-full.txt (expanded, one entry per page).
 *
 * The format follows the emerging llms.txt proposal: markdown-style sections with
 * site identity, a short description, and a link list of the most important URLs.
 *
 * Non-invasive by design:
 * - Hooks on `template_redirect` instead of rewrite rules -> no flush required.
 * - Caches the rendered body in a transient (12h) -> negligible DB load.
 * - Output is filterable so client themes can extend without overriding.
 */
class LlmsTxtProvider extends ServiceProvider
{
    private const TRANSIENT_INDEX = 'wp_starter_llms_txt_index';
    private const TRANSIENT_FULL = 'wp_starter_llms_txt_full';
    private const CACHE_TTL = HOUR_IN_SECONDS * 12;

    public function register(): void
    {
        // No container bindings needed.
    }

    public function boot(): void
    {
        add_action('template_redirect', [$this, 'maybeServe'], 1);
        add_action('save_post', [$this, 'flushCache']);
        add_action('switch_theme', [$this, 'flushCache']);
        add_action('acf/save_post', [$this, 'flushCache']);
    }

    /**
     * Intercept /llms.txt and /llms-full.txt requests before the template loads.
     */
    public function maybeServe(): void
    {
        $requestUri = isset($_SERVER['REQUEST_URI'])
            ? sanitize_text_field(wp_unslash( (string) $_SERVER['REQUEST_URI']))
            : '';
        $path = strtolower(parse_url($requestUri, PHP_URL_PATH) ?: '');

        if ($path === '/llms.txt') {
            $this->send($this->getIndexBody());
        }

        if ($path === '/llms-full.txt') {
            $this->send($this->getFullBody());
        }
    }

    /**
     * Flush the cached bodies when content changes.
     */
    public function flushCache(): void
    {
        delete_transient(self::TRANSIENT_INDEX);
        delete_transient(self::TRANSIENT_FULL);
    }

    /**
     * Short llms.txt: header + curated link list.
     */
    private function getIndexBody(): string
    {
        $cached = get_transient(self::TRANSIENT_INDEX);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $lines = array_merge(
            $this->renderHeader(),
            ['', '## Key pages', ''],
            $this->renderKeyPageLinks(),
            ['', '## Extended', ''],
            ['- [Full index](' . esc_url_raw(home_url('/llms-full.txt')) . '): complete list of indexable pages'],
            [''],
        );

        /**
         * Filter the rendered llms.txt body before it is cached.
         *
         * @param string[] $lines
         */
        $lines = apply_filters('wp_starter_llms_txt_index_lines', $lines);
        $body = implode("\n", $lines);
        set_transient(self::TRANSIENT_INDEX, $body, self::CACHE_TTL);

        return $body;
    }

    /**
     * Long llms-full.txt: header + one entry per published page and post.
     */
    private function getFullBody(): string
    {
        $cached = get_transient(self::TRANSIENT_FULL);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $lines = array_merge(
            $this->renderHeader(),
            ['', '## All pages', ''],
            $this->renderPostLinks('page'),
            ['', '## Blog posts', ''],
            $this->renderPostLinks('post'),
            [''],
        );

        $lines = apply_filters('wp_starter_llms_txt_full_lines', $lines);
        $body = implode("\n", $lines);
        set_transient(self::TRANSIENT_FULL, $body, self::CACHE_TTL);

        return $body;
    }

    /**
     * Shared header block (title + description + home link).
     *
     * @return string[]
     */
    private function renderHeader(): array
    {
        $name = get_bloginfo('name');
        $description = get_bloginfo('description');
        $home = home_url('/');

        $lines = [
            '# ' . $name,
            '',
            '> ' . ( $description !== '' ? $description : 'Website: ' . $home ),
            '',
            '- Home: ' . $home,
        ];

        $companyName = $this->themeOption('company_name');
        if (is_string($companyName) && $companyName !== '' && $companyName !== $name) {
            $lines[] = '- Organization: ' . $companyName;
        }

        $email = $this->themeOption('email');
        if (is_string($email) && $email !== '') {
            $lines[] = '- Contact: mailto:' . $email;
        }

        return $lines;
    }

    /**
     * Build a short curated list: front page, imprint, privacy, and common top-level pages.
     *
     * @return string[]
     */
    private function renderKeyPageLinks(): array
    {
        $links = [];

        $frontId = (int) get_option('page_on_front');
        if ($frontId > 0) {
            $links[] = $this->linkLineForPost($frontId);
        }

        $blogId = (int) get_option('page_for_posts');
        if ($blogId > 0) {
            $links[] = $this->linkLineForPost($blogId);
        }

        // Top-level published pages (excluding the two already listed).
        $topPages = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_parent' => 0,
            'numberposts' => 20,
            'orderby' => 'menu_order title',
            'order' => 'ASC',
            'exclude' => array_filter([$frontId, $blogId]),
        ]);

        foreach ($topPages as $page) {
            $links[] = $this->linkLineForPost($page->ID);
        }

        return array_values(array_filter($links));
    }

    /**
     * All published posts of a given type, each as a markdown link line.
     *
     * @return string[]
     */
    private function renderPostLinks(string $postType): array
    {
        $posts = get_posts([
            'post_type' => $postType,
            'post_status' => 'publish',
            'numberposts' => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- curated export for LLMs, capped and cached 12h
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $lines = [];
        foreach ($posts as $post) {
            $line = $this->linkLineForPost($post->ID);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    private function linkLineForPost(int $postId): string
    {
        $title = get_the_title($postId);
        $url = get_permalink($postId);
        if (!is_string($url) || $url === '') {
            return '';
        }

        $excerpt = wp_strip_all_tags( (string) get_post_field('post_excerpt', $postId));
        $suffix = $excerpt !== '' ? ': ' . $this->truncate($excerpt, 160) : '';

        return '- [' . $title . '](' . $url . ')' . $suffix;
    }

    private function truncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)) . '…';
    }

    /**
     * Read a single ACF theme option safely (no-op when ACF is unavailable).
     */
    private function themeOption(string $name): mixed
    {
        if (!function_exists('get_field')) {
            return null;
        }

        return get_field($name, 'option');
    }

    /**
     * Emit the response body with a plain-text content type and exit.
     */
    private function send(string $body): void
    {
        nocache_headers();
        header('Content-Type: text/plain; charset=UTF-8');
        header('X-Robots-Tag: noindex, follow');
        // The body is plain text (Content-Type: text/plain) composed from
        // already-sanitised titles/URLs and translated literals. HTML escaping
        // would break URLs containing `&`, so we intentionally emit as-is.
        echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }
}
