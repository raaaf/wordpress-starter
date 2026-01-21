{{--
    Stats/Counter Block

    Uses shared components: x-section
    Uses Alpine.js for animated counting
    Fields: title, stats (repeater: number, suffix, label, icon), background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $stats = $fields['stats'] ?? [];
    $background = $fields['background_color'] ?? 'brand';

    // Use explicit grid classes to ensure Tailwind includes them
    $statsCount = count($stats);
    $gridClass = match(true) {
        $statsCount >= 4 => 'md:grid-cols-4',
        $statsCount === 3 => 'md:grid-cols-3',
        $statsCount === 2 => 'md:grid-cols-2',
        default => 'md:grid-cols-1',
    };
@endphp

<x-section :background="$background" :anchor="$anchor" :wrapperAttributes="$wrapper_attributes" class="{{ $classes }} stats-block">
    @if($title)
        <h2 class="text-h2 mb-12 text-center text-content">{{ $title }}</h2>
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
                <div x-data="statsCounter({{ $number }})" class="p-6">
                    @if($icon)
                        <div class="flex justify-center mb-4 text-content-brand">
                            <x-icon :name="$icon" class="w-10 h-10" />
                        </div>
                    @endif

                    <div class="text-display mb-2 text-content">
                        <span x-text="current.toLocaleString('de-DE')">0</span><span>{{ $suffix }}</span>
                    </div>

                    @if($label)
                        <p class="text-body-large text-content-secondary">{{ $label }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-section>
