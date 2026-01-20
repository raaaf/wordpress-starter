{{--
    Image - Flexible Content Layout

    Uses shared components: x-section
    Fields: image, caption, alignment
--}}

@php
    $image = get_sub_field('image');
    $caption = get_sub_field('caption');
    $alignment = get_sub_field('alignment') ?: 'center';

    $alignmentClasses = [
        'left' => 'mr-auto',
        'center' => 'mx-auto',
        'right' => 'ml-auto',
        'wide' => 'w-full max-w-screen-xl mx-auto',
        'full' => 'w-full',
    ];

    $useContainer = $alignment !== 'full';
@endphp

<x-section padding="md" :container="$useContainer">
    <figure class="{{ $alignmentClasses[$alignment] ?? 'mx-auto' }}">
        @if($image)
            <img src="{{ $image['url'] }}"
                 alt="{{ $image['alt'] }}"
                 class="w-full rounded-lg shadow-xl"
                 loading="lazy">
        @endif

        @if($caption)
            <figcaption class="mt-4 text-sm text-content-secondary text-center">
                {{ $caption }}
            </figcaption>
        @endif
    </figure>
</x-section>
