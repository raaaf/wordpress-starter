{{--
    Google Maps Block

    Uses shared components: x-section
    Fields: title, address, embed_url, height, show_directions_link, background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $address = $fields['address'] ?? '';
    $embedUrl = $fields['embed_url'] ?? '';
    $height = $fields['height'] ?? 400;
    $showDirections = $fields['show_directions_link'] ?? true;
    $background = $fields['background_color'] ?? 'primary';

    // Generate directions URL
    $directionsUrl = $address ? 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($address) : '';
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} map">
    @if($title)
        <h2 class="text-h2 mb-8 text-center text-content">{{ $title }}</h2>
    @endif

    @if($embedUrl)
        <div
            class="relative overflow-hidden rounded-lg"
            x-data="{ loaded: false }"
        >
            {{-- Consent notice for GDPR compliance --}}
            <div
                x-show="!loaded"
                class="flex flex-col items-center justify-center p-8 text-center bg-surface-secondary"
                style="height: {{ esc_attr($height) }}px;"
            >
                <svg class="w-16 h-16 mb-4 text-content-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="mb-4 text-content-secondary">
                    Zum Anzeigen der Karte wird Google Maps geladen.<br>
                    Es gelten die <a href="https://policies.google.com/privacy" target="_blank" class="underline hover:text-content-link-hover">Datenschutzbestimmungen von Google</a>.
                </p>
                <button
                    @click="loaded = true"
                    class="px-6 py-2 font-medium transition-colors rounded-lg bg-surface-brand text-content-inverse hover:bg-surface-brand-hover"
                >
                    Karte laden
                </button>
            </div>

            {{-- Map iframe (loaded after consent) --}}
            <template x-if="loaded">
                <iframe
                    src="{{ esc_url($embedUrl) }}"
                    width="100%"
                    height="{{ esc_attr($height) }}"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    class="rounded-lg"
                ></iframe>
            </template>
        </div>

        @if($showDirections && $directionsUrl)
            <div class="mt-4 text-center">
                <a
                    href="{{ esc_url($directionsUrl) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 font-medium text-content-link hover:text-content-link-hover"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    Route planen
                </a>
            </div>
        @endif
    @else
        <div class="p-8 text-center rounded-lg bg-surface-secondary">
            <p class="text-content-secondary">Bitte füge eine Google Maps Embed-URL ein.</p>
        </div>
    @endif
</x-section>
