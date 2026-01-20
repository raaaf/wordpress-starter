{{--
    Two Columns Block

    Uses shared components: x-section, x-grid, x-prose
    Fields: content, content_2, background_color
--}}

@php
    $content_1 = $fields['content'] ?? '';
    $content_2 = $fields['content_2'] ?? '';
    $background = $fields['background_color'] ?? 'primary';
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} two-columns">
    <x-grid cols="2" gap="xl" align="items-center">
        <x-prose>{!! $content_1 !!}</x-prose>
        <x-prose>{!! $content_2 !!}</x-prose>
    </x-grid>
</x-section>
