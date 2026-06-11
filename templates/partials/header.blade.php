<!DOCTYPE html>
@php
    $colorScheme = \WordpressStarter\Acf\Fields::option('color_scheme', 'system');
@endphp
<html {!! get_language_attributes() !!} class="no-js"@if($colorScheme !== 'system') data-theme="{{ esc_attr($colorScheme) }}"@endif>

<head>
    <meta charset="{{ get_bloginfo('charset') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
        class="absolute top-0 left-0 p-2 text-content-inverse no-underline transform -translate-y-full bg-surface-inverse focus-visible:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-line-focus rounded">
        {{ __('Zum Inhalt springen', 'wp-starter') }}
    </a>

    @php
        $headerSticky = \WordpressStarter\Acf\Fields::option('header_sticky');
    @endphp

    <header class="bg-surface {{ $headerSticky ? 'sticky top-0 z-50 shadow-sm' : '' }}" role="banner">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav aria-label="{{ __('Hauptnavigation', 'wp-starter') }}">
                @include('partials.header-menu')
            </nav>
        </div>
    </header>
