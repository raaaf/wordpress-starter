{{--
    Button Flexible Content Layout

    Uses shared components: x-section, x-button
    Fields: button (link), button_secondary (link), variant, size, full_width, alignment
--}}

@php
    $button = get_sub_field('button');
    $buttonSecondary = get_sub_field('button_secondary');
    $variant = get_sub_field('variant') ?: 'primary';
    $size = get_sub_field('size') ?: 'md';
    $fullWidth = get_sub_field('full_width') ?? false;
    $alignment = get_sub_field('alignment') ?: 'left';
    $background = get_sub_field('background_color') ?: 'primary';

    // Flex statt text-align: mit zwei Buttons muss der Abstand dazwischen aus
    // dem Container kommen, und text-align greift auf inline-flex-Buttons nicht
    // mehr, sobald sie in einer Reihe stehen.
    $alignmentClasses = match($alignment) {
        'center' => 'justify-center',
        'right' => 'justify-end',
        default => 'justify-start',
    };
@endphp

@if($button && !empty($button['url']))
    <x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" padding="sm" class="button-block-section">
        <div class="button-block flex gap-4 {{ $fullWidth ? 'flex-col' : 'flex-wrap items-center ' . $alignmentClasses }}">
            <x-button
                :url="$button['url']"
                :title="$button['title'] ?: __('Mehr erfahren', 'wp-starter')"
                :target="$button['target'] ?? '_self'"
                :variant="$variant"
                :size="$size"
                :class="$fullWidth ? 'w-full' : ''"
            />

            @if($buttonSecondary && !empty($buttonSecondary['url']))
                <x-button
                    :url="$buttonSecondary['url']"
                    :title="$buttonSecondary['title'] ?: __('Mehr erfahren', 'wp-starter')"
                    :target="$buttonSecondary['target'] ?? '_self'"
                    variant="secondary"
                    :size="$size"
                    :class="$fullWidth ? 'w-full' : ''"
                />
            @endif
        </div>
    </x-section>
@endif
