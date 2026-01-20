{{--
    One Third / Two Thirds Block

    Uses shared components: x-section, x-grid, x-prose, x-card
    Fields: content, content_2, background_color
--}}

@php
    $content_1 = $fields['content'] ?? '';
    $content_2 = $fields['content_2'] ?? '';
    $background = $fields['background_color'] ?? 'primary';
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} one-third-two-thirds">
    <x-grid cols="1/3-2/3" gap="lg" align="items-center">
        <x-prose>{!! $content_1 !!}</x-prose>
        <x-card variant="outlined" padding="lg">
            <x-prose>{!! $content_2 !!}</x-prose>
        </x-card>
    </x-grid>
</x-section>
