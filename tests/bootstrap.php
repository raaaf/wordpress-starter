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
$GLOBALS['wp_mock_loop_posts'] = [];
$GLOBALS['wp_mock_loop_cursor'] = 0;

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
        $url ??= '';

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
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr(?string $text): string
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('esc_js')) {
    /**
     * Mirrors core esc_js() (wp-includes/formatting.php): entity-encode
     * &, <, >, " (ENT_COMPAT, so a bare apostrophe is left alone), then
     * unwrap an already-encoded apostrophe entity back to a literal ' so a
     * value round-tripped through esc_html() first is not double-encoded,
     * strip \r, and finally addslashes() (escapes ', ", \, NUL) before
     * turning literal \n into the two-character sequence \n. No
     * apply_filters('js_escape', ...): the mock env registers no such
     * filter, so it would be a no-op.
     */
    function esc_js(?string $text): string
    {
        $safeText = htmlspecialchars($text ?? '', ENT_COMPAT, 'UTF-8', false);
        $safeText = preg_replace('/&#(x)?0*(?(1)27|39);?/i', "'", stripslashes($safeText)) ?? $safeText;
        $safeText = str_replace("\r", '', $safeText);

        return str_replace("\n", '\\n', addslashes($safeText));
    }
}

if (!function_exists('esc_html_e')) {
    function esc_html_e(string $text, string $domain = 'default'): void
    {
        // The mock __() below is an identity function, so translating first
        // adds nothing here; mirrors esc_html__() right above, which skips
        // it for the same reason.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- test double echoes the already-escaped value, same as core's esc_html_e()
        echo esc_html($text);
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
if (!function_exists('wp_get_attachment_metadata')) {
    /**
     * Minimal wp_get_attachment_metadata() stub, reusing the same
     * $GLOBALS['wp_mock_attachments'][$id]['logo'] triple (url, width,
     * height) tests already seed for wp_get_attachment_image_url().
     */
    function wp_get_attachment_metadata(int $attachmentId, bool $unfiltered = false): array|false
    {
        foreach ($GLOBALS['wp_mock_attachments'][$attachmentId] ?? [] as $sizeData) {
            if (is_array($sizeData) && isset($sizeData[1], $sizeData[2])) {
                return ['width' => $sizeData[1], 'height' => $sizeData[2]];
            }
        }

        return false;
    }
}

if (!function_exists('wp_get_attachment_image_src')) {
    function wp_get_attachment_image_src(int $attachmentId, string $size = 'thumbnail'): array|false
    {
        return $GLOBALS['wp_mock_attachments'][$attachmentId][$size] ?? false;
    }
}

if (!function_exists('wp_get_attachment_image')) {
    function wp_get_attachment_image(int $attachmentId, string $size = 'thumbnail', bool $icon = false, array $attr = []): string
    {
        $src = wp_get_attachment_image_src($attachmentId, $size);
        if (!$src) {
            return '';
        }

        $alt = isset($attr['alt']) ? ' alt="' . esc_attr($attr['alt']) . '"' : '';

        return sprintf('<img src="%s" width="%d" height="%d"%s />', $src[0], $src[1], $src[2], $alt);
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
                    PREG_SET_ORDER,
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
            $content,
        );

        $filtered ??= $content;

        // Anything still starting with '<' at this point is a stray/malformed
        // angle bracket that never matched a full tag; escape it like core
        // does rather than leaving it as unescaped markup.
        $filtered = str_replace('<', '&lt;', $filtered);

        $filtered = preg_replace_callback(
            '/\x01(\d+)\x02/',
            static fn (array $m) => $builtTags[ (int) $m[1]],
            $filtered,
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

if (!function_exists('force_balance_tags')) {
    /**
     * Minimal stack-based re-implementation of core's tag balancer: closes
     * any still-open tags at the end of the string and drops closing tags
     * that have no matching opener. Good enough for the unclosed-heading
     * demotion pass in FooterAlertBar::demoteHeadings() (does not attempt
     * core's HTML-comment/CDATA edge cases).
     */
    function force_balance_tags(string $text): string
    {
        $voidTags = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];
        $stack = [];
        $offset = 0;

        $result = (string) preg_replace_callback(
            '#<(/?)([a-zA-Z][a-zA-Z0-9]*)[^>]*>#',
            static function (array $matches) use (&$stack, $voidTags): string {
                $isClosing = $matches[1] === '/';
                $tagName = strtolower($matches[2]);

                if (in_array($tagName, $voidTags, true)) {
                    return $matches[0];
                }

                if ($isClosing) {
                    if (!in_array($tagName, $stack, true)) {
                        return '';
                    }

                    $closed = '';
                    while ($stack) {
                        $open = array_pop($stack);
                        $closed .= '</' . $open . '>';
                        if ($open === $tagName) {
                            break;
                        }
                    }

                    return $closed;
                }

                $stack[] = $tagName;

                return $matches[0];
            },
            $text
        );

        while ($stack) {
            $result .= '</' . array_pop($stack) . '>';
        }

        return $result;
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

// Main-loop doubles for templates/partials that iterate the global query
// directly (have_posts()/the_post()), rather than through a WP_Query
// instance. Tests seed $GLOBALS['wp_mock_loop_posts'] with the posts to
// iterate; the_post() advances a cursor and sets $GLOBALS['post'].
if (!function_exists('have_posts')) {
    function have_posts(): bool
    {
        $posts = $GLOBALS['wp_mock_loop_posts'] ?? [];
        $cursor = $GLOBALS['wp_mock_loop_cursor'] ?? 0;

        return $cursor < count($posts);
    }
}

if (!function_exists('the_post')) {
    function the_post(): void
    {
        $posts = $GLOBALS['wp_mock_loop_posts'] ?? [];
        $cursor = $GLOBALS['wp_mock_loop_cursor'] ?? 0;

        if ($cursor >= count($posts)) {
            return;
        }

        $GLOBALS['post'] = $posts[$cursor]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test double mirrors core's the_post(), which sets this same global
        $GLOBALS['wp_mock_loop_cursor'] = $cursor + 1;
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

if (!function_exists('wp_parse_str')) {
    function wp_parse_str(string $string, mixed &$array): void
    {
        parse_str($string, $array);  // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_str_parse_str -- test double mirrors the core implementation
    }
}

if (!function_exists('wp_check_filetype')) {
    /**
     * Minimal wp_check_filetype() double: extension -> [ext, type] for the
     * types used across theme uploads (video, audio, image), false for
     * anything else, mirroring the shape core returns.
     */
    function wp_check_filetype(string $filename, ?array $mimes = null): array
    {
        $types = [
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'ogv' => 'video/ogg',
            'mp3' => 'audio/mpeg',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
        ];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return isset($types[$ext])
            ? ['ext' => $ext, 'type' => $types[$ext]]
            : ['ext' => false, 'type' => false];
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

if (!function_exists('get_privacy_policy_url')) {
    function get_privacy_policy_url(): string
    {
        return $GLOBALS['wp_mock_privacy_policy_url'] ?? '';
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

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in(): bool
    {
        return ( $GLOBALS['wp_mock_current_user_id'] ?? 0 ) > 0;
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

if (!function_exists('get_post_types')) {
    /**
     * Minimal get_post_types() stub, filtering the same
     * $GLOBALS['wp_mock_post_types'] map register_post_type()/
     * post_type_exists() already use. Only the 'names' output (an array of
     * slug => slug, core's default) is needed by callers in this theme.
     */
    function get_post_types(array $args = [], string $output = 'names'): array
    {
        $postTypes = $GLOBALS['wp_mock_post_types'] ?? [];
        $matching = array_filter($postTypes, static function (array $registeredArgs) use ($args): bool {
            foreach ($args as $key => $value) {
                if (( $registeredArgs[$key] ?? null ) !== $value) {
                    return false;
                }
            }

            return true;
        });

        $names = array_keys($matching);

        return array_combine($names, $names);
    }
}

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed -- test double needs a class alongside the function mocks in this single bootstrap file
if (!class_exists('WP_Query')) {
    /**
     * Minimal WP_Query double: no post data, always empty. Templates that
     * need the query to actually return posts are out of scope for the
     * render smoke test, which only proves the query can be constructed and
     * the empty-result branch renders without throwing.
     */
    class WP_Query
    {
        /** @var list<mixed> */
        public array $posts = [];

        /** @param array<string, mixed> $args */
        public function __construct(array $args = [])
        {
            $GLOBALS['wp_mock_last_query_args'] = $args;
        }

        public function have_posts(): bool
        {
            return false;
        }

        public function the_post(): void
        {
        }
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

// Post thumbnail / taxonomy / excerpt helpers used by templates/partials that
// iterate the main loop (post-loop.blade.php). Always the empty/false shape,
// same tolerance level as the other post-context doubles above: enough for a
// render smoke test to prove the template does not throw, not a content
// fixture.
if (!function_exists('has_post_thumbnail')) {
    function has_post_thumbnail(int|object|null $post = null): bool
    {
        return false;
    }
}

if (!function_exists('get_the_post_thumbnail')) {
    function get_the_post_thumbnail(int|object|null $post = null, string $size = 'post-thumbnail', string|array $attr = ''): string
    {
        return '';
    }
}

if (!function_exists('has_category')) {
    function has_category(int|string|array $category = '', int|object|null $post = null): bool
    {
        return false;
    }
}

if (!function_exists('get_the_category')) {
    /** @return list<object> */
    function get_the_category(int|object|null $post = null): array
    {
        return [];
    }
}

if (!function_exists('get_terms')) {
    /**
     * Faithful enough double: reads a flat list of term-like stdClass objects
     * (slug, name) registered per taxonomy via $GLOBALS['wp_mock_terms'], core
     * returns those objects or a WP_Error, never false.
     *
     * @param array<string, mixed> $args
     *
     * @return list<object>
     */
    function get_terms(array $args = []): array
    {
        $taxonomy = $args['taxonomy'] ?? '';

        return $GLOBALS['wp_mock_terms'][$taxonomy] ?? [];
    }
}

if (!function_exists('get_the_terms')) {
    /**
     * @return list<object>|false
     */
    function get_the_terms(int|object|null $post = null, string $taxonomy = ''): array|false
    {
        $id = is_object($post) ? ( $post->ID ?? 0 ) : (int) ( $post ?? 0 );

        return $GLOBALS['wp_mock_post_terms'][$id][$taxonomy] ?? false;
    }
}

if (!function_exists('wp_specialchars_decode')) {
    function wp_specialchars_decode(string $text, int $quoteStyle = ENT_NOQUOTES): string
    {
        return htmlspecialchars_decode($text, $quoteStyle);
    }
}

if (!function_exists('wp_trim_words')) {
    function wp_trim_words(string $text, int $numWords = 55, ?string $more = null): string
    {
        $words = preg_split('/[\n\r\t ]+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) <= $numWords) {
            return $text;
        }

        $more ??= '…';

        return implode(' ', array_slice($words, 0, $numWords)) . $more;
    }
}

if (!function_exists('get_the_date')) {
    function get_the_date(string $format = '', int|object|null $post = null): string
    {
        return $GLOBALS['wp_mock_the_date'] ?? '';
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
if (!function_exists('absint')) {
    function absint(mixed $maybeint): int
    {
        return abs( (int) $maybeint);
    }
}

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

if (!function_exists('remove_accents')) {
    function remove_accents(string $string): string
    {
        // Faithful to WordPress core's DEFAULT (non-German-locale) map for
        // the accents that occur in this theme's German-language content;
        // not the full core table. Core only maps ä/ö/ü to "ae"/"oe"/"ue"
        // when get_locale() is one of the de_* locales; this codebase runs
        // single-locale (see TRANSLATION_DATEIEN) and never switches that
        // check on, so the map here has to mirror the "else" branch instead
        // ('ä' => 'a', not 'ae'), or an anchor like "Über uns" would slugify
        // to "ueber-uns" here while sanitize_title() on the actual German
        // WordPress install this theme ships to produces "uber-uns".
        $map = [
            'ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'Ä' => 'A', 'Ö' => 'O', 'Ü' => 'U', 'ß' => 'ss',
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ç' => 'c', 'ñ' => 'n',
        ];

        return strtr($string, $map);
    }
}

if (!function_exists('sanitize_title')) {
    function sanitize_title(string $title, string $fallback = ''): string
    {
        // Faithful enough port of core's sanitize_title_with_dashes() for
        // the cases this theme actually hits: underscores survive (core's
        // charset keeps '_', unlike sanitize_html_class()), a literal dot
        // becomes a hyphen (core: str_replace('.', '-', $title)), and any
        // remaining non-ASCII byte sequence is percent-encoded rather than
        // silently dropped (core: utf8_uri_encode()) so a title outside the
        // accent map above still yields a valid, non-empty id fragment
        // instead of losing the character entirely.
        $title = remove_accents($title);
        $title = mb_strtolower($title);
        $title = preg_replace_callback(
            '/[^\x00-\x7f]+/',
            static fn (array $matches): string => strtolower(rawurlencode($matches[0])),
            $title,
        ) ?? '';
        $title = str_replace('.', '-', $title);
        $title = preg_replace('/[^a-z0-9%_\s-]/', '', $title) ?? '';
        $title = preg_replace('/[\s-]+/', '-', $title) ?? '';
        $title = trim($title, '-');

        return $title === '' ? $fallback : $title;
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

// Member-area auth doubles: wp_signon()/wp_check_password()/wp_set_current_user()/
// wp_clear_auth_cookie()/wp_logout()/wp_get_current_user()/wp_salt()/is_ssl(), plus a
// minimal WP_Error class and is_wp_error(), all needed to exercise
// WordpressStarter\MemberArea\Auth without a real WordPress runtime.
if (!defined('COOKIEPATH')) {
    define('COOKIEPATH', '/');
}

if (!defined('COOKIE_DOMAIN')) {
    define('COOKIE_DOMAIN', '');
}

if (!class_exists('WP_Error')) {
    /**
     * Minimal WP_Error double: a single code/message/data triple, enough for
     * the codes this theme's WP_Error usage actually branches on.
     */
    class WP_Error // phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound -- test double needs a class alongside the function mocks in this single bootstrap file
    {
        private string $code;
        private string $message;
        private mixed $data;

        public function __construct(string $code = '', string $message = '', mixed $data = '')
        {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_message(string $code = ''): string
        {
            return $this->message;
        }

        public function get_error_data(string $code = ''): mixed
        {
            return $this->data;
        }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error(mixed $thing): bool
    {
        return $thing instanceof WP_Error;
    }
}

if (!function_exists('is_ssl')) {
    function is_ssl(): bool
    {
        return $GLOBALS['wp_mock_is_ssl'] ?? false;
    }
}

if (!function_exists('wp_salt')) {
    function wp_salt(string $scheme = 'auth'): string
    {
        return 'test-salt-' . $scheme;
    }
}

if (!function_exists('wp_signon')) {
    /**
     * Tests seed $GLOBALS['wp_mock_signon_result'] with either a WP_Error or
     * an object carrying an ->ID property (mirrors a WP_User).
     */
    function wp_signon(array $credentials = [], bool $secureCookie = false): object
    {
        return $GLOBALS['wp_mock_signon_result'] ?? new WP_Error('invalid_username', 'Invalid username.');
    }
}

if (!function_exists('wp_check_password')) {
    function wp_check_password(string $password, string $hash, int|string $userId = ''): bool
    {
        return $GLOBALS['wp_mock_check_password_result'] ?? false;
    }
}

if (!function_exists('wp_set_current_user')) {
    function wp_set_current_user(int $id): void
    {
        $GLOBALS['wp_mock_current_user_id'] = $id;
    }
}

if (!function_exists('wp_clear_auth_cookie')) {
    function wp_clear_auth_cookie(): void
    {
        $GLOBALS['wp_mock_clear_auth_cookie_called'] = true;
    }
}

if (!function_exists('wp_logout')) {
    function wp_logout(): void
    {
        $GLOBALS['wp_mock_logout_called'] = true;
        $GLOBALS['wp_mock_current_user_id'] = 0;
    }
}

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user(): object
    {
        return (object) ['roles' => $GLOBALS['wp_mock_current_user_roles'] ?? []];
    }
}

if (!function_exists('wp_send_json_error')) {
    /**
     * Real core's wp_send_json_error() JSON-encodes the payload and calls
     * wp_die(), which terminates the request. Throws instead, so a test can
     * assert the payload/status of an AJAX handler without ending the PHP
     * process (see Tests\Support\WpJsonResponseException).
     *
     * @throws \Tests\Support\WpJsonResponseException always
     */
    function wp_send_json_error(mixed $data = null, ?int $statusCode = null): never
    {
        throw new \Tests\Support\WpJsonResponseException($data, $statusCode, false); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- test double, not real output
    }
}

if (!function_exists('wp_send_json_success')) {
    /**
     * @throws \Tests\Support\WpJsonResponseException always
     */
    function wp_send_json_success(mixed $data = null, ?int $statusCode = null): never
    {
        throw new \Tests\Support\WpJsonResponseException($data, $statusCode, true); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- test double, not real output
    }
}

if (!function_exists('wp_using_ext_object_cache')) {
    // Core returns the raw global, which is null until wp_start_object_cache()
    // set it; a bool-typed double hid a TypeError in RateLimiter (2026-09-05).
    function wp_using_ext_object_cache(): ?bool
    {
        return $GLOBALS['wp_mock_using_ext_object_cache'] ?? null;
    }
}

if (!function_exists('is_singular')) {
    function is_singular(string|array $type = ''): bool
    {
        return $GLOBALS['wp_mock_is_singular'] ?? false;
    }
}
