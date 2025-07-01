@php
    $left_column = get_sub_field('left_column');
    $center_column = get_sub_field('center_column');
    $right_column = get_sub_field('right_column');
@endphp

<div class="container mx-auto px-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-12">
        <div class="prose prose-lg max-w-none">
            {!! $left_column !!}
        </div>
        <div class="prose prose-lg max-w-none">
            {!! $center_column !!}
        </div>
        <div class="prose prose-lg max-w-none">
            {!! $right_column !!}
        </div>
    </div>
</div>