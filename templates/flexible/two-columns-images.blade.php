{{--
    Two Columns Images - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose, x-card, x-section-header
    ACF Fields: show_section_header, section_chip, section_headline, section_description, image_1, column_1, image_2, column_2, background_color
--}}

@php
    $showHeader = get_sub_field('show_section_header');
    $chip = $showHeader ? get_sub_field('section_chip') : null;
    $headline = $showHeader ? str_replace('[br]', '<br>', get_sub_field('section_headline') ?: '') : null;
    $description = $showHeader ? get_sub_field('section_description') : null;
    $alignment = $showHeader ? (get_sub_field('section_alignment') ?: 'center') : 'center';
    $image_1 = get_sub_field('image_1');
    $column_1 = get_sub_field('column_1');
    $image_2 = get_sub_field('image_2');
    $column_2 = get_sub_field('column_2');
    $background = get_sub_field('background_color') ?: 'primary';

    // Handle ID vs array format for images with proper sizing
    if (is_numeric($image_1)) {
        $imgSrc1 = wp_get_attachment_image_src($image_1, 'hero-split');
        $image_1 = [
            'url' => $imgSrc1 ? $imgSrc1[0] : wp_get_attachment_url($image_1),
            'alt' => get_post_meta($image_1, '_wp_attachment_image_alt', true) ?: '',
            'width' => $imgSrc1 ? $imgSrc1[1] : '',
            'height' => $imgSrc1 ? $imgSrc1[2] : '',
        ];
    }

    if (is_numeric($image_2)) {
        $imgSrc2 = wp_get_attachment_image_src($image_2, 'hero-split');
        $image_2 = [
            'url' => $imgSrc2 ? $imgSrc2[0] : wp_get_attachment_url($image_2),
            'alt' => get_post_meta($image_2, '_wp_attachment_image_alt', true) ?: '',
            'width' => $imgSrc2 ? $imgSrc2[1] : '',
            'height' => $imgSrc2 ? $imgSrc2[2] : '',
        ];
    }
@endphp

<x-section :anchor="$sectionAnchor" :background="$background" class="two-columns-images">
    <x-section-header :chip="$chip" :headline="$headline" :description="$description" :alignment="$alignment" />
    <x-grid cols="2" gap="xl">
        <x-card variant="outlined" padding="none" class="overflow-hidden">
            @if($image_1 && !empty($image_1['url']))
                <img src="{{ $image_1['url'] }}"
                     alt="{{ $image_1['alt'] ?? '' }}"
                     @if(!empty($image_1['width']) && !empty($image_1['height']))width="{{ $image_1['width'] }}" height="{{ $image_1['height'] }}"@endif
                     class="w-full object-cover"
                     loading="lazy">
            @endif
            <div class="p-6 lg:p-8">
                <x-prose>{!! $column_1 !!}</x-prose>
            </div>
        </x-card>
        <x-card variant="outlined" padding="none" class="overflow-hidden">
            @if($image_2 && !empty($image_2['url']))
                <img src="{{ $image_2['url'] }}"
                     alt="{{ $image_2['alt'] ?? '' }}"
                     @if(!empty($image_2['width']) && !empty($image_2['height']))width="{{ $image_2['width'] }}" height="{{ $image_2['height'] }}"@endif
                     class="w-full object-cover"
                     loading="lazy">
            @endif
            <div class="p-6 lg:p-8">
                <x-prose>{!! $column_2 !!}</x-prose>
            </div>
        </x-card>
    </x-grid>
</x-section>
