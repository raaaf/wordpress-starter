{{--
    Logo Slider Block

    Uses shared components: x-section
    CSS-based infinite scrolling animation
    Fields: title, logos (repeater: logo, link, name), autoplay, background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $logos = $fields['logos'] ?? [];
    $autoplay = $fields['autoplay'] ?? true;
    $background = $fields['background_color'] ?? 'primary';
    $uniqueId = 'logo-slider-' . uniqid();

    // Prepare logo data
    $logoData = [];
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
@endphp

<x-section :background="$background" :anchor="$anchor" :wrapperAttributes="$wrapper_attributes" padding="md" class="{{ $classes }} logo-slider">
    @if($title)
        <h2 class="text-h3 mb-8 text-center text-content">{{ $title }}</h2>
    @endif

    @if(!empty($logoData))
        <div
            id="{{ $uniqueId }}"
            class="relative overflow-hidden"
            @if(!$is_preview && $autoplay)
                x-data="{ paused: false }"
                @mouseenter="paused = true"
                @mouseleave="paused = false"
            @endif
        >
            {{-- Gradient overlays for seamless edges --}}
            <div class="absolute left-0 top-0 bottom-0 w-16 bg-gradient-to-r from-surface-primary to-transparent z-10 pointer-events-none"></div>
            <div class="absolute right-0 top-0 bottom-0 w-16 bg-gradient-to-l from-surface-primary to-transparent z-10 pointer-events-none"></div>

            <div
                class="flex gap-12 {{ $autoplay && !$is_preview ? 'logo-scroll' : '' }}"
                @if(!$is_preview && $autoplay) :class="{ 'animation-paused': paused }" @endif
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
                @if($autoplay && !$is_preview)
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

        @if($autoplay && !$is_preview)
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
