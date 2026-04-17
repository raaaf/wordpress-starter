{{--
    Button Component - Based on Figma Design System

    @param string $url - Link URL (renders <a>)
    @param string $title - Button text
    @param string $target - Link target (_blank, _self)
    @param string $variant - primary, secondary, ghost, danger (default: primary)
    @param string $size - sm, md, lg (default: md)
    @param string $class - Additional CSS classes
    @param bool $disabled - Disabled state
    @param string $type - Button type for <button> element (submit, button, reset)
    @param array $analytics - ['event' => 'name', 'meta' => 'value'] for Rybbit

    States from Figma:
    - Default: Gradient background with shadow
    - Hover: Darker gradient, enhanced shadow
    - Active: Darkest gradient with inner shadow
    - Focus: Focus ring using accent-alpha-50
    - Disabled: Greyed out, no interaction
--}}

@props([
    'url' => null,
    'title' => 'Click here',
    'target' => '_self',
    'variant' => 'primary',
    'size' => 'md',
    'class' => '',
    'disabled' => false,
    'type' => 'button',
    'analytics' => null,
])

@php
    // Base classes - common to all buttons
    // 'button' class is used for editor CSS overrides (prevents WordPress link styling)
    $baseClasses = 'button inline-flex items-center justify-center font-semibold transition-[color,background,border-color,box-shadow,transform] duration-200 no-underline cursor-pointer select-none focus-visible:outline-none active:scale-[0.98]';

    // Variants matching Figma design with gradients and shadows
    $variants = [
        'primary' => implode(' ', [
            'bg-gradient-to-b from-[var(--gradient-primary-start)] to-[var(--gradient-primary-end)]',
            'text-content-inverse',
            'border border-line',
            'shadow-[var(--shadow-button)]',
            'hover:from-[var(--gradient-primary-hover-start)] hover:to-[var(--gradient-primary-hover-end)]',
            'hover:shadow-[var(--shadow-button-hover)]',
            'active:from-[var(--gradient-primary-active-start)] active:to-[var(--gradient-primary-active-end)]',
            'active:shadow-[var(--shadow-inner)]',
            'focus-visible:shadow-[var(--shadow-focus-ring)]',
        ]),
        'secondary' => implode(' ', [
            'bg-surface-secondary',
            'text-content',
            'border border-line',
            'shadow-[var(--shadow-button)]',
            'hover:border-line-strong',
            'hover:shadow-[var(--shadow-button-hover)]',
            'active:bg-surface-tertiary',
            'active:shadow-[var(--shadow-inner)]',
            'focus-visible:shadow-[var(--shadow-focus-ring)]',
        ]),
        'ghost' => implode(' ', [
            'bg-transparent',
            'text-content',
            'border border-transparent',
            'hover:bg-surface-tertiary',
            'active:bg-surface-secondary',
            'active:border-line',
            'focus-visible:shadow-[var(--shadow-focus-ring-ghost)]',
        ]),
        'danger' => implode(' ', [
            'bg-surface-error-strong',
            'text-content-on-color',
            'border border-transparent',
            'shadow-[var(--shadow-button)]',
            'hover:bg-error-dark',
            'hover:shadow-[var(--shadow-button-hover)]',
            'active:shadow-[var(--shadow-inner)]',
            'focus-visible:shadow-[var(--shadow-focus-ring)]',
        ]),
        'inverse' => implode(' ', [
            'bg-surface',
            'text-content-brand',
            'border border-line',
            'shadow-[var(--shadow-button)]',
            'hover:bg-surface-secondary',
            'hover:shadow-[var(--shadow-button-hover)]',
            'active:bg-surface-tertiary',
            'active:shadow-[var(--shadow-inner)]',
            'focus-visible:shadow-[var(--shadow-focus-ring)]',
        ]),
    ];

    // Disabled state overrides (same for all variants)
    $disabledClasses = 'bg-surface-disabled text-content-disabled border border-line-disabled cursor-not-allowed shadow-none hover:bg-surface-disabled hover:shadow-none active:bg-surface-disabled';

    // Sizes matching Figma with CSS variables
    $sizes = [
        'sm' => 'px-[var(--button-sm-padding-x)] py-[var(--button-sm-padding-y)] text-xs min-h-[var(--button-sm-min-height)] gap-[var(--button-sm-gap)] rounded-[var(--button-sm-radius)]',
        'md' => 'px-[var(--button-md-padding-x)] py-[var(--button-md-padding-y)] text-sm min-h-[var(--button-md-min-height)] gap-[var(--button-md-gap)] rounded-[var(--button-md-radius)]',
        'lg' => 'px-[var(--button-lg-padding-x)] py-[var(--button-lg-padding-y)] text-base min-h-[var(--button-lg-min-height)] gap-[var(--button-lg-gap)] rounded-[var(--button-lg-radius)]',
    ];

    $variantClass = $disabled ? $disabledClasses : ($variants[$variant] ?? $variants['primary']);
    $sizeClass = $sizes[$size] ?? $sizes['md'];

    // Analytics attributes
    $analyticsAttrs = '';
    if ($analytics && !$disabled) {
        $analyticsAttrs = 'data-rybbit-event="' . esc_attr($analytics['event'] ?? 'button_click') . '"';
        if (isset($analytics['meta'])) {
            $analyticsAttrs .= ' data-rybbit-prop-key="' . esc_attr($analytics['meta']) . '"';
        }
    }
@endphp

@if($url)
    {{-- Link button --}}
    <a href="{{ $disabled ? '#' : esc_url($url) }}"
       target="{{ esc_attr($target) }}"
       @if($target === '_blank' && !$disabled) rel="noopener noreferrer" @endif
       @if($disabled) aria-disabled="true" tabindex="-1" role="link" onclick="event.preventDefault(); return false;" @endif
       {!! $analyticsAttrs !!}
       {{ $attributes->merge(['class' => "{$baseClasses} {$variantClass} {$sizeClass} {$class}"]) }}>
        {{ $title }}
        {{ $slot ?? '' }}
    </a>
@else
    {{-- Form button --}}
    <button type="{{ $type }}"
            @if($disabled) disabled aria-disabled="true" @endif
            {!! $analyticsAttrs !!}
            {{ $attributes->merge(['class' => "{$baseClasses} {$variantClass} {$sizeClass} {$class}"]) }}>
        {{ $title }}
        {{ $slot ?? '' }}
    </button>
@endif
