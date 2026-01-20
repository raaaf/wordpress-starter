<!DOCTYPE html>
<html @php(language_attributes()) class="no-js">

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
        <link rel="preload" href="{{ \WordpressStarter\Vite::getAssetUrl('resources/js/app.js') }}" as="script">
    @endif

    {{-- Remove no-js class when JS is enabled --}}
    <script>
        document.documentElement.classList.remove('no-js');
    </script>

    @php(wp_head())
</head>

<body @php(body_class('bg-surface antialiased'))>

    @php(wp_body_open())

    {{-- Skip Link for Accessibility --}}
    <a href="#main-content"
        class="absolute top-0 left-0 p-2 text-content-inverse transform -translate-y-full bg-surface-inverse focus:translate-y-0">
        Skip to content
    </a>

    <header class="p-4 md:p-8" role="banner">
        <nav role="navigation" aria-label="Main Navigation">
            @include('partials.header-menu')
        </nav>
    </header>
