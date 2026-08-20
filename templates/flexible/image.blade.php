{{--
    Image - Flexible Content Layout

    Uses shared components: x-section
    ACF Fields: title, section extras, image (ID), show_border, show_caption, width, background_color

    Uses wp_get_attachment_image() for automatic srcset/responsive images
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $kopf = \WordpressStarter\Helpers\SectionHeader::extras($title);
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

    // Feste Klassennamen und passende sizes, sonst laedt der Browser fuer ein
    // schmales Bild dieselbe grosse Datei wie fuer ein breites.
    [$widthClass, $sizes] = match(get_sub_field('width') ?: 'default') {
        'narrow' => ['max-w-2xl', '(max-width: 672px) 100vw, 672px'],
        'wide' => ['max-w-6xl', '(max-width: 1152px) 100vw, 1152px'],
        default => ['max-w-4xl', '(max-width: 896px) 100vw, 896px'],
    };
@endphp

@if($imageId || $title)
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" padding="md" class="image">
    <x-section-header :chip="$kopf['chip']" :headline="$kopf['headline']" :description="$kopf['description']" :alignment="$kopf['alignment']" />

    @if($imageId)
    <figure class="mx-auto {{ $widthClass }}">
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
            'sizes' => $sizes,
        ]) !!}

        @if($showCaption && ($caption || $alt))
            <figcaption class="mt-4 text-sm text-content-secondary text-center">
                {{ $caption ?: $alt }}
            </figcaption>
        @endif
    </figure>
    @endif
</x-section>
@endif
