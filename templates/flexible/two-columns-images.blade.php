{{--
    Two Columns Images - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose, x-card
    ACF Fields: image_1, column_1, image_2, column_2, background_color
--}}

@php
    $image_1 = get_sub_field('image_1');
    $column_1 = get_sub_field('column_1');
    $image_2 = get_sub_field('image_2');
    $column_2 = get_sub_field('column_2');
    $background = get_sub_field('background_color') ?: 'primary';

    // Handle ID vs array format for images
    if (is_numeric($image_1)) {
        $image_1 = [
            'url' => wp_get_attachment_url($image_1),
            'alt' => get_post_meta($image_1, '_wp_attachment_image_alt', true) ?: '',
        ];
    }

    if (is_numeric($image_2)) {
        $image_2 = [
            'url' => wp_get_attachment_url($image_2),
            'alt' => get_post_meta($image_2, '_wp_attachment_image_alt', true) ?: '',
        ];
    }
@endphp

<x-section :background="$background" class="two-columns-images">
    <x-grid cols="2" gap="xl">
        <x-card variant="outlined" padding="none" class="overflow-hidden">
            @if($image_1 && !empty($image_1['url']))
                <img src="{{ $image_1['url'] }}"
                     alt="{{ $image_1['alt'] ?? '' }}"
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
                     class="w-full object-cover"
                     loading="lazy">
            @endif
            <div class="p-6 lg:p-8">
                <x-prose>{!! $column_2 !!}</x-prose>
            </div>
        </x-card>
    </x-grid>
</x-section>
