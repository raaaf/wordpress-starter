{{--
    Logo Slider - Flexible Content Layout

    Uses shared components: x-section
    CSS-based infinite scrolling animation
    Fields: title, logos (repeater: logo, link, name), autoplay, background_color
--}}

@php
    $title = str_replace('[br]', '<br>', get_sub_field('title') ?: '');
    $logos = get_sub_field('logos');
    $autoplay = get_sub_field('autoplay') ?? true;
    $background = get_sub_field('background_color') ?: 'primary';
    $uniqueId = 'logo-slider-' . uniqid();

    $gradientColors = [
        'primary'      => 'from-surface',
        'secondary'    => 'from-surface-secondary',
        'tertiary'     => 'from-surface-tertiary',
        'brand'        => 'from-surface-brand',
        'brand-subtle' => 'from-surface-brand-subtle',
        'inverse'      => 'from-surface-inverse',
    ];
    $gradientFrom = $gradientColors[$background] ?? 'from-surface';

    // Prepare logo data
    $logoData = [];
    if ($logos) {
        foreach ($logos as $logo) {
            $logoId = $logo['logo'] ?? null;
            if (is_array($logoId)) {
                $logoId = $logoId['ID'] ?? $logoId['id'] ?? null;
            }
            $logoUrl = $logoId ? wp_get_attachment_image_url($logoId, 'logo') : '';
            if ($logoUrl) {
                $logoData[] = [
                    'url' => $logoUrl,
                    'link' => $logo['link'] ?? '',
                    'name' => $logo['name'] ?? ''
                ];
            }
        }
    }
@endphp

<x-section :background="$background" padding="md" class="logo-slider">
    @if($title)
        <h2 class="text-h3 mb-8 text-center text-content">{!! $title !!}</h2>
    @endif

    @if(!empty($logoData))
        <div
            id="{{ $uniqueId }}"
            class="relative overflow-hidden"
            @if($autoplay)
                x-data="{ paused: false }"
                @mouseenter="paused = true"
                @mouseleave="paused = false"
                @focusin="paused = true"
                @focusout="paused = false"
            @endif
            role="region"
            aria-label="{{ __('Partner-Logos Karussell', 'wp-starter') }}"
        >
            {{-- Gradient overlays for seamless edges --}}
            <div class="absolute left-0 top-0 bottom-0 w-16 bg-gradient-to-r {{ $gradientFrom }} to-transparent z-10 pointer-events-none"></div>
            <div class="absolute right-0 top-0 bottom-0 w-16 bg-gradient-to-l {{ $gradientFrom }} to-transparent z-10 pointer-events-none"></div>

            <div
                class="flex gap-12 {{ $autoplay ? 'logo-scroll' : '' }}"
                @if($autoplay) :class="{ 'animation-paused': paused }" @endif
            >
                {{-- First set of logos --}}
                @foreach($logoData as $logo)
                    <div class="flex-shrink-0 w-32 flex items-center justify-center">
                        @if($logo['link'])
                            <a
                                href="{{ $logo['link'] }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block transition-all duration-300 opacity-50 hover:opacity-100 grayscale hover:grayscale-0"
                                @if($logo['name']) title="{{ $logo['name'] }}" @endif
                            >
                                <img
                                    src="{{ $logo['url'] }}"
                                    alt="{{ $logo['name'] }}"
                                    class="object-contain w-full h-12 dark:invert"
                                    loading="lazy"
                                >
                            </a>
                        @else
                            <div class="transition-all duration-300 opacity-50 hover:opacity-100 grayscale hover:grayscale-0">
                                <img
                                    src="{{ $logo['url'] }}"
                                    alt="{{ $logo['name'] }}"
                                    class="object-contain w-full h-12 dark:invert"
                                    loading="lazy"
                                >
                            </div>
                        @endif
                    </div>
                @endforeach

                {{-- Duplicate set for infinite scroll effect --}}
                @if($autoplay)
                    @foreach($logoData as $logo)
                        <div class="flex-shrink-0 w-32 flex items-center justify-center">
                            @if($logo['link'])
                                <a
                                    href="{{ $logo['link'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="block transition-all duration-300 opacity-50 hover:opacity-100 grayscale hover:grayscale-0"
                                    @if($logo['name']) title="{{ $logo['name'] }}" @endif
                                >
                                    <img
                                        src="{{ $logo['url'] }}"
                                        alt="{{ $logo['name'] }}"
                                        class="object-contain w-full h-12 dark:invert"
                                        loading="lazy"
                                    >
                                </a>
                            @else
                                <div class="transition-all duration-300 opacity-50 hover:opacity-100 grayscale hover:grayscale-0">
                                    <img
                                        src="{{ $logo['url'] }}"
                                        alt="{{ $logo['name'] }}"
                                        class="object-contain w-full h-12 dark:invert"
                                        loading="lazy"
                                    >
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        @if($autoplay)
            <style nonce="{{ $GLOBALS['csp_nonce'] ?? '' }}">
                #{{ $uniqueId }} .logo-scroll {
                    animation: logo-scroll {{ count($logoData) * 3 }}s linear infinite;
                }
                #{{ $uniqueId }} .animation-paused {
                    animation-play-state: paused;
                }
                @keyframes logo-scroll {
                    0% {
                        transform: translateX(0);
                    }
                    100% {
                        transform: translateX(calc(-{{ count($logoData) }} * (8rem + 3rem)));
                    }
                }
            </style>
        @endif
    @endif
</x-section>
