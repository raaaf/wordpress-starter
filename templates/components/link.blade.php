{{--
    Link Component - Based on Figma Design System

    @param string $url - Link URL
    @param string $target - Link target (_blank, _self)
    @param string $variant - accent, dark (default: accent)
    @param string $size - sm, md, lg (default: md)
    @param string $iconLeft - Icon name for left side
    @param string $iconRight - Icon name for right side
    @param bool $disabled - Disabled state
    @param string $class - Additional CSS classes

    States from Figma:
    - Default: Underlined link with accent/dark color
    - Hover: Color shifts to hover variant
    - Visited: Tertiary text color
    - Disabled: Muted color, no interaction
--}}

@props([
    'url' => '#',
    'target' => '_self',
    'variant' => 'accent',
    'size' => 'md',
    'iconLeft' => null,
    'iconRight' => null,
    'disabled' => false,
    'class' => '',
    'ariaLabel' => null,
])

@php
    $sizes = [
        'sm' => 'text-sm gap-1',
        'md' => 'text-base gap-1.5',
        'lg' => 'text-lg gap-2',
    ];

    $iconSizes = [
        'sm' => 'w-3.5 h-3.5',
        'md' => 'w-4 h-4',
        'lg' => 'w-5 h-5',
    ];

    $variants = [
        'accent' => 'text-content-link hover:text-content-link-hover visited:text-content-tertiary',
        'dark' => 'text-content hover:text-content-secondary visited:text-content-tertiary',
    ];

    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $iconSize = $iconSizes[$size] ?? $iconSizes['md'];
    $variantClass = $disabled
        ? 'text-content-disabled cursor-not-allowed'
        : 'cursor-pointer ' . ($variants[$variant] ?? $variants['accent']);
@endphp

<a href="{{ $disabled ? '#' : esc_url($url) }}"
   target="{{ esc_attr($target) }}"
   @if($target === '_blank' && !$disabled) rel="noopener noreferrer" @endif
   @if($disabled) aria-disabled="true" tabindex="-1" @endif
   @if($ariaLabel) aria-label="{{ esc_attr($ariaLabel) }}" @endif
   class="link inline-flex items-center font-medium underline underline-offset-4 transition-colors duration-200 focus-visible:shadow-[var(--shadow-focus-ring-ghost)] focus-visible:outline-none {{ $variantClass }} {{ $sizeClass }} {{ $class }}">
    @if($iconLeft)
        <x-icon name="{{ $iconLeft }}" class="{{ $iconSize }}" />
    @endif

    {{ $slot }}

    @if($iconRight)
        <x-icon name="{{ $iconRight }}" class="{{ $iconSize }}" />
    @endif
</a>
