{{--
    Stats/Counter Flexible Content Layout

    Uses shared components: x-section
    Uses Alpine.js for animated counting
    Fields: title, stats (repeater: number, suffix, label, icon), background_color
--}}

@php
    $title = str_replace('[br]', '<br>', get_sub_field('title') ?: '');
    $stats = get_sub_field('stats') ?: [];
    $background = get_sub_field('background_color') ?: 'secondary';

    // Use explicit grid classes to ensure Tailwind includes them
    $statsCount = count($stats);
    $gridClass = match(true) {
        $statsCount >= 4 => 'md:grid-cols-4',
        $statsCount === 3 => 'md:grid-cols-3',
        $statsCount === 2 => 'md:grid-cols-2',
        default => 'md:grid-cols-1',
    };
@endphp

<x-section :background="$background" class="stats">
    @if($title)
        <h2 class="text-h2 mb-12 text-center text-content">{!! $title !!}</h2>
    @endif

    @if(!empty($stats))
        <div class="grid gap-8 text-center {{ $gridClass }}">
            @foreach($stats as $stat)
                @php
                    $number = intval($stat['number'] ?? 0);
                    $suffix = $stat['suffix'] ?? '';
                    $label = $stat['label'] ?? '';
                    $icon = $stat['icon'] ?? '';
                @endphp
                <div
                    x-data="statsCounter({{ $number }})"
                    class="p-6"
                    role="group"
                    aria-label="{{ $label ?: __('Statistik', 'wp-starter') }}"
                >
                    @if($icon)
                        <div class="flex justify-center mb-4 text-content-brand">
                            <x-icon :name="$icon" class="w-10 h-10" aria-hidden="true" />
                        </div>
                    @endif

                    <div class="text-display mb-2 text-content" role="status" aria-live="polite" aria-atomic="true">
                        <span x-text="current.toLocaleString('de-DE')" aria-hidden="true">0</span><span aria-hidden="true">{{ $suffix }}</span>
                        <span class="sr-only" x-text="`${current.toLocaleString('de-DE')}{{ $suffix }}`">{{ $number }}{{ $suffix }}</span>
                    </div>

                    @if($label)
                        <p class="text-body-large text-content-secondary" id="stat-label-{{ $loop->index }}">{{ $label }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-section>
