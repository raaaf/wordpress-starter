@php
    $label = $fields['label'] ?? '';
    $trimmed = str_replace(' ', '', $label);
    $content = $fields['content'] ?? '';
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

<section class="{{ $classes }} px-6 one-column md:px-8 text-blue-700 {{ $bgColor ? 'bg-' . $bgColor : '' }}"
         @if($anchor) id="{{ $anchor }}" @endif>
    <div class="max-w-2xl mx-auto" @if($trimmed) id="{{ $trimmed }}" @endif>
        @if($label)
            <p class="text-sm font-semibold uppercase tracking-wider mb-4">{{ $label }}</p>
        @endif
        <div class="prose prose-lg max-w-none">
            {!! $content !!}
        </div>
    </div>
</section>