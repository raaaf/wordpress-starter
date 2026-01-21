{{--
    Hero Block - 3 Variants

    Variants:
    - centered: Badge, Title, Copy, Buttons zentriert (kein Bild)
    - split: Inhalt links, Bild rechts (50/50 Grid)
    - background: Inhalt zentriert über Hintergrundbild mit Overlay

    Uses: x-button, x-badge
    Fields: variant, badge, title, copy, cta_primary, cta_secondary, image, background_image, overlay_opacity, background_color
--}}

@php
    // Extract fields with defaults
    $variant = $fields['variant'] ?? 'centered';
    $badge = $fields['badge'] ?? '';
    $title = $fields['title'] ?? '';
    $copy = $fields['copy'] ?? '';
    $cta_primary = $fields['cta_primary'] ?? null;
    $cta_secondary = $fields['cta_secondary'] ?? null;
    $image = $fields['image'] ?? null;
    $background_image = $fields['background_image'] ?? null;
    $overlay_opacity = $fields['overlay_opacity'] ?? 80;
    $background_color = $fields['background_color'] ?? 'primary';

    // Helper to get image data (supports both array and ID format)
    $getImageData = function($img) {
        if (!$img) return null;
        if (is_array($img)) {
            return [
                'url' => $img['url'] ?? '',
                'alt' => $img['alt'] ?? '',
            ];
        }
        $src = wp_get_attachment_image_src($img, 'full');
        return $src ? ['url' => $src[0], 'alt' => get_post_meta($img, '_wp_attachment_image_alt', true) ?: ''] : null;
    };

    $imageData = $getImageData($image);
    $bgImageData = $getImageData($background_image);

    // Text color based on background
    $textColorClass = match($variant) {
        'background' => '', // Handled dynamically
        default => $background_color === 'inverse' ? 'text-content-inverse' : 'text-content',
    };
@endphp

{{-- Shared Content Component --}}
@php
    $renderContent = function($centered = true) use ($badge, $title, $copy, $cta_primary, $cta_secondary) {
        $alignClass = $centered ? 'items-center text-center' : 'items-start text-left';
        $copyMaxWidth = $centered ? 'max-w-2xl mx-auto' : 'max-w-xl';

        return view('blocks.hero.partials.content', compact(
            'badge', 'title', 'copy', 'cta_primary', 'cta_secondary', 'alignClass', 'copyMaxWidth'
        ))->render();
    };
@endphp

@switch($variant)
    {{-- Variant 1: Centered --}}
    @case('centered')
        <section {!! $wrapper_attributes !!}
                 class="hero hero--centered bg-surface-{{ esc_attr($background_color) }} {{ $textColorClass }}"
                 style="min-height: calc(100dvh - var(--header-height, 80px));"
                 @if($anchor) id="{{ esc_attr($anchor) }}" @endif>
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 flex flex-col items-center justify-center text-center min-h-[inherit] py-16 lg:py-20">
                @if($badge)
                    <x-badge variant="brand" size="md">{{ $badge }}</x-badge>
                @endif

                @if($title)
                    <h1 class="text-display font-headline mt-4">{{ $title }}</h1>
                @endif

                @if($copy)
                    <p class="text-body-large max-w-2xl">{{ $copy }}</p>
                @endif

                @if(($cta_primary && !empty($cta_primary['url'])) || ($cta_secondary && !empty($cta_secondary['url'])))
                    <div class="flex flex-wrap justify-center gap-4 mt-8">
                        @if($cta_primary && !empty($cta_primary['url']))
                            <x-button
                                :url="$cta_primary['url']"
                                :title="$cta_primary['title'] ?? 'Mehr erfahren'"
                                :target="$cta_primary['target'] ?? '_self'"
                                variant="primary"
                                size="lg"
                                :analytics="['event' => 'Hero_Primary_CTA', 'meta' => 'hero_centered']"
                            />
                        @endif
                        @if($cta_secondary && !empty($cta_secondary['url']))
                            <x-button
                                :url="$cta_secondary['url']"
                                :title="$cta_secondary['title'] ?? 'Kontakt'"
                                :target="$cta_secondary['target'] ?? '_self'"
                                variant="secondary"
                                size="lg"
                                :analytics="['event' => 'Hero_Secondary_CTA', 'meta' => 'hero_centered']"
                            />
                        @endif
                    </div>
                @endif
            </div>
        </section>
        @break

    {{-- Variant 2: Split (Content left, Image right) --}}
    @case('split')
        <section {!! $wrapper_attributes !!}
                 class="hero hero--split bg-surface-{{ esc_attr($background_color) }} {{ $textColorClass }}"
                 style="min-height: calc(100dvh - var(--header-height, 80px));"
                 @if($anchor) id="{{ esc_attr($anchor) }}" @endif>
            <div class="grid lg:grid-cols-2 min-h-[inherit]">
                {{-- Content (links auf Desktop, unten auf Mobile) --}}
                <div class="flex flex-col justify-center px-4 sm:px-8 lg:px-16 py-12 lg:py-20 order-2 lg:order-1">
                    @if($badge)
                        <x-badge variant="brand" size="md">{{ $badge }}</x-badge>
                    @endif

                    @if($title)
                        <h1 class="text-display font-headline mt-4">{{ $title }}</h1>
                    @endif

                    @if($copy)
                        <p class="text-body-large max-w-xl">{{ $copy }}</p>
                    @endif

                    @if(($cta_primary && !empty($cta_primary['url'])) || ($cta_secondary && !empty($cta_secondary['url'])))
                        <div class="flex flex-wrap gap-4 mt-8">
                            @if($cta_primary && !empty($cta_primary['url']))
                                <x-button
                                    :url="$cta_primary['url']"
                                    :title="$cta_primary['title'] ?? 'Mehr erfahren'"
                                    :target="$cta_primary['target'] ?? '_self'"
                                    variant="primary"
                                    size="lg"
                                    :analytics="['event' => 'Hero_Primary_CTA', 'meta' => 'hero_split']"
                                />
                            @endif
                            @if($cta_secondary && !empty($cta_secondary['url']))
                                <x-button
                                    :url="$cta_secondary['url']"
                                    :title="$cta_secondary['title'] ?? 'Kontakt'"
                                    :target="$cta_secondary['target'] ?? '_self'"
                                    variant="secondary"
                                    size="lg"
                                    :analytics="['event' => 'Hero_Secondary_CTA', 'meta' => 'hero_split']"
                                />
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Image (rechts auf Desktop, oben auf Mobile) --}}
                <div class="relative order-1 lg:order-2 min-h-[300px] lg:min-h-0">
                    @if($imageData && $imageData['url'])
                        <img src="{{ esc_url($imageData['url']) }}"
                             alt="{{ esc_attr($imageData['alt']) }}"
                             class="absolute inset-0 w-full h-full object-cover"
                             loading="lazy">
                    @else
                        <div class="absolute inset-0 bg-surface-secondary"></div>
                    @endif
                </div>
            </div>
        </section>
        @break

    {{-- Variant 3: Background Image with Overlay --}}
    @case('background')
        <section {!! $wrapper_attributes !!}
                 class="hero hero--background"
                 @if($anchor) id="{{ esc_attr($anchor) }}" @endif>
            {{-- Inner wrapper for absolute positioning context --}}
            <div class="relative overflow-hidden" style="min-height: calc(100dvh - var(--header-height, 80px));">
                {{-- Background Image --}}
                @if($bgImageData && $bgImageData['url'])
                    <img src="{{ esc_url($bgImageData['url']) }}"
                         alt=""
                         aria-hidden="true"
                         class="absolute inset-0 w-full h-full object-cover"
                         loading="lazy">
                @endif

                {{-- Overlay (opacity from slider, uses primary bg for light/dark mode) --}}
                <div class="absolute inset-0 bg-surface-primary"
                     style="opacity: {{ esc_attr($overlay_opacity / 100) }}"></div>

                {{-- Content --}}
                <div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 flex flex-col items-center justify-center text-center min-h-[inherit] py-16 lg:py-20 text-content">
                    @if($badge)
                        <x-badge variant="brand" size="md">{{ $badge }}</x-badge>
                    @endif

                    @if($title)
                        <h1 class="text-display font-headline mt-4">{{ $title }}</h1>
                    @endif

                    @if($copy)
                        <p class="text-body-large max-w-2xl">{{ $copy }}</p>
                    @endif

                    @if(($cta_primary && !empty($cta_primary['url'])) || ($cta_secondary && !empty($cta_secondary['url'])))
                        <div class="flex flex-wrap justify-center gap-4 mt-8">
                            @if($cta_primary && !empty($cta_primary['url']))
                                <x-button
                                    :url="$cta_primary['url']"
                                    :title="$cta_primary['title'] ?? 'Mehr erfahren'"
                                    :target="$cta_primary['target'] ?? '_self'"
                                    variant="primary"
                                    size="lg"
                                    :analytics="['event' => 'Hero_Primary_CTA', 'meta' => 'hero_background']"
                                />
                            @endif
                            @if($cta_secondary && !empty($cta_secondary['url']))
                                <x-button
                                    :url="$cta_secondary['url']"
                                    :title="$cta_secondary['title'] ?? 'Kontakt'"
                                    :target="$cta_secondary['target'] ?? '_self'"
                                    variant="secondary"
                                    size="lg"
                                    :analytics="['event' => 'Hero_Secondary_CTA', 'meta' => 'hero_background']"
                                />
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>
        @break

    {{-- Default fallback --}}
    @default
        <section {!! $wrapper_attributes !!}
                 class="hero hero--centered bg-surface-primary"
                 @if($anchor) id="{{ esc_attr($anchor) }}" @endif>
            <div class="container mx-auto px-4 py-20 text-center">
                <p class="text-content-tertiary">Hero-Block: Bitte eine Variante auswählen.</p>
            </div>
        </section>
@endswitch
