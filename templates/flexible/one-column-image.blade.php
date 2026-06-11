{{--
    One Column Image - Flexible Content Layout

    Uses shared components: x-section, x-prose, x-card, x-section-header
    ACF Fields: show_section_header, section_chip, section_headline, section_description, image, accordion, background_color
--}}

@php
    $showHeader = get_sub_field('show_section_header');
    $chip = $showHeader ? get_sub_field('section_chip') : null;
    $headline = $showHeader ? \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('section_headline')) : null;
    $description = $showHeader ? \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('section_description')) : null;
    $alignment = $showHeader ? (get_sub_field('section_alignment') ?: 'center') : 'center';
    $label = get_sub_field('label');
    $image = get_sub_field('image');
    $content = get_sub_field('content');
    $accordion = get_sub_field('accordion') ?: [];
    $background = get_sub_field('background_color') ?: 'primary';
    $accordionPrefix = 'acc-oci-' . uniqid();

    // Preserve numeric ID for wp_get_attachment_image; also build array fallback
    $imageId = is_numeric($image) ? (int) $image : null;
    if (is_numeric($image)) {
        $imgSrc = wp_get_attachment_image_src($image, 'hero-split');
        $image = [
            'url' => $imgSrc ? $imgSrc[0] : wp_get_attachment_url($image),
            'alt' => get_post_meta($image, '_wp_attachment_image_alt', true) ?: '',
            'width' => $imgSrc ? $imgSrc[1] : '',
            'height' => $imgSrc ? $imgSrc[2] : '',
        ];
    }
@endphp

<x-section :anchor="$sectionAnchor" :background="$background" class="one-column-image">
    <x-section-header :chip="$chip" :headline="$headline" :description="$description" :alignment="$alignment" />
    <div class="mx-auto max-w-3xl">
        <x-card variant="outlined" padding="none" class="overflow-hidden">
            @if($label)
                <div class="p-6 lg:p-8 pb-0 lg:pb-0">
                    <p class="text-sm font-bold uppercase tracking-wider text-content-secondary mb-4">{{ $label }}</p>
                </div>
            @endif
            @if($imageId)
                {!! wp_get_attachment_image($imageId, 'hero-split', false, [
                    'class' => 'w-full object-cover',
                    'alt' => $image['alt'] ?? '',
                    'loading' => 'lazy',
                    'decoding' => 'async',
                ]) !!}
            @elseif($image && !empty($image['url']))
                <img src="{{ $image['url'] }}"
                     alt="{{ $image['alt'] ?? '' }}"
                     @if(!empty($image['width']) && !empty($image['height']))width="{{ $image['width'] }}" height="{{ $image['height'] }}"@endif
                     class="w-full object-cover"
                     loading="lazy">
            @endif
            @if($content)
                <div class="p-6 lg:p-8">
                    <x-prose>@kses($content)</x-prose>
                </div>
            @endif
            @if(!empty($accordion))
                @include('partials.inline-accordion', [
                    'items' => $accordion,
                    'idPrefix' => $accordionPrefix,
                ])
            @endif
        </x-card>
    </div>
</x-section>
