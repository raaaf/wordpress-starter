{{--
    CTA - Flexible Content Layout

    Uses shared components: x-section, x-button
    Fields: title, content, button
    Note: Always uses brand background with inverse text
--}}

@php
    $title = str_replace('[br]', '<br>', get_sub_field('title') ?: '');
    $content = get_sub_field('content');
    $button = get_sub_field('button');
@endphp

<x-section background="primary" padding="lg" class="cta">
    <div class="max-w-3xl mx-auto bg-surface-brand rounded-2xl p-8 md:p-12 text-center">
        @if($title)
            <h2 class="mb-4 text-content-inverse">{!! $title !!}</h2>
        @endif

        @if($content)
            <div class="mb-8 text-content-inverse prose-headings:text-content-inverse prose-p:text-content-inverse prose-a:text-content-inverse prose-strong:text-content-inverse">
                {!! $content !!}
            </div>
        @endif

        @if($button)
            <x-button
                :url="$button['url']"
                :title="$button['title']"
                :target="$button['target'] ?? '_self'"
                variant="inverse"
                size="lg"
            />
        @endif
    </div>
</x-section>
