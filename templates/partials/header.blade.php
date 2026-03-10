<!DOCTYPE html>
@php
    $colorScheme = \WordpressStarter\Acf\Fields::option('color_scheme', 'system');
@endphp
<html {!! get_language_attributes() !!} class="no-js"@if($colorScheme !== 'system') data-theme="{{ esc_attr($colorScheme) }}"@endif>

<head>
    <meta charset="{{ get_bloginfo('charset') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="pingback" href="{{ esc_url(get_bloginfo('pingback_url')) }}">

    {{-- Preload critical assets --}}
    @if(!WP_DEBUG || !\WordpressStarter\Vite::isDevServerRunning())
        <link rel="preload" href="{{ \WordpressStarter\Vite::getAssetUrl('resources/css/app.css') }}" as="style">
        <link rel="preload" href="{{ \WordpressStarter\Vite::getAssetUrl('resources/js/app.ts') }}" as="script" crossorigin>
        {{-- Preload critical fonts (regular weights only) to reduce font rendering delay --}}
        @php
            $fontDir = get_template_directory() . '/resources/fonts';
            $fontUri = get_template_directory_uri() . '/resources/fonts';
            $cacheKey = 'theme_woff2_files_' . get_template();
            $woff2Files = wp_cache_get($cacheKey, 'theme');
            if ($woff2Files === false) {
                $woff2Files = is_dir($fontDir) ? (glob($fontDir . '/*.woff2') ?: []) : [];
                wp_cache_set($cacheKey, $woff2Files, 'theme', DAY_IN_SECONDS);
            }
            // Only preload regular weight fonts (not bold/light variants) for critical rendering
            $criticalFonts = array_filter($woff2Files, function($font) {
                $basename = strtolower(basename($font));
                // Preload regular weights - exclude bold, light, thin, medium, semibold variants
                return preg_match('/(regular|reg-|reg\.|-400)/i', $basename)
                    && !preg_match('/(bold|bol|light|thin|medium|semi|700|300|500|600)/i', $basename);
            });
        @endphp
        @foreach($criticalFonts as $font)
            <link rel="preload" href="{{ $fontUri }}/{{ basename($font) }}" as="font" type="font/woff2" crossorigin>
        @endforeach
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
