{{--
    Heading plus list of upcoming events, with an overflow note when more exist.

    All variables are passed explicitly by the caller (events.blade.php), none is inherited from its scope.

    @param array    $events        Entries from Event::getUpcomingEvents()
    @param callable $formatDate    Required. The events layout's date formatter
    @param bool     $overflow      Optional, default false. More events exist than are shown
    @param string   $headingClass  Optional, default ''. Extra classes on the heading (spacing)
--}}
<h3 class="text-h4 font-semibold mb-4 {{ $headingClass ?? '' }}">{{ __('Weitere Termine', 'wp-starter') }}</h3>
<ul class="flex flex-col gap-4" role="list">
    @foreach($events as $event)
        @include('partials.event-row', ['event' => $event, 'date' => $formatDate($event)])
    @endforeach
</ul>
@if($overflow ?? false)
    <p class="text-body-small text-content-secondary mt-4 mb-0">{{ __('+ weitere Termine', 'wp-starter') }}</p>
@endif
