<!DOCTYPE html>
@php
    $colorScheme = \WordpressStarter\Acf\Fields::option('color_scheme', 'system');
@endphp
{{-- data-theme-storage-key: single source for the localStorage key, read by
     the inline script below and by theme-switcher.blade.php, so the two never
     drift apart. --}}
<html {!! get_language_attributes() !!} class="no-js" data-theme-storage-key="wp-starter-theme"@if($colorScheme !== 'system') data-theme="{{ esc_attr($colorScheme) }}"@endif>

<head>
    <meta charset="{{ get_bloginfo('charset') }}">
    @if($colorScheme === 'system')
        {{-- Gespeicherte Auswahl setzen, bevor das erste Pixel gemalt wird. Ohne
             das blitzt bei jedem Laden kurz der andere Modus auf. --}}
        <script nonce="{{ $GLOBALS['csp_nonce'] ?? '' }}">
            (function () {
                try {
                    var m = localStorage.getItem(document.documentElement.dataset.themeStorageKey || 'wp-starter-theme');
                    if (m === 'light' || m === 'dark') {
                        document.documentElement.setAttribute('data-theme', m);
                    }
                } catch (e) {}
            })();
        </script>
    @endif
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Browser chrome colour per scheme. Values are --bg-primary from
         resources/css/tokens.css (light: --color-gray-100 #F5F5F5, dark:
         --color-black #000000), the same token bg-surface resolves to. --}}
    <meta name="theme-color" content="#F5F5F5" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#000000" media="(prefers-color-scheme: dark)">
    <link rel="pingback" href="{{ esc_url(get_bloginfo('pingback_url')) }}">

    {{-- Preload critical assets. Font preloads are emitted by AssetOptimizationServiceProvider::addResourcePreloading. --}}
    @if(!WP_DEBUG || !\WordpressStarter\Vite::isDevServerRunning())
        <link rel="preload" href="{{ \WordpressStarter\Vite::getAssetUrl('resources/css/app.css') }}" as="style">
        <link rel="preload" href="{{ \WordpressStarter\Vite::getAssetUrl('resources/js/app.ts') }}" as="script" crossorigin>
    @endif

    {{-- Remove no-js class when JS is enabled --}}
    <script nonce="{{ $GLOBALS['csp_nonce'] ?? '' }}">
        document.documentElement.classList.remove('no-js');
    </script>

    @php wp_head(); @endphp
</head>

<body class="{{ implode(' ', get_body_class('bg-surface antialiased')) }}">

    @php wp_body_open(); @endphp

    {{-- Skip Link for Accessibility --}}
    <a href="#main-content"
        class="absolute top-0 left-0 p-2 text-content-inverse no-underline transform -translate-y-full bg-surface-inverse transition-transform duration-150 ease-[var(--motion-enter-ease)] motion-reduce:transition-none focus-visible:translate-y-0 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)] rounded">
        {{ __('Zum Inhalt springen', 'wp-starter') }}
    </a>

    @php
        $headerSticky = \WordpressStarter\Acf\Fields::option('header_sticky');
        $isLandingPage = \WordpressStarter\Acf\PageSettings::isLandingPage();
    @endphp

    <header class="bg-surface {{ $headerSticky ? 'sticky top-0 z-50 shadow-sm' : '' }}" role="banner">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($isLandingPage)
                <div>
                    @include('partials.header-menu')
                </div>
            @else
                <nav aria-label="{{ __('Hauptnavigation', 'wp-starter') }}">
                    @include('partials.header-menu')
                </nav>
            @endif
        </div>
    </header>

    {{-- Die Kopfhoehe steht in CSS als Rueckfallwert (80px), gemessen wird sie in
         app.ts. Bis das verzoegerte Modul lief, rechnete der Hero mit dem falschen
         Wert und sprang beim Nachtragen sichtbar. Deshalb hier, direkt hinter dem
         Kopf und vor dem ersten Anstrich, einmal messen; app.ts haelt den Wert
         danach ueber seinen ResizeObserver aktuell. --}}
    <script nonce="{{ $GLOBALS['csp_nonce'] ?? '' }}">
        (function () {
            var header = document.querySelector('header[role="banner"]');
            if (header) {
                document.documentElement.style.setProperty('--header-height', header.offsetHeight + 'px');
            }
        })();
    </script>
