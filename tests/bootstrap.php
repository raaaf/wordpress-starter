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
        return get_template_directory() . ($file ? '/' . ltrim($file, '/') : '');
    }
}

if (!function_exists('get_theme_file_uri')) {
    function get_theme_file_uri(string $file = ''): string
    {
        return get_template_directory_uri() . ($file ? '/' . ltrim($file, '/') : '');
    }
}

// WordPress escaping functions
if (!function_exists('esc_url')) {
    function esc_url(string $url): string
    {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
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
    function add_action(string $hook, callable $callback, int $priority = 10, int $args = 1): bool
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
    function add_filter(string $hook, callable $callback, int $priority = 10, int $args = 1): bool
    {
        $GLOBALS['wp_mock_hooks']['filters'][$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
            'args' => $args,
        ];
        return true;
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
        array|bool $args = false
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
        string $media = 'all'
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
            $index[$key]++;
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
            $locations
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
