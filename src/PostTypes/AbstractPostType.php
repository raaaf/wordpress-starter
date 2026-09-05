<?php

declare(strict_types=1);

namespace WordpressStarter\PostTypes;

use WordpressStarter\Providers\LogServiceProvider;

/**
 * Abstract base class for Custom Post Types
 *
 * Provides a fluent interface for registering custom post types with
 * consistent patterns and default configurations.
 *
 * Usage:
 *   class Testimonial extends AbstractPostType {
 *       protected static string $postType = 'testimonial';
 *       protected static string $singular = 'Testimonial';
 *       protected static string $plural = 'Testimonials';
 *   }
 *
 *   Testimonial::register();
 */
abstract class AbstractPostType
{
    /**
     * The post type slug (e.g., 'testimonial')
     */
    protected static string $postType = '';

    /**
     * Singular label (e.g., 'Testimonial')
     */
    protected static string $singular = '';

    /**
     * Plural label (e.g., 'Testimonials')
     */
    protected static string $plural = '';

    /**
     * Dashicon or custom icon URL for admin menu
     */
    protected static string $menuIcon = 'dashicons-admin-post';

    /**
     * Menu position in admin sidebar
     */
    protected static int $menuPosition = 20;

    /**
     * Whether the post type is public
     */
    protected static bool $public = true;

    /**
     * Whether to show in admin menu
     */
    protected static bool $showInMenu = true;

    /**
     * Whether to show in REST API
     */
    protected static bool $showInRest = true;

    /**
     * Post type supports (title, editor, thumbnail, etc.)
     *
     * @var array<string>
     */
    protected static array $supports = ['title', 'thumbnail'];

    /**
     * Whether this post type has an archive page
     */
    protected static bool $hasArchive = true;

    /**
     * Custom rewrite rules
     *
     * @var array<string, mixed>|false
     */
    protected static array|false $rewrite = [];

    /**
     * Associated taxonomies
     *
     * @var array<string>
     */
    protected static array $taxonomies = [];

    /**
     * When set, this post type's edit, delete, publish, create, and
     * read-private-posts capability checks (including their "others'"
     * and "published" variants) map to this single WordPress capability
     * instead of the default post capability_type. Ordinary "read" of a
     * published post stays unaffected, and there is no "list" meta cap
     * in WordPress. Use this when the post type holds data more
     * sensitive than an ordinary post (e.g. stored credentials), so
     * Contributors/Authors with plain edit_posts cannot touch it.
     */
    protected static ?string $requiredCapability = null;

    /**
     * Register the custom post type
     */
    public static function register(): void
    {
        add_action('init', [static::class, 'registerPostType']);
    }

    /**
     * Register the post type with WordPress
     */
    public static function registerPostType(): void
    {
        $labels = static::getLabels();
        $args = static::getArgs($labels);

        register_post_type(static::$postType, $args);
    }

    /**
     * Get the post type slug
     */
    public static function getPostType(): string
    {
        return static::$postType;
    }

    /**
     * Get localized labels for the post type
     *
     * @return array<string, string>
     */
    protected static function getLabels(): array
    {
        $singular = static::$singular;
        $plural = static::$plural;

        return [
            'name' => $plural,
            'singular_name' => $singular,
            // translators: %s is the singular post type name
            'add_new' => sprintf(__('%s hinzufügen', 'wp-starter'), $singular),
            // translators: %s is the singular post type name
            'add_new_item' => sprintf(__('Neue %s hinzufügen', 'wp-starter'), $singular),
            // translators: %s is the singular post type name
            'edit_item' => sprintf(__('%s bearbeiten', 'wp-starter'), $singular),
            // translators: %s is the singular post type name
            'new_item' => sprintf(__('Neue %s', 'wp-starter'), $singular),
            // translators: %s is the singular post type name
            'view_item' => sprintf(__('%s ansehen', 'wp-starter'), $singular),
            // translators: %s is the plural post type name
            'view_items' => sprintf(__('%s ansehen', 'wp-starter'), $plural),
            // translators: %s is the plural post type name
            'search_items' => sprintf(__('%s suchen', 'wp-starter'), $plural),
            // translators: %s is the plural post type name
            'not_found' => sprintf(__('Keine %s gefunden', 'wp-starter'), $plural),
            // translators: %s is the plural post type name
            'not_found_in_trash' => sprintf(__('Keine %s im Papierkorb', 'wp-starter'), $plural),
            // translators: %s is the singular post type name
            'parent_item_colon' => sprintf(__('Übergeordnete %s:', 'wp-starter'), $singular),
            // translators: %s is the plural post type name
            'all_items' => sprintf(__('Alle %s', 'wp-starter'), $plural),
            // translators: %s is the plural post type name
            'archives' => sprintf(__('%s Archiv', 'wp-starter'), $plural),
            // translators: %s is the plural post type name
            'attributes' => sprintf(__('%s Attribute', 'wp-starter'), $plural),
            // translators: %s is the singular post type name
            'insert_into_item' => sprintf(__('In %s einfügen', 'wp-starter'), $singular),
            // translators: %s is the singular post type name
            'uploaded_to_this_item' => sprintf(__('Zu dieser %s hochgeladen', 'wp-starter'), $singular),
            // translators: %s is the plural post type name
            'filter_items_list' => sprintf(__('%s Liste filtern', 'wp-starter'), $plural),
            // translators: %s is the plural post type name
            'items_list_navigation' => sprintf(__('%s Liste Navigation', 'wp-starter'), $plural),
            // translators: %s is the plural post type name
            'items_list' => sprintf(__('%s Liste', 'wp-starter'), $plural),
            // translators: %s is the singular post type name
            'item_published' => sprintf(__('%s veröffentlicht.', 'wp-starter'), $singular),
            // translators: %s is the singular post type name
            'item_published_privately' => sprintf(__('%s privat veröffentlicht.', 'wp-starter'), $singular),
            // translators: %s is the singular post type name
            'item_reverted_to_draft' => sprintf(__('%s als Entwurf gespeichert.', 'wp-starter'), $singular),
            // translators: %s is the singular post type name
            'item_scheduled' => sprintf(__('%s geplant.', 'wp-starter'), $singular),
            // translators: %s is the singular post type name
            'item_updated' => sprintf(__('%s aktualisiert.', 'wp-starter'), $singular),
            'menu_name' => $plural,
            'name_admin_bar' => $singular,
        ];
    }

    /**
     * Get post type registration arguments
     *
     * @param array<string, string> $labels
     * @return array<string, mixed>
     */
    protected static function getArgs(array $labels): array
    {
        $args = [
            'labels' => $labels,
            'public' => static::$public,
            'publicly_queryable' => static::$public,
            'show_ui' => true,
            'show_in_menu' => static::$showInMenu,
            'show_in_rest' => static::$showInRest,
            'query_var' => true,
            'capability_type' => 'post',
            'has_archive' => static::$hasArchive,
            'hierarchical' => false,
            'menu_position' => static::$menuPosition,
            'menu_icon' => static::$menuIcon,
            'supports' => static::$supports,
            'taxonomies' => static::$taxonomies,
        ];

        if (static::$requiredCapability !== null) {
            $cap = static::$requiredCapability;
            $args['map_meta_cap'] = true;
            $args['capabilities'] = [
                'edit_posts' => $cap,
                'edit_others_posts' => $cap,
                'publish_posts' => $cap,
                'read_private_posts' => $cap,
                'delete_posts' => $cap,
                'delete_private_posts' => $cap,
                'delete_published_posts' => $cap,
                'delete_others_posts' => $cap,
                'edit_private_posts' => $cap,
                'edit_published_posts' => $cap,
                'create_posts' => $cap,
            ];
        }

        // Set rewrite rules
        if (static::$rewrite !== false) {
            $args['rewrite'] = array_merge(
                ['slug' => static::$postType, 'with_front' => false],
                static::$rewrite
            );
        } else {
            $args['rewrite'] = false;
        }

        return $args;
    }

    /**
     * Register ACF fields for this post type
     *
     * Override this method in child classes to add custom fields.
     */
    public static function registerFields(): void
    {
        // Override in child class
    }

    /**
     * Declarative admin list-table columns for this post type.
     *
     * Override in child classes. Each entry:
     *   'label'    => string column header
     *   'render'   => callable(int $postId): void
     *   'sortable' => string|null meta key to sort by (null/omitted = not sortable)
     *   'sort_type' => 'meta_value'|'meta_value_num' (default 'meta_value')
     *   'width'    => int|null column width in px (null/omitted = no forced width)
     *   'before'   => string|null existing column key to insert this column before
     *   'after'    => string|null existing column key to insert this column after
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function adminColumns(): array
    {
        return [];
    }

    /**
     * Wire the four admin-column hooks (columns, render, sortable, width)
     * from the declarative adminColumns() definition.
     */
    protected static function registerAdminColumns(): void
    {
        $definitions = static::adminColumns();
        if ($definitions === []) {
            return;
        }

        add_filter('manage_' . static::$postType . '_posts_columns', function (array $columns) use ($definitions): array {
            $newColumns = [];
            foreach ($columns as $key => $value) {
                foreach ($definitions as $columnKey => $definition) {
                    if (( $definition['before'] ?? null ) === $key) {
                        $newColumns[$columnKey] = $definition['label'];
                    }
                }
                $newColumns[$key] = $value;
                foreach ($definitions as $columnKey => $definition) {
                    if (( $definition['after'] ?? null ) === $key) {
                        $newColumns[$columnKey] = $definition['label'];
                    }
                }
            }

            // A 'before'/'after' anchor that names a column key which never
            // appears in $columns would otherwise silently drop the column.
            // Append it at the end instead, so it always renders.
            foreach ($definitions as $columnKey => $definition) {
                if (isset($newColumns[$columnKey])) {
                    continue;
                }

                $newColumns[$columnKey] = $definition['label'];

                LogServiceProvider::warning('Admin column anchor not found, appended at the end', [
                    'post_type' => static::$postType,
                    'column' => $columnKey,
                    'anchor' => $definition['before'] ?? $definition['after'] ?? null,
                ]);
            }

            return $newColumns;
        });

        add_action('manage_' . static::$postType . '_posts_custom_column', function (string $column, int $postId) use ($definitions): void {
            if (isset($definitions[$column]['render'])) {
                ( $definitions[$column]['render'] )($postId);
            }
        }, 10, 2);

        add_filter('manage_edit-' . static::$postType . '_sortable_columns', function (array $columns) use ($definitions): array {
            foreach ($definitions as $columnKey => $definition) {
                if (!empty($definition['sortable'])) {
                    $columns[$columnKey] = $columnKey;
                }
            }

            return $columns;
        });

        add_action('pre_get_posts', function (\WP_Query $query) use ($definitions): void {
            if (!is_admin() || !$query->is_main_query()) {
                return;
            }
            if ($query->get('post_type') !== static::$postType) {
                return;
            }

            $orderby = $query->get('orderby');
            foreach ($definitions as $columnKey => $definition) {
                if (!empty($definition['sortable']) && $orderby === $columnKey) {
                    $query->set('meta_key', $definition['sortable']); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                    $query->set('orderby', $definition['sort_type'] ?? 'meta_value');
                }
            }
        });

        $widths = [];
        foreach ($definitions as $columnKey => $definition) {
            if (!empty($definition['width'])) {
                $widths[$columnKey] = $definition['width'];
            }
        }

        if ($widths !== []) {
            add_action('admin_head', function () use ($widths): void {
                $screen = get_current_screen();
                if (!$screen || $screen->post_type !== static::$postType) {
                    return;
                }

                $css = '';
                foreach ($widths as $columnKey => $width) {
                    $css .= '.column-' . $columnKey . ' { width: ' . $width . 'px; } ';
                }
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static column keys/widths, not user input
                echo '<style>' . trim($css) . '</style>';
            });
        }
    }

    /**
     * Get all posts of this type
     *
     * @param array<string, mixed> $args Additional WP_Query arguments
     * @return \WP_Post[]
     */
    public static function all(array $args = []): array
    {
        $defaults = [
            'post_type'              => static::$postType,
            'posts_per_page'         => -1,
            'post_status'            => 'publish',
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
        ];

        $query = new \WP_Query(array_merge($defaults, $args));
        return $query->posts;
    }

    /**
     * Find a post by ID
     */
    public static function find(int $id): ?\WP_Post
    {
        $post = get_post($id);

        if (!$post || $post->post_type !== static::$postType) {
            return null;
        }

        return $post;
    }
}
