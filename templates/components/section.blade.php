{{--
    Section Component - Wrapper for content sections

    @param string $background - bg color: primary, secondary, tertiary, brand, brand-subtle, inverse
    @param string $padding - sm, md, lg, xl (default: lg)
    @param string $anchor - HTML ID for anchor links
    @param string $class - Additional CSS classes
    @param bool $container - Wrap content in container (default: true)
    @param bool|null $animate - Enable scroll animation (null = use global setting)
--}}

@props([
    'background' => 'primary',
    'padding' => 'lg',
    'anchor' => null,
    'class' => '',
    'container' => true,
    'animate' => null,
])

@php
    $backgrounds = [
        'primary' => 'bg-surface',
        'secondary' => 'bg-surface-secondary',
        'tertiary' => 'bg-surface-tertiary',
        'brand' => 'bg-surface-brand text-content-inverse',
        'brand-subtle' => 'bg-surface-brand-subtle',
        'inverse' => 'bg-surface-inverse text-content-inverse',
    ];

    $paddings = [
        'none' => '',
        'sm' => 'py-8 md:py-12',
        'md' => 'py-12 md:py-16',
        'lg' => 'py-16 md:py-20',
        'xl' => 'py-20 md:py-28',
    ];

    $bgClass = $backgrounds[$background] ?? $backgrounds['primary'];
    $paddingClass = $paddings[$padding] ?? $paddings['lg'];

    // Determine if animations should be enabled
    $globalAnimations = function_exists('get_field')
        ? get_field('animations_enabled', 'option')
        : false;
    $shouldAnimate = $animate ?? $globalAnimations;
@endphp

<section
    @if($anchor) id="{{ esc_attr($anchor) }}" @endif
    @if($shouldAnimate)
        x-data="{ shown: false }"
        x-init="if (location.hash) shown = true"
        x-on:hashchange.window="shown = true"
        x-intersect.once.threshold.10="shown = true"
        :class="{ 'is-visible': shown }"
    @endif
    class="section {{ $bgClass }} {{ $paddingClass }} {{ $class }}"
>
    @if($container)
        <div
            class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 @if($shouldAnimate) motion-reduce:opacity-100! motion-reduce:transform-none! @endif"
            @if($shouldAnimate)
                x-show="shown"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
            @endif
        >
            {{ $slot }}
        </div>
    @else
        @if($shouldAnimate)
            <div
                class="motion-reduce:opacity-100! motion-reduce:transform-none!"
                x-show="shown"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
            >
                {{ $slot }}
            </div>
        @else
            {{ $slot }}
        @endif
    @endif
</section>
