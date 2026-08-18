{{--
    Before/After Slider Flexible Content Layout

    Uses shared components: x-section, x-section-header
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

    $hasImageBefore = $beforeId && wp_attachment_is_image($beforeId);
    $hasImageAfter  = $afterId && wp_attachment_is_image($afterId);

    $altBefore = $beforeId ? (get_post_meta($beforeId, '_wp_attachment_image_alt', true) ?: $labelBefore) : $labelBefore;
    $altAfter  = $afterId  ? (get_post_meta($afterId,  '_wp_attachment_image_alt', true) ?: $labelAfter)  : $labelAfter;
@endphp

@if(($hasImageBefore && $hasImageAfter) || $title || current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :background="$background" class="before-after">
    <x-section-header :headline="$title" />

    @if($hasImageBefore && $hasImageAfter)
        <div
            id="{{ $uniqueId }}"
            x-data="beforeAfterSlider()"
            class="relative max-w-4xl mx-auto select-none"
        >
            {{-- Bildbereich traegt das overflow-hidden jetzt selbst, nicht mehr der
                 aeussere Container: der Slider-Griff ist per inset-y-0 randbuendig
                 mit dessen Kanten, sein Fokusring waere dort auf voller Hoehe und
                 zusaetzlich bei Position 0/100 auch seitlich abgeschnitten worden
                 (gleiche Falle wie im Akkordeon geloest). --}}
            <div class="relative overflow-hidden rounded-[var(--card-radius)]">
                {{-- After image (background) --}}
                {!! wp_get_attachment_image($afterId, 'content', false, [
                    'class'   => 'block w-full',
                    'alt'     => $altAfter,
                ]) !!}

                {{-- Before image (clipped) --}}
                <div
                    class="absolute inset-0 overflow-hidden"
                    :style="'clip-path: inset(0 ' + (100 - position) + '% 0 0)'"
                >
                    {!! wp_get_attachment_image($beforeId, 'content', false, [
                        'class'   => 'block w-full',
                        'alt'     => $altBefore,
                    ]) !!}
                </div>
            </div>

            {{-- Slider handle (48px hit area with 1px visual bar) --}}
            <div
                role="slider"
                tabindex="0"
                :aria-valuenow="Math.round(position)"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-label="{{ __('Bildvergleich: Nutze die Pfeiltasten, um zwischen Vorher und Nachher zu wechseln', 'wp-starter') }}"
                class="absolute inset-y-0 w-12 -translate-x-1/2 cursor-ew-resize before-after-handle focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring)] rounded-full"
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
                {{-- Visual divider bar (1px, centered) --}}
                <span class="absolute inset-y-0 left-1/2 -translate-x-1/2 w-1 bg-surface opacity-80 pointer-events-none" aria-hidden="true"></span>

                {{-- Handle circle --}}
                <div class="absolute w-12 h-12 -translate-x-1/2 -translate-y-1/2 bg-surface rounded-full shadow-lg top-1/2 left-1/2 flex items-center justify-center border-2 border-line pointer-events-none">
                    <x-icon name="chevron-left" class="w-6 h-6 text-content-secondary" />
                    <x-icon name="chevron-right" class="w-6 h-6 text-content-secondary -ml-2" />
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
    @elseif(current_user_can('edit_posts'))
        <div class="p-8 text-center rounded-lg bg-surface-secondary">
            <p class="text-content-secondary">{{ __('Bitte füge ein Vorher- und Nachher-Bild hinzu.', 'wp-starter') }}</p>
        </div>
    @endif
</x-section>
@endif
