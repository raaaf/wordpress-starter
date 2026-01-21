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
                {{ __('Seite nicht gefunden', 'wp-starter') }}
            </h2>
            <p class="text-lg text-content-secondary mb-8">
                {{ __('Die angeforderte Seite existiert nicht oder wurde verschoben.', 'wp-starter') }}
            </p>

            {{-- Search Form --}}
            <div class="mb-8">
                <form role="search" method="get" action="{{ esc_url(home_url('/')) }}" class="flex gap-2 max-w-md mx-auto">
                    <div class="flex-1">
                        <x-input
                            type="search"
                            name="s"
                            id="search-404"
                            :placeholder="__('Suchen...', 'wp-starter')"
                            size="lg"
                        />
                    </div>
                    <x-button type="submit" variant="primary" size="lg" :title="__('Suchen', 'wp-starter')" />
                </form>
            </div>

            {{-- Navigation Links --}}
            <div class="flex flex-wrap justify-center gap-4">
                <x-button :url="home_url('/')" variant="primary" size="lg" title="">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    {{ __('Zur Startseite', 'wp-starter') }}
                </x-button>

                <x-button variant="secondary" size="lg" title="" onclick="history.back()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('Zurück', 'wp-starter') }}
                </x-button>
            </div>
        </div>
    </x-section>
@endsection
