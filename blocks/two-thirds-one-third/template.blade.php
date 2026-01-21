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

<x-section :background="$background" :anchor="$anchor" :wrapperAttributes="$wrapper_attributes" class="{{ $classes }} two-thirds-one-third">
    <x-grid cols="2/3-1/3" gap="lg" align="items-center">
        <div class="md:col-span-2">
            <x-card variant="outlined" padding="lg">
                <x-prose>{!! $column_1 !!}</x-prose>
            </x-card>
        </div>
        <div class="md:col-span-1">
            <x-prose>{!! $column_2 !!}</x-prose>
        </div>
    </x-grid>
</x-section>
