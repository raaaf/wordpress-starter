{{--
    Logo Slider - Flexible Content Layout

    Uses shared components: x-section, x-section-header
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

    // How many times the logo list must repeat so the track never runs out of
    // material before the keyframe animation completes one loop. The keyframe
    // shifts the track by exactly one list-width (see below), so the track has
    // to cover that shift plus the widest viewport we expect, or the last
    // stretch of the loop scrolls past the end of the material into a gap.
    // One list item is w-32 (8rem = 128px) plus gap-12 (3rem = 48px) = 176px.
    // copies = 1 + ceil(widest_viewport / list_width), minimum 2.
    $logoCount = count($logoData);
    $listWidthPx = $logoCount * 176;
    $copies = ($autoplay && $listWidthPx > 0)
        ? max(2, 1 + (int) ceil(1920 / $listWidthPx))
        : 1;
@endphp

@if($title || !empty($logoData))
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :background="$background" padding="md" class="logo-slider">
    <x-section-header :headline="$title" />

    @if(!empty($logoData))
        <div
            id="{{ $uniqueId }}"
            class="relative overflow-hidden"
            @if($autoplay)
                {{-- Zwei getrennte Zustaende: pausedByUser (Knopf, bleibt bis
                     zum naechsten Klick bestehen) und hovered (Maus/Fokus).
                     Ohne diese Trennung ueberschreibt mouseleave eine per
                     Klick gesetzte Pause. Die Animation pausiert bei einem
                     der beiden Zustaende, der Knopf selbst zeigt/toggelt nur
                     pausedByUser. --}}
                x-data="{ pausedByUser: false, hovered: false, get paused() { return this.pausedByUser || this.hovered } }"
                @mouseenter="hovered = true"
                @mouseleave="hovered = false"
                @focusin="hovered = true"
                @focusout="hovered = false"
            @endif
            role="region"
            aria-label="{{ __('Partner-Logos Karussell', 'wp-starter') }}"
        >
            @if($autoplay)
                {{-- WCAG 2.2.2: laufende Inhalte brauchen eine Pause, die nicht
                     nur am Zeigegeraet haengt. --}}
                <button
                    type="button"
                    x-on:click="pausedByUser = !pausedByUser"
                    x-bind:aria-pressed="pausedByUser ? 'true' : 'false'"
                    class="absolute z-20 p-2 transition-colors border rounded-full right-2 top-2 bg-surface border-line text-content hover:bg-surface-secondary focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring)]"
                >
                    <span class="sr-only" x-text="pausedByUser ? '{{ esc_js(__('Logolauf fortsetzen', 'wp-starter')) }}' : '{{ esc_js(__('Logolauf anhalten', 'wp-starter')) }}'">{{ __('Logolauf anhalten', 'wp-starter') }}</span>
                    <svg class="w-4 h-4" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <rect x="4" y="3" width="3" height="10" rx="1" x-show="!pausedByUser"></rect>
                        <rect x="9" y="3" width="3" height="10" rx="1" x-show="!pausedByUser"></rect>
                        <path d="M5 3.5v9l8-4.5-8-4.5z" x-show="pausedByUser" x-cloak></path>
                    </svg>
                </button>
            @endif
            {{-- Gradient overlays for seamless edges --}}
            <div class="absolute left-0 top-0 bottom-0 w-16 bg-gradient-to-r {{ $gradientFrom }} to-transparent z-10 pointer-events-none"></div>
            <div class="absolute right-0 top-0 bottom-0 w-16 bg-gradient-to-l {{ $gradientFrom }} to-transparent z-10 pointer-events-none"></div>

            <div
                class="flex gap-12 {{ $autoplay ? 'logo-scroll' : 'flex-wrap justify-center' }}"
                @if($autoplay) :class="{ 'animation-paused': paused }" @endif
            >
                {{-- The list repeats $copies times so the track still has material
                     left to show at the end of the keyframe's one-list-width shift
                     (see the $copies calculation above). Only the first copy is
                     real content; every repeat is presentation only and hidden
                     from assistive tech and keyboard/tab order. --}}
                @for($copy = 0; $copy < $copies; $copy++)
                    @foreach($logoData as $logo)
                        <div
                            class="flex-shrink-0 w-32 flex items-center justify-center"
                            @if($copy > 0) aria-hidden="true" inert @endif
                        >
                            @if($logo['link'])
                                <a
                                    href="{{ esc_url($logo['link']) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="block transition-[opacity,filter] duration-200 opacity-50 hover:opacity-100 grayscale hover:grayscale-0"
                                    aria-label="{{ $logo['name'] ? $logo['name'] . ' ' : '' }}{{ __('(öffnet in neuem Tab)', 'wp-starter') }}"
                                >
                                    <img
                                        src="{{ $logo['url'] }}"
                                        alt=""
                                        class="object-contain w-full h-12 dark:invert"
                                        loading="lazy"
                                        @if($logo['width']) width="{{ $logo['width'] }}" @endif
                                        @if($logo['height']) height="{{ $logo['height'] }}" @endif
                                    >
                                </a>
                            @else
                                {{-- Not linked: no hover promise to make, so no
                                     hover/transition classes either. --}}
                                <div class="opacity-50 grayscale">
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
                @endfor
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
@endif
