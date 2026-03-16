{{--
    Image - Flexible Content Layout

    Uses shared components: x-section
    ACF Fields: image (ID), show_border, show_caption, background_color

    Uses wp_get_attachment_image() for automatic srcset/responsive images
--}}

@php
    $imageId = get_sub_field('image');
    $showBorder = get_sub_field('show_border');
    $showCaption = get_sub_field('show_caption');
    $background = get_sub_field('background_color') ?: 'primary';

    $caption = '';
    $alt = '';
    if ($imageId) {
        // Get caption and alt from attachment
        $attachment = get_post($imageId);
        if ($attachment) {
            $caption = $attachment->post_excerpt;
        }
        $alt = get_post_meta($imageId, '_wp_attachment_image_alt', true) ?: '';
    }

    $borderClass = $showBorder ? 'border border-line' : '';
@endphp

<x-section :anchor="$sectionAnchor" :background="$background" padding="md" class="image">
    <figure class="mx-auto max-w-4xl">
        @if($imageId)
            {!! wp_get_attachment_image($imageId, 'content', false, [
                'class' => 'w-full rounded-lg shadow-xl ' . $borderClass,
                'loading' => 'lazy',
                'sizes' => '(max-width: 896px) 100vw, 896px',
            ]) !!}
        @endif

        @if($showCaption && ($caption || $alt))
            <figcaption class="mt-4 text-sm text-content-secondary text-center">
                {{ $caption ?: $alt }}
            </figcaption>
        @endif
    </figure>
</x-section>
