@php
    $left_image = get_sub_field('left_image');
    $left_content = get_sub_field('left_content');
    $right_image = get_sub_field('right_image');
    $right_content = get_sub_field('right_content');
@endphp

<div class="container mx-auto px-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
        <div>
            @if($left_image)
                <img src="{{ $left_image['url'] }}" 
                     alt="{{ $left_image['alt'] }}" 
                     class="w-full rounded-lg mb-4"
                     loading="lazy">
            @endif
            <div class="prose prose-lg max-w-none">
                {!! $left_content !!}
            </div>
        </div>
        <div>
            @if($right_image)
                <img src="{{ $right_image['url'] }}" 
                     alt="{{ $right_image['alt'] }}" 
                     class="w-full rounded-lg mb-4"
                     loading="lazy">
            @endif
            <div class="prose prose-lg max-w-none">
                {!! $right_content !!}
            </div>
        </div>
    </div>
</div>