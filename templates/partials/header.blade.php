<!DOCTYPE html>
@php
    $colorScheme = function_exists('get_field') ? (get_field('color_scheme', 'option') ?: 'system') : 'system';
@endphp
<html @php(language_attributes()) class="no-js"@if($colorScheme !== 'system') data-theme="{{ esc_attr($colorScheme) }}"@endif>

<head>
    <meta charset="@php(bloginfo('charset'))">
    <meta name="viewport" content="width=device-width">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <link rel="pingback" href="{{ esc_url(get_bloginfo('pingback_url')) }}">

    {{-- Resource Hints for Performance --}}
    <link rel="dns-prefetch" href="//api.pirsch.io">

    {{-- Preload critical assets --}}
    @if(!WP_DEBUG || !\WordpressStarter\Vite::isDevServerRunning())
        <link rel="preload" href="{{ \WordpressStarter\Vite::getAssetUrl('resources/css/app.css') }}" as="style">
        <link rel="preload" href="{{ \WordpressStarter\Vite::getAssetUrl('resources/js/app.ts') }}" as="script" crossorigin>
    @endif

    {{-- Remove no-js class when JS is enabled --}}
    <script nonce="{{ $GLOBALS['csp_nonce'] ?? '' }}">
        document.documentElement.classList.remove('no-js');
    </script>

    @php(wp_head())
</head>

<body @php(body_class('bg-surface antialiased'))>

    @php(wp_body_open())

    {{-- Skip Link for Accessibility --}}
    <a href="#main-content"
        class="absolute top-0 left-0 p-2 text-content-inverse no-underline transform -translate-y-full bg-surface-inverse focus:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-line-focus rounded">
        {{ __('Zum Inhalt springen', 'wp-starter') }}
    </a>

    @php
        $headerSticky = function_exists('get_field') ? get_field('header_sticky', 'option') : false;
    @endphp

    <header class="px-4 md:px-8 bg-surface {{ $headerSticky ? 'sticky top-0 z-50 shadow-sm' : '' }}" role="banner">
        <div class="container mx-auto">
            <nav role="navigation" aria-label="Main Navigation">
                @include('partials.header-menu')
            </nav>
        </div>
    </header>
