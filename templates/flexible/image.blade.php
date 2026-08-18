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

@if($imageId)
<x-section :anchor="$sectionAnchor" :background="$background" padding="md" class="image">
    <figure class="mx-auto max-w-4xl">
        {{-- Bewusst ohne 'loading': wp_get_attachment_image() ruft
             wp_get_loading_optimization_attributes() auf. Die zaehlt die
             Bilder der Hauptschleife und entscheidet selbst, welches
             eager laedt und welches lazy. Ein gesetztes 'lazy' liest der
             Kern als "nicht im Viewport" und schaltet damit genau diese
             Logik ab, inklusive fetchpriority="high" fuers erste Bild.
             Weglassen ist der Fix. Die rohen img-Tags im URL-Fallback
             behalten ihr lazy: dort laeuft der Kern gar nicht. --}}
        {!! wp_get_attachment_image($imageId, 'content', false, [
            'alt' => \WordpressStarter\Helpers\Text::imageAlt((int) $imageId, $caption),
            'class' => 'w-full rounded-[var(--card-radius)] shadow-xl ' . $borderClass,
            'sizes' => '(max-width: 896px) 100vw, 896px',
        ]) !!}

        @if($showCaption && ($caption || $alt))
            <figcaption class="mt-4 text-sm text-content-secondary text-center">
                {{ $caption ?: $alt }}
            </figcaption>
        @endif
    </figure>
</x-section>
@endif
