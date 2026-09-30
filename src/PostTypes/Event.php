<?php

declare(strict_types=1);

namespace WordpressStarter\PostTypes;

use WordpressStarter\Acf\FieldDefinitions;
use WordpressStarter\Providers\AcfServiceProvider;
use WP_Query;

/**
 * Event Custom Post Type
 *
 * Manages upcoming events for display via the "events" flexible layout.
 *
 * @phpstan-type EventEntry array{
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
class Event extends AbstractPostType
{
    /**
     * Allowed values of the event_format field. Labels: see formatLabels().
     */
    public const FORMATS = ['onsite', 'online', 'hybrid'];

    protected static string $postType = 'event';

    protected static string $singular = 'Veranstaltung';

    protected static string $genus = 'f';

    protected static string $plural = 'Veranstaltungen';

    protected static string $menuIcon = 'dashicons-calendar-alt';

    protected static int $menuPosition = 27;

    protected static bool $hasArchive = false;

    /**
     * Not public: there is no single-event template, events are displayed
     * entirely via the "events" flexible layout (same reasoning as
     * Team::$public).
     */
    protected static bool $public = false;

    /**
     * Matches $public = false above: no REST reach for anonymous requests
     * (same reasoning as Team::$showInRest).
     */
    protected static bool $showInRest = false;

    /** @var array<string> */
    protected static array $supports = ['title', 'thumbnail'];

    /** @var array<string, mixed>|false */
    protected static array|false $rewrite = false;

    /**
     * Labels of the event_format values (value => label), as literal __()
     * calls so they are extractable for translation.
     *
     * @return array<string, string>
     */
    public static function formatLabels(): array
    {
        return [
            'onsite' => __('Vor Ort', 'wp-starter'),
            'online' => __('Online', 'wp-starter'),
            'hybrid' => __('Hybrid', 'wp-starter'),
        ];
    }

    /**
     * Register the custom post type with admin columns and default sort order
     */
    public static function register(): void
    {
        parent::register();
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
                    $date = AcfServiceProvider::parseStrict('!Ymd', get_field('event_date', $postId, false));
                    echo $date ? esc_html($date->format('d.m.Y')) : '–';
                },
            ],
        ];
    }

    /**
     * Sort the admin list table by event_date ascending by default. Events
     * without an event_date (e.g. drafts) stay listed, sorted first. Only
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

            $query->set('meta_query', [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                'relation' => 'OR',
                'event_date_clause' => ['key' => 'event_date', 'compare' => 'EXISTS'],
                ['key' => 'event_date', 'compare' => 'NOT EXISTS'],
            ]);
            $query->set('orderby', ['event_date_clause' => 'ASC']);
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
            'title' => __('Veranstaltungsdetails', 'wp-starter'),
            'fields' => self::getFieldDefinitions(),
            'location' => self::locationForThisType(),
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
                __('Termin', 'wp-starter'),
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
                __('Art der Veranstaltung', 'wp-starter'),
                'event_format',
                self::formatLabels(),
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
     * The named meta_query clause is reused in orderby (instead of a separate
     * meta_key/orderby pair) so WordPress needs no second wp_postmeta join.
     *
     * @param int $limit Number of events to return (-1 for all)
     *
     * @return array<int, EventEntry>
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
     * @return EventEntry
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
            'event_date' => AcfServiceProvider::parseStrict('!Ymd', $fields['event_date'] ?? null) ? $fields['event_date'] : '',
            'event_time' => self::displayTime($fields['event_time'] ?? null),
            'event_format' => is_string($format) && in_array($format, self::FORMATS, true) ? $format : 'onsite',
            'event_location' => $text($fields['event_location'] ?? null),
            'event_description' => $text($fields['event_description'] ?? null),
            'event_link' => $eventLink,
            'image' => get_post_thumbnail_id($postId) ?: null,
        ];
    }

    /**
     * Map one getUpcomingEvents() entry to a schema.org Event (without @context).
     * Keys with empty values are omitted. Returns [] for an entry without a valid
     * event_date or without a location (online without link, onsite/hybrid without address).
     * Only http(s) URLs reach the output.
     *
     * @param array<string, mixed> $event
     *
     * @return array<string, mixed>
     */
    public static function toSchema(array $event): array
    {
        $date = AcfServiceProvider::parseStrict('!Ymd', $event['event_date'] ?? null);
        if ($date === null) {
            return [];
        }

        $text = static fn (mixed $value): string => is_string($value) ? trim(wp_strip_all_tags($value)) : '';

        $time = self::displayTime($event['event_time'] ?? null);
        if ($time !== null) {
            $start = \DateTimeImmutable::createFromFormat('!Ymd H:i', $date->format('Ymd') . ' ' . $time, wp_timezone());
            $startDate = $start ? $start->format('Y-m-d\TH:iP') : $date->format('Y-m-d');
        } else {
            $startDate = $date->format('Y-m-d');
        }

        $format = $event['event_format'] ?? 'onsite';
        $modes = [
            'onsite' => 'OfflineEventAttendanceMode',
            'online' => 'OnlineEventAttendanceMode',
            'hybrid' => 'MixedEventAttendanceMode',
        ];

        $address = $text($event['event_location'] ?? null);
        $link = $event['event_link'] ?? null;
        $linkUrl = is_array($link) ? self::httpUrl($link['url'] ?? null) : '';

        $place = $address !== '' ? ['@type' => 'Place', 'name' => $address, 'address' => $address] : null;
        $virtual = $linkUrl !== '' ? ['@type' => 'VirtualLocation', 'url' => $linkUrl] : null;
        $location = match ($format) {
            'online' => $virtual,
            'hybrid' => array_values(array_filter([$place, $virtual])),
            default => $place,
        };

        // Google requires a location; without one the event is not emitted.
        if ($location === null || $location === []) {
            return [];
        }

        $imageId = $event['image'] ?? null;
        $image = is_int($imageId) ? wp_get_attachment_image_url($imageId, 'large') : false;

        $eventId = $event['id'] ?? null;

        $schema = [
            '@type' => 'Event',
            '@id' => is_int($eventId) ? home_url('/#event-' . $eventId) : null,
            'name' => $text($event['title'] ?? null),
            'startDate' => $startDate,
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/' . ( $modes[$format] ?? $modes['onsite'] ),
            'location' => $location,
            'description' => $text($event['event_description'] ?? null),
            'image' => $image,
            'url' => $linkUrl,
        ];

        return array_filter($schema, static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== false && $value !== []);
    }

    /**
     * The URL when it is http(s), otherwise ''. Keeps javascript:, data: etc. out of JSON-LD.
     */
    private static function httpUrl(mixed $url): string
    {
        if (!is_string($url)) {
            return '';
        }

        $scheme = wp_parse_url($url, PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true) ? esc_url_raw($url) : '';
    }

    /**
     * Raw stored time (H:i:s, or H:i) to the H:i display format; null if invalid.
     */
    private static function displayTime(mixed $value): ?string
    {
        $time = AcfServiceProvider::parseStrict('!H:i:s', $value) ?? AcfServiceProvider::parseStrict('!H:i', $value);

        return $time?->format('H:i');
    }
}
