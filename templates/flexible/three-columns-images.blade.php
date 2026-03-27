{{--
    Three Columns Images - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose, x-card, x-section-header
    ACF Fields: show_section_header, section_chip, section_headline, section_description, image_1, column_1, image_2, column_2, image_3, column_3, background_color
--}}

@php
    $showHeader = get_sub_field('show_section_header');
    $chip = $showHeader ? get_sub_field('section_chip') : null;
    $headline = $showHeader ? \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('section_headline')) : null;
    $description = $showHeader ? \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('section_description')) : null;
    $alignment = $showHeader ? (get_sub_field('section_alignment') ?: 'center') : 'center';
    $image_1 = get_sub_field('image_1');
    $column_1 = get_sub_field('column_1');
    $image_2 = get_sub_field('image_2');
    $column_2 = get_sub_field('column_2');
    $image_3 = get_sub_field('image_3');
    $column_3 = get_sub_field('column_3');
    $background = get_sub_field('background_color') ?: 'primary';

    // Handle ID vs array format for images with proper sizing
    foreach (['image_1', 'image_2', 'image_3'] as $var) {
        if (is_numeric($$var)) {
            $imgSrc = wp_get_attachment_image_src($$var, 'hero-split');
            $$var = [
                'url' => $imgSrc ? $imgSrc[0] : wp_get_attachment_url($$var),
                'alt' => get_post_meta($$var, '_wp_attachment_image_alt', true) ?: '',
                'width' => $imgSrc ? $imgSrc[1] : '',
                'height' => $imgSrc ? $imgSrc[2] : '',
            ];
        }
    }
@endphp

<x-section :anchor="$sectionAnchor" :background="$background" class="three-columns-images">
    <x-section-header :chip="$chip" :headline="$headline" :description="$description" :alignment="$alignment" />
    <x-grid cols="3" gap="xl">
        <x-card variant="outlined" padding="none" class="overflow-hidden">
            @if($image_1 && !empty($image_1['url']))
                <img src="{{ $image_1['url'] }}"
                     alt="{{ $image_1['alt'] ?? '' }}"
                     @if(!empty($image_1['width']) && !empty($image_1['height']))width="{{ $image_1['width'] }}" height="{{ $image_1['height'] }}"@endif
                     class="w-full object-cover"
                     loading="lazy">
            @endif
            @if($column_1)
                <div class="p-6 lg:p-8">
                    <x-prose>{!! $column_1 !!}</x-prose>
                </div>
            @endif
        </x-card>
        <x-card variant="outlined" padding="none" class="overflow-hidden">
            @if($image_2 && !empty($image_2['url']))
                <img src="{{ $image_2['url'] }}"
                     alt="{{ $image_2['alt'] ?? '' }}"
                     @if(!empty($image_2['width']) && !empty($image_2['height']))width="{{ $image_2['width'] }}" height="{{ $image_2['height'] }}"@endif
                     class="w-full object-cover"
                     loading="lazy">
            @endif
            @if($column_2)
                <div class="p-6 lg:p-8">
                    <x-prose>{!! $column_2 !!}</x-prose>
                </div>
            @endif
        </x-card>
        <x-card variant="outlined" padding="none" class="overflow-hidden">
            @if($image_3 && !empty($image_3['url']))
                <img src="{{ $image_3['url'] }}"
                     alt="{{ $image_3['alt'] ?? '' }}"
                     @if(!empty($image_3['width']) && !empty($image_3['height']))width="{{ $image_3['width'] }}" height="{{ $image_3['height'] }}"@endif
                     class="w-full object-cover"
                     loading="lazy">
            @endif
            @if($column_3)
                <div class="p-6 lg:p-8">
                    <x-prose>{!! $column_3 !!}</x-prose>
                </div>
            @endif
        </x-card>
    </x-grid>
</x-section>
