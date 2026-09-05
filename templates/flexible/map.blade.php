{{--
    Google Maps Flexible Content Layout

    Uses shared components: x-section, x-section-header, x-button, x-link
    Fields: title, address, embed_url, height, show_directions_link, background_color
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $kopf = \WordpressStarter\Helpers\SectionHeader::extras($title);
    $address = get_sub_field('address') ?: '';
    $embedUrl = get_sub_field('embed_url') ?: '';
    // Clamp to the field's own min/max (FieldDefinitions: 200-800, default
    // 400) so a hand-edited postmeta value cannot produce a broken layout.
    $height = (int) (get_sub_field('height') ?: 400);
    $height = max(200, min(800, $height));
    $showDirections = get_sub_field('show_directions_link') ?? true;
    $background = get_sub_field('background_color') ?: 'primary';

    // Same host allowlist as Security::getCSPHeader() (which writes the same
    // check into frame-src) and embed.blade.php: an address the CSP would
    // block anyway must not even be attempted as an iframe.
    $isAllowedHost = $embedUrl !== '' && \WordpressStarter\Security::isAllowedEmbedHost($embedUrl);

    // Generate directions URL
    $directionsUrl = $address ? 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($address) : '';
@endphp

@if(($embedUrl && $isAllowedHost) || $title || current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" class="map">
    <x-section-header :chip="$kopf['chip']" :headline="$kopf['headline']" :description="$kopf['description']" :alignment="$kopf['alignment']" />

    @if($embedUrl && $isAllowedHost)
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
                x-text="iframeError ? '{{ esc_js(__('Die Karte konnte nicht geladen werden.', 'wp-starter')) }}' : (loaded && !iframeLoaded ? '{{ esc_js(__('Karte wird geladen...', 'wp-starter')) }}' : '')"
            ></div>

            {{-- Consent notice for GDPR compliance. Geteiltes Markup wie in
                 embed.blade.php und video.blade.php, siehe
                 partials/consent-gate.blade.php. --}}
            @include('partials.consent-gate', [
                'containerRef' => 'mapContainer',
                'icon' => 'map-pin',
                'iconClass' => 'text-content-secondary',
                'wrapperClass' => 'bg-surface-secondary map-consent-notice',
                'textClass' => 'text-content-secondary',
                'message' => __('Zum Anzeigen der Karte wird Google Maps geladen.', 'wp-starter'),
                'buttonLabel' => __('Karte laden', 'wp-starter'),
                'buttonClass' => 'map-consent-btn',
                'providerName' => __('Google', 'wp-starter'),
                'privacyLink' => 'https://policies.google.com/privacy',
            ])

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
                    referrerpolicy="strict-origin-when-cross-origin"
                    {{-- allow-scripts + allow-same-origin: the Google Maps embed
                         needs its own JS runtime and same-origin storage/cookies
                         to render the map; without allow-same-origin it shows a
                         blank frame. allow-popups: "Open in Google Maps" opens a
                         new tab from inside the iframe. --}}
                    sandbox="allow-scripts allow-same-origin allow-popups"
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
    @elseif($embedUrl && current_user_can('edit_posts'))
        <div class="p-8 text-center rounded-lg bg-surface-secondary surface-sheen">
            <p class="text-content-secondary">
                {{ __('Nur für dich sichtbar: Diese Google-Maps-Adresse ist nicht in den Theme-Einstellungen freigegeben. Trage den Host dort ein, sonst bleibt die Fläche für Besucher leer.', 'wp-starter') }}
            </p>
        </div>
    @elseif(current_user_can('edit_posts'))
        <div class="p-8 text-center rounded-lg bg-surface-secondary surface-sheen">
            <p class="text-content-secondary">{{ __('Bitte füge eine Google Maps Embed-URL ein.', 'wp-starter') }}</p>
        </div>
    @endif
</x-section>
@endif
