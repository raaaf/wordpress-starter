<!DOCTYPE html>
<!--[if IE 7]>
<html class="ie ie7" @php( language_attributes() )>
<![endif]-->
<!--[if IE 8]>
<html class="ie ie8" @php( language_attributes() )>
<![endif]-->
<!--[if !(IE 7) & !(IE 8)]><!-->
<html @php( language_attributes() ) class="no-js">
<!--<![endif]-->

<head>
    <meta charset="@php( bloginfo( 'charset' ))">
    <meta name="viewport" content="width=device-width">
    <title>{{ wp_title( '|', true, 'right' ) }}</title>
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <link rel="pingback" href="{{ esc_url( get_bloginfo( 'pingback_url' )) }}">

    <link rel="prefetch" href="{{ esc_url(get_stylesheet_directory_uri()) }}/dist/app.css" as="style">
    <link rel="prefetch" href="{{ esc_url(get_stylesheet_directory_uri()) }}/dist/app.js" as="script">

    {{-- create your favicon-package here: https://realfavicongenerator.net/ --}}

    {{-- <link rel="apple-touch-icon" sizes="180x180" href="{{ esc_url(get_stylesheet_directory_uri()) }}/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ esc_url(get_stylesheet_directory_uri()) }}/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ esc_url(get_stylesheet_directory_uri()) }}/favicons/favicon-16x16.png">
    <link rel="manifest" href="{{ esc_url(get_stylesheet_directory_uri()) }}/favicons/site.webmanifest">
    <link rel="mask-icon" href="{{ esc_url(get_stylesheet_directory_uri()) }}/favicons/safari-pinned-tab.svg" color="#15b48d">
    <link rel="shortcut icon" href="{{ esc_url(get_stylesheet_directory_uri()) }}/favicons/favicon.ico">
    <meta name="msapplication-TileColor" content="#00aba9">
    <meta name="msapplication-config" content="{{ esc_url(get_stylesheet_directory_uri()) }}/favicons/browserconfig.xml">
    <meta name="theme-color" content="#ffffff"> --}}

    {{-- remove no-js from html tag --}}
    <script>
        document.documentElement.classList.remove('no-js');

    </script>

    {{-- Plausible is your choice? Uncomment and change ur data-domain. Otherwise delete those lines. --}}

    {{-- <script async defer data-domain="YOUR DATA-DOMAIN" src="https://plausible.io/js/plausible.outbound-links.js"></script>
    <script>
        window.plausible = window.plausible || function() {
            (window.plausible.q = window.plausible.q || []).push(arguments)
        }

    </script> --}}

    @php(wp_head())
</head>

<body @php(body_class( 'bg-white antialiased has-primary-text-color' ))>

    @php(wp_body_open())

    <header class="p-4 md:p-8">
        @include('partials.header-menu')
    </header>
