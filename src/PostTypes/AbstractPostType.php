<?php

declare(strict_types=1);

namespace WordpressStarter\PostTypes;

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
     * Get all posts of this type
     *
     * @param array<string, mixed> $args Additional WP_Query arguments
     * @return \WP_Post[]
     */
    public static function all(array $args = []): array
    {
        $defaults = [
            'post_type' => static::$postType,
            'posts_per_page' => -1,
            'post_status' => 'publish',
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
