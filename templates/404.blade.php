@extends('layouts.app')

@section('content')
    <x-section background="primary" padding="xl">
        <div class="text-center max-w-2xl mx-auto">
            {{-- 404 Icon --}}
            <div class="mb-8">
                <svg class="w-24 h-24 mx-auto text-content-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            {{-- Error Message --}}
            <h1 class="text-display text-content-tertiary mb-4">404</h1>
            <h2 class="mb-4">
                {{ __('Seite nicht gefunden', 'wp-starter') }}
            </h2>
            <p class="text-lg text-content-secondary mb-8">
                {{ __('Die angeforderte Seite existiert nicht oder wurde verschoben.', 'wp-starter') }}
            </p>

            {{-- Search Form --}}
            <div class="mb-8">
                <form role="search" method="get" action="{{ esc_url(home_url('/')) }}" class="flex items-end gap-2 max-w-md mx-auto">
                    <div class="flex-1">
                        <x-input
                            type="search"
                            name="s"
                            id="search-404"
                            :label="__('Suchbegriff', 'wp-starter')"
                            :placeholder="__('Suchen...', 'wp-starter')"
                            size="lg"
                        />
                    </div>
                    <x-button type="submit" variant="primary" size="lg" :title="__('Suchen', 'wp-starter')" />
                </form>
            </div>

            {{-- Navigation Links --}}
            <div class="flex flex-wrap justify-center gap-4">
                <x-button :url="home_url('/')" variant="primary" size="lg" iconLeft="home" :title="__('Zur Startseite', 'wp-starter')" />

                <x-button
                    x-data
                    x-show="window.history.length > 1"
                    @click.prevent="window.history.back()"
                    variant="secondary"
                    size="lg"
                    iconLeft="arrow-left"
                    :title="__('Zurück', 'wp-starter')"
                />
            </div>
        </div>
    </x-section>
@endsection
