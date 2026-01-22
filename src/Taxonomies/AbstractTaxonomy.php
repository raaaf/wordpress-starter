<?php

declare(strict_types=1);

namespace WordpressStarter\Taxonomies;

/**
 * Abstract base class for Custom Taxonomies
 *
 * Provides a fluent interface for registering custom taxonomies with
 * consistent patterns and default configurations.
 *
 * Usage:
 *   class ServiceCategory extends AbstractTaxonomy {
 *       protected static string $taxonomy = 'service_category';
 *       protected static string $singular = 'Kategorie';
 *       protected static string $plural = 'Kategorien';
 *       protected static array $postTypes = ['service'];
 *   }
 *
 *   ServiceCategory::register();
 */
abstract class AbstractTaxonomy
{
    /**
     * The taxonomy slug
     */
    protected static string $taxonomy = '';

    /**
     * Singular label
     */
    protected static string $singular = '';

    /**
     * Plural label
     */
    protected static string $plural = '';

    /**
     * Post types to attach this taxonomy to
     *
     * @var array<string>
     */
    protected static array $postTypes = [];

    /**
     * Whether the taxonomy is hierarchical (like categories)
     */
    protected static bool $hierarchical = true;

    /**
     * Whether the taxonomy is public
     */
    protected static bool $public = true;

    /**
     * Whether to show in admin UI
     */
    protected static bool $showUi = true;

    /**
     * Whether to show in REST API
     */
    protected static bool $showInRest = true;

    /**
     * Whether to show admin column in post list
     */
    protected static bool $showAdminColumn = true;

    /**
     * Custom rewrite rules
     *
     * @var array<string, mixed>|false
     */
    protected static array|false $rewrite = [];

    /**
     * Register the custom taxonomy
     */
    public static function register(): void
    {
        add_action('init', [static::class, 'registerTaxonomy']);
    }

    /**
     * Register the taxonomy with WordPress
     */
    public static function registerTaxonomy(): void
    {
        $labels = static::getLabels();
        $args = static::getArgs($labels);

        register_taxonomy(static::$taxonomy, static::$postTypes, $args);
    }

    /**
     * Get the taxonomy slug
     */
    public static function getTaxonomy(): string
    {
        return static::$taxonomy;
    }

    /**
     * Get localized labels for the taxonomy
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
            // translators: %s is the plural taxonomy name
            'search_items' => sprintf(__('%s suchen', 'wp-starter'), $plural),
            // translators: %s is the plural taxonomy name
            'all_items' => sprintf(__('Alle %s', 'wp-starter'), $plural),
            // translators: %s is the singular taxonomy name
            'parent_item' => sprintf(__('Übergeordnete %s', 'wp-starter'), $singular),
            // translators: %s is the singular taxonomy name
            'parent_item_colon' => sprintf(__('Übergeordnete %s:', 'wp-starter'), $singular),
            // translators: %s is the singular taxonomy name
            'edit_item' => sprintf(__('%s bearbeiten', 'wp-starter'), $singular),
            // translators: %s is the singular taxonomy name
            'update_item' => sprintf(__('%s aktualisieren', 'wp-starter'), $singular),
            // translators: %s is the singular taxonomy name
            'add_new_item' => sprintf(__('Neue %s hinzufügen', 'wp-starter'), $singular),
            // translators: %s is the singular taxonomy name
            'new_item_name' => sprintf(__('Neuer %s Name', 'wp-starter'), $singular),
            'menu_name' => $plural,
            // translators: %s is the plural taxonomy name
            'popular_items' => sprintf(__('Beliebte %s', 'wp-starter'), $plural),
            // translators: %s is the plural taxonomy name
            'separate_items_with_commas' => sprintf(__('%s mit Kommas trennen', 'wp-starter'), $plural),
            // translators: %s is the plural taxonomy name
            'add_or_remove_items' => sprintf(__('%s hinzufügen oder entfernen', 'wp-starter'), $plural),
            // translators: %s is the plural taxonomy name
            'choose_from_most_used' => sprintf(__('Aus häufig verwendeten %s wählen', 'wp-starter'), $plural),
            // translators: %s is the plural taxonomy name
            'not_found' => sprintf(__('Keine %s gefunden', 'wp-starter'), $plural),
            // translators: %s is the plural taxonomy name
            'back_to_items' => sprintf(__('Zurück zu %s', 'wp-starter'), $plural),
        ];
    }

    /**
     * Get taxonomy registration arguments
     *
     * @param array<string, string> $labels
     * @return array<string, mixed>
     */
    protected static function getArgs(array $labels): array
    {
        $args = [
            'labels' => $labels,
            'hierarchical' => static::$hierarchical,
            'public' => static::$public,
            'show_ui' => static::$showUi,
            'show_in_rest' => static::$showInRest,
            'show_admin_column' => static::$showAdminColumn,
            'query_var' => true,
        ];

        // Set rewrite rules
        if (static::$rewrite !== false) {
            $args['rewrite'] = array_merge(
                ['slug' => static::$taxonomy, 'with_front' => false, 'hierarchical' => static::$hierarchical],
                static::$rewrite
            );
        } else {
            $args['rewrite'] = false;
        }

        return $args;
    }

    /**
     * Get all terms for this taxonomy
     *
     * @param array<string, mixed> $args Additional get_terms arguments
     * @return \WP_Term[]
     */
    public static function all(array $args = []): array
    {
        $defaults = [
            'taxonomy' => static::$taxonomy,
            'hide_empty' => false,
        ];

        $terms = get_terms(array_merge($defaults, $args));

        if (is_wp_error($terms)) {
            return [];
        }

        return $terms;
    }

    /**
     * Find a term by ID
     */
    public static function find(int $termId): ?\WP_Term
    {
        $term = get_term($termId, static::$taxonomy);

        if (is_wp_error($term) || !$term) {
            return null;
        }

        return $term;
    }

    /**
     * Find a term by slug
     */
    public static function findBySlug(string $slug): ?\WP_Term
    {
        $term = get_term_by('slug', $slug, static::$taxonomy);

        return $term instanceof \WP_Term ? $term : null;
    }
}
