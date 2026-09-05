<?php

/**
 * PHPUnit Bootstrap File
 *
 * This file is loaded before any tests run.
 * It sets up WordPress constants and mock functions.
 */

declare(strict_types=1);

// Load env() from src/helpers.php BEFORE the autoloader, so Laravel's
// illuminate/support never gets a chance to define its own env(). helpers.php
// only declares function_exists()-guarded functions at the top level, so it
// loads cleanly without a WordPress runtime.
require_once __DIR__ . '/../src/helpers.php';

require_once __DIR__ . '/../vendor/autoload.php';

// Define WordPress constants if not already defined
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

if (!defined('WEEK_IN_SECONDS')) {
    define('WEEK_IN_SECONDS', 604800);
}

// Global mock storage for tests
$GLOBALS['wp_mock_fields'] = [];
$GLOBALS['wp_mock_options'] = [];
$GLOBALS['wp_mock_cache'] = [];
$GLOBALS['wp_mock_hooks'] = ['actions' => [], 'filters' => []];
$GLOBALS['wp_mock_enqueued'] = ['scripts' => [], 'styles' => []];
$GLOBALS['wp_mock_have_rows_cursor'] = [];

// WordPress path functions
if (!function_exists('get_template')) {
    function get_template(): string
    {
        return $GLOBALS['wp_mock_template'] ?? 'wordpress-starter-theme';
    }
}

if (!function_exists('get_stylesheet')) {
    function get_stylesheet(): string
    {
        return $GLOBALS['wp_mock_stylesheet'] ?? get_template();
    }
}

if (!function_exists('get_template_directory')) {
    function get_template_directory(): string
    {
        return $GLOBALS['wp_mock_template_directory'] ?? __DIR__ . '/fixtures';
    }
}

if (!function_exists('get_template_directory_uri')) {
    function get_template_directory_uri(): string
    {
        return $GLOBALS['wp_mock_template_directory_uri'] ?? 'https://example.com/wp-content/themes/wp-starter';
    }
}

if (!function_exists('wp_nav_menu')) {
    function wp_nav_menu(array $args = []): ?string
    {
        $output = '<ul id="mock-nav-menu" class="' . ( $args['menu_class'] ?? '' ) . '"></ul>';

        if (!empty($args['echo']) || !isset($args['echo'])) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- test double echoes the menu markup the test supplied
            echo $output;

            return null;
        }

        return $output;
    }
}

if (!function_exists('get_theme_mod')) {
    function get_theme_mod(string $name, mixed $default = false): mixed
    {
        return $GLOBALS['wp_mock_theme_mods'][$name] ?? $default;
    }
}

if (!function_exists('has_nav_menu')) {
    function has_nav_menu(string $location): bool
    {
        return $GLOBALS['wp_mock_nav_menus'][$location] ?? true;
    }
}

if (!function_exists('get_theme_file_path')) {
    function get_theme_file_path(string $file = ''): string
    {
        return get_template_directory() . ( $file ? '/' . ltrim($file, '/') : '' );
    }
}

if (!function_exists('get_theme_file_uri')) {
    function get_theme_file_uri(string $file = ''): string
    {
        return get_template_directory_uri() . ( $file ? '/' . ltrim($file, '/') : '' );
    }
}

if (!function_exists('get_language_attributes')) {
    function get_language_attributes(): string
    {
        return 'lang="de-DE"';
    }
}

if (!function_exists('wp_head')) {
    function wp_head(): void
    {
    }
}

if (!function_exists('wp_body_open')) {
    function wp_body_open(): void
    {
    }
}

if (!function_exists('get_body_class')) {
    function get_body_class(array|string $class = ''): array
    {
        return is_array($class) ? $class : array_filter(explode(' ', $class));
    }
}

// WordPress escaping functions
if (!function_exists('wp_kses_test_double_check_scheme')) {
    /**
     * Shared allowed-scheme check for esc_url()/esc_url_raw() and the
     * href/src/action filtering inside wp_kses(). Not core's implementation,
     * only the decision whether a URL's scheme is allowed: http, https,
     * mailto, tel, ftp, or no scheme at all (relative/anchor/query URL).
     */
    function wp_kses_test_double_check_scheme(string $url): bool
    {
        // Strip control characters and whitespace, and decode HTML entities,
        // BEFORE reading the scheme: otherwise "java\tscript:" or
        // "&#106;avascript:" pass as if they had no scheme. Repeat until the
        // value stops changing (capped at 3 rounds) so a nested encoding like
        // "jav&#x0A;ascript:" (control char inside an entity) cannot survive
        // a single decode+strip pass.
        $url = trim($url);

        for ($i = 0; $i < 3; $i++) {
            $previous = $url;
            $url = preg_replace('/[\x00-\x20\x7f]/', '', $url) ?? $url;
            $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

            if ($url === $previous) {
                break;
            }
        }

        if ($url === '' || $url[0] === '#' || $url[0] === '/' || $url[0] === '?') {
            return true;
        }

        if (!preg_match('/^([a-zA-Z][a-zA-Z0-9+.\-]*):/', $url, $matches)) {
            return true;
        }

        return in_array(strtolower($matches[1]), ['http', 'https', 'mailto', 'tel', 'ftp'], true);
    }
}

if (!function_exists('esc_url')) {
    function esc_url(?string $url): string
    {
        $url = $url ?? '';

        if (!wp_kses_test_double_check_scheme($url)) {
            return '';
        }

        $url = preg_replace('/[\x00-\x1F\x7F\s]/', '', $url);

        // Core treats a schemeless, non-relative value as a bare host and
        // prepends http://, unless it looks like a local .php path.
        if (
            $url !== ''
            && !str_contains($url, ':')
            && !in_array($url[0], ['/', '#', '?'], true)
            && !preg_match('/^[a-z0-9-]+?\.php/i', $url)
        ) {
            $url = 'http://' . $url;
        }

        // Core encodes & as the numeric entity &#038;, not &amp;.
        return str_replace('&amp;', '&#038;', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
    }
}

if (!function_exists('esc_html')) {
    function esc_html(?string $text): string
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr(?string $text): string
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// WordPress cache functions
if (!function_exists('wp_cache_get')) {
    function wp_cache_get(string $key, string $group = 'default', bool $force = false, ?bool &$found = null): mixed
    {
        $cacheKey = "{$group}:{$key}";
        $found = isset($GLOBALS['wp_mock_cache']) && array_key_exists($cacheKey, $GLOBALS['wp_mock_cache']);

        return $found ? $GLOBALS['wp_mock_cache'][$cacheKey] : false;
    }
}

if (!function_exists('wp_cache_set')) {
    function wp_cache_set(string $key, mixed $data, string $group = 'default', int $expire = 0): bool
    {
        $cacheKey = "{$group}:{$key}";
        $GLOBALS['wp_mock_cache'][$cacheKey] = $data;

        return true;
    }
}

// Attachment lookup. Tests seed $GLOBALS['wp_mock_attachments'] with url => id.
if (!function_exists('attachment_url_to_postid')) {
    function attachment_url_to_postid(string $url): int
    {
        return (int) ( $GLOBALS['wp_mock_attachments'][$url] ?? 0 );
    }
}

if (!function_exists('wp_cache_delete')) {
    function wp_cache_delete(string $key, string $group = 'default'): bool
    {
        $cacheKey = "{$group}:{$key}";
        unset($GLOBALS['wp_mock_cache'][$cacheKey]);

        return true;
    }
}

// WordPress hook functions
if (!function_exists('add_action')) {
    function add_action(string $hook, mixed $callback, int $priority = 10, int $args = 1): bool
    {
        $GLOBALS['wp_mock_hooks']['actions'][$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
            'args' => $args,
        ];

        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter(string $hook, mixed $callback, int $priority = 10, int $args = 1): bool
    {
        $GLOBALS['wp_mock_hooks']['filters'][$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
            'args' => $args,
        ];

        return true;
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode(string $tag, callable $callback): void
    {
        $GLOBALS['wp_mock_shortcodes'][$tag] = $callback;
    }
}

// WordPress return functions
if (!function_exists('__return_true')) {
    function __return_true(): bool
    {
        return true;
    }
}

if (!function_exists('__return_false')) {
    function __return_false(): bool
    {
        return false;
    }
}

if (!function_exists('__return_null')) {
    function __return_null(): mixed
    {
        return null;
    }
}

if (!function_exists('__return_empty_array')) {
    function __return_empty_array(): array
    {
        return [];
    }
}

if (!function_exists('__return_empty_string')) {
    function __return_empty_string(): string
    {
        return '';
    }
}

if (!function_exists('__return_zero')) {
    function __return_zero(): int
    {
        return 0;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (isset($GLOBALS['wp_mock_hooks']['filters'][$hook])) {
            foreach ($GLOBALS['wp_mock_hooks']['filters'][$hook] as $filter) {
                $value = call_user_func($filter['callback'], $value, ...$args);
            }
        }

        return $value;
    }
}

if (!function_exists('do_action')) {
    function do_action(string $hook, mixed ...$args): void
    {
        if (isset($GLOBALS['wp_mock_hooks']['actions'][$hook])) {
            foreach ($GLOBALS['wp_mock_hooks']['actions'][$hook] as $action) {
                call_user_func($action['callback'], ...$args);
            }
        }
    }
}

// WordPress script/style enqueue functions
if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(
        string $handle,
        string $src = '',
        array $deps = [],
        string|bool|null $ver = false,
        array|bool $args = false,
    ): void {
        $GLOBALS['wp_mock_enqueued']['scripts'][$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'args' => $args,
        ];
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style(
        string $handle,
        string $src = '',
        array $deps = [],
        string|bool|null $ver = false,
        string $media = 'all',
    ): void {
        $GLOBALS['wp_mock_enqueued']['styles'][$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'media' => $media,
        ];
    }
}

// ACF functions
if (!function_exists('acf_add_local_field_group')) {
    function acf_add_local_field_group(array $group): void
    {
        $GLOBALS['wp_mock_registered_field_groups'][] = $group;
    }
}

if (!function_exists('get_field')) {
    function get_field(string $field, mixed $postId = false): mixed
    {
        $key = $postId !== false ? "{$field}:{$postId}" : $field;

        return $GLOBALS['wp_mock_fields'][$key] ?? $GLOBALS['wp_mock_fields'][$field] ?? null;
    }
}

if (!function_exists('get_fields')) {
    function get_fields(mixed $postId = false): array|false
    {
        return $GLOBALS['wp_mock_all_fields'][$postId] ?? false;
    }
}

if (!function_exists('get_sub_field')) {
    function get_sub_field(string $field): mixed
    {
        return $GLOBALS['wp_mock_sub_fields'][$field] ?? null;
    }
}

if (!function_exists('have_rows')) {
    function have_rows(string $field, mixed $postId = false): bool
    {
        $key = "{$field}:{$postId}";

        if (!isset($GLOBALS['wp_mock_have_rows_cursor'][$key])) {
            $GLOBALS['wp_mock_have_rows_cursor'][$key] = 0;
        }

        $rows = $GLOBALS['wp_mock_repeater_rows'][$field] ?? [];

        if ($GLOBALS['wp_mock_have_rows_cursor'][$key] < count($rows)) {
            $GLOBALS['wp_mock_current_row'] = $rows[$GLOBALS['wp_mock_have_rows_cursor'][$key]];
            ++$GLOBALS['wp_mock_have_rows_cursor'][$key];

            return true;
        }

        $GLOBALS['wp_mock_have_rows_cursor'][$key] = 0;

        return false;
    }
}

if (!function_exists('the_row')) {
    function the_row(): void
    {
        // Row is set in have_rows
    }
}

// WordPress attachment functions
if (!function_exists('wp_get_attachment_image_src')) {
    function wp_get_attachment_image_src(int $attachmentId, string $size = 'thumbnail'): array|false
    {
        return $GLOBALS['wp_mock_attachments'][$attachmentId][$size] ?? false;
    }
}

if (!function_exists('wp_get_attachment_image')) {
    function wp_get_attachment_image(int $attachmentId, string $size = 'thumbnail'): string
    {
        $src = wp_get_attachment_image_src($attachmentId, $size);
        if (!$src) {
            return '';
        }

        return sprintf('<img src="%s" width="%d" height="%d" />', $src[0], $src[1], $src[2]);
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta(int $postId, string $key = '', bool $single = false): mixed
    {
        $meta = $GLOBALS['wp_mock_post_meta'][$postId][$key] ?? null;

        return $single ? $meta : [$meta];
    }
}

// WordPress theme support
if (!function_exists('add_theme_support')) {
    function add_theme_support(string $feature, mixed ...$args): void
    {
        $GLOBALS['wp_mock_theme_support'][$feature] = $args ?: true;
    }
}

if (!function_exists('register_nav_menus')) {
    function register_nav_menus(array $locations): void
    {
        $GLOBALS['wp_mock_nav_menus'] = array_merge(
            $GLOBALS['wp_mock_nav_menus'] ?? [],
            $locations,
        );
    }
}

// WordPress admin functions
if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return $GLOBALS['wp_mock_is_admin'] ?? false;
    }
}

if (!function_exists('is_multisite')) {
    function is_multisite(): bool
    {
        return $GLOBALS['wp_mock_is_multisite'] ?? false;
    }
}

if (!function_exists('get_current_blog_id')) {
    function get_current_blog_id(): int
    {
        return $GLOBALS['wp_mock_current_blog_id'] ?? 1;
    }
}

if (!function_exists('get_queried_object_id')) {
    function get_queried_object_id(): int
    {
        return (int) ( $GLOBALS['wp_mock_queried_object_id'] ?? 0 );
    }
}

// Block editor functions
if (!function_exists('acf_register_block_type')) {
    function acf_register_block_type(array $settings): void
    {
        $GLOBALS['wp_mock_registered_blocks'][] = $settings;
    }
}

// WordPress script data functions
if (!function_exists('wp_script_add_data')) {
    function wp_script_add_data(string $handle, string $key, mixed $value): bool
    {
        $GLOBALS['wp_mock_script_data'][$handle][$key] = $value;

        return true;
    }
}

if (!function_exists('wp_doing_ajax')) {
    function wp_doing_ajax(): bool
    {
        return $GLOBALS['wp_mock_doing_ajax'] ?? false;
    }
}

// Filesystem functions
if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p(string $target): bool
    {
        if (is_dir($target)) {
            return true;
        }

        return mkdir($target, 0o755, true);
    }
}

// JSON functions
if (!function_exists('wp_json_encode')) {
    function wp_json_encode(mixed $data, int $options = 0, int $depth = 512): string|false
    {
        return json_encode($data, $options, $depth);  // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- test double mirrors the core implementation
    }
}

// Randomness functions
if (!function_exists('wp_rand')) {
    function wp_rand(int $min = 0, int $max = PHP_INT_MAX): int
    {
        return random_int($min, $max);
    }
}

// Script inline functions
if (!function_exists('wp_add_inline_script')) {
    function wp_add_inline_script(string $handle, string $data, string $position = 'after'): bool
    {
        $GLOBALS['wp_mock_inline_scripts'][$handle][$position][] = $data;

        return true;
    }
}

// Sanitization functions
if (!function_exists('wp_kses_test_double_safecss')) {
    /**
     * Minimal safecss test double for the `style` attribute: rejects values
     * that carry a known script-execution vector (url(), expression(),
     * javascript:, a backslash escape, or a stray angle bracket). Not a full
     * CSS parser or property allowlist.
     */
    function wp_kses_test_double_safecss(string $value): string
    {
        if (str_contains($value, '\\') || preg_match('/url\s*\(|expression\s*\(|javascript:|</i', $value)) {
            return '';
        }

        return $value;
    }
}

if (!function_exists('wp_kses')) {
    /**
     * Test-double, not a full port of core's wp_kses: it works with a regex
     * pass over tags/attributes instead of core's HTML parser, but it does
     * enforce what security assertions rely on: a per-tag allowlist, per-tag
     * attribute allowlists (including the `data-*`/`aria-*` wildcards core
     * uses), unconditional removal of any `on*` event-handler attribute, and
     * a scheme check on href/src/action via wp_kses_test_double_check_scheme().
     *
     * @param array<string, array<string, mixed>> $allowedTags
     */
    function wp_kses(?string $content, array $allowedTags = []): string
    {
        if ($content === null) {
            return '';
        }

        // Core does not preserve HTML comments through kses; strip them
        // before tag matching so nothing hides inside one.
        $content = preg_replace('/<!--.*?-->/s', '', $content) ?? $content;

        $allowedTags = array_change_key_case($allowedTags, CASE_LOWER);

        // Built replacement tags are parked behind a placeholder and spliced
        // back in only after stray '<' characters have been escaped, so a
        // malformed/unmatched tag can never smuggle markup through.
        $builtTags = [];

        $filtered = preg_replace_callback(
            '/<(\/?)([a-zA-Z][a-zA-Z0-9-]*)((?:\s+(?:"[^"]*"|\'[^\']*\'|[^"\'<>])*)?)\s*(\/?)>/',
            function (array $matches) use ($allowedTags, &$builtTags) {
                $closing = $matches[1] === '/';
                $tag = strtolower($matches[2]);
                $attrString = $matches[3];
                $selfClosing = $matches[4] === '/';

                if (!array_key_exists($tag, $allowedTags)) {
                    // Core strips a disallowed tag entirely and keeps only the
                    // inner text (wp_kses_post('<script>alert(1)</script>')
                    // returns 'alert(1)', not escaped tag markup) - it does
                    // not turn it into visible entities.
                    return '';
                }

                if ($closing) {
                    $builtTags[] = '</' . $tag . '>';

                    return "\x01" . ( count($builtTags) - 1 ) . "\x02";
                }

                $allowedAttrs = is_array($allowedTags[$tag]) ? array_change_key_case($allowedTags[$tag], CASE_LOWER) : [];
                $keptAttrs = '';

                preg_match_all(
                    '/([a-zA-Z_:][-\w:.]*)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+)))?/',
                    $attrString,
                    $attrMatches,
                    PREG_SET_ORDER
                );

                foreach ($attrMatches as $attrMatch) {
                    $name = strtolower($attrMatch[1]);
                    $value = '';

                    foreach ([2, 3, 4] as $groupIndex) {
                        if (isset($attrMatch[$groupIndex]) && $attrMatch[$groupIndex] !== '') {
                            $value = $attrMatch[$groupIndex];

                            break;
                        }
                    }

                    if (str_starts_with($name, 'on')) {
                        continue;
                    }

                    $isAllowed = isset($allowedAttrs[$name])
                        || ( str_starts_with($name, 'data-') && isset($allowedAttrs['data-*']) )
                        || ( str_starts_with($name, 'aria-') && isset($allowedAttrs['aria-*']) );

                    if (!$isAllowed) {
                        continue;
                    }

                    if (in_array($name, ['href', 'src', 'action'], true) && !wp_kses_test_double_check_scheme($value)) {
                        continue;
                    }

                    if ($name === 'style') {
                        $value = wp_kses_test_double_safecss($value);

                        if ($value === '') {
                            continue;
                        }
                    }

                    $keptAttrs .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
                }

                $builtTags[] = '<' . $tag . $keptAttrs . ( $selfClosing ? ' /' : '' ) . '>';

                return "\x01" . ( count($builtTags) - 1 ) . "\x02";
            },
            $content
        );

        $filtered = $filtered ?? $content;

        // Anything still starting with '<' at this point is a stray/malformed
        // angle bracket that never matched a full tag; escape it like core
        // does rather than leaving it as unescaped markup.
        $filtered = str_replace('<', '&lt;', $filtered);

        $filtered = preg_replace_callback(
            '/\x01(\d+)\x02/',
            static fn (array $m) => $builtTags[ (int) $m[1]],
            $filtered
        );

        return $filtered ?? $content;
    }
}

if (!function_exists('wp_kses_post')) {
    /**
     * Test-double allowlist mirroring core's post-context kses list closely
     * enough for tests: common inline/structural tags, `<img>`, tables. Core
     * does not carry `<form>` in the base post allowlist either; it and its
     * form-control tags only appear here through the `wp_kses_allowed_html`
     * filter added by AcfServiceProvider::allowFormControlTags() for `<form
     * action>` support in post content (see docs/CLAUDE.md "Audit Context").
     */
    function wp_kses_post(?string $content): string
    {
        $common = [
            'class' => true,
            'id' => true,
            'style' => true,
            'title' => true,
            'role' => true,
            'dir' => true,
            'lang' => true,
            'tabindex' => true,
            'data-*' => true,
            'aria-*' => true,
        ];

        $tags = [
            'p' => $common,
            'br' => [],
            'a' => array_merge($common, ['href' => true, 'target' => true, 'rel' => true]),
            'strong' => $common,
            'em' => $common,
            'b' => $common,
            'i' => $common,
            'u' => $common,
            'ul' => $common,
            'ol' => $common,
            'li' => $common,
            'h1' => $common,
            'h2' => $common,
            'h3' => $common,
            'h4' => $common,
            'h5' => $common,
            'h6' => $common,
            'blockquote' => $common,
            'code' => $common,
            'pre' => $common,
            'img' => array_merge($common, ['src' => true, 'alt' => true, 'width' => true, 'height' => true, 'srcset' => true, 'sizes' => true, 'loading' => true, 'decoding' => true]),
            'span' => $common,
            'div' => $common,
            'table' => $common,
            'thead' => $common,
            'tbody' => $common,
            'tr' => $common,
            'th' => $common,
            'td' => $common,
        ];

        $tags = apply_filters('wp_kses_allowed_html', $tags, 'post');

        return wp_kses($content ?? '', $tags);
    }
}

if (!function_exists('wp_filter_content_tags')) {
    /**
     * Passthrough test-double. Real core adds loading/width/height attributes
     * to <img> tags found in content; nothing in this project's tests
     * exercises that behaviour, they only need the function to exist so
     *
     * @kses-compiled views (AcfServiceProvider.php:174) can render.
     */
    function wp_filter_content_tags(?string $content, string $context = 'content'): string
    {
        return $content ?? '';
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $text): string
    {
        return strip_tags($text);  // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- test double mirrors the core implementation
    }
}

// Translation functions
if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = 'default'): string
    {
        return esc_html($text);
    }
}

// Transient functions
if (!function_exists('get_transient')) {
    function get_transient(string $transient): mixed
    {
        return $GLOBALS['wp_mock_transients'][$transient] ?? false;
    }
}

if (!function_exists('set_transient')) {
    function set_transient(string $transient, mixed $value, int $expiration = 0): bool
    {
        $GLOBALS['wp_mock_transients'][$transient] = $value;

        return true;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient(string $transient): bool
    {
        unset($GLOBALS['wp_mock_transients'][$transient]);

        return true;
    }
}

// Localization
if (!function_exists('wp_localize_script')) {
    function wp_localize_script(string $handle, string $objectName, array $l10n): bool
    {
        $GLOBALS['wp_mock_localized'][$handle][$objectName] = $l10n;

        return true;
    }
}

// Post functions
if (!function_exists('get_the_ID')) {
    function get_the_ID(): int|false
    {
        return $GLOBALS['wp_mock_post_id'] ?? false;
    }
}

// Theme functions
if (!function_exists('wp_get_theme')) {
    function wp_get_theme(): object
    {
        return new class() {
            public function get(string $header): string
            {
                return match ($header) {
                    'Name' => 'WP Starter',
                    'Version' => '1.0.0',
                    'TextDomain' => 'wp-starter',
                    default => '',
                };
            }
        };
    }
}

if (!function_exists('wp_parse_url')) {
    function wp_parse_url(string $url, int $component = -1): mixed
    {
        return $component === -1 ? parse_url($url) : parse_url($url, $component);  // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- test double mirrors the core implementation
    }
}

if (!function_exists('get_option')) {
    function get_option(string $option, mixed $default = false): mixed
    {
        return $GLOBALS['wp_mock_options'][$option] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option(string $option, mixed $value, mixed $autoload = null): bool
    {
        $GLOBALS['wp_mock_options'][$option] = $value;

        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option(string $option): bool
    {
        unset($GLOBALS['wp_mock_options'][$option]);

        return true;
    }
}

// Admin notices
if (!function_exists('add_settings_error')) {
    function add_settings_error(string $setting, string $code, string $message, string $type = 'error'): void
    {
        $GLOBALS['wp_mock_settings_errors'][] = [
            'setting' => $setting,
            'code' => $code,
            'message' => $message,
            'type' => $type,
        ];
    }
}

// Image functions
if (!function_exists('wp_get_attachment_image_url')) {
    function wp_get_attachment_image_url(int $attachmentId, string $size = 'thumbnail'): string|false
    {
        $src = wp_get_attachment_image_src($attachmentId, $size);

        return $src ? $src[0] : false;
    }
}

// URL functions
if (!function_exists('home_url')) {
    function home_url(string $path = ''): string
    {
        return 'https://example.com' . ( $path ? '/' . ltrim($path, '/') : '' );
    }
}

if (!function_exists('site_url')) {
    function site_url(string $path = ''): string
    {
        return 'https://example.com' . ( $path ? '/' . ltrim($path, '/') : '' );
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        return 'https://example.com/wp-admin' . ( $path ? '/' . ltrim($path, '/') : '' );
    }
}

// Nonce functions
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce(string $action = '-1'): string
    {
        return 'mock_nonce_' . $action;
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce(string $nonce, string $action = '-1'): int|false
    {
        return $nonce === 'mock_nonce_' . $action ? 1 : false;
    }
}

// Post type and taxonomy
if (!function_exists('register_post_type')) {
    function register_post_type(string $postType, array $args = []): void
    {
        $GLOBALS['wp_mock_post_types'][$postType] = $args;
    }
}

if (!function_exists('post_type_exists')) {
    function post_type_exists(string $postType): bool
    {
        return isset($GLOBALS['wp_mock_post_types'][$postType]);
    }
}

if (!function_exists('register_taxonomy')) {
    function register_taxonomy(string $taxonomy, array|string $objectType, array $args = []): void
    {
        $GLOBALS['wp_mock_taxonomies'][$taxonomy] = $args;
    }
}

// Current user functions
if (!function_exists('current_user_can')) {
    function current_user_can(string $capability): bool
    {
        return $GLOBALS['wp_mock_current_user_can'][$capability] ?? false;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id(): int
    {
        return $GLOBALS['wp_mock_current_user_id'] ?? 0;
    }
}

// Posts / permalinks
if (!function_exists('get_post')) {
    /**
     * Minimal get_post() stub.
     *
     * Reads $GLOBALS['wp_mock_posts_by_id'][$id] which tests fill with
     * ['post_type' => 'page', 'post_status' => 'private'].
     */
    function get_post(int|object|null $post = null): ?object
    {
        $id = is_object($post) ? (int) $post->ID : (int) $post;
        $data = $GLOBALS['wp_mock_posts_by_id'][$id] ?? null;

        if ($data === null) {
            return null;
        }

        return (object) array_merge(['ID' => $id, 'post_type' => 'page', 'post_status' => 'publish'], $data);
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta(int $postId, string $key, mixed $value, mixed $prev = ''): bool
    {
        $GLOBALS['wp_mock_post_meta'][$postId][$key] = $value;

        return true;
    }
}

if (!function_exists('delete_post_meta')) {
    function delete_post_meta(int $postId, string $key, mixed $value = ''): bool
    {
        unset($GLOBALS['wp_mock_post_meta'][$postId][$key]);

        return true;
    }
}

if (!function_exists('get_posts')) {
    function get_posts(array $args = []): array
    {
        $postType = $args['post_type'] ?? 'post';

        return $GLOBALS['wp_mock_posts'][$postType] ?? [];
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink(int|object|null $post = null): string|false
    {
        $id = is_object($post) ? ( $post->ID ?? 0 ) : (int) ( $post ?? 0 );

        return $GLOBALS['wp_mock_permalinks'][$id] ?? ( 'https://example.com/?p=' . $id );
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title(int|object|null $post = null): string
    {
        $id = is_object($post) ? ( $post->ID ?? 0 ) : (int) ( $post ?? 0 );

        return $GLOBALS['wp_mock_titles'][$id] ?? ( 'Post ' . $id );
    }
}

if (!function_exists('get_post_field')) {
    function get_post_field(string $field, int|object $post): string
    {
        $id = is_object($post) ? ( $post->ID ?? 0 ) : (int) $post;

        return $GLOBALS['wp_mock_post_fields'][$id][$field] ?? '';
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo(string $show = ''): string
    {
        return $GLOBALS['wp_mock_bloginfo'][$show] ?? '';
    }
}

// URL escaping / sanitization
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $str): string
    {
        return trim(strip_tags($str));  // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- test double mirrors the core implementation
    }
}

if (!function_exists('sanitize_file_name')) {
    function sanitize_file_name(string $filename): string
    {
        $specialChars = ['?', '[', ']', '/', '\\', '=', '<', '>', ':', ';', ',', "'", '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}', '%', '+', chr(0)];
        $filename = str_replace($specialChars, '', $filename);

        return trim($filename, '.-');
    }
}

if (!function_exists('sanitize_html_class')) {
    function sanitize_html_class(string $class, string $fallback = ''): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9_-]/', '', $class) ?? '';

        return $sanitized === '' ? $fallback : $sanitized;
    }
}

if (!function_exists('shortcode_atts')) {
    function shortcode_atts(array $pairs, array|string $atts, string $shortcode = ''): array
    {
        $atts = (array) $atts;
        $out = [];

        foreach ($pairs as $name => $default) {
            $out[$name] = array_key_exists($name, $atts) ? $atts[$name] : $default;
        }

        return $out;
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash(mixed $value): mixed
    {
        if (is_string($value)) {
            return stripslashes($value);
        }

        return $value;
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw(string $url): string
    {
        if (!wp_kses_test_double_check_scheme($url)) {
            return '';
        }

        return preg_replace('/[\x00-\x1F\x7F\s]/', '', $url);
    }
}

if (!function_exists('nocache_headers')) {
    function nocache_headers(): void
    {
        $GLOBALS['wp_mock_nocache_called'] = true;
    }
}

// Conditionals used by SeoServiceProvider
if (!function_exists('is_404')) {
    function is_404(): bool
    {
        return $GLOBALS['wp_mock_is_404'] ?? false;
    }
}

if (!function_exists('is_front_page')) {
    function is_front_page(): bool
    {
        return $GLOBALS['wp_mock_is_front_page'] ?? false;
    }
}

if (!function_exists('is_page')) {
    function is_page(mixed $page = ''): bool
    {
        return $GLOBALS['wp_mock_is_page'] ?? false;
    }
}

if (!function_exists('post_password_required')) {
    function post_password_required(mixed $post = null): bool
    {
        return $GLOBALS['wp_mock_password_required'] ?? false;
    }
}

if (!function_exists('get_the_excerpt')) {
    function get_the_excerpt(mixed $post = null): string
    {
        return $GLOBALS['wp_mock_excerpt'] ?? '';
    }
}

if (!function_exists('get_the_archive_description')) {
    function get_the_archive_description(): string
    {
        return $GLOBALS['wp_mock_archive_description'] ?? '';
    }
}

if (!function_exists('is_search')) {
    function is_search(): bool
    {
        return $GLOBALS['wp_mock_is_search'] ?? false;
    }
}

if (!function_exists('is_singular')) {
    function is_singular(string|array $type = ''): bool
    {
        return $GLOBALS['wp_mock_is_singular'] ?? false;
    }
}
