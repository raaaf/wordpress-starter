<?php

declare(strict_types=1);

namespace WordpressStarter\PostTypes;

use WordpressStarter\Acf\FieldDefinitions;
use WP_Query;

/**
 * Event Custom Post Type
 *
 * Manages upcoming events for display via the "events" flexible layout.
 */
class Event extends AbstractPostType
{
    /**
     * Allowed values of the event_format field (value => label).
     * Labels are German source strings, translated at use via __().
     */
    public const FORMATS = [
        'onsite' => 'Vor Ort',
        'online' => 'Online',
        'hybrid' => 'Hybrid',
    ];

    protected static string $postType = 'event';

    protected static string $singular = 'Veranstaltung';

    protected static string $plural = 'Veranstaltungen';

    protected static string $menuIcon = 'dashicons-calendar-alt';

    protected static int $menuPosition = 27;

    protected static bool $hasArchive = false;

    /**
     * Not public: there is no single-event template, events are displayed
     * entirely via the "events" flexible layout (same reasoning as
     * Team.php:39).
     */
    protected static bool $public = false;

    /**
     * Matches $public = false above: no REST reach for anonymous requests
     * (same reasoning as Team.php:46).
     */
    protected static bool $showInRest = false;

    /** @var array<string> */
    protected static array $supports = ['title', 'thumbnail'];

    /** @var array<string, mixed>|false */
    protected static array|false $rewrite = false;

    /**
     * Register the custom post type with admin columns and default sort order
     */
    public static function register(): void
    {
        parent::register();
        self::registerAdminColumns();
        self::registerDefaultSortOrder();
    }

    /**
     * Declarative admin list-table columns (event_date)
     */
    protected static function adminColumns(): array
    {
        return [
            'event_date' => [
                'label' => __('Termin', 'wp-starter'),
                'after' => 'title',
                'sortable' => 'event_date',
                'sort_type' => 'meta_value',
                'render' => function (int $postId): void {
                    // Stored as Ymd; format it directly so no timezone shift can move the day.
                    // Read unformatted: ACF's formatting throws on invalid stored values.
                    $date = self::parseStored('!Ymd', get_field('event_date', $postId, false));
                    echo $date ? esc_html($date->format('d.m.Y')) : '–';
                },
            ],
        ];
    }

    /**
     * Sort the admin list table by event_date ascending by default. Only
     * applies when no explicit orderby is set, so a manually chosen sort
     * (e.g. via the sortable column above) is never overridden.
     */
    private static function registerDefaultSortOrder(): void
    {
        add_action('pre_get_posts', function (WP_Query $query): void {
            if (!is_admin() || !$query->is_main_query()) {
                return;
            }
            if ($query->get('post_type') !== self::$postType) {
                return;
            }
            if ($query->get('orderby') !== '') {
                return;
            }

            $query->set('meta_key', 'event_date'); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            $query->set('orderby', 'meta_value');
            $query->set('order', 'ASC');
        });
    }

    /**
     * Register ACF fields for events
     */
    public static function registerFields(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_event',
            'title' => __('Veranstaltung Details', 'wp-starter'),
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
    }

    /**
     * Get field definitions for events
     *
     * @return array<int, array<string, mixed>>
     */
    private static function getFieldDefinitions(): array
    {
        return [
            FieldDefinitions::dateField(
                'event_date',
                __('Datum', 'wp-starter'),
                'event_date',
                true,
                __('Das Datum der Veranstaltung.', 'wp-starter'),
            ),
            FieldDefinitions::timeField(
                'event_time',
                __('Uhrzeit', 'wp-starter'),
                'event_time',
                false,
                __('Die Uhrzeit der Veranstaltung (optional).', 'wp-starter'),
            ),
            FieldDefinitions::buttonGroupField(
                'event_format',
                __('Art', 'wp-starter'),
                'event_format',
                array_map(
                    static fn (string $label): string => __($label, 'wp-starter'), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
                    self::FORMATS,
                ),
                'onsite',
                __('Findet die Veranstaltung vor Ort, online oder beides statt?', 'wp-starter'),
            ),
            FieldDefinitions::textField(
                'event_location',
                __('Ort / Adresse', 'wp-starter'),
                'event_location',
                false,
                __('Adresse für den Kartenlink.', 'wp-starter'),
                __('z.B. Königstraße 1, 90762 Fürth', 'wp-starter'),
                [[['field' => 'event_format', 'operator' => '!=', 'value' => 'online']]],
            ),
            FieldDefinitions::textareaField(
                'event_description',
                __('Kurzbeschreibung', 'wp-starter'),
                'event_description',
                3,
                __('Kurzbeschreibung für den Teaser (optional).', 'wp-starter'),
            ),
            FieldDefinitions::linkField(
                'event_link',
                __('Link', 'wp-starter'),
                'event_link',
                false,
                __('Optional, z.B. Anmeldung oder Livestream.', 'wp-starter'),
            ),
        ];
    }

    /**
     * Get upcoming events (event_date today or later), sorted ascending.
     *
     * @param int $limit Number of events to return (-1 for all)
     *
     * @return array<int, array{
     *   id: int,
     *   title: string,
     *   event_date: string,
     *   event_time: string|null,
     *   event_format: 'onsite'|'online'|'hybrid',
     *   event_location: string|null,
     *   event_description: string|null,
     *   event_link: array{url: string, title: string, target: string}|null,
     *   image: int|null
     * }>
     */
    public static function getUpcomingEvents(int $limit = -1): array
    {
        // Site timezone, matching the wp_date() used for display.
        $today = wp_date('Ymd');

        $posts = self::all([
            'posts_per_page' => $limit,
            'has_password' => false,
            'orderby' => ['event_date_clause' => 'ASC'],
            'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                'event_date_clause' => [
                    'key' => 'event_date',
                    'value' => $today,
                    'compare' => '>=',
                ],
            ],
        ]);

        // Unformatted read: ACF's formatting throws on invalid stored values.
        // Events without a valid date are dropped.
        $events = array_map(
            static fn ($post) => self::mapEvent($post->ID, get_the_title($post), get_fields($post->ID, false) ?: []),
            $posts,
        );

        return array_values(array_filter($events, static fn (array $event): bool => $event['event_date'] !== ''));
    }

    /**
     * Map a post and its ACF fields to the entry shape of getUpcomingEvents().
     * Fields are user-writable, so every value is type-checked; anything of
     * the wrong type becomes null instead of reaching the templates.
     *
     * @param array<string, mixed> $fields
     *
     * @return array{
     *   id: int,
     *   title: string,
     *   event_date: string,
     *   event_time: string|null,
     *   event_format: 'onsite'|'online'|'hybrid',
     *   event_location: string|null,
     *   event_description: string|null,
     *   event_link: array{url: string, title: string, target: string}|null,
     *   image: int|null
     * }
     */
    public static function mapEvent(int $postId, string $title, array $fields): array
    {
        $text = static fn (mixed $value): ?string => is_string($value) && $value !== '' ? $value : null;

        $link = $fields['event_link'] ?? null;
        $eventLink = null;
        if (is_array($link) && is_string($link['url'] ?? null) && $link['url'] !== '') {
            $eventLink = [
                'url' => $link['url'],
                'title' => is_string($link['title'] ?? null) ? $link['title'] : '',
                'target' => is_string($link['target'] ?? null) ? $link['target'] : '',
            ];
        }

        $format = $fields['event_format'] ?? '';

        return [
            'id' => $postId,
            'title' => $title,
            'event_date' => self::parseStored('!Ymd', $fields['event_date'] ?? null) ? $fields['event_date'] : '',
            'event_time' => self::displayTime($fields['event_time'] ?? null),
            'event_format' => is_string($format) && array_key_exists($format, self::FORMATS) ? $format : 'onsite',
            'event_location' => $text($fields['event_location'] ?? null),
            'event_description' => $text($fields['event_description'] ?? null),
            'event_link' => $eventLink,
            'image' => get_post_thumbnail_id($postId) ?: null,
        ];
    }

    /**
     * Strict parse of a stored value; null unless it round-trips unchanged
     * (rejects non-strings, overflowing dates like 20261332 and huge numbers).
     */
    private static function parseStored(string $format, mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value)) {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat($format, $value);

        return $parsed !== false && $parsed->format(ltrim($format, '!')) === $value ? $parsed : null;
    }

    /**
     * Raw stored time (H:i:s, or H:i) to the H:i display format; null if invalid.
     */
    private static function displayTime(mixed $value): ?string
    {
        $time = self::parseStored('!H:i:s', $value) ?? self::parseStored('!H:i', $value);

        return $time?->format('H:i');
    }
}
