<?php

declare(strict_types=1);

namespace Tests\Unit\PostTypes;

use Tests\Support\TestCase;
use WordpressStarter\PostTypes\Event;
use WP_Query;

/**
 * Pins the query-construction assumptions Event::getUpcomingEvents() and the
 * admin default-sort hook rely on. This test harness mocks WP_Query as an
 * always-empty double (see tests/bootstrap.php), so the actual ACF
 * date_picker round trip (stored as Ymd) cannot be exercised here; that
 * would require a real WordPress + ACF integration environment, which this
 * theme's unit suite does not provide.
 */
final class EventTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['wp_mock_last_query_args'], $GLOBALS['wp_mock_is_admin'], $GLOBALS['wp_mock_timezone'], $GLOBALS['wp_mock_attachments']);

        parent::tearDown();
    }

    public function testGetUpcomingEventsFiltersFromTodayOnwardsInYmdFormat(): void
    {
        Event::getUpcomingEvents(21);

        $args = $GLOBALS['wp_mock_last_query_args'];
        $clause = $args['meta_query']['event_date_clause'];

        $this->assertSame('event_date', $clause['key']);
        $this->assertSame('>=', $clause['compare']);
        $this->assertSame(wp_date('Ymd'), $clause['value']);
    }

    public function testGetUpcomingEventsReusesTheNamedMetaQueryClauseAsOrderby(): void
    {
        // The named meta_query clause is reused in orderby instead of a
        // separate top-level meta_key/orderby pair, to avoid a second
        // wp_postmeta join (see Event::getUpcomingEvents() docblock).
        Event::getUpcomingEvents(21);

        $args = $GLOBALS['wp_mock_last_query_args'];

        $this->assertSame(['event_date_clause' => 'ASC'], $args['orderby']);
    }

    public function testAdminListDefaultsToEventDateAscendingWhenNoOrderbyIsSet(): void
    {
        $GLOBALS['wp_mock_is_admin'] = true;
        Event::register();

        $query = new WP_Query(['post_type' => 'event', 'orderby' => '']);
        $query->is_main_query = true;

        do_action('pre_get_posts', $query);

        // Named clause on EXISTS OR NOT EXISTS keeps events without a date (drafts) in the list.
        $metaQuery = $query->get('meta_query');
        $this->assertSame('OR', $metaQuery['relation']);
        $this->assertSame('EXISTS', $metaQuery['event_date_clause']['compare']);
        $this->assertSame('NOT EXISTS', $metaQuery[0]['compare']);
        $this->assertSame(['event_date_clause' => 'ASC'], $query->get('orderby'));
    }

    public function testAdminListDefaultSortDoesNotOverrideAnExplicitOrderby(): void
    {
        $GLOBALS['wp_mock_is_admin'] = true;
        Event::register();

        $query = new WP_Query(['post_type' => 'event', 'orderby' => 'title']);
        $query->is_main_query = true;

        do_action('pre_get_posts', $query);

        $this->assertSame('title', $query->get('orderby'));
        $this->assertNull($query->get('meta_key', null));
    }

    public function testGetUpcomingEventsExcludesPasswordProtectedPosts(): void
    {
        Event::getUpcomingEvents(21);

        $this->assertFalse($GLOBALS['wp_mock_last_query_args']['has_password']);
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    private function mapEvent(array $fields): array
    {
        return Event::mapEvent(77, 'Titel', $fields);
    }

    public function testMapEventFallsBackToOnsiteForUnknownFormat(): void
    {
        $this->assertSame('onsite', $this->mapEvent(['event_format' => 'teleport'])['event_format']);
        $this->assertSame('onsite', $this->mapEvent([])['event_format']);
    }

    public function testMapEventKeepsKnownFormats(): void
    {
        $this->assertSame('online', $this->mapEvent(['event_format' => 'online'])['event_format']);
        $this->assertSame('hybrid', $this->mapEvent(['event_format' => 'hybrid'])['event_format']);
    }

    public function testMapEventTurnsEmptyStringsIntoNull(): void
    {
        $entry = $this->mapEvent([
            'event_time' => '',
            'event_location' => '',
            'event_description' => '',
        ]);

        $this->assertNull($entry['event_time']);
        $this->assertNull($entry['event_location']);
        $this->assertNull($entry['event_description']);
    }

    public function testMapEventKeepsNonEmptyStrings(): void
    {
        $entry = $this->mapEvent([
            'event_time' => '18:30',
            'event_location' => 'Fürth',
            'event_description' => 'Teaser',
        ]);

        $this->assertSame('18:30', $entry['event_time']);
        $this->assertSame('Fürth', $entry['event_location']);
        $this->assertSame('Teaser', $entry['event_description']);
    }

    public function testMapEventTurnsNonStringValuesIntoNull(): void
    {
        $entry = $this->mapEvent([
            'event_time' => ['18:30'],
            'event_location' => ['Fürth'],
            'event_description' => ['Teaser'],
        ]);

        $this->assertNull($entry['event_time']);
        $this->assertNull($entry['event_location']);
        $this->assertNull($entry['event_description']);
    }

    public function testMapEventLinkIsNullUnlessUrlIsNonEmptyString(): void
    {
        $link = ['url' => 'https://example.com', 'title' => 'Anmeldung', 'target' => '_blank'];

        $this->assertSame($link, $this->mapEvent(['event_link' => $link])['event_link']);
        $this->assertNull($this->mapEvent(['event_link' => ['url' => '', 'title' => 'x']])['event_link']);
        $this->assertNull($this->mapEvent(['event_link' => ['title' => 'x']])['event_link']);
        $this->assertNull($this->mapEvent(['event_link' => ['url' => ['a']]])['event_link']);
        $this->assertNull($this->mapEvent(['event_link' => 'https://example.com'])['event_link']);
        $this->assertNull($this->mapEvent([])['event_link']);
    }

    public function testMapEventBlanksAnInvalidDateSoItCanBeDropped(): void
    {
        $this->assertSame('20261008', $this->mapEvent(['event_date' => '20261008'])['event_date']);
        $this->assertSame('', $this->mapEvent(['event_date' => '300000000000'])['event_date']);
        $this->assertSame('', $this->mapEvent(['event_date' => '20261332'])['event_date']);
        $this->assertSame('', $this->mapEvent(['event_date' => ['20261008']])['event_date']);
    }

    public function testMapEventNormalisesRawTimeToHoursAndMinutesAndRejectsInvalid(): void
    {
        $this->assertSame('18:30', $this->mapEvent(['event_time' => '18:30:00'])['event_time']);
        $this->assertNull($this->mapEvent(['event_time' => '99:99:99'])['event_time']);
        $this->assertNull($this->mapEvent(['event_time' => '300000000000'])['event_time']);
    }

    private const PLACE = ['event_location' => 'Fürth'];

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function schema(array $overrides = []): array
    {
        return Event::toSchema(Event::mapEvent(77, 'Titel', array_merge(['event_date' => '20261008'], $overrides)));
    }

    public function testToSchemaMapsEachFormatToItsAttendanceMode(): void
    {
        $fields = self::PLACE + ['event_link' => ['url' => 'https://example.org/live']];

        $this->assertSame('https://schema.org/OfflineEventAttendanceMode', $this->schema(['event_format' => 'onsite'] + $fields)['eventAttendanceMode']);
        $this->assertSame('https://schema.org/OnlineEventAttendanceMode', $this->schema(['event_format' => 'online'] + $fields)['eventAttendanceMode']);
        $this->assertSame('https://schema.org/MixedEventAttendanceMode', $this->schema(['event_format' => 'hybrid'] + $fields)['eventAttendanceMode']);
    }

    public function testToSchemaStartDateIsPlainDateWithoutTime(): void
    {
        $schema = $this->schema(self::PLACE);

        $this->assertSame('Event', $schema['@type']);
        $this->assertSame('Titel', $schema['name']);
        $this->assertSame('2026-10-08', $schema['startDate']);
        $this->assertSame('https://schema.org/EventScheduled', $schema['eventStatus']);
    }

    public function testToSchemaStartDateCarriesSiteTimezoneOffsetWithTime(): void
    {
        $GLOBALS['wp_mock_timezone'] = 'Europe/Berlin';

        $this->assertSame('2026-10-08T18:30+02:00', $this->schema(self::PLACE + ['event_time' => '18:30:00'])['startDate']);
        $this->assertSame('2026-12-08T18:30+01:00', $this->schema(self::PLACE + ['event_date' => '20261208', 'event_time' => '18:30'])['startDate']);
    }

    public function testToSchemaOnsiteLocationIsAPlaceFromTheAddress(): void
    {
        $schema = $this->schema(['event_location' => 'Königstraße 1, Fürth', 'event_link' => ['url' => 'https://example.org/live']]);

        $this->assertSame(['@type' => 'Place', 'name' => 'Königstraße 1, Fürth', 'address' => 'Königstraße 1, Fürth'], $schema['location']);
    }

    public function testToSchemaOnlineLocationIsAVirtualLocationFromTheLinkUrl(): void
    {
        $fields = ['event_format' => 'online', 'event_location' => 'Ignored Street 1'];
        $withLink = $this->schema($fields + ['event_link' => ['url' => 'https://example.org/live']]);

        $this->assertSame(['@type' => 'VirtualLocation', 'url' => 'https://example.org/live'], $withLink['location']);
    }

    public function testToSchemaHybridLocationListsOnlyThePartsThatAreSet(): void
    {
        $fields = ['event_format' => 'hybrid'];
        $link = ['event_link' => ['url' => 'https://example.org/live']];

        $both = $this->schema($fields + $link + ['event_location' => 'Fürth']);
        $this->assertSame(['Place', 'VirtualLocation'], array_column($both['location'], '@type'));

        $this->assertSame(['Place'], array_column($this->schema($fields + ['event_location' => 'Fürth'])['location'], '@type'));
        $this->assertSame(['VirtualLocation'], array_column($this->schema($fields + $link)['location'], '@type'));
    }

    public function testToSchemaImageIsOmittedWithoutAUrl(): void
    {
        $this->assertArrayNotHasKey('image', $this->schema(self::PLACE));

        $GLOBALS['wp_mock_attachments'] = [];
        $entry = Event::mapEvent(77, 'Titel', ['event_date' => '20261008'] + self::PLACE);
        $entry['image'] = 55;
        $this->assertArrayNotHasKey('image', Event::toSchema($entry));

        $GLOBALS['wp_mock_attachments'][55] = ['large' => ['https://example.org/large.jpg', 800, 600]];
        $this->assertSame('https://example.org/large.jpg', Event::toSchema($entry)['image']);
    }

    public function testToSchemaStripsTagsAndOmitsEmptyKeys(): void
    {
        $entry = Event::mapEvent(77, '<b>Fest</b>', ['event_date' => '20261008', 'event_description' => '<i>Hallo</i>'] + self::PLACE);
        $schema = Event::toSchema($entry);

        $this->assertSame('Fest', $schema['name']);
        $this->assertSame('Hallo', $schema['description']);
        $this->assertArrayNotHasKey('url', $schema);
        $this->assertSame('https://example.org/a', $this->schema(self::PLACE + ['event_link' => ['url' => 'https://example.org/a']])['url']);
    }

    public function testToSchemaDropsNonHttpUrls(): void
    {
        $online = ['event_format' => 'online', 'event_link' => ['url' => 'javascript:alert(1)']];

        // The only location source is the unsafe link, so the event goes away entirely.
        $this->assertSame([], $this->schema($online));

        $schema = $this->schema(self::PLACE + ['event_link' => ['url' => 'javascript:alert(1)']]);
        $this->assertArrayNotHasKey('url', $schema);
        $this->assertSame('Place', $schema['location']['@type']);
    }

    public function testToSchemaReturnsEmptyArrayWithoutALocation(): void
    {
        $this->assertSame([], $this->schema());
        $this->assertSame([], $this->schema(['event_format' => 'online', 'event_location' => 'Ignored']));
        $this->assertSame([], $this->schema(['event_format' => 'hybrid']));
    }

    public function testToSchemaCarriesAStableId(): void
    {
        $this->assertSame('https://example.com/#event-77', $this->schema(self::PLACE)['@id']);
    }

    public function testToSchemaReturnsEmptyArrayForAnInvalidDate(): void
    {
        $this->assertSame([], Event::toSchema(['event_date' => '', 'title' => 'x']));
        $this->assertSame([], Event::toSchema(['event_date' => '20261332', 'title' => 'x']));
    }
}
