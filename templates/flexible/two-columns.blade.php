{{--
    Two Columns - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose
    ACF Fields: column_1, column_2, background_color
--}}

@php
    $column_1 = get_sub_field('column_1');
    $column_2 = get_sub_field('column_2');
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

<x-section :background="$background" class="two-columns">
    <x-grid cols="2" gap="lg">
        <x-prose>{!! $column_1 !!}</x-prose>
        <x-prose>{!! $column_2 !!}</x-prose>
    </x-grid>
</x-section>