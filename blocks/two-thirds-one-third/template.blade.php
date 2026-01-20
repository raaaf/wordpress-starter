{{--
    Two Thirds / One Third Block

    Uses shared components: x-section, x-grid, x-prose, x-card
    Fields: column_1, column_2, background_color
--}}

@php
    $column_1 = $fields['column_1'] ?? '';
    $column_2 = $fields['column_2'] ?? '';
    $background = $fields['background_color'] ?? 'primary';
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} two-thirds-one-third">
    <x-grid cols="2/3-1/3" gap="lg" align="items-center">
        <x-card variant="outlined" padding="lg">
            <x-prose>{!! $column_1 !!}</x-prose>
        </x-card>
        <x-prose>{!! $column_2 !!}</x-prose>
    </x-grid>
</x-section>
