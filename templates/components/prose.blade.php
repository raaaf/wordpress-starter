{{--
    Prose Component - Typography wrapper for WYSIWYG content

    @param string $size - sm, base, lg (default: lg)
    @param string $class - Additional CSS classes
--}}

@props([
    'size' => 'lg',
    'class' => '',
])

@php
    $sizes = [
        'sm' => 'prose-sm',
        'base' => 'prose',
        'lg' => 'prose-lg',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['lg'];
@endphp

<div class="prose {{ $sizeClass }} max-w-none {{ $class }}">
    {{ $slot }}
</div>
