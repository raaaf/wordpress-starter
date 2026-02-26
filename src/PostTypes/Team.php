<?php

declare(strict_types=1);

namespace WordpressStarter\PostTypes;

use WordpressStarter\Acf\FieldDefinitions;

/**
 * Team Member Custom Post Type
 *
 * Manages team members for display on the website.
 * Uses WordPress featured image for member photos.
 */
class Team extends AbstractPostType
{
    protected static string $postType = 'team_member';
    protected static string $singular = 'Teammitglied';
    protected static string $plural = 'Team';
    protected static string $menuIcon = 'dashicons-groups';
    protected static int $menuPosition = 26;
    protected static bool $hasArchive = false;

    /** @var array<string> */
    protected static array $supports = ['title', 'thumbnail'];

    /** @var array<string, mixed>|false */
    protected static array|false $rewrite = ['slug' => 'team'];

    /**
     * Register the custom post type with admin columns
     */
    public static function register(): void
    {
        parent::register();
        self::registerAdminColumns();
    }

    /**
     * Register custom admin columns for better list view UX
     */
    private static function registerAdminColumns(): void
    {
        // Add custom columns
        add_filter('manage_' . self::$postType . '_posts_columns', function (array $columns): array {
            $newColumns = [];
            foreach ($columns as $key => $value) {
                if ($key === 'title') {
                    $newColumns['thumbnail'] = __('Foto', 'wp-starter');
                }
                $newColumns[$key] = $value;
                if ($key === 'title') {
                    $newColumns['position'] = __('Position', 'wp-starter');
                    $newColumns['email'] = __('E-Mail', 'wp-starter');
                    $newColumns['display_order'] = __('Reihenfolge', 'wp-starter');
                }
            }
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
                case 'position':
                    echo esc_html(get_field('position', $postId) ?: '—');
                    break;
                case 'email':
                    $email = get_field('email', $postId);
                    echo $email ? '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>' : '—';
                    break;
                case 'display_order':
                    echo esc_html(get_field('display_order', $postId) ?: '0');
                    break;
            }
        }, 10, 2);

        // Make columns sortable
        add_filter('manage_edit-' . self::$postType . '_sortable_columns', function (array $columns): array {
            $columns['display_order'] = 'display_order';
            $columns['position'] = 'position';
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
            if ($orderby === 'display_order') {
                $query->set('meta_key', 'display_order');
                $query->set('orderby', 'meta_value_num');
            } elseif ($orderby === 'position') {
                $query->set('meta_key', 'position');
                $query->set('orderby', 'meta_value');
            }
        });

        // Set thumbnail column width
        add_action('admin_head', function (): void {
            $screen = get_current_screen();
            if ($screen && $screen->post_type === self::$postType) {
                echo '<style>.column-thumbnail { width: 60px; } .column-display_order { width: 100px; }</style>';
            }
        });
    }

    /**
     * Register ACF fields for team members
     */
    public static function registerFields(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_team_member',
            'title' => __('Teammitglied Details', 'wp-starter'),
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

        // Sidebar field group for display order
        acf_add_local_field_group([
            'key' => 'group_team_member_sidebar',
            'title' => __('Anzeigeoptionen', 'wp-starter'),
            'fields' => [
                FieldDefinitions::numberField(
                    'team_member_order',
                    __('Reihenfolge', 'wp-starter'),
                    'display_order',
                    0,
                    0,
                    999,
                    1,
                    '',
                    __('Sortierreihenfolge (niedrigere Zahlen zuerst).', 'wp-starter')
                ),
                [
                    'key' => 'team_member_thumbnail_hint',
                    'label' => '',
                    'name' => '',
                    'type' => 'message',
                    'message' => __('<strong>Profilbild:</strong> Verwende das "Beitragsbild" rechts für das Foto der Person.', 'wp-starter'),
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
     * Get field definitions for team members
     *
     * @return array<int, array<string, mixed>>
     */
    private static function getFieldDefinitions(): array
    {
        return [
            // Accordion: Berufliche Informationen
            FieldDefinitions::accordionField('team_acc_job', __('Berufliche Informationen', 'wp-starter'), true),
            FieldDefinitions::textField(
                'team_member_position',
                __('Position', 'wp-starter'),
                'position',
                false,
                __('Jobtitel oder Rolle im Unternehmen.', 'wp-starter'),
                __('z.B. Geschäftsführer', 'wp-starter')
            ),
            FieldDefinitions::textareaField(
                'team_member_bio',
                __('Kurzbiografie', 'wp-starter'),
                'bio',
                3,
                __('Kurze Beschreibung der Person.', 'wp-starter'),
                __('z.B. Seit 2020 im Unternehmen...', 'wp-starter')
            ),

            // Accordion: Kontaktdaten
            FieldDefinitions::accordionField('team_acc_contact', __('Kontaktdaten', 'wp-starter')),
            [
                'key' => 'team_member_email',
                'label' => __('E-Mail', 'wp-starter'),
                'name' => 'email',
                'type' => 'email',
                'instructions' => __('Direkte E-Mail-Adresse.', 'wp-starter'),
                'placeholder' => 'max@beispiel.de',
                'wrapper' => ['width' => '50'],
            ],
            [
                'key' => 'team_member_phone',
                'label' => __('Telefon', 'wp-starter'),
                'name' => 'phone',
                'type' => 'text',
                'instructions' => __('Telefonnummer (optional).', 'wp-starter'),
                'placeholder' => '+49 123 456789',
                'wrapper' => ['width' => '50'],
            ],
            [
                'key' => 'team_member_linkedin',
                'label' => __('LinkedIn', 'wp-starter'),
                'name' => 'linkedin',
                'type' => 'url',
                'instructions' => __('Link zum LinkedIn-Profil.', 'wp-starter'),
                'placeholder' => 'https://linkedin.com/in/...',
                'wrapper' => ['width' => '50'],
            ],
            [
                'key' => 'team_member_xing',
                'label' => __('Xing', 'wp-starter'),
                'name' => 'xing',
                'type' => 'url',
                'instructions' => __('Link zum Xing-Profil.', 'wp-starter'),
                'placeholder' => 'https://xing.com/profile/...',
                'wrapper' => ['width' => '50'],
            ],

            // Accordion Ende
            FieldDefinitions::accordionField('team_acc_end', '', false, true, true),
        ];
    }

    /**
     * Get team members for display
     *
     * @param int $limit Number of members to return (-1 for all)
     * @param string $orderby Order by field ('menu_order', 'title', 'date', 'rand')
     * @param string $order Order direction
     * @return array<int, array{
     *   id: int,
     *   name: string,
     *   position: string,
     *   bio: string,
     *   email: string|null,
     *   phone: string|null,
     *   linkedin: string|null,
     *   xing: string|null,
     *   image: int|null
     * }>
     */
    public static function getTeamMembers(int $limit = -1, string $orderby = 'meta_value_num', string $order = 'ASC'): array
    {
        $args = [
            'posts_per_page' => $limit,
            'orderby' => $orderby,
            'order' => $order,
        ];

        // Sort by display_order meta field
        if ($orderby === 'meta_value_num') {
            $args['meta_key'] = 'display_order'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        }

        $posts = self::all($args);

        $members = [];
        foreach ($posts as $post) {
            $members[] = [
                'id' => $post->ID,
                'name' => $post->post_title,
                'position' => get_field('position', $post->ID) ?: '',
                'bio' => get_field('bio', $post->ID) ?: '',
                'email' => get_field('email', $post->ID) ?: null,
                'phone' => get_field('phone', $post->ID) ?: null,
                'linkedin' => get_field('linkedin', $post->ID) ?: null,
                'xing' => get_field('xing', $post->ID) ?: null,
                'image' => get_post_thumbnail_id($post->ID) ?: null,
            ];
        }

        return $members;
    }

    /**
     * Get a single team member by ID
     *
     * @param int $id Post ID
     * @return array<string, mixed>|null
     */
    public static function getMember(int $id): ?array
    {
        $post = self::find($id);
        if (!$post) {
            return null;
        }

        return [
            'id' => $post->ID,
            'name' => $post->post_title,
            'position' => get_field('position', $post->ID) ?: '',
            'bio' => get_field('bio', $post->ID) ?: '',
            'email' => get_field('email', $post->ID) ?: null,
            'phone' => get_field('phone', $post->ID) ?: null,
            'linkedin' => get_field('linkedin', $post->ID) ?: null,
            'xing' => get_field('xing', $post->ID) ?: null,
            'image' => get_post_thumbnail_id($post->ID) ?: null,
        ];
    }
}
