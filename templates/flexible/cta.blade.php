{{--
    CTA - Flexible Content Layout

    Uses shared components: x-section, x-button
    Fields: title, content, button, content_width, background_color
    content_width "container" spans the card over the whole container; the text then
    stays at most 80 characters. Empty or "readable" keeps the text column (max-w-3xl).
    Note: The inner card always uses the brand surface with on-brand text (see
    --text-on-brand in app.css); background_color only sets the surface around it.
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $content = get_sub_field('content');
    $button = get_sub_field('button');
    $background = get_sub_field('background_color') ?: 'primary';
    $kopf = \WordpressStarter\Helpers\SectionHeader::extras(null);
    $wide = get_sub_field('content_width') === 'container';
@endphp

@if($title || $content || $button)
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" padding="lg" class="cta">
    <x-section-header :chip="$kopf['chip']" :description="$kopf['description']" :alignment="$kopf['alignment']" />
    <div class="{{ $wide ? '' : 'max-w-3xl mx-auto' }} bg-surface-brand surface-sheen rounded-[var(--card-radius)] p-8 md:p-12 text-center">
        @if($title)
            <h2 class="mb-4 text-content-on-brand">{!! $title !!}</h2>
        @endif

        @if($content)
            <div class="mb-8 {{ $wide ? 'max-w-[80ch] mx-auto' : '' }} text-content-on-brand prose-headings:text-content-on-brand prose-p:text-content-on-brand prose-a:text-content-on-brand prose-strong:text-content-on-brand">
                @kses($content)
            </div>
        @endif

        @if($button)
            <x-button
                :url="$button['url']"
                :title="$button['title'] ?: null"
                :target="$button['target'] ?? '_self'"
                variant="inverse"
                size="lg"
            />
        @endif
    </div>
</x-section>
@endif
