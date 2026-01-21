{{--
    Card Component - Based on Figma Design System

    Simple usage (container only):
    @param string $variant - default, elevated, outlined, filled
    @param string $padding - none, sm, md, lg
    @param string $class - Additional CSS classes

    Full card usage:
    @param string $image - Image URL
    @param string $imageAlt - Image alt text
    @param string $title - Card title
    @param string $subtitle - Card subtitle
    @param string $description - Card description
    @param string $size - sm, md, lg (affects spacing and image height)
    @param bool $hoverable - Add hover effect (brand border)
    @param string $url - Make entire card clickable
    @param bool $disabled - Disabled state

    States from Figma:
    - Default: Subtle shadow
    - Hover: Brand border, enhanced shadow
    - Active: Pressed effect with inner shadow
    - Focus: Focus ring for accessibility
    - Selected: Brand border, enhanced shadow (persistent)
    - Disabled: Muted colors, no interaction
--}}

@props([
    'variant' => 'default',
    'padding' => 'md',
    'class' => '',
    'image' => null,
    'imageAlt' => '',
    'title' => null,
    'subtitle' => null,
    'description' => null,
    'size' => 'md',
    'hoverable' => false,
    'url' => null,
    'disabled' => false,
    'selected' => false,
])

@php
    // Variants from Figma
    $variants = [
        'default' => 'bg-[var(--card-bg,#ffffff)] border border-[var(--card-border,#e5e5e5)] shadow-[var(--shadow-card)]',
        'elevated' => 'bg-[var(--card-bg,#ffffff)] shadow-lg',
        'outlined' => 'bg-[var(--card-bg,#ffffff)] border border-line',
        'filled' => 'bg-surface-secondary',
    ];

    $sizes = [
        'sm' => ['padding' => 'p-4', 'imageHeight' => 'h-32', 'gap' => 'gap-3'],
        'md' => ['padding' => 'p-[var(--card-padding)]', 'imageHeight' => 'h-40', 'gap' => 'gap-[var(--card-gap)]'],
        'lg' => ['padding' => 'p-8', 'imageHeight' => 'h-48', 'gap' => 'gap-5'],
    ];

    // Legacy padding support
    $legacyPaddings = [
        'none' => '',
        'sm' => 'p-4',
        'md' => 'p-[var(--card-padding)]',
        'lg' => 'p-8',
    ];

    $sizeConfig = $sizes[$size] ?? $sizes['md'];
    $variantClass = $variants[$variant] ?? $variants['default'];

    // Use legacy padding if no structured content, else use size-based padding
    $isStructuredCard = $image || $title || $subtitle || $description;
    $paddingClass = $isStructuredCard ? '' : ($legacyPaddings[$padding] ?? $legacyPaddings['md']);

    // Interactive states (hover, active, focus)
    $isInteractive = $hoverable || $url;
    $interactiveClasses = $isInteractive && !$disabled
        ? implode(' ', [
            'transition-all duration-200 cursor-pointer',
            'hover:border-line-brand hover:shadow-[var(--shadow-card-hover)]',
            'active:shadow-[var(--shadow-inner)] active:scale-[0.99]',
            'focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring)]',
        ])
        : '';

    // Disabled state
    $disabledClasses = $disabled
        ? 'opacity-60 cursor-not-allowed'
        : '';

    // Selected state (from Figma)
    $selectedClasses = $selected && !$disabled
        ? 'border-line-brand shadow-[var(--shadow-card-hover)]'
        : '';

    $tag = $url && !$disabled ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if($url && !$disabled) href="{{ esc_url($url) }}" @endif
    @if($disabled) aria-disabled="true" @endif
    class="block rounded-[var(--card-radius)] overflow-hidden {{ $variantClass }} {{ $paddingClass }} {{ $interactiveClasses }} {{ $selectedClasses }} {{ $disabledClasses }} {{ $class }}"
>
    @if($isStructuredCard)
        {{-- Image --}}
        @if($image)
            <div class="w-full {{ $sizeConfig['imageHeight'] }} overflow-hidden rounded-[var(--card-media-radius,8px)]">
                <img src="{{ esc_url($image) }}" alt="{{ esc_attr($imageAlt) }}" class="w-full h-full object-cover" loading="lazy" />
            </div>
        @endif

        {{-- Content --}}
        <div class="{{ $sizeConfig['padding'] }} {{ $sizeConfig['gap'] }} flex flex-col">
            @if($title || $subtitle)
                <div class="space-y-1">
                    @if($title)
                        <h3 class="text-lg font-semibold text-content">{{ $title }}</h3>
                    @endif
                    @if($subtitle)
                        <p class="text-sm text-content-secondary">{{ $subtitle }}</p>
                    @endif
                </div>
            @endif

            @if($description)
                <p class="text-base text-content">{{ $description }}</p>
            @endif

            {{-- Actions slot --}}
            @if(isset($actions))
                <div class="flex items-center gap-[var(--card-footer-gap,12px)] mt-auto pt-[var(--card-footer-padding-top,16px)]">
                    {{ $actions }}
                </div>
            @endif

            {{-- Default slot for additional content --}}
            {{ $slot }}
        </div>
    @else
        {{-- Simple container mode --}}
        {{ $slot }}
    @endif
</{{ $tag }}>
