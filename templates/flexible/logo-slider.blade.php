{{--
    Logo Slider - Flexible Content Layout

    Uses shared components: x-section
    CSS-based infinite scrolling animation
    Fields: title, logos (repeater: logo, link, name), autoplay, background_color
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
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
            if ($logoId) {
                $logoMeta = wp_get_attachment_metadata($logoId);
                $logoW = $logoMeta['width'] ?? null;
                $logoH = $logoMeta['height'] ?? null;
                $logoUrl = wp_get_attachment_image_url($logoId, 'logo') ?: '';
            } else {
                $logoUrl = '';
                $logoW = null;
                $logoH = null;
            }
            if ($logoUrl) {
                $logoData[] = [
                    'id'     => $logoId,
                    'url'    => $logoUrl,
                    'link'   => $logo['link'] ?? '',
                    'name'   => $logo['name'] ?? '',
                    'width'  => $logoW,
                    'height' => $logoH,
                ];
            }
        }
    }
@endphp

<x-section :anchor="$sectionAnchor" :background="$background" padding="md" class="logo-slider">
    @if($title)
        <h2 class="text-h3 mb-8 text-center">{!! $title !!}</h2>
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
                                href="{{ esc_url($logo['link']) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block transition-[opacity,filter] duration-200 opacity-50 hover:opacity-100 grayscale hover:grayscale-0"
                                @if($logo['name']) aria-label="{{ $logo['name'] }}" @endif
                            >
                                <img
                                    src="{{ $logo['url'] }}"
                                    alt=""
                                    class="object-contain w-full h-12 dark:invert"
                                    loading="lazy"
                                    @if($logo['width']) width="{{ $logo['width'] }}" @endif
                                    @if($logo['height']) height="{{ $logo['height'] }}" @endif
                                ><span class="sr-only">{{ __('(öffnet in neuem Tab)', 'wp-starter') }}</span>
                            </a>
                        @else
                            <div class="transition-[opacity,filter] duration-200 opacity-50 hover:opacity-100 grayscale hover:grayscale-0">
                                <img
                                    src="{{ $logo['url'] }}"
                                    alt="{{ $logo['name'] }}"
                                    class="object-contain w-full h-12 dark:invert"
                                    loading="lazy"
                                    @if($logo['width']) width="{{ $logo['width'] }}" @endif
                                    @if($logo['height']) height="{{ $logo['height'] }}" @endif
                                >
                            </div>
                        @endif
                    </div>
                @endforeach

                {{-- Duplicate set for infinite scroll effect --}}
                @if($autoplay)
                    @foreach($logoData as $logo)
                        <div class="flex-shrink-0 w-32 flex items-center justify-center" aria-hidden="true" inert>
                            @if($logo['link'])
                                <a
                                    href="{{ esc_url($logo['link']) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="block transition-[opacity,filter] duration-200 opacity-50 hover:opacity-100 grayscale hover:grayscale-0"
                                    @if($logo['name']) aria-label="{{ $logo['name'] }}" @endif
                                >
                                    <img
                                        src="{{ $logo['url'] }}"
                                        alt=""
                                        class="object-contain w-full h-12 dark:invert"
                                        loading="lazy"
                                        @if($logo['width']) width="{{ $logo['width'] }}" @endif
                                        @if($logo['height']) height="{{ $logo['height'] }}" @endif
                                    ><span class="sr-only">{{ __('(öffnet in neuem Tab)', 'wp-starter') }}</span>
                                </a>
                            @else
                                <div class="transition-[opacity,filter] duration-200 opacity-50 hover:opacity-100 grayscale hover:grayscale-0">
                                    <img
                                        src="{{ $logo['url'] }}"
                                        alt="{{ $logo['name'] }}"
                                        class="object-contain w-full h-12 dark:invert"
                                        loading="lazy"
                                        @if($logo['width']) width="{{ $logo['width'] }}" @endif
                                        @if($logo['height']) height="{{ $logo['height'] }}" @endif
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
                @media (prefers-reduced-motion: reduce) {
                    #{{ $uniqueId }} .logo-scroll {
                        animation: none;
                    }
                }
            </style>
        @endif
    @endif
</x-section>
