{{--
    Logo Slider - Flexible Content Layout

    Uses shared components: x-section
    CSS-based infinite scrolling animation
    Fields: title, logos (repeater: logo, link, name), autoplay, background_color
--}}

@php
    $title = get_sub_field('title');
    $logos = get_sub_field('logos');
    $autoplay = get_sub_field('autoplay') ?? true;
    $background = get_sub_field('background_color') ?: 'primary';
    $uniqueId = 'logo-slider-' . uniqid();

    // Prepare logo data
    $logoData = [];
    if ($logos) {
        foreach ($logos as $logo) {
            $logoId = $logo['logo'] ?? null;
            if (is_array($logoId)) {
                $logoId = $logoId['ID'] ?? $logoId['id'] ?? null;
            }
            $logoUrl = $logoId ? wp_get_attachment_url($logoId) : '';
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
        <h2 class="text-h3 mb-8 text-center text-content">{{ $title }}</h2>
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
            @if($autoplay)
                {{-- Accessible pause button --}}
                <button
                    type="button"
                    @click="paused = !paused"
                    @keydown.enter.prevent="paused = !paused"
                    @keydown.space.prevent="paused = !paused"
                    class="absolute top-2 right-20 z-20 p-2 rounded-lg bg-surface/80 text-content-secondary hover:text-content hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-line-focus transition-colors"
                    :aria-label="paused ? '{{ __('Animation fortsetzen', 'wp-starter') }}' : '{{ __('Animation pausieren', 'wp-starter') }}'"
                    :aria-pressed="paused"
                >
                    <svg x-show="!paused" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <svg x-show="paused" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>
            @endif
            {{-- Gradient overlays for seamless edges --}}
            <div class="absolute left-0 top-0 bottom-0 w-16 bg-gradient-to-r from-surface-primary to-transparent z-10 pointer-events-none"></div>
            <div class="absolute right-0 top-0 bottom-0 w-16 bg-gradient-to-l from-surface-primary to-transparent z-10 pointer-events-none"></div>

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
