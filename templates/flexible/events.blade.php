{{--
    Events - Flexible Content Layout

    Uses shared components: x-section, x-section-header, x-prose, x-link, x-card, x-button;
    partials: partials.empty-state, partials.event-meta, partials.event-list
    (which includes partials.event-row).
    Fields: title, text, link, all_events_link, background_color, plus the section header extras read by
    SectionHeader::extras() (section_chip, section_description, section_alignment).

    Data comes from the "event" custom post type (Event::getUpcomingEvents()), not from a
    repeater. No event is shown twice: the featured event is
    taken off the front, the list shows the ones after them, capped at $listMax.

    Layout: the next event large on top, the following events as a list below.
    Per event: format (on site / online / hybrid), address as Google Maps link, one optional link.
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $kopf = \WordpressStarter\Helpers\SectionHeader::extras($title);
    $text = get_sub_field('text');
    $link = get_sub_field('link');
    $allEventsLink = get_sub_field('all_events_link');
    $background = get_sub_field('background_color') ?: 'primary';

    // Nobody scrolls past eight list entries on a content page.
    $listMax = 8;
    $leadCount = 1;

    // Fetch one more than shown so "exactly full" does not count as overflow.
    $fetched = \WordpressStarter\PostTypes\Event::getUpcomingEvents($leadCount + $listMax + 1);
    $lead = array_slice($fetched, 0, $leadCount);
    $rest = array_slice($fetched, $leadCount, $listMax);
    $overflow = count($fetched) > $leadCount + $listMax;

    /**
     * @param array<string, mixed> $event
     * @return array{iso: string, day: string, month: string, year: string|null}
     */
    $formatDate = function (array $event): array {
        $timezone = wp_timezone();
        // Stored as Ymd; '!' zeroes the time so the date stays a calendar day in site time.
        // Event::getUpcomingEvents() only returns valid dates; fall back to today rather than fatal.
        $date = \DateTimeImmutable::createFromFormat('!Ymd', (string) $event['event_date'], $timezone)
            ?: new \DateTimeImmutable('today', $timezone);
        $timestamp = $date->getTimestamp();

        return [
            'iso' => wp_date('Y-m-d', $timestamp, $timezone),
            'day' => wp_date('d', $timestamp, $timezone),
            'month' => wp_date('M', $timestamp, $timezone),
            'year' => wp_date('Y', $timestamp, $timezone) !== wp_date('Y', null, $timezone)
                ? wp_date('Y', $timestamp, $timezone)
                : null,
        ];
    };
@endphp

@if($overflow)
    @php \WordpressStarter\Providers\LogServiceProvider::warning('Events-Layout: Listenlimit erreicht, weitere Termine werden nicht angezeigt.'); @endphp
@endif

<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" class="events">
    @if(empty($kopf['headline']))
        {{-- Ohne Titel gaebe es keine h2 vor den h3-Terminkarten. --}}
        <h2 class="sr-only">{{ __('Veranstaltungen', 'wp-starter') }}</h2>
    @endif
    <x-section-header :chip="$kopf['chip']" :headline="$kopf['headline']" :description="$kopf['description']" :alignment="$kopf['alignment']" :class="$text ? 'mb-4!' : ''" />

    @if($text)
        <div class="max-w-2xl mx-auto text-center mb-8"><x-prose>@kses($text)</x-prose></div>
    @endif

    @if(!empty($link['url']))
        <div class="text-center mb-8">
            <x-link :url="$link['url']" :target="$link['target'] ?? '_self'">{{ $link['title'] ?: __('Mehr erfahren', 'wp-starter') }}</x-link>
        </div>
    @endif

    @if(empty($fetched))
        @include('partials.empty-state', [
            'title' => __('Keine Veranstaltungen geplant', 'wp-starter'),
            'text' => __('Aktuell sind keine zukünftigen Termine eingetragen.', 'wp-starter'),
            'icon' => 'calendar',
        ])
    @else
        @php
            $next = $lead[0];
            $date = $formatDate($next);
            $nextImage = $next['image'] ?? null;
        @endphp
        <div class="max-w-3xl mx-auto w-full">
            <x-card variant="filled" padding="lg">
                <div class="{{ $nextImage ? 'flex flex-col sm:flex-row sm:items-center gap-6' : 'flex items-center gap-6' }}">
                    <div class="{{ $nextImage ? 'relative shrink-0 w-full sm:w-56 aspect-[4/3] overflow-hidden rounded-lg' : 'shrink-0' }}">
                        @if($nextImage)
                            {!! wp_get_attachment_image((int) $nextImage, 'card-video', false, [
                                'alt' => \WordpressStarter\Helpers\Text::imageAlt((int) $nextImage, $next['title']),
                                'class' => 'object-cover w-full h-full',
                                'sizes' => '(min-width: 640px) 224px, 100vw',
                            ]) !!}
                        @endif
                        <time datetime="{{ $date['iso'] }}" class="{{ $nextImage ? 'absolute top-3 left-3' : '' }} flex flex-col items-center justify-center w-20 h-20 rounded-lg text-center bg-[var(--bg-brand-tint)] text-content-brand">
                            <span class="text-h3 leading-none w-full text-center m-0">{{ $date['day'] }}</span>
                            <span class="text-xs uppercase tracking-wide w-full text-center text-content-brand">{{ $date['month'] }}{{ $date['year'] ? ' ' . $date['year'] : '' }}</span>
                        </time>
                    </div>
                    <div class="min-w-0">
                        <p class="text-body-small text-content-brand font-medium mb-1">{{ __('Nächster Termin', 'wp-starter') }}</p>
                        <h3 class="text-h4 font-semibold m-0 break-words">{{ $next['title'] }}</h3>
                        @include('partials.event-meta', ['event' => $next, 'class' => 'mt-1'])
                        @if($next['event_description'])
                            <p class="text-body-small text-content-secondary mt-2 mb-0">{{ $next['event_description'] }}</p>
                        @endif
                        @if($next['event_link'])
                            <x-button :url="$next['event_link']['url']" :title="$next['event_link']['title'] ?: sprintf(__('Mehr zu %s', 'wp-starter'), $next['title'])" :target="$next['event_link']['target'] ?: '_self'" size="sm" class="mt-4" />
                        @endif
                    </div>
                </div>
            </x-card>

            @if(!empty($rest))
                {{-- Mit Uebersichtslink ersetzt der Link den Hinweis "+ weitere Termine". --}}
                @include('partials.event-list', ['events' => $rest, 'headingClass' => 'mt-10', 'formatDate' => $formatDate, 'overflow' => $overflow && empty($allEventsLink['url'])])
            @endif

            @if(!empty($allEventsLink['url']))
                <div class="mt-6">
                    <x-link :url="$allEventsLink['url']" :target="$allEventsLink['target'] ?: '_self'">{{ $allEventsLink['title'] ?: __('Alle Termine ansehen', 'wp-starter') }}</x-link>
                </div>
            @endif
        </div>
    @endif
</x-section>

{{-- Event JSON-LD, only for content the public can reach (shared gate: SeoServiceProvider::canEmitSchema()). --}}
@if(!empty($fetched) && \WordpressStarter\Providers\SeoServiceProvider::canEmitSchema())
    @php \WordpressStarter\Providers\SeoServiceProvider::emitEventSchema(array_merge($lead, $rest)); @endphp
@endif
