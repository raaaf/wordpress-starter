{{--
    Three Columns Block

    Uses shared components: x-section, x-grid, x-prose
    Fields: column_1, column_2, column_3, background_color
--}}

@php
    $column_1 = $fields['column_1'] ?? '';
    $column_2 = $fields['column_2'] ?? '';
    $column_3 = $fields['column_3'] ?? '';
    $background = $fields['background_color'] ?? 'primary';
@endphp

<x-section :background="$background" :anchor="$anchor" :wrapperAttributes="$wrapper_attributes" class="three-columns {{ $classes }}">
    <x-grid cols="3" gap="xl">
        <x-prose>{!! $column_1 !!}</x-prose>
        <x-prose>{!! $column_2 !!}</x-prose>
        <x-prose>{!! $column_3 !!}</x-prose>
    </x-grid>
</x-section>
