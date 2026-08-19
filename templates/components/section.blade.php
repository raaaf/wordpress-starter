{{--
    Section Component - Wrapper for content sections

    @param string $background - bg color: primary, secondary, tertiary, brand, brand-subtle, inverse
    @param string $padding - sm, md, lg, xl (default: lg)
    @param string|null $spacing - editor override for $padding: default, sm, xl, none
    @param string $anchor - HTML ID for anchor links
    @param string $class - Additional CSS classes
    @param bool $container - Wrap content in container (default: true)
    @param bool|null $animate - Enable scroll animation (null = use global setting)
--}}

@props([
    'background' => 'primary',
    'padding' => 'lg',
    'spacing' => null,
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
        'brand' => 'bg-surface-brand text-content-on-brand',
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

    // The editor field wins over the template default, but only when it says
    // something: "default" means "keep the rhythm the template designed".
    $paddingKey = ($spacing && $spacing !== 'default' && isset($paddings[$spacing])) ? $spacing : $padding;
    $paddingClass = $paddings[$paddingKey] ?? $paddings['lg'];

    // Determine if animations should be enabled
    $globalAnimations = \WordpressStarter\Acf\Fields::option('animations_enabled', false);
    $shouldAnimate = $animate ?? $globalAnimations;
@endphp

<section
    @if($anchor) id="{{ esc_attr($anchor) }}" @endif
    @if($shouldAnimate)
        x-data="{ shown: false }"
        x-init="if (location.hash) shown = true"
        x-on:hashchange.window="shown = true"
        x-intersect.once="shown = true"
        :class="{ 'is-visible': shown }"
    @endif
    class="section {{ $bgClass }} {{ $paddingClass }} {{ $class }}"
>
    @if($container)
        <div
            class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 @if($shouldAnimate) transition duration-200 ease-out motion-reduce:opacity-100! motion-reduce:transform-none! motion-reduce:transition-none! @endif"
            @if($shouldAnimate)
                {{-- Deliberately not x-show: that sets display:none until the
                     intersection observer fires, so the section collapses out of
                     the layout and everything below it jumps, twice. Measured
                     0.765 CLS on the one page whose first section is animated.
                     Toggling opacity and transform keeps the element in the
                     layout the whole time. --}}
                :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            @endif
        >
            {{ $slot }}
        </div>
    @else
        @if($shouldAnimate)
            <div
                class="transition duration-200 ease-out motion-reduce:opacity-100! motion-reduce:transform-none! motion-reduce:transition-none!"
                :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            >
                {{ $slot }}
            </div>
        @else
            {{ $slot }}
        @endif
    @endif
</section>
