{{--
    Google Maps Flexible Content Layout

    Uses shared components: x-section, x-section-header, x-button, x-link
    Fields: title, address, embed_url, height, show_directions_link, background_color
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $address = get_sub_field('address') ?: '';
    $embedUrl = get_sub_field('embed_url') ?: '';
    $height = get_sub_field('height') ?: 400;
    $showDirections = get_sub_field('show_directions_link') ?? true;
    $background = get_sub_field('background_color') ?: 'primary';

    // Generate directions URL
    $directionsUrl = $address ? 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($address) : '';
@endphp

@if($embedUrl || $title || current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :background="$background" class="map">
    <x-section-header :headline="$title" />

    @if($embedUrl)
        <div
            class="relative overflow-hidden rounded-lg"
            x-data="{ loaded: false, iframeLoaded: false, iframeError: false }"
            x-ref="mapContainer"
            tabindex="-1"
            style="min-height: {{ esc_attr($height) }}px;"
        >
            {{-- Live region: always present in the DOM so screen readers pick up the text change (loading/error), never toggled with x-show/hidden --}}
            <div
                class="sr-only"
                role="status"
                aria-live="polite"
                x-text="iframeError ? '{{ __('Die Karte konnte nicht geladen werden.', 'wp-starter') }}' : (loaded && !iframeLoaded ? '{{ __('Karte wird geladen...', 'wp-starter') }}' : '')"
            ></div>

            {{-- Consent notice for GDPR compliance --}}
            <div
                x-show="!loaded"
                {{-- Wie beim strukturell identischen Overlay in video.blade.php:
                     ohne Leave-Transition verschwindet der Hinweis in einem Frame. --}}
                x-transition:leave="transition duration-[var(--motion-exit-duration)] ease-[var(--motion-exit-ease)]"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="flex flex-col items-center justify-center p-8 text-center bg-surface-secondary map-consent-notice"
                style="height: {{ esc_attr($height) }}px;"
            >
                <x-icon name="map-pin" class="w-16 h-16 mb-4 text-content-secondary" />
                <p class="mb-4 text-content-secondary">
                    {{ __('Zum Anzeigen der Karte wird Google Maps geladen.', 'wp-starter') }}<br>
                    {{ __('Es gelten die', 'wp-starter') }} <x-link url="https://policies.google.com/privacy" target="_blank">{{ __('Datenschutzbestimmungen von Google', 'wp-starter') }}</x-link>.
                </p>
                <x-button
                    :title="__('Karte laden', 'wp-starter')"
                    variant="primary"
                    size="md"
                    x-on:click="loaded = true; $nextTick(() => $refs.mapContainer.focus())"
                    class="map-consent-btn"
                />
            </div>

            {{-- Loading indicator --}}
            <div
                x-show="loaded && !iframeLoaded && !iframeError"
                class="absolute inset-0 flex flex-col items-center justify-center bg-surface-secondary"
                style="height: {{ esc_attr($height) }}px;"
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
                <x-icon name="warning" class="w-16 h-16 mb-4 text-content-error" />
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

        @if($address)
            <address class="mt-4 not-italic text-center text-content-secondary">{{ $address }}</address>
        @endif

        @if($showDirections && $directionsUrl)
            <div class="mt-4 text-center">
                <x-link url="{{ $directionsUrl }}" target="_blank" variant="accent" size="md">
                    <x-icon name="map-trifold" class="w-5 h-5" />
                    {{ __('Route planen', 'wp-starter') }}
                </x-link>
            </div>
        @endif
    @elseif(current_user_can('edit_posts'))
        <div class="p-8 text-center rounded-lg bg-surface-secondary">
            <p class="text-content-secondary">{{ __('Bitte füge eine Google Maps Embed-URL ein.', 'wp-starter') }}</p>
        </div>
    @endif
</x-section>
@endif
