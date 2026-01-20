{{--
    Four Columns - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose
    Fields: column_1, column_2, column_3, column_4
--}}

@php
    $column_1 = get_sub_field('column_1');
    $column_2 = get_sub_field('column_2');
    $column_3 = get_sub_field('column_3');
    $column_4 = get_sub_field('column_4');
@endphp

<x-section>
    <x-grid cols="4" gap="lg">
        <x-prose>{!! $column_1 !!}</x-prose>
        <x-prose>{!! $column_2 !!}</x-prose>
        <x-prose>{!! $column_3 !!}</x-prose>
        <x-prose>{!! $column_4 !!}</x-prose>
    </x-grid>
</x-section>
