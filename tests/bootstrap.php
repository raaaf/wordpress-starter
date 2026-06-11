<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap File
 *
 * This file is loaded before any tests run.
 * It sets up WordPress constants and mock functions.
 */

// Define our own env() function BEFORE loading the autoloader
// This prevents Laravel's illuminate/support from overriding it
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }
}

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

// WordPress escaping functions
if (!function_exists('esc_url')) {
    function esc_url(?string $url): string
    {
        return htmlspecialchars($url ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

// WordPress cache functions
if (!function_exists('wp_cache_get')) {
    function wp_cache_get(string $key, string $group = 'default'): mixed
    {
        $cacheKey = "{$group}:{$key}";

        return $GLOBALS['wp_mock_cache'][$cacheKey] ?? false;
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
        static $index = [];
        $key = "{$field}:{$postId}";

        if (!isset($index[$key])) {
            $index[$key] = 0;
        }

        $rows = $GLOBALS['wp_mock_repeater_rows'][$field] ?? [];

        if ($index[$key] < count($rows)) {
            $GLOBALS['wp_mock_current_row'] = $rows[$index[$key]];
            ++$index[$key];

            return true;
        }

        $index[$key] = 0;

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
        return json_encode($data, $options, $depth);
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
if (!function_exists('wp_kses_post')) {
    function wp_kses_post(?string $content): string
    {
        // Simplified - just return the content in tests (core accepts null)
        return $content ?? '';
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $text): string
    {
        return strip_tags($text);
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
        return str_starts_with($nonce, 'mock_nonce_') ? 1 : false;
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
        return trim(strip_tags($str));
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
        return $url;
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

if (!function_exists('is_singular')) {
    function is_singular(string|array $type = ''): bool
    {
        return $GLOBALS['wp_mock_is_singular'] ?? false;
    }
}
