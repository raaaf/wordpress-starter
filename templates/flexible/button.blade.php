{{--
    Button Flexible Content Layout

    Uses shared component: x-button
    Fields: button (link), variant, size, full_width, alignment
--}}

@php
    $button = get_sub_field('button');
    $variant = get_sub_field('variant') ?: 'primary';
    $size = get_sub_field('size') ?: 'md';
    $fullWidth = get_sub_field('full_width') ?? false;
    $alignment = get_sub_field('alignment') ?: 'left';

    // Alignment classes
    $alignmentClasses = match($alignment) {
        'center' => 'text-center',
        'right' => 'text-right',
        'left' => 'text-left',
        default => '',
    };
@endphp

@if($button && !empty($button['url']))
    <div class="button-block {{ $alignmentClasses }}">
        <x-button
            :url="$button['url']"
            :title="$button['title'] ?: __('Mehr erfahren', 'wp-starter')"
            :target="$button['target'] ?? '_self'"
            :variant="$variant"
            :size="$size"
            :class="$fullWidth ? 'w-full' : ''"
        />
    </div>
@endif
