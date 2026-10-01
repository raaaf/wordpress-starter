{{--
    Einbettung - Flexible Content Layout

    Uses shared components: x-section, x-section-header
    Fields: title, url, iframe_title, aspect_ratio, height, background_color
    Section header extras (section_chip, section_description, section_alignment) kommen
    ueber SectionHeader::extras($title). Section-Felder (section_spacing, section_width,
    section_anchor) kommen als $sectionSpacing, $sectionWidth, $sectionAnchor von der einbindenden Seite.

    Bewusst nur eine Adresse statt des Einbettungscodes des Anbieters. Zwei
    Gruende: ACF entfernt <iframe> beim Speichern fuer jeden ohne Recht
    unfiltered_html, im Multisite also fuer jeden Admin ausser dem Super-Admin.
    Und der Rahmen gehoert ohnehin uns, damit title, loading und referrerpolicy
    gesetzt sind, statt davon abzuhaengen, was im kopierten Schnipsel stand.

    Geladen wird nur, was die Hostliste erlaubt: sie steht unter
    Theme-Einstellungen. Security::isAllowedEmbedHost() prueft den Host
    serverseitig, damit eine nicht erlaubte Adresse gar nicht erst als
    <iframe> ausgeliefert wird. Erst nach Einwilligung
    laedt der Iframe ueberhaupt (DSGVO), siehe partials/consent-gate.
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $url = (string) get_sub_field('url');
    $iframeTitle = (string) get_sub_field('iframe_title');
    $ratio = get_sub_field('aspect_ratio') ?: '16-9';
    $height = (int) (get_sub_field('height') ?: 700);
    $background = get_sub_field('background_color') ?: 'primary';
    $kopf = \WordpressStarter\Helpers\SectionHeader::extras($title);

    $host = $url !== '' ? (string) wp_parse_url($url, PHP_URL_HOST) : '';
    $isHttps = $url !== '' && strtolower((string) wp_parse_url($url, PHP_URL_SCHEME)) === 'https';

    // Die CSP hat kein frame-src: diese serverseitige Host-Zulassung ist das
    // einzige Gate, eine nicht erlaubte Adresse wird gar nicht erst gerendert.
    $isAllowedHost = $url !== '' && \WordpressStarter\Security::isAllowedEmbedHost($url);
    $canEdit = current_user_can('edit_posts');

    // Eigene Datenschutzerklaerung statt einer anbieterspezifischen: anders als
    // beim Video kennen wir hier den Anbieter nicht vorab (jede erlaubte
    // Adresse ist moeglich), nur den Host aus der URL.
    $privacyPolicyUrl = (string) get_privacy_policy_url();

    // Feste Klassennamen, damit Tailwind sie findet.
    $aspectClass = match($ratio) {
        '4-3' => 'aspect-[4/3]',
        '1-1' => 'aspect-square',
        'fixed' => '',
        default => 'aspect-video',
    };
@endphp

@if(($url !== '' && $isHttps && $isAllowedHost) || $canEdit)
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" class="embed">
    <x-section-header :chip="$kopf['chip']" :headline="$kopf['headline']" :description="$kopf['description']" :alignment="$kopf['alignment']" />

    @if($url !== '' && $isHttps && $isAllowedHost)
        <div class="max-w-4xl mx-auto">
            <div
                class="relative overflow-hidden rounded-[var(--card-radius)] bg-surface-secondary {{ $aspectClass }}"
                @if(!$aspectClass) style="height: {{ $height }}px" @endif
                x-data="{ loaded: false, iframeLoaded: false }"
                x-ref="embedContainer"
                tabindex="-1"
            >
                {{-- Live region: immer im DOM, damit Screenreader den Textwechsel (Laden) melden. Ohne Text unsichtbar, pointer-events-none damit der Iframe bedienbar bleibt. --}}
                <div
                    class="absolute inset-0 flex items-center justify-center p-8 text-center text-content-secondary pointer-events-none"
                    role="status"
                    aria-live="polite"
                    x-text="loaded && !iframeLoaded ? @js(__('Inhalt wird geladen...', 'wp-starter')) : ''"
                ></div>

                {{-- Consent notice: das Widget laedt erst nach Klick, DSGVO-konform
                     wie bei video.blade.php. Geteiltes Markup in
                     partials/consent-gate.blade.php. --}}
                @include('partials.consent-gate', [
                    'containerRef' => 'embedContainer',
                    'icon' => 'info',
                    'iconClass' => 'text-content-secondary',
                    'wrapperClass' => 'bg-surface-secondary',
                    'textClass' => 'text-content-secondary',
                    'message' => sprintf(__('Beim Laden werden Daten an %s übertragen.', 'wp-starter'), $host),
                    'buttonLabel' => __('Inhalt laden', 'wp-starter'),
                    'providerName' => $host,
                    'privacyLink' => $privacyPolicyUrl,
                    'ownPrivacyPolicy' => true,
                ])

                <template x-if="loaded">
                    <iframe
                        src="{{ esc_url($url) }}"
                        title="{{ esc_attr($iframeTitle ?: ($title ? wp_strip_all_tags($title) : __('Eingebetteter Inhalt', 'wp-starter'))) }}"
                        class="absolute inset-0 w-full h-full border-0"
                        loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin"
                        {{-- allow-scripts: Buchungs-Widgets (z. B. Calendly) laufen auf JS.
                             allow-same-origin: dieselben Widgets lesen/setzen eigene Cookies/Storage.
                             allow-forms: Formularfelder im Widget (Terminwahl, Kontaktdaten).
                             allow-popups: Zahlungs-/Login-Popups (z. B. Google-Kalender-Verbindung). --}}
                        sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
                        allowfullscreen
                        @load="iframeLoaded = true"
                    ></iframe>
                </template>
            </div>
        </div>
    @elseif($url !== '' && $isHttps && $canEdit)
        <div class="p-8 text-center rounded-[var(--card-radius)] bg-surface-secondary surface-sheen">
            <p class="text-content-secondary">
                {{ sprintf(__('Nur für dich sichtbar: Der Host %s ist nicht in den Theme-Einstellungen freigegeben. Trage ihn dort ein, sonst bleibt die Fläche für Besucher leer.', 'wp-starter'), $host) }}
            </p>
        </div>
    @elseif($canEdit)
        <div class="p-8 text-center rounded-[var(--card-radius)] bg-surface-secondary surface-sheen">
            <p class="text-content-secondary">{{ __('Bitte trage eine https-Adresse ein. Der Einbettungscode des Anbieters enthält sie im src-Attribut.', 'wp-starter') }}</p>
        </div>
    @endif
</x-section>
@endif
