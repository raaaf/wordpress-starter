{{--
    One Column Block

    Uses shared components: x-section, x-prose
    Fields: label, content, background_color
--}}

@php
    $label = $fields['label'] ?? '';
    $content = $fields['content'] ?? '';
    $background = $fields['background_color'] ?? 'primary';
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} one-column">
    <div class="max-w-2xl mx-auto">
        @if($label)
            <p class="text-overline mb-4 text-content-secondary">{{ $label }}</p>
        @endif
        <x-prose>{!! $content !!}</x-prose>
    </div>
</x-section>
