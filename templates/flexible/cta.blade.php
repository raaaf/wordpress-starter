{{--
    CTA - Flexible Content Layout

    Uses shared components: x-section, x-prose, x-button
    Fields: title, content, button, background_color
--}}

@php
    $title = get_sub_field('title');
    $content = get_sub_field('content');
    $button = get_sub_field('button');
    $background = get_sub_field('background_color') ?: 'brand';
@endphp

<x-section :background="$background" padding="lg">
    <div class="text-center">
        @if($title)
            <h2 class="text-3xl md:text-4xl font-bold mb-4">{{ $title }}</h2>
        @endif

        @if($content)
            <div class="max-w-2xl mx-auto mb-8">
                <x-prose>{!! $content !!}</x-prose>
            </div>
        @endif

        @if($button)
            <x-button
                :url="$button['url']"
                :title="$button['title']"
                :target="$button['target'] ?? '_self'"
                variant="primary"
                size="lg"
            />
        @endif
    </div>
</x-section>
