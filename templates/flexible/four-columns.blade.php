@php
    $column_1 = get_sub_field('column_1');
    $column_2 = get_sub_field('column_2');
    $column_3 = get_sub_field('column_3');
    $column_4 = get_sub_field('column_4');
@endphp

<div class="container mx-auto px-4">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <div class="prose prose-lg max-w-none">
            {!! $column_1 !!}
        </div>
        <div class="prose prose-lg max-w-none">
            {!! $column_2 !!}
        </div>
        <div class="prose prose-lg max-w-none">
            {!! $column_3 !!}
        </div>
        <div class="prose prose-lg max-w-none">
            {!! $column_4 !!}
        </div>
    </div>
</div>