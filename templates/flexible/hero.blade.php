{{--
    Hero - Flexible Content Layout

    Variants: centered, split, background
    ACF Fields: variant, badge, title, copy, cta_primary, cta_secondary, image, background_image, background_color
--}}

@php
    $variant = get_sub_field('variant') ?: 'centered';
    $badge = get_sub_field('badge');
    $title = get_sub_field('title');
    $copy = get_sub_field('copy');
    $cta_primary = get_sub_field('cta_primary');
    $cta_secondary = get_sub_field('cta_secondary');
    $image = get_sub_field('image');
    $background_image = get_sub_field('background_image');
    $background_color = get_sub_field('background_color') ?: 'primary';
    $overlay_opacity = get_sub_field('overlay_opacity');
    $overlay_opacity = is_numeric($overlay_opacity) ? (int) $overlay_opacity : 70;

    // Convert 0-100 to 0-1 for CSS opacity
    $overlay_opacity_css = $overlay_opacity / 100;

    // Handle ID vs array format for images - include dimensions
    if (is_numeric($image)) {
        $imageSrc = wp_get_attachment_image_src($image, 'hero-split');
        $image = [
            'url' => $imageSrc ? $imageSrc[0] : wp_get_attachment_url($image),
            'alt' => get_post_meta($image, '_wp_attachment_image_alt', true) ?: '',
            'width' => $imageSrc ? $imageSrc[1] : '',
            'height' => $imageSrc ? $imageSrc[2] : '',
        ];
    }

    if (is_numeric($background_image)) {
        $bgSrc = wp_get_attachment_image_src($background_image, 'full');
        $background_image = [
            'url' => $bgSrc ? $bgSrc[0] : wp_get_attachment_url($background_image),
            'alt' => get_post_meta($background_image, '_wp_attachment_image_alt', true) ?: '',
            'width' => $bgSrc ? $bgSrc[1] : '',
            'height' => $bgSrc ? $bgSrc[2] : '',
        ];
    }
@endphp

@if($variant === 'background')
    {{-- BACKGROUND VARIANT: Full-width image with overlay --}}
    <section class="hero hero--background relative overflow-hidden flex items-center" style="min-height: calc(100vh - var(--header-height, 80px)); min-height: calc(100dvh - var(--header-height, 80px));">
        @if($background_image && !empty($background_image['url']))
            <div class="absolute inset-0">
                <img src="{{ $background_image['url'] }}"
                     alt="{{ $background_image['alt'] ?? '' }}"
                     @if(!empty($background_image['width']) && !empty($background_image['height']))width="{{ $background_image['width'] }}" height="{{ $background_image['height'] }}"@endif
                     class="w-full h-full object-cover">
                {{-- Overlay with configurable opacity using CSS custom property --}}
                <div class="absolute inset-0 bg-surface" style="--tw-bg-opacity: {{ $overlay_opacity_css }};"></div>
            </div>
        @else
            <div class="absolute inset-0 bg-surface-brand"></div>
        @endif

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 flex items-center justify-center text-center w-full">
            <div class="max-w-3xl">
                @if($badge)
                    <x-badge variant="brand" style="outline" size="md" class="mb-4">{{ $badge }}</x-badge>
                @endif

                @if($title)
                    <h1 class="text-display mb-6 text-content">
                        {!! $title !!}
                    </h1>
                @endif

                @if($copy)
                    <p class="text-body-large mb-8 text-content-secondary">{{ $copy }}</p>
                @endif

                @if($cta_primary || $cta_secondary)
                    <div class="flex flex-wrap gap-4 justify-center">
                        @if($cta_primary)
                            <x-button
                                :url="$cta_primary['url']"
                                :title="$cta_primary['title']"
                                :target="$cta_primary['target'] ?? '_self'"
                                variant="primary"
                                size="lg"
                            />
                        @endif
                        @if($cta_secondary)
                            <x-button
                                :url="$cta_secondary['url']"
                                :title="$cta_secondary['title']"
                                :target="$cta_secondary['target'] ?? '_self'"
                                variant="secondary"
                                size="lg"
                            />
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

@elseif($variant === 'split')
    {{-- SPLIT VARIANT: Content left, image right --}}
    <x-section :background="$background_color" padding="lg" class="hero hero--split">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                @if($badge)
                    <x-badge variant="accent" size="md" class="mb-4">{{ $badge }}</x-badge>
                @endif

                @if($title)
                    <h1 class="text-4xl md:text-5xl font-bold mb-6 text-content">
                        {!! $title !!}
                    </h1>
                @endif

                @if($copy)
                    <p class="text-lg mb-8 text-content-secondary">{{ $copy }}</p>
                @endif

                @if($cta_primary || $cta_secondary)
                    <div class="flex flex-wrap gap-4">
                        @if($cta_primary)
                            <x-button
                                :url="$cta_primary['url']"
                                :title="$cta_primary['title']"
                                :target="$cta_primary['target'] ?? '_self'"
                                variant="primary"
                                size="lg"
                            />
                        @endif
                        @if($cta_secondary)
                            <x-button
                                :url="$cta_secondary['url']"
                                :title="$cta_secondary['title']"
                                :target="$cta_secondary['target'] ?? '_self'"
                                variant="secondary"
                                size="lg"
                            />
                        @endif
                    </div>
                @endif
            </div>

            @if($image && !empty($image['url']))
                <div class="relative">
                    <img src="{{ $image['url'] }}"
                         alt="{{ $image['alt'] ?? '' }}"
                         @if(!empty($image['width']) && !empty($image['height']))width="{{ $image['width'] }}" height="{{ $image['height'] }}"@endif
                         class="w-full h-auto rounded-2xl shadow-xl"
                         loading="lazy">
                </div>
            @endif
        </div>
    </x-section>

@else
    {{-- CENTERED VARIANT (default): Centered content --}}
    <x-section :background="$background_color" padding="xl" class="hero hero--centered">
        <div class="max-w-3xl mx-auto text-center">
            @if($badge)
                <x-badge variant="accent" size="md" class="mb-4">{{ $badge }}</x-badge>
            @endif

            @if($title)
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-content">
                    {!! $title !!}
                </h1>
            @endif

            @if($copy)
                <p class="text-lg md:text-xl mb-8 text-content-secondary">{{ $copy }}</p>
            @endif

            @if($cta_primary || $cta_secondary)
                <div class="flex flex-wrap gap-4 justify-center">
                    @if($cta_primary)
                        <x-button
                            :url="$cta_primary['url']"
                            :title="$cta_primary['title']"
                            :target="$cta_primary['target'] ?? '_self'"
                            variant="primary"
                            size="lg"
                        />
                    @endif
                    @if($cta_secondary)
                        <x-button
                            :url="$cta_secondary['url']"
                            :title="$cta_secondary['title']"
                            :target="$cta_secondary['target'] ?? '_self'"
                            variant="outline"
                            size="lg"
                        />
                    @endif
                </div>
            @endif
        </div>
    </x-section>
@endif
