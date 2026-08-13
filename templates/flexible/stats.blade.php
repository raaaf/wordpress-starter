{{--
    Stats/Counter Flexible Content Layout

    Uses shared components: x-section, x-section-header
    Uses Alpine.js for animated counting
    Fields: title, stats (repeater: number, suffix, label, icon), background_color
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $stats = get_sub_field('stats') ?: [];
    $background = get_sub_field('background_color') ?: 'primary';
    $statsId = uniqid();

    // Flex statt Grid, damit eine unvollstaendige letzte Zeile mittig steht.
    //
    // Vorher: ab vier Kennzahlen immer vier Spalten. Bei fuenf sass die fuenfte
    // allein und linksbuendig unter vier Nachbarn, mit drei leeren Zellen
    // daneben. Ein Raster kann seine letzte Zeile nicht zentrieren, ein
    // umbrechender Flexcontainer schon.
    //
    // Die Breitenklassen bleiben fest notiert, damit Tailwind sie findet.
    $statsCount = count($stats);
    $itemClass = match(true) {
        $statsCount >= 4 => 'md:w-[calc(25%-1.5rem)]',
        $statsCount === 3 => 'md:w-[calc(33.333%-1.334rem)]',
        $statsCount === 2 => 'md:w-[calc(50%-1rem)]',
        default => 'md:w-full',
    };
@endphp

@if($title || !empty($stats))
<x-section :anchor="$sectionAnchor" :background="$background" class="stats">
    <x-section-header :headline="$title" />

    @if(!empty($stats))
        <div class="flex flex-wrap justify-center gap-8 text-center">
            @foreach($stats as $stat)
                @php
                    $number = floatval($stat['number'] ?? 0);
                    $suffix = $stat['suffix'] ?? '';
                    $label = $stat['label'] ?? '';
                    $icon = $stat['icon'] ?? '';
                @endphp
                <div
                    x-data="statsCounter({{ $number }})"
                    class="w-full p-6 {{ $itemClass }}"
                    role="group"
                    @if($label)
                        aria-labelledby="stat-label-{{ $statsId }}-{{ $loop->index }}"
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
                        <span x-text="current.toLocaleString('de-DE', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })">0</span>@if($suffix)<span>{{ in_array($suffix, ['%', '‰', '°C', '°F'], true) ? ' ' : '' }}{{ $suffix }}</span>@endif
                    </div>
                    <span class="sr-only">{{ $number }}{{ $suffix ? ' ' . $suffix : '' }}</span>

                    @if($label)
                        <p class="text-body-large text-content-secondary" id="stat-label-{{ $statsId }}-{{ $loop->index }}">{{ $label }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-section>
@endif
