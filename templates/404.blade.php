@extends('layouts.app')

@section('content')
    <x-section background="secondary" padding="xl">
        <div class="text-center max-w-2xl mx-auto">
            {{-- 404 Icon --}}
            <div class="mb-8">
                <svg class="w-24 h-24 mx-auto text-content-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            {{-- Error Message --}}
            <h1 class="text-6xl md:text-8xl font-bold text-content-tertiary mb-4">404</h1>
            <h2 class="text-2xl md:text-3xl font-semibold text-content mb-4">
                {{ esc_html__('Seite nicht gefunden', 'wp-starter') }}
            </h2>
            <p class="text-lg text-content-secondary mb-8">
                {{ esc_html__('Die angeforderte Seite existiert nicht oder wurde verschoben.', 'wp-starter') }}
            </p>

            {{-- Search Form --}}
            <div class="mb-8">
                <form role="search" method="get" action="{{ esc_url(home_url('/')) }}" class="flex gap-2 max-w-md mx-auto">
                    <label for="search-404" class="sr-only">{{ esc_html__('Suche', 'wp-starter') }}</label>
                    <input
                        type="search"
                        id="search-404"
                        name="s"
                        placeholder="{{ esc_attr__('Suchen...', 'wp-starter') }}"
                        class="flex-1 px-4 py-3 rounded-lg border border-line bg-surface text-content placeholder-content-tertiary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-line-focus"
                    >
                    <button
                        type="submit"
                        class="px-6 py-3 rounded-lg bg-surface-brand text-content-inverse font-medium hover:bg-surface-brand-hover transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-line-focus focus-visible:ring-offset-2"
                    >
                        {{ esc_html__('Suchen', 'wp-starter') }}
                    </button>
                </form>
            </div>

            {{-- Navigation Links --}}
            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ esc_url(home_url('/')) }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-surface-brand text-content-inverse font-medium hover:bg-surface-brand-hover transition-colors no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-line-focus focus-visible:ring-offset-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    {{ esc_html__('Zur Startseite', 'wp-starter') }}
                </a>

                <button
                    onclick="history.back()"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-lg border-2 border-line text-content font-medium hover:bg-surface-secondary transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-line-focus focus-visible:ring-offset-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ esc_html__('Zurück', 'wp-starter') }}
                </button>
            </div>
        </div>
    </x-section>
@endsection
