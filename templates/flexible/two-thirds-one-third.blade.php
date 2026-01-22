{{--
    Two Thirds / One Third - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose, x-card
    ACF Fields: column_1, column_2, background_color
--}}

@php
    $column_1 = get_sub_field('column_1');
    $column_2 = get_sub_field('column_2');
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

<x-section :background="$background" class="two-thirds-one-third">
    <x-grid cols="2/3-1/3" gap="lg" align="items-center">
        <x-card variant="outlined" padding="lg">
            <x-prose>{!! $column_1 !!}</x-prose>
        </x-card>
        <x-prose>{!! $column_2 !!}</x-prose>
    </x-grid>
</x-section>
