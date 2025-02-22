<!DOCTYPE html>
<html @php( language_attributes() ) class="no-js">
<head>
    <meta charset="@php( bloginfo( 'charset' ))">
    <meta name="viewport" content="width=device-width">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <link rel="pingback" href="{{ esc_url( get_bloginfo( 'pingback_url' )) }}">

    <link rel="preload" href="{{ esc_url(get_stylesheet_directory_uri()) }}/dist/app.css" as="style">
    <link rel="preload" href="{{ esc_url(get_stylesheet_directory_uri()) }}/dist/app.js" as="script">

    {{-- create your favicon-package here: https://realfavicongenerator.net/ --}}
    {{-- Uncomment and update these favicon links when ready for production
    <link rel="apple-touch-icon" sizes="180x180" href="{{ esc_url(get_stylesheet_directory_uri()) }}/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ esc_url(get_stylesheet_directory_uri()) }}/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ esc_url(get_stylesheet_directory_uri()) }}/favicons/favicon-16x16.png">
    <link rel="manifest" href="{{ esc_url(get_stylesheet_directory_uri()) }}/favicons/site.webmanifest">
    <link rel="mask-icon" href="{{ esc_url(get_stylesheet_directory_uri()) }}/favicons/safari-pinned-tab.svg" color="#15b48d">
    <link rel="shortcut icon" href="{{ esc_url(get_stylesheet_directory_uri()) }}/favicons/favicon.ico">
    <meta name="msapplication-TileColor" content="#00aba9">
    <meta name="msapplication-config" content="{{ esc_url(get_stylesheet_directory_uri()) }}/favicons/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
    --}}

    {{-- Remove no-js class to indicate JavaScript is enabled --}}
    <script>
        document.documentElement.classList.remove('no-js');
    </script>

    @php(wp_head())
</head>
<body @php(body_class( 'bg-white antialiased' ))>

    @php(wp_body_open())

    <header class="p-4 md:p-8">
        @include('partials.header-menu')
    </header>
