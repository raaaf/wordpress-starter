{{--
    Google Maps Flexible Content Layout

    Uses shared components: x-section, x-button, x-link
    Fields: title, address, embed_url, height, show_directions_link, background_color
--}}

@php
    $title = get_sub_field('title') ?: '';
    $address = get_sub_field('address') ?: '';
    $embedUrl = get_sub_field('embed_url') ?: '';
    $height = get_sub_field('height') ?: 400;
    $showDirections = get_sub_field('show_directions_link') ?? true;
    $background = get_sub_field('background_color') ?: 'primary';

    // Generate directions URL
    $directionsUrl = $address ? 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($address) : '';
@endphp

<x-section :background="$background" class="map">
    @if($title)
        <h2 class="text-h2 mb-8 text-center text-content">{!! $title !!}</h2>
    @endif

    @if($embedUrl)
        <div
            class="relative overflow-hidden rounded-lg"
            x-data="{ loaded: false, iframeLoaded: false, iframeError: false }"
            style="min-height: {{ esc_attr($height) }}px;"
        >
            {{-- Consent notice for GDPR compliance --}}
            <div
                x-show="!loaded"
                class="flex flex-col items-center justify-center p-8 text-center bg-surface-secondary map-consent-notice"
                style="height: {{ esc_attr($height) }}px;"
            >
                <svg class="w-16 h-16 mb-4 text-content-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="mb-4 text-content-secondary">
                    {{ __('Zum Anzeigen der Karte wird Google Maps geladen.', 'wp-starter') }}<br>
                    {{ __('Es gelten die', 'wp-starter') }} <x-link url="https://policies.google.com/privacy" target="_blank">{{ __('Datenschutzbestimmungen von Google', 'wp-starter') }}</x-link>.
                </p>
                <x-button
                    :title="__('Karte laden', 'wp-starter')"
                    variant="primary"
                    size="md"
                    x-on:click="loaded = true"
                    class="map-consent-btn"
                />
            </div>

            {{-- Loading indicator --}}
            <div
                x-show="loaded && !iframeLoaded && !iframeError"
                class="absolute inset-0 flex flex-col items-center justify-center bg-surface-secondary"
                style="height: {{ esc_attr($height) }}px;"
                role="status"
                aria-live="polite"
            >
                <div class="animate-spin rounded-full h-12 w-12 border-4 border-line border-t-line-brand mb-4"></div>
                <span class="text-content-secondary">{{ __('Karte wird geladen...', 'wp-starter') }}</span>
            </div>

            {{-- Error state --}}
            <div
                x-show="iframeError"
                x-cloak
                class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center bg-surface-secondary"
                style="height: {{ esc_attr($height) }}px;"
            >
                <svg class="w-16 h-16 mb-4 text-content-error" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="mb-4 text-content-secondary">{{ __('Die Karte konnte nicht geladen werden.', 'wp-starter') }}</p>
                <x-button
                    :title="__('Erneut versuchen', 'wp-starter')"
                    variant="secondary"
                    size="md"
                    x-on:click="iframeError = false; iframeLoaded = false"
                />
            </div>

            {{-- Map iframe (loaded after consent) --}}
            <template x-if="loaded && !iframeError">
                <iframe
                    src="{{ esc_url($embedUrl) }}"
                    width="100%"
                    height="{{ esc_attr($height) }}"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    class="rounded-lg"
                    title="{{ __('Google Maps Karte', 'wp-starter') }}{{ $address ? ': ' . esc_attr($address) : '' }}"
                    x-on:load="iframeLoaded = true"
                    x-on:error="iframeError = true"
                ></iframe>
            </template>
        </div>

        @if($showDirections && $directionsUrl)
            <div class="mt-4 text-center">
                <x-link url="{{ $directionsUrl }}" target="_blank" variant="accent" size="md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    {{ __('Route planen', 'wp-starter') }}
                </x-link>
            </div>
        @endif
    @else
        <div class="p-8 text-center rounded-lg bg-surface-secondary">
            <p class="text-content-secondary">Bitte füge eine Google Maps Embed-URL ein.</p>
        </div>
    @endif
</x-section>
