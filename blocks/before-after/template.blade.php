{{--
    Before/After Slider Block

    Uses shared components: x-section
    Uses Alpine.js beforeAfterSlider component for slider functionality
    Fields: title, image_before, image_after, label_before, label_after, background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $labelBefore = $fields['label_before'] ?? 'Vorher';
    $labelAfter = $fields['label_after'] ?? 'Nachher';
    $background = $fields['background_color'] ?? 'primary';
    $uniqueId = 'before-after-' . uniqid();

    // Handle both ID and array format for images
    $beforeId = $fields['image_before'] ?? null;
    $afterId = $fields['image_after'] ?? null;
    if (is_array($beforeId)) {
        $beforeId = $beforeId['ID'] ?? $beforeId['id'] ?? null;
    }
    if (is_array($afterId)) {
        $afterId = $afterId['ID'] ?? $afterId['id'] ?? null;
    }

    $imageBefore = $beforeId ? wp_get_attachment_image_src($beforeId, 'large') : null;
    $imageAfter = $afterId ? wp_get_attachment_image_src($afterId, 'large') : null;
@endphp

<x-section :background="$background" :anchor="$anchor" :wrapperAttributes="$wrapper_attributes" class="before-after {{ $classes }}">
    @if($title)
        <h2 class="text-h2 mb-8 text-center text-content">{{ $title }}</h2>
    @endif

    @if($imageBefore && $imageAfter)
        <div
            id="{{ $uniqueId }}"
            @if(!$is_preview) x-data="beforeAfterSlider()" @endif
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
                @if(!$is_preview) :style="'clip-path: inset(0 ' + (100 - position) + '% 0 0)'" @else style="clip-path: inset(0 50% 0 0)" @endif
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
                class="absolute inset-y-0 w-1 -translate-x-1/2 cursor-ew-resize bg-surface opacity-80 before-after-handle"
                @if(!$is_preview)
                    :style="'left: ' + position + '%'"
                    @mousedown="handleMouseDown($event)"
                    @touchstart="handleTouchStart($event)"
                @else
                    style="left: 50%"
                @endif
            >
                {{-- Handle circle --}}
                <div class="absolute w-12 h-12 -translate-x-1/2 -translate-y-1/2 bg-surface rounded-full shadow-lg top-1/2 left-1/2 flex items-center justify-center border-2 border-line">
                    <svg class="w-6 h-6 text-content-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <svg class="w-6 h-6 text-content-secondary -ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
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
            <p class="text-content-secondary">Bitte füge ein Vorher- und Nachher-Bild hinzu.</p>
        </div>
    @endif
</x-section>
