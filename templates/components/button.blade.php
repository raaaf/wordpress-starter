{{--
    Button Component

    @param string $url - Link URL
    @param string $title - Button text
    @param string $target - Link target (_blank, _self)
    @param string $variant - primary, secondary, accent (default: primary)
    @param string $size - sm, md, lg (default: md)
    @param string $class - Additional CSS classes
    @param array $analytics - ['event' => 'name', 'meta' => 'value'] for Pirsch
--}}

@props([
    'url' => '#',
    'title' => 'Click here',
    'target' => '_self',
    'variant' => 'primary',
    'size' => 'md',
    'class' => '',
    'analytics' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-2 font-semibold rounded-md transition-all duration-200 no-underline';

    $variants = [
        'primary' => 'bg-surface-accent text-content-on-color hover:bg-surface-accent-hover border border-line-accent',
        'secondary' => 'bg-surface-brand-secondary text-content-on-color hover:bg-surface-inverse border border-line-brand',
        'outline' => 'bg-transparent text-content-accent hover:bg-surface-accent-subtle border-2 border-line-accent',
        'ghost' => 'bg-transparent text-content hover:bg-surface-tertiary',
        'warning' => 'bg-surface-warning text-content hover:bg-surface-warning border border-line-warning',
    ];

    $sizes = [
        'sm' => 'px-4 py-2 text-sm',
        'md' => 'px-6 py-2.5 text-base',
        'lg' => 'px-8 py-3 text-lg',
    ];

    $variantClass = $variants[$variant] ?? $variants['primary'];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<a href="{{ $url }}"
   target="{{ $target }}"
   @if($analytics)
       pirsch-event="{{ $analytics['event'] ?? 'button_click' }}"
       @if(isset($analytics['meta']))
           pirsch-meta-key="{{ $analytics['meta'] }}"
       @endif
   @endif
   class="{{ $baseClasses }} {{ $variantClass }} {{ $sizeClass }} {{ $class }}">
    {{ $title }}
    {{ $slot ?? '' }}
</a>
