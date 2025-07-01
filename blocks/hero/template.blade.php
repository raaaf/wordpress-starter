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
@endphp

<div class="{{ $classes }} relative overflow-hidden" 
     @if($anchor) id="{{ $anchor }}" @endif
     style="min-height: {{ $min_height }};">
    
    {{-- Background Image --}}
    @if($image_url)
        <div class="absolute inset-0">
            <img src="{{ $image_url }}" 
                 alt="" 
                 class="w-full h-full object-cover"
                 loading="lazy">
            <div class="absolute inset-0 bg-black" 
                 style="opacity: {{ $overlay_opacity / 100 }}"></div>
        </div>
    @endif
    
    {{-- Content --}}
    <div class="relative z-10 container mx-auto px-4 py-20 flex items-center justify-{{ $alignment }}" 
         style="min-height: {{ $min_height }};">
        <div class="max-w-3xl text-{{ $text_color }}">
            @if($title)
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6">
                    {!! $title !!}
                </h1>
            @endif
            
            @if($content)
                <div class="text-lg md:text-xl prose prose-lg prose-{{ $text_color }} max-w-none">
                    {!! $content !!}
                </div>
            @endif
            
            @hasfield('cta_button')
                @php $button = \WordpressStarter\Acf\Fields::link('cta_button'); @endphp
                @if($button)
                    <div class="mt-8">
                        <a href="{{ $button['url'] }}" 
                           target="{{ $button['target'] }}"
                           class="inline-block px-8 py-4 bg-brand-primary text-white rounded-lg hover:bg-opacity-90 transition-all"
                           pirsch-event="Hero_CTA_Click"
                           pirsch-meta-key="hero_block"
                           pirsch-meta-button-text="{{ $button['title'] }}">
                            {{ $button['title'] }}
                        </a>
                    </div>
                @endif
            @endhasfield
        </div>
    </div>
</div>