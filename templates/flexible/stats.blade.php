{{--
    Stats/Counter Flexible Content Layout

    Uses shared components: x-section
    Uses Alpine.js for animated counting
    Fields: title, stats (repeater: number, suffix, label, icon), background_color
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $stats = get_sub_field('stats') ?: [];
    $background = get_sub_field('background_color') ?: 'primary';

    // Use explicit grid classes to ensure Tailwind includes them
    $statsCount = count($stats);
    $gridClass = match(true) {
        $statsCount >= 4 => 'md:grid-cols-4',
        $statsCount === 3 => 'md:grid-cols-3',
        $statsCount === 2 => 'md:grid-cols-2',
        default => 'md:grid-cols-1',
    };
@endphp

<x-section :anchor="$sectionAnchor" :background="$background" class="stats">
    @if($title)
        <h2 class="mb-12 text-center">{!! $title !!}</h2>
    @endif

    @if(!empty($stats))
        <div class="grid gap-8 text-center {{ $gridClass }}">
            @foreach($stats as $stat)
                @php
                    $number = floatval($stat['number'] ?? 0);
                    $suffix = $stat['suffix'] ?? '';
                    $label = $stat['label'] ?? '';
                    $icon = $stat['icon'] ?? '';
                @endphp
                <div
                    x-data="statsCounter({{ $number }})"
                    class="p-6"
                    role="group"
                    @if($label)
                        aria-labelledby="stat-label-{{ $loop->index }}"
                    @else
                        aria-label="{{ __('Statistik', 'wp-starter') }}"
                    @endif
                >
                    @if($icon)
                        <div class="flex justify-center mb-4 text-content-brand">
                            <x-icon :name="$icon" class="w-10 h-10" aria-hidden="true" />
                        </div>
                    @endif

                    <div class="text-display tabular-nums mb-2 text-content" aria-hidden="true">
                        <span x-text="current.toLocaleString('de-DE', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })">0</span>@if($suffix)<span> {{ $suffix }}</span>@endif
                    </div>
                    <span class="sr-only">{{ $number }}{{ $suffix ? ' ' . $suffix : '' }}</span>

                    @if($label)
                        <p class="text-body-large text-content-secondary" id="stat-label-{{ $loop->index }}">{{ $label }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-section>
