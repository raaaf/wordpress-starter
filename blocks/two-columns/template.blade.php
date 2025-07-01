@php
    $content_1 = $fields['content'] ?? '';
    $content_2 = $fields['content_2'] ?? '';
    $bgColor = $fields['background_color'] ?? '';
    // Map old color names to Tailwind equivalents if needed
    $colorMap = [
        'marine' => 'blue-700',
        'primary' => 'blue-600',
        'secondary' => 'gray-600'
    ];
    if (isset($colorMap[$bgColor])) {
        $bgColor = $colorMap[$bgColor];
    }
@endphp

<section class="{{ $classes }} two-columns px-6 md:px-8 {{ $bgColor ? 'bg-' . $bgColor : '' }}"
         @if($anchor) id="{{ $anchor }}" @endif>
    <div class="relative grid items-start max-w-6xl gap-4 mx-auto lg:items-center md:gap-16 lg:gap-24 md:grid-cols-2 xl:gap-32">
        <div class="text-blue-700 prose prose-lg max-w-none">
            {!! $content_1 !!}
        </div>
        <div class="text-blue-700 prose prose-lg max-w-none">
            {!! $content_2 !!}
        </div>
    </div>
</section>