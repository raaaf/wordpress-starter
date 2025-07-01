@php
    $content_1 = $fields['content'] ?? '';
    $content_2 = $fields['content_2'] ?? '';
    $bgColor = $fields['background_color'] ?? '';
@endphp

<section class="{{ $classes }} two-columns px-6 md:px-8 {{ $bgColor ? 'bg-' . $bgColor : '' }}"
         @if($anchor) id="{{ $anchor }}" @endif>
    <div class="relative grid items-start max-w-6xl gap-4 mx-auto lg:items-center md:gap-16 lg:gap-24 md:grid-cols-2 xl:gap-32">
        <div class="text-marine prose prose-lg max-w-none">
            {!! $content_1 !!}
        </div>
        <div class="text-marine prose prose-lg max-w-none">
            {!! $content_2 !!}
        </div>
    </div>
</section>