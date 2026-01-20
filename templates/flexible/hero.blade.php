{{--
    Hero - Flexible Content Layout

    Uses shared components: x-section, x-prose, x-button
    Fields: title, subtitle, content, background_image, cta
--}}

@php
    $title = get_sub_field('title');
    $subtitle = get_sub_field('subtitle');
    $content = get_sub_field('content');
    $background_image = get_sub_field('background_image');
    $cta = get_sub_field('cta');

    $image_url = $background_image ? ($background_image['url'] ?? '') : '';
@endphp

<div class="relative overflow-hidden text-text-inverse" style="min-height: 500px;">
    {{-- Background Image --}}
    @if($image_url)
        <div class="absolute inset-0">
            <img src="{{ $image_url }}"
                 alt=""
                 class="w-full h-full object-cover"
                 loading="lazy">
            <div class="absolute inset-0 bg-bg-overlay" style="opacity: 0.5"></div>
        </div>
    @endif

    {{-- Content --}}
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 flex items-center justify-center" style="min-height: 500px;">
        <div class="max-w-3xl text-center">
            @if($subtitle)
                <p class="text-sm font-semibold uppercase tracking-wider mb-4">{{ $subtitle }}</p>
            @endif

            @if($title)
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 font-headline">
                    {!! $title !!}
                </h1>
            @endif

            @if($content)
                <x-prose size="lg">{!! $content !!}</x-prose>
            @endif

            @if($cta)
                <div class="mt-8">
                    <x-button
                        :url="$cta['url']"
                        :title="$cta['title']"
                        :target="$cta['target'] ?? '_self'"
                        variant="primary"
                        size="lg"
                    />
                </div>
            @endif
        </div>
    </div>
</div>