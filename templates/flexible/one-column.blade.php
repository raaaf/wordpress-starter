@php
    $content = get_sub_field('content');
@endphp

<div class="container mx-auto px-4">
    <div class="prose prose-lg max-w-none">
        {!! $content !!}
    </div>
</div>