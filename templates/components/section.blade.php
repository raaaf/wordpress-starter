{{--
    Section Component - Wrapper for content sections

    @param string $background - bg color: primary, secondary, tertiary, brand, brand-subtle, inverse
    @param string $padding - sm, md, lg, xl (default: lg)
    @param string $anchor - HTML ID for anchor links
    @param string $class - Additional CSS classes
    @param bool $container - Wrap content in container (default: true)
--}}

@props([
    'background' => 'primary',
    'padding' => 'lg',
    'anchor' => null,
    'class' => '',
    'container' => true,
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
@endphp

<section @if($anchor) id="{{ $anchor }}" @endif
         class="{{ $bgClass }} {{ $paddingClass }} {{ $class }}">
    @if($container)
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</section>
