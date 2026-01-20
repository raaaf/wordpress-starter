{{--
    One Column - Flexible Content Layout

    Uses shared components: x-section, x-prose
    Fields: content
--}}

@php
    $content = get_sub_field('content');
@endphp

<x-section>
    <div class="max-w-2xl mx-auto">
        <x-prose>{!! $content !!}</x-prose>
    </div>
</x-section>
