@extends('layouts.app')

@php
    global $wp_query;
@endphp

@section('content')
    <x-section background="primary" padding="lg">
        <div class="max-w-2xl mx-auto text-center">
            {{-- index.blade.php is WordPress' catch-all fallback in the template hierarchy;
                 archive.blade.php and home.blade.php handle is_archive()/is_home() before this
                 template is ever reached, so branching on them here was dead code. --}}
            <h1 class="mb-4">
                {{ get_bloginfo('name') }}
            </h1>
            @if (get_bloginfo('description'))
                <p class="text-lg text-content-secondary">
                    {{ get_bloginfo('description') }}
                </p>
            @endif
        </div>
    </x-section>

    <x-section padding="lg">
        @if (have_posts())
            @include('partials.post-loop')

            {{-- Pagination --}}
            @php
                $pagination = paginate_links([
                    'type' => 'array',
                    'prev_text' => __('Zurück', 'wp-starter'),
                    'next_text' => __('Weiter', 'wp-starter'),
                ]);
            @endphp
            @include('partials.pagination', [
                'pagination' => $pagination,
                'ariaLabel'  => __('Navigation', 'wp-starter'),
                'navClass'   => 'mt-16',
            ])
        @else
            {{-- No Posts --}}
            @include('partials.empty-state', [
                'title'       => __('Keine Beiträge gefunden', 'wp-starter'),
                'text'        => __('Es sind noch keine Inhalte vorhanden.', 'wp-starter'),
                'buttonLabel' => __('Zur Startseite', 'wp-starter'),
                'buttonUrl'   => home_url('/'),
            ])
        @endif
    </x-section>
@endsection
