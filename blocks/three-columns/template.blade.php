@php
    $content_1 = $fields['content'] ?? '';
    $content_2 = $fields['content_2'] ?? '';
    $content_3 = $fields['content_3'] ?? '';
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

<section class="{{ $classes }} px-6 three-columns md:px-8 {{ $bgColor ? 'bg-' . $bgColor : '' }}"
         @if($anchor) id="{{ $anchor }}" @endif>
    <div class="max-w-6xl mx-auto">
        <div class="relative grid items-start gap-12 lg:gap-16 lg:grid-cols-3 max-w-none">
            <div class="prose prose-lg max-w-none">
                {!! $content_1 !!}
            </div>
            <div class="prose prose-lg max-w-none">
                {!! $content_2 !!}
            </div>
            <div class="prose prose-lg max-w-none">
                {!! $content_3 !!}
            </div>
        </div>
    </div>
</section>