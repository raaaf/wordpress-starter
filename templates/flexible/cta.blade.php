{{--
    CTA - Flexible Content Layout

    Uses shared components: x-section, x-button
    Fields: title, content, button
    Note: Always uses brand background with on-brand text (see --text-on-brand in app.css)
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $content = get_sub_field('content');
    $button = get_sub_field('button');
@endphp

@if($title || $content || $button)
<x-section :anchor="$sectionAnchor" background="primary" padding="lg" class="cta">
    <div class="max-w-3xl mx-auto bg-surface-brand rounded-[var(--card-radius)] p-8 md:p-12 text-center">
        @if($title)
            <h2 class="mb-4 text-content-on-brand">{!! $title !!}</h2>
        @endif

        @if($content)
            <div class="mb-8 text-content-on-brand prose-headings:text-content-on-brand prose-p:text-content-on-brand prose-a:text-content-on-brand prose-strong:text-content-on-brand">
                @kses($content)
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
@endif
