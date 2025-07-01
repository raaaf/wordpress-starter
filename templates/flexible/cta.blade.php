@php
    $title = get_sub_field('title');
    $content = get_sub_field('content');
    $button = get_sub_field('button');
    $background_color = get_sub_field('background_color') ?: '#f8f9fa';
@endphp

<div class="container mx-auto px-4">
    <div class="rounded-lg p-8 md:p-12 text-center" style="background-color: {{ $background_color }}">
        @if($title)
            <h2 class="text-3xl md:text-4xl font-bold mb-4">{{ $title }}</h2>
        @endif
        
        @if($content)
            <p class="text-lg md:text-xl mb-8 max-w-2xl mx-auto">{{ $content }}</p>
        @endif
        
        @if($button)
            <a href="{{ $button['url'] }}" 
               target="{{ $button['target'] }}"
               class="inline-block px-8 py-4 bg-brand-primary text-white rounded-lg hover:bg-opacity-90 transition-all">
                {{ $button['title'] }}
            </a>
        @endif
    </div>
</div>