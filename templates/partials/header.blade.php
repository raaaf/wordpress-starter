<!DOCTYPE html>
<html @php(language_attributes()) class="no-js">

<head>
    <meta charset="@php(bloginfo('charset'))">
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="Content-Security-Policy"
        content="default-src 'self'; script-src 'self' 'unsafe-inline' https://trusted.cdn.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com;">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <link rel="pingback" href="{{ esc_url(get_bloginfo('pingback_url')) }}">

    {{-- Preload critical assets --}}
    <link rel="preload" href="{{ theme_get_mix_asset('dist/app.css') }}" as="style">
    <link rel="preload" href="{{ theme_get_mix_asset('dist/app.js') }}" as="script">

    {{-- Inline critical CSS --}}
    <style>
        {{ file_get_contents(get_stylesheet_directory() . '/dist/critical.css') }}
    </style>

    {{-- Remove no-js class when JS is enabled --}}
    <script>
        document.documentElement.classList.remove('no-js');
    </script>

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "{{ get_bloginfo('name') }}",
        "url": "{{ home_url() }}",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "{{ home_url() }}/?s={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>

    @php(wp_head())
</head>

<body @php(body_class('bg-white antialiased'))>

    @php(wp_body_open())

    {{-- Skip Link for Accessibility --}}
    <a href="#main-content" class="skip-link">Skip to content</a>

    <header class="p-4 md:p-8" role="banner">
        <nav role="navigation" aria-label="Main Navigation">
            @include('partials.header-menu')
        </nav>
    </header>
