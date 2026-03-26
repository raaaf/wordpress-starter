{{--
    Before/After Slider Flexible Content Layout

    Uses shared components: x-section
    Uses Alpine.js beforeAfterSlider component for slider functionality
    Fields: title, image_before, image_after, label_before, label_after, background_color
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $labelBefore = get_sub_field('label_before') ?: 'Vorher';
    $labelAfter = get_sub_field('label_after') ?: 'Nachher';
    $background = get_sub_field('background_color') ?: 'primary';
    $uniqueId = 'before-after-' . uniqid();

    // Handle both ID and array format for images
    $beforeId = get_sub_field('image_before');
    $afterId = get_sub_field('image_after');
    if (is_array($beforeId)) {
        $beforeId = $beforeId['ID'] ?? $beforeId['id'] ?? null;
    }
    if (is_array($afterId)) {
        $afterId = $afterId['ID'] ?? $afterId['id'] ?? null;
    }

    $imageBefore = $beforeId ? wp_get_attachment_image_src($beforeId, 'content') : null;
    $imageAfter = $afterId ? wp_get_attachment_image_src($afterId, 'content') : null;
@endphp

<x-section :anchor="$sectionAnchor" :background="$background" class="before-after">
    @if($title)
        <h2 class="mb-8 text-center">{!! $title !!}</h2>
    @endif

    @if($imageBefore && $imageAfter)
        <div
            id="{{ $uniqueId }}"
            x-data="beforeAfterSlider()"
            class="relative max-w-4xl mx-auto overflow-hidden rounded-xl select-none"
        >
            {{-- After image (background) --}}
            <img
                src="{{ $imageAfter[0] }}"
                alt="{{ $labelAfter }}"
                class="block w-full"
                loading="lazy"
            >

            {{-- Before image (clipped) --}}
            <div
                class="absolute inset-0 overflow-hidden"
                :style="'clip-path: inset(0 ' + (100 - position) + '% 0 0)'"
            >
                <img
                    src="{{ $imageBefore[0] }}"
                    alt="{{ $labelBefore }}"
                    class="block w-full"
                    loading="lazy"
                >
            </div>

            {{-- Slider handle --}}
            <div
                role="slider"
                tabindex="0"
                :aria-valuenow="Math.round(position)"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-label="{{ __('Bildvergleich: Verwenden Sie die Pfeiltasten, um zwischen Vorher und Nachher zu wechseln', 'wp-starter') }}"
                class="absolute inset-y-0 w-1 -translate-x-1/2 cursor-ew-resize bg-surface opacity-80 before-after-handle focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-line-focus focus-visible:ring-offset-2"
                :style="'left: ' + position + '%'"
                @mousedown="handleMouseDown($event)"
                @touchstart="handleTouchStart($event)"
                @keydown.right.prevent="position = Math.min(100, position + 5)"
                @keydown.left.prevent="position = Math.max(0, position - 5)"
                @keydown.up.prevent="position = Math.min(100, position + 5)"
                @keydown.down.prevent="position = Math.max(0, position - 5)"
                @keydown.home.prevent="position = 0"
                @keydown.end.prevent="position = 100"
                @keydown.page-up.prevent="position = Math.min(100, position + 10)"
                @keydown.page-down.prevent="position = Math.max(0, position - 10)"
            >
                {{-- Handle circle --}}
                <div class="absolute w-12 h-12 -translate-x-1/2 -translate-y-1/2 bg-surface rounded-full shadow-lg top-1/2 left-1/2 flex items-center justify-center border-2 border-line">
                    <svg class="w-6 h-6 text-content-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <svg class="w-6 h-6 text-content-secondary -ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>

            {{-- Labels --}}
            <div class="absolute top-4 left-4">
                <x-badge variant="brand" size="sm">{{ $labelBefore }}</x-badge>
            </div>
            <div class="absolute top-4 right-4">
                <x-badge variant="brand" size="sm">{{ $labelAfter }}</x-badge>
            </div>
        </div>
    @else
        <div class="p-8 text-center rounded-lg bg-surface-secondary">
            <p class="text-content-secondary">{{ __('Bitte füge ein Vorher- und Nachher-Bild hinzu.', 'wp-starter') }}</p>
        </div>
    @endif
</x-section>
