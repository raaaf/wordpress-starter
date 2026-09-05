{{--
    Video - Flexible Content Layout

    Uses shared components: x-section, x-button, x-link
    ACF Fields: title, section extras, source, video, video_url, video_file_url, video_title,
                captions, captions_language, aspect_ratio, autoplay, loop,
                background_color
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $kopf = \WordpressStarter\Helpers\SectionHeader::extras($title);
    $source = get_sub_field('source') ?: 'wordpress';
    $video = get_sub_field('video'); // URL string for self-hosted
    $video_url = get_sub_field('video_url'); // YouTube/Vimeo URL
    $video_file_url = get_sub_field('video_file_url'); // Direct file URL (CDN etc.)
    $video_title = get_sub_field('video_title'); // Optional accessible title
    $background = get_sub_field('background_color') ?: 'primary';

    // Captions (WCAG 1.2.2, Level A). Only self-hosted and direct-URL videos:
    // YouTube and Vimeo serve their own caption tracks.
    // A <track> is emitted only when a file exists — an empty one would claim
    // captions that are not there, which is worse than none at all.
    $captions = get_sub_field('captions');
    $captionsLanguage = get_sub_field('captions_language') ?: 'de';
    $captionsLabels = [
        'de' => __('Deutsch', 'wp-starter'),
        'en' => __('Englisch', 'wp-starter'),
        'fr' => __('Französisch', 'wp-starter'),
        'es' => __('Spanisch', 'wp-starter'),
        'it' => __('Italienisch', 'wp-starter'),
    ];
    $captionsLabel = $captionsLabels[$captionsLanguage] ?? $captionsLanguage;

    // Detect video type from URL for external videos
    $video_type = 'self';
    $video_id = '';

    if ($source === 'external' && $video_url) {
        $videoUrlParts = wp_parse_url($video_url);
        // www./m. abschneiden, damit youtube.com und m.youtube.com denselben Host treffen.
        $videoUrlHost = isset($videoUrlParts['host']) ? preg_replace('/^(?:www|m)\./', '', strtolower($videoUrlParts['host'])) : '';
        $videoUrlPath = $videoUrlParts['path'] ?? '';
        $videoUrlQuery = [];
        if (!empty($videoUrlParts['query'])) {
            wp_parse_str($videoUrlParts['query'], $videoUrlQuery);
        }

        if (in_array($videoUrlHost, ['youtube.com', 'youtube-nocookie.com'], true)) {
            if (!empty($videoUrlQuery['v']) && preg_match('/^[A-Za-z0-9_-]{11}$/', $videoUrlQuery['v'])) {
                $video_type = 'youtube';
                $video_id = $videoUrlQuery['v'];
            } elseif (preg_match('#^/(?:embed|shorts|live)/([A-Za-z0-9_-]{11})#', $videoUrlPath, $matches)) {
                $video_type = 'youtube';
                $video_id = $matches[1];
            }
        } elseif ($videoUrlHost === 'youtu.be' && preg_match('#^/([A-Za-z0-9_-]{11})#', $videoUrlPath, $matches)) {
            $video_type = 'youtube';
            $video_id = $matches[1];
        } elseif ($videoUrlHost === 'vimeo.com' && preg_match('#^/(?:channels/[^/]+/|video/)?(\d+)/?$#', $videoUrlPath, $matches)) {
            $video_type = 'vimeo';
            $video_id = $matches[1];
        } elseif ($videoUrlHost === 'player.vimeo.com' && preg_match('#/video/(\d+)#', $videoUrlPath, $matches)) {
            $video_type = 'vimeo';
            $video_id = $matches[1];
        }
    }

    // Determine privacy policy link based on video type
    $privacyLink = match($video_type) {
        'youtube' => 'https://policies.google.com/privacy',
        'vimeo' => 'https://vimeo.com/privacy',
        default => '',
    };
    $providerName = match($video_type) {
        'youtube' => 'YouTube (Google)',
        'vimeo' => 'Vimeo',
        default => '',
    };

    // Feste Klassennamen, damit Tailwind sie findet.
    $aspectClass = match(get_sub_field('aspect_ratio') ?: '16-9') {
        '4-3' => 'aspect-[4/3]',
        '1-1' => 'aspect-square',
        '21-9' => 'aspect-[21/9]',
        default => 'aspect-video',
    };

    // Automatisches Abspielen geht nur stumm, sonst blockieren es die Browser.
    // YouTube und Vimeo laufen ueber die Einwilligung und bleiben aussen vor.
    $selfHosted = in_array($source, ['wordpress', 'url'], true);
    $autoplay = $selfHosted && (bool) get_sub_field('autoplay');
    $loop = $selfHosted && (bool) get_sub_field('loop');

    $posterId = (int) (get_sub_field('poster') ?: 0);
    $posterUrl = $posterId > 0 ? wp_get_attachment_image_url($posterId, 'hero-background') : '';

    // Das Feld erlaubt mp4/webm/ogg, "video/mp4" war unabhaengig vom
    // hochgeladenen Format immer fest. Eigene Endungs-Zuordnung fuer genau die
    // im Feld erlaubten Formate, unabhaengig von der Extension-zu-MIME-Zuordnung
    // des Servers; bei unbekanntem Typ bleibt type leer statt eine falsche
    // Angabe zu machen.
    $videoMimeTypes = [
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogg' => 'video/ogg',
        'ogv' => 'video/ogg',
    ];
    $videoExtension = static fn ($url) => strtolower(pathinfo(wp_parse_url($url, PHP_URL_PATH) ?: $url, PATHINFO_EXTENSION));
    $videoMimeType = $video ? ($videoMimeTypes[$videoExtension($video)] ?? '') : '';
    $videoFileUrlMimeType = $video_file_url ? ($videoMimeTypes[$videoExtension($video_file_url)] ?? '') : '';

    // Check if we have a valid video
    $hasVideo = ($source === 'external' && $video_id) ||
                ($source === 'wordpress' && $video) ||
                ($source === 'url' && $video_file_url);

    // Editor-only Hinweis: eine externe URL wurde eingetragen, aber keiner
    // der beiden Anbieter-Muster hat gegriffen. Ohne diesen Sonderfall zeigt
    // der generische Hinweis unten faelschlich "keine URL eingefuegt" an,
    // obwohl eine drinsteht, die der Parser nur nicht erkennt.
    $unrecognizedExternalUrl = $source === 'external' && $video_url && !$video_id;
@endphp

@if($hasVideo || current_user_can('edit_posts'))
<x-section :background="$background" :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" class="video">
    <x-section-header :chip="$kopf['chip']" :headline="$kopf['headline']" :description="$kopf['description']" :alignment="$kopf['alignment']" />
    @if($hasVideo)
        <div class="max-w-6xl mx-auto">
            <div
                class="relative overflow-hidden rounded-lg {{ $aspectClass }} bg-surface-secondary"
                x-data="{ loaded: {{ $selfHosted ? 'true' : 'false' }}, iframeLoaded: false, iframeError: false }"
                x-ref="videoContainer"
                tabindex="-1"
            >
                @if($source === 'external' && $video_id)
                    {{-- Live region: always present in the DOM so screen readers pick up the text change (loading/error), never toggled with x-show/hidden --}}
                    <div
                        class="sr-only"
                        role="status"
                        aria-live="polite"
                        x-text="iframeError ? @js(__('Das Video konnte nicht geladen werden.', 'wp-starter')) : (loaded && !iframeLoaded ? @js(__('Video wird geladen…', 'wp-starter')) : '')"
                    ></div>

                    {{-- Standbild hinter der Einwilligung. Ohne es steht hier bis zum
                         Klick eine leere graue Flaeche ueber die volle Breite, bei
                         16:9 und 1200px Spalte rund 675px hoch. Das Bild ist
                         dekorativ, der Text darueber traegt die Aussage, deshalb
                         aria-hidden und leerer Alt-Text. --}}
                    @if($posterUrl)
                        <img
                            src="{{ esc_url($posterUrl) }}"
                            alt=""
                            aria-hidden="true"
                            x-show="!loaded"
                            class="absolute inset-0 object-cover w-full h-full"
                            loading="lazy"
                        />
                        {{-- Abdunkeln, damit Text und Schaltflaeche auf jedem
                             Standbild lesbar bleiben. bg-surface-overlay statt
                             hartem bg-black/55, damit der Scrim wie jedes andere
                             Overlay im Theme dem Farbschema folgt (heller Modus
                             .50, dunkler Modus .70). --}}
                        <div x-show="!loaded" class="absolute inset-0 bg-surface-overlay" aria-hidden="true"></div>
                    @endif

                    {{-- Consent notice for GDPR compliance. Geteiltes Markup in
                         partials/consent-gate.blade.php. --}}
                    @include('partials.consent-gate', [
                        'containerRef' => 'videoContainer',
                        'icon' => 'play',
                        {{-- Auf dem Standbild helle Schrift erzwingen: die
                             Standardfarben sind auf die Sektionsflaeche
                             gerechnet, hier liegt aber ein abgedunkeltes Bild
                             darunter. Gemessen ohne diese Klassen: 2.9:1. --}}
                        'iconClass' => $posterUrl ? 'text-white/80' : 'text-content-secondary',
                        'wrapperClass' => 'video-consent-notice ' . ($posterUrl ? 'text-white [&_a]:text-white [&_a]:decoration-white/60' : ''),
                        'textClass' => $posterUrl ? 'text-white' : 'text-content-secondary',
                        'message' => __('Zum Abspielen des Videos wird ein externer Dienst geladen.', 'wp-starter'),
                        'buttonLabel' => __('Video laden', 'wp-starter'),
                        'providerName' => $providerName,
                        'privacyLink' => $privacyLink,
                    ])

                    {{-- Loading indicator --}}
                    <div
                        x-show="loaded && !iframeLoaded && !iframeError"
                        class="absolute inset-0 flex flex-col items-center justify-center bg-surface-secondary"
                    >
                        <div class="animate-spin rounded-full h-12 w-12 border-4 border-line border-t-line-brand mb-4"></div>
                        <span class="text-content-secondary">{{ __('Video wird geladen…', 'wp-starter') }}</span>
                    </div>

                    {{-- Error state --}}
                    <div
                        x-show="iframeError"
                        x-cloak
                        class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center bg-surface-secondary"
                    >
                        <x-icon name="warning" class="w-16 h-16 mb-4 text-content-error" />
                        {{-- Kein Poster/Scrim mehr im Fehler-Zustand (x-show="!loaded" hat beides
                             bereits ausgeblendet), deshalb hier immer die Textfarbe fuer helle
                             Flaeche, nie den Poster-Ternary aus dem Consent-Overlay. --}}
                        <p class="mb-4 text-content-secondary">{{ __('Das Video konnte nicht geladen werden.', 'wp-starter') }}</p>
                        <x-button
                            :title="__('Erneut versuchen', 'wp-starter')"
                            variant="secondary"
                            size="md"
                            x-on:click="iframeError = false; iframeLoaded = false"
                        />
                    </div>

                    {{-- Video iframe (loaded after consent) --}}
                    <template x-if="loaded && !iframeError">
                        @if($video_type === 'youtube')
                            <iframe
                                src="https://www.youtube-nocookie.com/embed/{{ $video_id }}?dnt=1&autoplay=0"
                                frameborder="0"
                                allow="autoplay; fullscreen; picture-in-picture"
                                allowfullscreen
                                loading="lazy"
                                class="absolute inset-0 w-full h-full"
                                title="{{ $video_title ? sprintf(__('Video: %s', 'wp-starter'), $video_title) : __('YouTube-Video', 'wp-starter') }}"
                                x-on:load="iframeLoaded = true"
                                x-on:error="iframeError = true"
                            ></iframe>
                        @elseif($video_type === 'vimeo')
                            <iframe
                                src="https://player.vimeo.com/video/{{ $video_id }}?dnt=1&autoplay=0"
                                frameborder="0"
                                allow="autoplay; fullscreen; picture-in-picture"
                                allowfullscreen
                                loading="lazy"
                                class="absolute inset-0 w-full h-full"
                                title="{{ $video_title ? sprintf(__('Video: %s', 'wp-starter'), $video_title) : __('Vimeo-Video', 'wp-starter') }}"
                                x-on:load="iframeLoaded = true"
                                x-on:error="iframeError = true"
                            ></iframe>
                        @endif
                    </template>
                @elseif($source === 'wordpress' && $video)
                    {{-- Self-hosted video - no consent needed. Geteiltes Markup in
                         partials/video-player.blade.php. --}}
                    @include('partials.video-player', [
                        'url' => $video,
                        'mimeType' => $videoMimeType,
                        'poster' => $posterUrl,
                        'captions' => $captions,
                        'captionsLanguage' => $captionsLanguage,
                        'captionsLabel' => $captionsLabel,
                        'autoplay' => $autoplay,
                        'loop' => $loop,
                        'ariaLabel' => $video_title ? sprintf(__('Video: %s', 'wp-starter'), $video_title) : __('Video', 'wp-starter'),
                        'aspectClass' => $aspectClass,
                    ])
                @elseif($source === 'url' && $video_file_url)
                    {{-- External file URL - no consent needed. Geteiltes Markup in
                         partials/video-player.blade.php. --}}
                    @include('partials.video-player', [
                        'url' => $video_file_url,
                        'mimeType' => $videoFileUrlMimeType,
                        'poster' => $posterUrl,
                        'captions' => $captions,
                        'captionsLanguage' => $captionsLanguage,
                        'captionsLabel' => $captionsLabel,
                        'autoplay' => $autoplay,
                        'loop' => $loop,
                        'ariaLabel' => $video_title ? sprintf(__('Video: %s', 'wp-starter'), $video_title) : __('Video', 'wp-starter'),
                        'aspectClass' => $aspectClass,
                    ])
                @endif
            </div>
        </div>
    @elseif(current_user_can('edit_posts'))
        <div class="p-8 text-center rounded-lg bg-surface-secondary surface-sheen">
            @if($unrecognizedExternalUrl)
                <p class="text-content-secondary">{{ __('Video-URL nicht erkannt. Unterstützt werden YouTube und Vimeo.', 'wp-starter') }}</p>
            @else
                <p class="text-content-secondary">{{ __('Bitte füge eine Video-URL ein oder lade eine Videodatei hoch.', 'wp-starter') }}</p>
            @endif
        </div>
    @endif
</x-section>
@endif
