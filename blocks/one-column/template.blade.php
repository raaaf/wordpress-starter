@php
    $label = $fields['label'] ?? '';
    $trimmed = str_replace(' ', '', $label);
    $content = $fields['content'] ?? '';
    $bgColor = $fields['background_color'] ?? '';
@endphp

<section class="{{ $classes }} px-6 one-column md:px-8 text-marine {{ $bgColor ? 'bg-' . $bgColor : '' }}"
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