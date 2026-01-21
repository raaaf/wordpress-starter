{{--
    Badge Component - Based on Figma Design System

    @param string $variant - gray, brand, success, warning, error (default: gray)
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
    // Sizes from Figma tokens - subtle border radius per Figma design
    $sizes = [
        'sm' => 'px-[var(--badge-sm-padding-x)] py-[var(--badge-sm-padding-y)] text-xs gap-[var(--badge-sm-gap)] rounded-md',
        'md' => 'px-[var(--badge-md-padding-x)] py-[var(--badge-md-padding-y)] text-sm gap-[var(--badge-md-gap)] rounded-md',
        'lg' => 'px-[var(--badge-lg-padding-x)] py-[var(--badge-lg-padding-y)] text-base gap-[var(--badge-lg-gap)] rounded-lg',
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

    // Filled variants (renamed accent to brand for Figma consistency)
    $filledVariants = [
        'gray' => 'bg-surface-tertiary text-content',
        'brand' => 'bg-surface-accent text-content-on-color',
        'accent' => 'bg-surface-accent text-content-on-color', // Alias for backwards compatibility
        'success' => 'bg-surface-success-strong text-content-on-color',
        'warning' => 'bg-surface-warning-strong text-content-on-color',
        'error' => 'bg-surface-error-strong text-content-on-color',
    ];

    // Outline variants
    $outlineVariants = [
        'gray' => 'bg-transparent text-content border border-line',
        'brand' => 'bg-surface-accent-subtle text-content-accent border border-line-accent',
        'accent' => 'bg-surface-accent-subtle text-content-accent border border-line-accent', // Alias
        'success' => 'bg-surface-success text-content-success border border-line-success',
        'warning' => 'bg-surface-warning text-content-warning border border-line-warning',
        'error' => 'bg-surface-error text-content-error border border-line-error',
    ];

    // Dot colors - use semantic tokens for theme compatibility
    $dotColors = [
        'gray' => 'bg-content-secondary',
        'brand' => $style === 'filled' ? 'bg-surface-on-color' : 'bg-content-accent',
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

<span class="inline-flex w-fit items-center font-medium {{ $variantClass }} {{ $sizeClass }} {{ $class }}">
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
