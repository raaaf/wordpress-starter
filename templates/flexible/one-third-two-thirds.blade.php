{{--
    One Third / Two Thirds - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose, x-card
    ACF Fields: column_1, column_2, background_color
--}}

@php
    $column_1 = get_sub_field('column_1');
    $column_2 = get_sub_field('column_2');
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

<x-section :background="$background" class="one-third-two-thirds">
    <x-grid cols="1/3-2/3" gap="lg" align="items-center">
        <x-prose>{!! $column_1 !!}</x-prose>
        <x-card variant="outlined" padding="lg">
            <x-prose>{!! $column_2 !!}</x-prose>
        </x-card>
    </x-grid>
</x-section>
