{{--
    Meta line of an event: time, address as Google Maps link, format hint.

    @param array  $event  One entry from Event::getUpcomingEvents()
    @param string $class  Optional additional CSS classes
--}}
@php
    $format = $event['event_format'] ?? 'onsite';
    $hasAddress = $format !== 'online' && !empty($event['event_location']);
    $formatHint = match ($format) {
        'online' => __('Online', 'wp-starter'),
        'hybrid' => __('auch online', 'wp-starter'),
        default => null,
    };
    $hasMeta = !empty($event['event_time']) || $hasAddress || $formatHint;
@endphp
@if($hasMeta)
    <p class="text-body-small text-content-tertiary mb-0 {{ $class ?? '' }}">
        @php $first = true; @endphp
        @if(!empty($event['event_time']))
            {{ $event['event_time'] }}
            @php $first = false; @endphp
        @endif
        @if($hasAddress)
            @if(!$first) · @endif
            <x-link :url="'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($event['event_location'])" target="_blank" variant="dark" size="sm" class="text-inherit! text-[length:inherit]! hover:text-content-secondary!">{{ $event['event_location'] }}</x-link>
            @php $first = false; @endphp
        @endif
        @if($formatHint)
            @if(!$first) · @endif
            {{ $formatHint }}
        @endif
    </p>
@endif
