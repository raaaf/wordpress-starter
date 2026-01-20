{{--
    Three Columns Block

    Uses shared components: x-section, x-grid, x-prose
    Fields: content, content_2, content_3, background_color
--}}

@php
    $content_1 = $fields['content'] ?? '';
    $content_2 = $fields['content_2'] ?? '';
    $content_3 = $fields['content_3'] ?? '';
    $background = $fields['background_color'] ?? 'primary';
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} three-columns">
    <x-grid cols="3" gap="xl">
        <x-prose>{!! $content_1 !!}</x-prose>
        <x-prose>{!! $content_2 !!}</x-prose>
        <x-prose>{!! $content_3 !!}</x-prose>
    </x-grid>
</x-section>
