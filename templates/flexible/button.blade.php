{{--
    Button Flexible Content Layout

    Uses shared components: x-section, x-button
    Fields: button (link), variant, size, full_width, alignment
--}}

@php
    $button = get_sub_field('button');
    $variant = get_sub_field('variant') ?: 'primary';
    $size = get_sub_field('size') ?: 'md';
    $fullWidth = get_sub_field('full_width') ?? false;
    $alignment = get_sub_field('alignment') ?: 'left';
    $background = get_sub_field('background_color') ?: 'primary';

    // Alignment classes
    $alignmentClasses = match($alignment) {
        'center' => 'text-center',
        'right' => 'text-right',
        'left' => 'text-left',
        default => '',
    };
@endphp

@if($button && !empty($button['url']))
    <x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :background="$background" padding="sm" class="button-block-section">
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
    </x-section>
@endif
