{{--
    Gallery - Flexible Content Layout

    Uses shared components: x-section, x-grid
    Uses medium-zoom for lightbox functionality
    Fields: title, images (gallery), columns, background_color
--}}

@php
    $title = get_sub_field('title');
    $images = get_sub_field('images');
    $columns = get_sub_field('columns') ?: '3';
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

<x-section :background="$background" class="gallery">
    @if($title)
        <h2 class="text-h2 mb-12 text-center text-content">{{ $title }}</h2>
    @endif

    @if(!empty($images))
        <x-grid :cols="$columns" gap="md">
            @foreach($images as $image)
                @php
                    $thumb = wp_get_attachment_image_src($image['ID'] ?? $image, 'medium_large');
                    $full = wp_get_attachment_image_src($image['ID'] ?? $image, 'full');
                    $alt = get_post_meta($image['ID'] ?? $image, '_wp_attachment_image_alt', true);
                    $caption = wp_get_attachment_caption($image['ID'] ?? $image);
                @endphp
                @if($thumb && $full)
                    <figure class="relative overflow-hidden rounded-lg group">
                        <img
                            src="{{ esc_url($thumb[0]) }}"
                            data-zoom-src="{{ esc_url($full[0]) }}"
                            alt="{{ esc_attr($alt ?: '') }}"
                            width="{{ $thumb[1] }}"
                            height="{{ $thumb[2] }}"
                            class="object-cover w-full transition-transform duration-300 ease-in-out cursor-zoom-in aspect-square gallery-zoom group-hover:scale-105"
                            loading="lazy"
                        >
                        @if($caption)
                            <figcaption class="absolute inset-x-0 bottom-0 p-3 text-body-small text-content-inverse transition-opacity duration-300 opacity-0 bg-gradient-to-t from-surface-inverse/70 to-transparent group-hover:opacity-100">
                                {{ $caption }}
                            </figcaption>
                        @endif
                    </figure>
                @endif
            @endforeach
        </x-grid>
    @endif
</x-section>

{{-- Gallery zoom is initialized in app.ts via initGalleryZoom() --}}
