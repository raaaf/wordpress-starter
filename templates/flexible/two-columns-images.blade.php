{{--
    Two Columns Images - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose, x-card
    Fields: left_image, left_content, right_image, right_content
--}}

@php
    $left_image = get_sub_field('left_image');
    $left_content = get_sub_field('left_content');
    $right_image = get_sub_field('right_image');
    $right_content = get_sub_field('right_content');
@endphp

<x-section>
    <x-grid cols="2" gap="xl">
        <x-card variant="outlined" padding="none" class="overflow-hidden">
            @if($left_image)
                <img src="{{ $left_image['url'] }}"
                     alt="{{ $left_image['alt'] }}"
                     class="w-full object-cover"
                     loading="lazy">
            @endif
            <div class="p-6 lg:p-8">
                <x-prose>{!! $left_content !!}</x-prose>
            </div>
        </x-card>
        <x-card variant="outlined" padding="none" class="overflow-hidden">
            @if($right_image)
                <img src="{{ $right_image['url'] }}"
                     alt="{{ $right_image['alt'] }}"
                     class="w-full object-cover"
                     loading="lazy">
            @endif
            <div class="p-6 lg:p-8">
                <x-prose>{!! $right_content !!}</x-prose>
            </div>
        </x-card>
    </x-grid>
</x-section>
