{{--
    Einbettung - Flexible Content Layout

    Uses shared components: x-section
    Fields: title, url, iframe_title, aspect_ratio, height, background_color

    Bewusst nur eine Adresse statt des Einbettungscodes des Anbieters. Zwei
    Gruende: ACF entfernt <iframe> beim Speichern fuer jeden ohne Recht
    unfiltered_html, im Multisite also fuer jeden Admin ausser dem Super-Admin.
    Und der Rahmen gehoert ohnehin uns, damit title, loading und referrerpolicy
    gesetzt sind, statt davon abzuhaengen, was im kopierten Schnipsel stand.

    Geladen wird trotzdem nur, was die CSP erlaubt: die Hostliste steht unter
    Theme-Einstellungen und landet in frame-src (siehe Security::getEmbedOrigins).
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
    $isHttps = $url !== '' && wp_parse_url($url, PHP_URL_SCHEME) === 'https';

    // Feste Klassennamen, damit Tailwind sie findet.
    $aspectClass = match($ratio) {
        '4-3' => 'aspect-[4/3]',
        '1-1' => 'aspect-square',
        'fixed' => '',
        default => 'aspect-video',
    };
@endphp

@if(($url !== '' && $isHttps) || current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" class="embed">
    <x-section-header :chip="$kopf['chip']" :headline="$kopf['headline']" :description="$kopf['description']" :alignment="$kopf['alignment']" />

    @if($url !== '' && $isHttps)
        <div class="max-w-4xl mx-auto {{ $aspectClass }}">
            <iframe
                src="{{ esc_url($url) }}"
                title="{{ esc_attr($iframeTitle ?: ($title ? wp_strip_all_tags($title) : __('Eingebetteter Inhalt', 'wp-starter'))) }}"
                class="w-full {{ $aspectClass ? 'h-full' : '' }} border-0 rounded-[var(--card-radius)]"
                @if(!$aspectClass) height="{{ $height }}" style="height: {{ $height }}px" @endif
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen
            ></iframe>
        </div>

        @if(current_user_can('edit_posts') && $host !== '')
            <p class="mt-4 text-center text-body-small text-content-secondary">
                {{ sprintf(__('Nur für dich sichtbar: Diese Einbettung lädt von %s. Bleibt die Fläche leer, fehlt der Host in den Theme-Einstellungen.', 'wp-starter'), $host) }}
            </p>
        @endif
    @elseif(current_user_can('edit_posts'))
        <div class="p-8 text-center rounded-[var(--card-radius)] bg-surface-secondary">
            <p class="text-content-secondary">{{ __('Bitte trage eine https-Adresse ein. Der Einbettungscode des Anbieters enthält sie im src-Attribut.', 'wp-starter') }}</p>
        </div>
    @endif
</x-section>
@endif
