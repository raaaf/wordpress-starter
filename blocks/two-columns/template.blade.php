{{--
    Two Columns Block

    Uses shared components: x-section, x-grid, x-prose
    Fields: column_1, column_2, background_color
--}}

@php
    $column_1 = $fields['column_1'] ?? '';
    $column_2 = $fields['column_2'] ?? '';
    $background = $fields['background_color'] ?? 'primary';
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} two-columns">
    <x-grid cols="2" gap="xl" align="items-center">
        <x-prose>{!! $column_1 !!}</x-prose>
        <x-prose>{!! $column_2 !!}</x-prose>
    </x-grid>
</x-section>
