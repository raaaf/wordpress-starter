{{--
    Card Component - Encapsulated content block

    @param string $variant - default, elevated, outlined (default: default)
    @param string $padding - sm, md, lg (default: md)
    @param string $class - Additional CSS classes
--}}

@props([
    'variant' => 'default',
    'padding' => 'md',
    'class' => '',
])

@php
    $variants = [
        'default' => 'bg-surface',
        'elevated' => 'bg-surface shadow-lg',
        'outlined' => 'bg-surface border border-line',
        'filled' => 'bg-surface-secondary',
    ];

    $paddings = [
        'none' => '',
        'sm' => 'p-4',
        'md' => 'p-6',
        'lg' => 'p-8',
    ];

    $variantClass = $variants[$variant] ?? $variants['default'];
    $paddingClass = $paddings[$padding] ?? $paddings['md'];
@endphp

<div class="rounded-lg {{ $variantClass }} {{ $paddingClass }} {{ $class }}">
    {{ $slot }}
</div>
