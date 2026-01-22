{{--
    Image - Flexible Content Layout

    Uses shared components: x-section
    ACF Fields: image (ID), show_border, show_caption, background_color
--}}

@php
    $imageId = get_sub_field('image');
    $showBorder = get_sub_field('show_border');
    $showCaption = get_sub_field('show_caption');
    $background = get_sub_field('background_color') ?: 'primary';

    // Get image data from ID including dimensions
    $image = null;
    $caption = '';
    $imageWidth = '';
    $imageHeight = '';
    if ($imageId) {
        $imageSrc = wp_get_attachment_image_src($imageId, 'content');
        $image = [
            'url' => $imageSrc ? $imageSrc[0] : wp_get_attachment_url($imageId),
            'alt' => get_post_meta($imageId, '_wp_attachment_image_alt', true) ?: '',
        ];
        if ($imageSrc) {
            $imageWidth = $imageSrc[1];
            $imageHeight = $imageSrc[2];
        }
        // Get caption from attachment
        $attachment = get_post($imageId);
        if ($attachment) {
            $caption = $attachment->post_excerpt;
        }
    }

    $borderClass = $showBorder ? 'border border-line' : '';
@endphp

<x-section :background="$background" padding="md" class="image">
    <figure class="mx-auto max-w-4xl">
        @if($image && !empty($image['url']))
            <img src="{{ $image['url'] }}"
                 alt="{{ $image['alt'] ?? '' }}"
                 @if($imageWidth && $imageHeight)width="{{ $imageWidth }}" height="{{ $imageHeight }}"@endif
                 class="w-full rounded-lg shadow-xl {{ $borderClass }}"
                 loading="lazy">
        @endif

        @if($showCaption && ($caption || !empty($image['alt'])))
            <figcaption class="mt-4 text-sm text-content-secondary text-center">
                {{ $caption ?: $image['alt'] }}
            </figcaption>
        @endif
    </figure>
</x-section>
