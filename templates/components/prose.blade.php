{{--
    Prose Component - Typography wrapper for WYSIWYG content

    @param string $size - sm, base, lg (default: lg)
    @param string $class - Additional CSS classes
    @param bool $inherit - Inherit text color from parent (default: false)
--}}

@props([
    'size' => 'lg',
    'class' => '',
    'inherit' => false,
])

@php
    $sizes = [
        'sm' => 'prose-sm',
        'base' => 'prose',
        'lg' => 'prose-lg',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['lg'];
    // prose-inherit-colors is a custom class that makes prose inherit text colors
    $inheritClass = $inherit ? 'prose-inherit-colors' : '';
@endphp

<div class="prose {{ $sizeClass }} max-w-none {{ $inheritClass }} {{ $class }}">
    {{ $slot }}
</div>
