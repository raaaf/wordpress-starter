{{--
    Three Columns - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose
    Fields: left_column, center_column, right_column
--}}

@php
    $left_column = get_sub_field('left_column');
    $center_column = get_sub_field('center_column');
    $right_column = get_sub_field('right_column');
@endphp

<x-section>
    <x-grid cols="3" gap="xl">
        <x-prose>{!! $left_column !!}</x-prose>
        <x-prose>{!! $center_column !!}</x-prose>
        <x-prose>{!! $right_column !!}</x-prose>
    </x-grid>
</x-section>
