{{--
    Hero Block

    Uses shared components: x-button, x-prose
    Fields: title, content, background_image, overlay_opacity, text_color, min_height, content_alignment, cta_button
--}}

@php
    $title = $fields['title'] ?? '';
    $content = $fields['content'] ?? '';
    $background_image = $fields['background_image'] ?? null;
    $overlay_opacity = $fields['overlay_opacity'] ?? 50;
    $text_color = $fields['text_color'] ?? 'white';
    $min_height = $fields['min_height'] ?? '500px';
    $alignment = $fields['content_alignment'] ?? 'center';

    $image_url = '';
    if ($background_image) {
        $image = \WordpressStarter\Acf\Fields::image('background_image', 'full');
        $image_url = $image['url'] ?? '';
    }

    $textClass = $text_color === 'white' ? 'text-content-inverse' : 'text-content';
@endphp

<div class="{{ $classes }} relative overflow-hidden {{ $textClass }}"
     @if($anchor) id="{{ esc_attr($anchor) }}" @endif
     style="min-height: {{ esc_attr($min_height) }};">

    {{-- Background Image --}}
    @if($image_url)
        <div class="absolute inset-0">
            <img src="{{ $image_url }}"
                 alt=""
                 class="w-full h-full object-cover"
                 loading="lazy">
            <div class="absolute inset-0 bg-surface-overlay"
                 style="opacity: {{ esc_attr($overlay_opacity / 100) }}"></div>
        </div>
    @endif

    {{-- Content --}}
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 flex items-center justify-{{ $alignment }}"
         style="min-height: {{ esc_attr($min_height) }};">
        <div class="max-w-3xl">
            @if($title)
                <h1 class="text-display mb-6 font-headline">
                    {!! $title !!}
                </h1>
            @endif

            @if($content)
                <x-prose size="lg">{!! $content !!}</x-prose>
            @endif

            @hasfield('cta_button')
                @php $button = \WordpressStarter\Acf\Fields::link('cta_button'); @endphp
                @if($button)
                    <div class="mt-8">
                        <x-button
                            :url="$button['url']"
                            :title="$button['title']"
                            :target="$button['target']"
                            variant="primary"
                            size="lg"
                            :analytics="['event' => 'Hero_CTA_Click', 'meta' => 'hero_block']"
                        />
                    </div>
                @endif
            @endhasfield
        </div>
    </div>
</div>
