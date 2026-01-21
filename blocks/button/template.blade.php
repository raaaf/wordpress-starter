{{--
    Button Block

    Uses shared component: x-button
    Fields: button (link), variant, size, full_width
--}}

@php
    $button = $fields['button'] ?? null;
    $variant = $fields['variant'] ?? 'primary';
    $size = $fields['size'] ?? 'md';
    $fullWidth = $fields['full_width'] ?? false;

    // Alignment classes
    $alignmentClasses = match($block['align'] ?? '') {
        'center' => 'text-center',
        'right' => 'text-right',
        'left' => 'text-left',
        default => '',
    };
@endphp

@if($button && !empty($button['url']))
    <div {!! $wrapper_attributes !!}
         class="button-block {{ $classes }} {{ $alignmentClasses }}"
         @if($anchor && !$wrapper_attributes) id="{{ esc_attr($anchor) }}" @endif>
        <x-button
            :url="$button['url']"
            :title="$button['title'] ?: 'Mehr erfahren'"
            :target="$button['target'] ?? '_self'"
            :variant="$variant"
            :size="$size"
            :class="$fullWidth ? 'w-full' : ''"
        />
    </div>
@elseif($is_preview)
    <div class="button-block {{ $classes }} {{ $alignmentClasses }}">
        <x-button
            title="Button Vorschau"
            :variant="$variant"
            :size="$size"
            :class="$fullWidth ? 'w-full' : ''"
        />
        <p class="text-sm text-content-secondary mt-2">Bitte einen Link im Seitenleisten-Panel hinzufügen.</p>
    </div>
@endif
