{{--
    Gallery - Flexible Content Layout

    Uses shared components: x-section, x-grid
    Uses medium-zoom for lightbox functionality
    Uses wp_get_attachment_image() for automatic srcset/responsive images
    Fields: title, images (gallery), columns, background_color
--}}

@php
    $title = str_replace('[br]', '<br>', get_sub_field('title') ?: '');
    $images = get_sub_field('images');
    $columns = get_sub_field('columns') ?: '3';
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

<x-section :background="$background" class="gallery">
    @if($title)
        <h2 class="mb-12 text-center">{!! $title !!}</h2>
    @endif

    @if(!empty($images))
        <x-grid :cols="$columns" gap="md">
            @foreach($images as $image)
                @php
                    $imageId = $image['ID'] ?? $image;
                    $full = wp_get_attachment_image_src($imageId, 'full');
                    $caption = wp_get_attachment_caption($imageId);
                @endphp
                @if($imageId && $full)
                    <figure class="relative overflow-hidden rounded-lg group">
                        <button type="button" class="block w-full focus:outline-none focus-visible:ring-2 focus-visible:ring-line-focus" aria-label="{{ sprintf(__('Bild vergrößern: %s', 'wp-starter'), get_post_meta($imageId, '_wp_attachment_image_alt', true) ?: wp_get_attachment_caption($imageId) ?: __('Galeriebild', 'wp-starter')) }}">
                        {!! wp_get_attachment_image($imageId, 'gallery-thumb', false, [
                            'class' => 'object-cover w-full transition-transform duration-300 ease-in-out cursor-zoom-in aspect-square gallery-zoom group-hover:scale-105',
                            'loading' => 'lazy',
                            'sizes' => '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw',
                            'data-zoom-src' => esc_url($full[0]),
                        ]) !!}
                        </button>
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
