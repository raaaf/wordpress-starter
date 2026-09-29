{{--
    One upcoming event as a list row: calendar-sheet date badge, title, time, address, format, optional link.

    @param array $event   One entry from Event::getUpcomingEvents()
    @param array $date    ['iso', 'day', 'month', 'year'] from the events layout's $formatDate
--}}
<li class="flex items-center gap-4">
    {{-- Bewusst ohne Veranstaltungsbild: bei 64px liest sich kein Motiv, das Bild zeigt nur die grosse Karte. --}}
    <time datetime="{{ $date['iso'] }}" class="shrink-0 flex flex-col items-center justify-center w-16 h-16 rounded-lg text-center bg-[var(--card-surface,var(--bg-secondary))]">
        <span class="text-h4 leading-none w-full text-center m-0">{{ $date['day'] }}</span>
        <span class="text-xs uppercase tracking-wide w-full text-center text-content-secondary">{{ $date['month'] }}{{ $date['year'] ? ' ' . $date['year'] : '' }}</span>
    </time>
    <div class="min-w-0">
        <h4 class="m-0 break-words font-sans text-base font-medium leading-normal tracking-normal text-content">
            @if(!empty($event['event_link']['url']))
                <x-link :url="$event['event_link']['url']" :target="$event['event_link']['target'] ?: '_self'" variant="dark">{{ $event['title'] }}</x-link>
            @else
                {{ $event['title'] }}
            @endif
        </h4>
        @include('partials.event-meta', ['event' => $event])
    </div>
</li>
