{{--
    Before/After Slider Block

    Uses shared components: x-section
    Uses Alpine.js for slider functionality
    Fields: title, image_before, image_after, label_before, label_after, background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $imageBefore = wp_get_attachment_image_src($fields['image_before'] ?? null, 'large');
    $imageAfter = wp_get_attachment_image_src($fields['image_after'] ?? null, 'large');
    $labelBefore = $fields['label_before'] ?? 'Vorher';
    $labelAfter = $fields['label_after'] ?? 'Nachher';
    $background = $fields['background_color'] ?? 'primary';
    $uniqueId = 'before-after-' . uniqid();
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} before-after-block">
    @if($title)
        <h2 class="mb-8 text-3xl font-bold text-center text-content">{{ $title }}</h2>
    @endif

    @if($imageBefore && $imageAfter)
        <div
            id="{{ $uniqueId }}"
            x-data="{ position: 50 }"
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
                :style="`clip-path: inset(0 ${100 - position}% 0 0)`"
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
                class="absolute inset-y-0 w-1 -translate-x-1/2 cursor-ew-resize bg-white"
                :style="`left: ${position}%`"
                @mousedown.prevent="
                    const rect = $el.parentElement.getBoundingClientRect();
                    const onMove = (e) => {
                        position = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
                    };
                    const onUp = () => {
                        document.removeEventListener('mousemove', onMove);
                        document.removeEventListener('mouseup', onUp);
                    };
                    document.addEventListener('mousemove', onMove);
                    document.addEventListener('mouseup', onUp);
                "
                @touchstart.prevent="
                    const rect = $el.parentElement.getBoundingClientRect();
                    const onMove = (e) => {
                        const touch = e.touches[0];
                        position = Math.max(0, Math.min(100, ((touch.clientX - rect.left) / rect.width) * 100));
                    };
                    const onEnd = () => {
                        document.removeEventListener('touchmove', onMove);
                        document.removeEventListener('touchend', onEnd);
                    };
                    document.addEventListener('touchmove', onMove);
                    document.addEventListener('touchend', onEnd);
                "
            >
                {{-- Handle circle --}}
                <div class="absolute w-10 h-10 -translate-x-1/2 -translate-y-1/2 bg-white rounded-full shadow-lg top-1/2 left-1/2 flex items-center justify-center">
                    <svg class="w-6 h-6 text-content" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                    </svg>
                </div>
            </div>

            {{-- Labels --}}
            <div class="absolute px-3 py-1 text-sm font-medium text-white rounded top-4 left-4 bg-black/50">
                {{ $labelBefore }}
            </div>
            <div class="absolute px-3 py-1 text-sm font-medium text-white rounded top-4 right-4 bg-black/50">
                {{ $labelAfter }}
            </div>
        </div>
    @else
        <div class="p-8 text-center rounded-lg bg-surface-secondary">
            <p class="text-content-secondary">Bitte füge ein Vorher- und Nachher-Bild hinzu.</p>
        </div>
    @endif
</x-section>
