{{--
    Badge Component - Based on Figma Design System

    @param string $variant - gray, accent, success, warning, error (default: gray)
    @param string $style - filled, outline (default: filled)
    @param string $size - sm, md, lg (default: md)
    @param bool $dot - Show status dot
    @param string $iconLeft - Icon name for left side
    @param string $iconRight - Icon name for right side
    @param string $class - Additional CSS classes
--}}

@props([
    'variant' => 'gray',
    'style' => 'filled',
    'size' => 'md',
    'dot' => false,
    'iconLeft' => null,
    'iconRight' => null,
    'class' => '',
])

@php
    $sizes = [
        'sm' => 'px-2 py-0.5 text-xs gap-1',
        'md' => 'px-2.5 py-1 text-sm gap-1.5',
        'lg' => 'px-3 py-1.5 text-base gap-2',
    ];

    $iconSizes = [
        'sm' => 'w-3 h-3',
        'md' => 'w-3.5 h-3.5',
        'lg' => 'w-4 h-4',
    ];

    $dotSizes = [
        'sm' => 'w-1.5 h-1.5',
        'md' => 'w-2 h-2',
        'lg' => 'w-2.5 h-2.5',
    ];

    // Filled variants
    $filledVariants = [
        'gray' => 'bg-surface-tertiary text-content',
        'accent' => 'bg-surface-accent text-content-on-color',
        'success' => 'bg-surface-success-strong text-content-on-color',
        'warning' => 'bg-surface-warning-strong text-content-on-color',
        'error' => 'bg-surface-error-strong text-content-on-color',
    ];

    // Outline variants
    $outlineVariants = [
        'gray' => 'bg-transparent text-content border border-line',
        'accent' => 'bg-surface-accent-subtle text-content-accent border border-line-accent',
        'success' => 'bg-surface-success text-content-success border border-line-success',
        'warning' => 'bg-surface-warning text-content-warning border border-line-warning',
        'error' => 'bg-surface-error text-content-error border border-line-error',
    ];

    // Dot colors - use semantic tokens for theme compatibility
    $dotColors = [
        'gray' => 'bg-content-secondary',
        'accent' => $style === 'filled' ? 'bg-surface-on-color' : 'bg-content-accent',
        'success' => $style === 'filled' ? 'bg-surface-on-color' : 'bg-content-success',
        'warning' => $style === 'filled' ? 'bg-surface-on-color' : 'bg-content-warning',
        'error' => $style === 'filled' ? 'bg-surface-on-color' : 'bg-content-error',
    ];

    $variants = $style === 'outline' ? $outlineVariants : $filledVariants;
    $variantClass = $variants[$variant] ?? $variants['gray'];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $iconSize = $iconSizes[$size] ?? $iconSizes['md'];
    $dotSize = $dotSizes[$size] ?? $dotSizes['md'];
    $dotColor = $dotColors[$variant] ?? $dotColors['gray'];
@endphp

<span class="inline-flex items-center font-medium rounded-full {{ $variantClass }} {{ $sizeClass }} {{ $class }}">
    @if($dot)
        <span class="rounded-full {{ $dotSize }} {{ $dotColor }}"></span>
    @endif

    @if($iconLeft)
        <x-icon name="{{ $iconLeft }}" class="{{ $iconSize }}" />
    @endif

    {{ $slot }}

    @if($iconRight)
        <x-icon name="{{ $iconRight }}" class="{{ $iconSize }}" />
    @endif
</span>
