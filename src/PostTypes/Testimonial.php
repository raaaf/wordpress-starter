<?php

declare(strict_types=1);

namespace WordpressStarter\PostTypes;

use WordpressStarter\Acf\FieldDefinitions;

/**
 * Testimonial Custom Post Type
 *
 * Manages customer testimonials/reviews for display on the website.
 * Uses WordPress featured image for customer photos.
 */
class Testimonial extends AbstractPostType
{
    protected static string $postType = 'testimonial';
    protected static string $singular = 'Testimonial';
    protected static string $plural = 'Testimonials';
    protected static string $menuIcon = 'dashicons-format-quote';
    protected static int $menuPosition = 25;
    protected static bool $hasArchive = false;

    /** @var array<string> */
    protected static array $supports = ['thumbnail'];

    /** @var array<string, mixed>|false */
    protected static array|false $rewrite = ['slug' => 'testimonials'];

    /**
     * Register the custom post type with admin columns
     */
    public static function register(): void
    {
        parent::register();
        self::registerAdminColumns();
        self::registerAutoTitle();
    }

    /**
     * Register custom admin columns for better list view UX
     */
    private static function registerAdminColumns(): void
    {
        // Add custom columns
        add_filter('manage_' . self::$postType . '_posts_columns', function (array $columns): array {
            $newColumns = [];
            $newColumns['cb'] = $columns['cb'];
            $newColumns['thumbnail'] = __('Foto', 'wp-starter');
            $newColumns['title'] = $columns['title'];
            $newColumns['author_name'] = __('Kunde', 'wp-starter');
            $newColumns['rating'] = __('Bewertung', 'wp-starter');
            $newColumns['date'] = $columns['date'];
            return $newColumns;
        });

        // Populate custom columns
        add_action('manage_' . self::$postType . '_posts_custom_column', function (string $column, int $postId): void {
            switch ($column) {
                case 'thumbnail':
                    $thumbnail = get_the_post_thumbnail( $postId, [50, 50], ['style' => 'border-radius: 50%; object-fit: cover;'] );
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_the_post_thumbnail() returns safe HTML
                    echo $thumbnail ?: '<span style="color: #999;">—</span>';
                    break;
                case 'author_name':
                    $name = get_field('author_name', $postId);
                    $position = get_field('author_position', $postId);
                    echo '<strong>' . esc_html($name ?: '—') . '</strong>';
                    if ($position) {
                        echo '<br><span style="color: #666;">' . esc_html($position) . '</span>';
                    }
                    break;
                case 'rating':
                    $rating = get_field('rating', $postId);
                    if ($rating) {
                        $stars = str_repeat('★', (int) $rating) . str_repeat('☆', 5 - (int) $rating);
                        echo '<span style="color: #f5a623; font-size: 14px;">' . esc_html( $stars ) . '</span>';
                    } else {
                        echo '<span style="color: #999;">—</span>';
                    }
                    break;
            }
        }, 10, 2);

        // Make columns sortable
        add_filter('manage_edit-' . self::$postType . '_sortable_columns', function (array $columns): array {
            $columns['author_name'] = 'author_name';
            $columns['rating'] = 'rating';
            return $columns;
        });

        // Handle sorting
        add_action('pre_get_posts', function (\WP_Query $query): void {
            if (!is_admin() || !$query->is_main_query()) {
                return;
            }
            if ($query->get('post_type') !== self::$postType) {
                return;
            }

            $orderby = $query->get('orderby');
            if ($orderby === 'author_name') {
                $query->set('meta_key', 'author_name');
                $query->set('orderby', 'meta_value');
            } elseif ($orderby === 'rating') {
                $query->set('meta_key', 'rating');
                $query->set('orderby', 'meta_value_num');
            }
        });

        // Set thumbnail column width
        add_action('admin_head', function (): void {
            $screen = get_current_screen();
            if ($screen && $screen->post_type === self::$postType) {
                echo '<style>.column-thumbnail { width: 60px; } .column-rating { width: 100px; }</style>';
            }
        });
    }

    /**
     * Auto-generate post title from author_name field
     */
    private static function registerAutoTitle(): void
    {
        // Set title on save
        add_filter('wp_insert_post_data', function (array $data, array $postarr): array {
            if ( ( $data['post_type'] ?? '' ) !== self::$postType ) {
                return $data;
            }

            // Get author_name from ACF field
            $authorName = '';
            if (!empty($postarr['acf']['testimonial_author_name'])) {
                $authorName = sanitize_text_field($postarr['acf']['testimonial_author_name']);
            } elseif (!empty($postarr['ID'])) {
                $authorName = get_field('author_name', $postarr['ID']) ?: '';
            }

            if ($authorName) {
                $data['post_title'] = $authorName;
                $data['post_name'] = sanitize_title($authorName);
            } elseif (empty($data['post_title']) || $data['post_title'] === 'Automatischer Entwurf') {
                $data['post_title'] = __('Neues Testimonial', 'wp-starter');
            }

            return $data;
        }, 10, 2);

        // Hide title field in editor
        add_action('admin_head', function (): void {
            $screen = get_current_screen();
            if ($screen && $screen->post_type === self::$postType) {
                echo '<style>#titlediv { display: none !important; }</style>';
            }
        });
    }

    /**
     * Register ACF fields for testimonials
     */
    public static function registerFields(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_testimonial',
            'title' => __('Testimonial Details', 'wp-starter'),
            'fields' => self::getFieldDefinitions(),
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => self::$postType,
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
        ]);

        // Sidebar hint
        acf_add_local_field_group([
            'key' => 'group_testimonial_sidebar',
            'title' => __('Hinweise', 'wp-starter'),
            'fields' => [
                [
                    'key' => 'testimonial_thumbnail_hint',
                    'label' => '',
                    'name' => '',
                    'type' => 'message',
                    'message' => __('<strong>Kundenfoto:</strong> Verwende das "Beitragsbild" rechts für das Profilbild des Kunden.', 'wp-starter'),
                    'esc_html' => 0,
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => self::$postType,
                    ],
                ],
            ],
            'menu_order' => 1,
            'position' => 'side',
            'style' => 'default',
        ]);
    }

    /**
     * Get field definitions for testimonials
     *
     * @return array<int, array<string, mixed>>
     */
    private static function getFieldDefinitions(): array
    {
        return [
            // Accordion: Kunde
            FieldDefinitions::accordionField('testimonial_acc_customer', __('Kundeninformationen', 'wp-starter'), true),
            FieldDefinitions::textField(
                'testimonial_author_name',
                __('Name', 'wp-starter'),
                'author_name',
                true,
                __('Der Name des Kunden/der Kundin.', 'wp-starter'),
                __('z.B. Max Mustermann', 'wp-starter')
            ),
            FieldDefinitions::textField(
                'testimonial_author_position',
                __('Position / Unternehmen', 'wp-starter'),
                'author_position',
                false,
                __('Jobtitel und/oder Firmenname.', 'wp-starter'),
                __('z.B. Geschäftsführer, Musterfirma GmbH', 'wp-starter')
            ),

            // Accordion: Bewertung
            FieldDefinitions::accordionField('testimonial_acc_review', __('Bewertung', 'wp-starter')),
            FieldDefinitions::textareaField(
                'testimonial_content',
                __('Testimonial Text', 'wp-starter'),
                'content',
                4,
                __('Das Testimonial/die Kundenbewertung.', 'wp-starter'),
                __('z.B. Die Zusammenarbeit war hervorragend...', 'wp-starter')
            ),
            [
                'key' => 'testimonial_rating',
                'label' => __('Sterne-Bewertung', 'wp-starter'),
                'name' => 'rating',
                'type' => 'range',
                'instructions' => __('Bewertung von 1-5 Sternen (optional).', 'wp-starter'),
                'min' => 0,
                'max' => 5,
                'step' => 1,
                'default_value' => 0,
                'prepend' => '☆',
                'append' => '★',
            ],

            // Accordion: Quelle
            FieldDefinitions::accordionField('testimonial_acc_source', __('Quelle (optional)', 'wp-starter')),
            FieldDefinitions::urlField(
                'testimonial_source_url',
                __('Quell-URL', 'wp-starter'),
                'source_url',
                __('Link zur Original-Bewertung (z.B. Google, Trustpilot).', 'wp-starter'),
                null,
                'https://g.page/r/...'
            ),

            // Accordion Ende
            FieldDefinitions::accordionField('testimonial_acc_end', '', false, true, true),
        ];
    }

    /**
     * Get testimonials for display
     *
     * @param int $limit Number of testimonials to return (-1 for all)
     * @param string $orderby Order by field
     * @param string $order Order direction
     * @return array<int, array{
     *   id: int,
     *   author_name: string,
     *   author_position: string,
     *   content: string,
     *   rating: int|null,
     *   image: int|null,
     *   source_url: string|null
     * }>
     */
    public static function getTestimonials(int $limit = -1, string $orderby = 'date', string $order = 'DESC'): array
    {
        $posts = self::all([
            'posts_per_page' => $limit,
            'orderby' => $orderby,
            'order' => $order,
        ]);

        $testimonials = [];
        foreach ($posts as $post) {
            $fields = get_fields($post->ID) ?: [];
            $testimonials[] = [
                'id'              => $post->ID,
                'author_name'     => $fields['author_name'] ?? '',
                'author_position' => $fields['author_position'] ?? '',
                'content'         => $fields['content'] ?? '',
                'rating'          => $fields['rating'] ?: null,
                'image'           => get_post_thumbnail_id($post->ID) ?: null,
                'source_url'      => $fields['source_url'] ?: null,
            ];
        }

        return $testimonials;
    }

    /**
     * Get a random testimonial
     *
     * @return array<string, mixed>|null
     */
    public static function getRandom(): ?array
    {
        $testimonials = self::getTestimonials(-1, 'rand');
        return $testimonials[0] ?? null;
    }
}
