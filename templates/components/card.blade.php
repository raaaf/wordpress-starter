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
    @param bool $hoverable - Add hover effect (orange border)
    @param string $url - Make entire card clickable
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
])

@php
    $variants = [
        'default' => 'bg-surface',
        'elevated' => 'bg-surface shadow-lg',
        'outlined' => 'bg-surface border border-line',
        'filled' => 'bg-surface-secondary',
    ];

    $sizes = [
        'sm' => ['padding' => 'p-4', 'imageHeight' => 'h-32', 'gap' => 'gap-3'],
        'md' => ['padding' => 'p-5', 'imageHeight' => 'h-40', 'gap' => 'gap-4'],
        'lg' => ['padding' => 'p-6', 'imageHeight' => 'h-48', 'gap' => 'gap-5'],
    ];

    // Legacy padding support
    $legacyPaddings = [
        'none' => '',
        'sm' => 'p-4',
        'md' => 'p-6',
        'lg' => 'p-8',
    ];

    $sizeConfig = $sizes[$size] ?? $sizes['md'];
    $variantClass = $variants[$variant] ?? $variants['default'];

    // Use legacy padding if no structured content, else use size-based padding
    $isStructuredCard = $image || $title || $subtitle || $description;
    $paddingClass = $isStructuredCard ? '' : ($legacyPaddings[$padding] ?? $legacyPaddings['md']);

    $hoverClass = $hoverable ? 'transition-all duration-200 hover:border-line-accent hover:shadow-md cursor-pointer' : '';
    $tag = $url ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if($url) href="{{ esc_url($url) }}" @endif
    class="block rounded-xl overflow-hidden {{ $variantClass }} {{ $paddingClass }} {{ $hoverClass }} {{ $class }}"
>
    @if($isStructuredCard)
        {{-- Image --}}
        @if($image)
            <div class="w-full {{ $sizeConfig['imageHeight'] }} overflow-hidden">
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
                <div class="flex items-center gap-3 mt-auto pt-2">
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
