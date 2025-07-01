@php
    // Adapt flexible content data to block template format
    $fields = [
        'title' => get_sub_field('title'),
        'subtitle' => get_sub_field('subtitle'),
        'content' => get_sub_field('content'),
        'background_image' => get_sub_field('background_image'),
        'cta' => get_sub_field('cta'),
    ];
    
    // Set default block variables
    $classes = 'block-hero';
    $anchor = '';
    $is_preview = false;
@endphp

<div class="container mx-auto px-4">
    @include('blocks.hero.template', compact('fields', 'classes', 'anchor', 'is_preview'))
</div>