{{--
    Gallery Block

    Uses shared components: x-section, x-grid
    Uses medium-zoom for lightbox functionality
    Fields: title, images (gallery), columns, background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $images = $fields['images'] ?? [];
    $columns = $fields['columns'] ?? '3';
    $background = $fields['background_color'] ?? 'primary';
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} gallery">
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
                            class="object-cover w-full transition-transform duration-300 cursor-zoom-in aspect-square gallery-zoom group-hover:scale-105"
                            loading="lazy"
                        >
                        @if($caption)
                            <figcaption class="absolute inset-x-0 bottom-0 p-3 text-body-small text-white transition-opacity duration-300 opacity-0 bg-gradient-to-t from-black/70 to-transparent group-hover:opacity-100">
                                {{ $caption }}
                            </figcaption>
                        @endif
                    </figure>
                @endif
            @endforeach
        </x-grid>
    @endif
</x-section>

@pushOnce('scripts')
<script type="module">
    import mediumZoom from 'medium-zoom';
    mediumZoom('.gallery-zoom', {
        margin: 24,
        background: 'rgba(0, 0, 0, 0.9)',
        scrollOffset: 40,
    });
</script>
@endPushOnce
