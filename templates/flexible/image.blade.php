@php
    $image = get_sub_field('image');
    $caption = get_sub_field('caption');
    $alignment = get_sub_field('alignment') ?: 'center';
    
    $alignment_classes = [
        'left' => 'mr-auto',
        'center' => 'mx-auto',
        'right' => 'ml-auto',
        'wide' => 'w-full max-w-screen-xl mx-auto',
        'full' => 'w-full',
    ];
    
    $container_class = $alignment === 'full' ? '' : 'container mx-auto px-4';
@endphp

<div class="{{ $container_class }}">
    <figure class="{{ $alignment_classes[$alignment] ?? 'mx-auto' }}">
        @if($image)
            <img src="{{ $image['url'] }}" 
                 alt="{{ $image['alt'] }}" 
                 class="w-full rounded-lg"
                 loading="lazy">
        @endif
        
        @if($caption)
            <figcaption class="mt-2 text-sm text-gray-600 text-center">
                {{ $caption }}
            </figcaption>
        @endif
    </figure>
</div>