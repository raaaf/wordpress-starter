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
        unset($GLOBALS['wp_mock_last_query_args'], $GLOBALS['wp_mock_is_admin']);

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

        $this->assertSame('event_date', $query->get('meta_key'));
        $this->assertSame('meta_value', $query->get('orderby'));
        $this->assertSame('ASC', $query->get('order'));
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
}
