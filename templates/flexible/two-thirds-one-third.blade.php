{{--
    Two Thirds / One Third - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose, x-card
    Fields: left_column, right_column
--}}

@php
    $left_column = get_sub_field('left_column');
    $right_column = get_sub_field('right_column');
@endphp

<x-section>
    <x-grid cols="2/3-1/3" gap="lg" align="items-center">
        <x-card variant="outlined" padding="lg">
            <x-prose>{!! $left_column !!}</x-prose>
        </x-card>
        <x-prose>{!! $right_column !!}</x-prose>
    </x-grid>
</x-section>
