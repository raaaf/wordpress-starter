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
    @param array $analytics - ['event' => 'name', 'meta' => 'value'] for Pirsch

    States from Figma:
    - Default: Base appearance
    - Hover: Slightly darker background
    - Active: Even darker background (pressed)
    - Focus: Orange focus ring
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
    $baseClasses = 'inline-flex items-center justify-center gap-2 font-semibold rounded-lg transition-all duration-200 no-underline cursor-pointer select-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-line-focus focus-visible:ring-offset-2';

    // Variants matching Figma design: Default → Hover → Active states
    $variants = [
        'primary' => 'bg-surface-accent text-content-on-color hover:bg-surface-accent-hover active:bg-accent-700 border border-transparent',
        'secondary' => 'bg-surface text-content hover:bg-surface-secondary active:bg-surface-tertiary border border-line',
        'ghost' => 'bg-transparent text-content hover:bg-surface-secondary active:bg-surface-tertiary border border-transparent',
        'danger' => 'bg-surface-error-strong text-content-on-color hover:bg-error-dark active:bg-red-900 border border-transparent',
    ];

    // Disabled state overrides
    $disabledClasses = 'bg-surface-disabled text-content-disabled border-line-disabled cursor-not-allowed hover:bg-surface-disabled active:bg-surface-disabled';

    // Sizes matching Figma: sm, md, lg
    $sizes = [
        'sm' => 'px-3 py-1.5 text-sm min-h-8',
        'md' => 'px-4 py-2 text-base min-h-10',
        'lg' => 'px-6 py-3 text-lg min-h-12',
    ];

    $variantClass = $disabled ? $disabledClasses : ($variants[$variant] ?? $variants['primary']);
    $sizeClass = $sizes[$size] ?? $sizes['md'];

    // Analytics attributes
    $analyticsAttrs = '';
    if ($analytics && !$disabled) {
        $analyticsAttrs = 'pirsch-event="' . esc_attr($analytics['event'] ?? 'button_click') . '"';
        if (isset($analytics['meta'])) {
            $analyticsAttrs .= ' pirsch-meta-key="' . esc_attr($analytics['meta']) . '"';
        }
    }
@endphp

@if($url)
    {{-- Link button --}}
    <a href="{{ $disabled ? '#' : esc_url($url) }}"
       target="{{ esc_attr($target) }}"
       @if($target === '_blank' && !$disabled) rel="noopener noreferrer" @endif
       @if($disabled) aria-disabled="true" tabindex="-1" @endif
       {!! $analyticsAttrs !!}
       class="{{ $baseClasses }} {{ $variantClass }} {{ $sizeClass }} {{ $class }}">
        {{ $title }}
        {{ $slot ?? '' }}
    </a>
@else
    {{-- Form button --}}
    <button type="{{ $type }}"
            @if($disabled) disabled aria-disabled="true" @endif
            {!! $analyticsAttrs !!}
            class="{{ $baseClasses }} {{ $variantClass }} {{ $sizeClass }} {{ $class }}">
        {{ $title }}
        {{ $slot ?? '' }}
    </button>
@endif
