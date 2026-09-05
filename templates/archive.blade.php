@extends('layouts.app')

@php
    global $wp_query;
@endphp

@section('content')
    <x-section background="primary" padding="lg">
        <div class="max-w-2xl mx-auto text-center">
            {{-- Archive Title --}}
            <h1 class="mb-4">
                @if (is_category())
                    {{ sprintf(__('Kategorie: %s', 'wp-starter'), single_cat_title('', false)) }}
                @elseif (is_tag())
                    {{ sprintf(__('Schlagwort: %s', 'wp-starter'), single_tag_title('', false)) }}
                @elseif (is_author())
                    {{ sprintf(__('Autor: %s', 'wp-starter'), get_the_author()) }}
                @elseif (is_year())
                    {{ sprintf(__('Jahr: %s', 'wp-starter'), get_the_date('Y')) }}
                @elseif (is_month())
                    {{ sprintf(__('Monat: %s', 'wp-starter'), get_the_date('F Y')) }}
                @elseif (is_day())
                    {{ sprintf(__('Tag: %s', 'wp-starter'), get_the_date()) }}
                @elseif (is_post_type_archive())
                    {{ post_type_archive_title('', false) }}
                @else
                    {{ __('Blog', 'wp-starter') }}
                @endif
            </h1>

            {{-- Archive Description --}}
            @if (get_the_archive_description())
                <div class="text-lg text-content-secondary prose max-w-none">
                    {!! wp_kses_post(get_the_archive_description()) !!}
                </div>
            @endif

            {{-- Post Count --}}
            <p class="text-content-tertiary mt-4">
                {{ sprintf(_n('%d Beitrag', '%d Beiträge', $wp_query->found_posts, 'wp-starter'), $wp_query->found_posts) }}
            </p>
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
                'ariaLabel'  => __('Archiv-Navigation', 'wp-starter'),
                'navClass'   => 'mt-16',
            ])
        @else
            {{-- No Posts --}}
            @include('partials.empty-state', [
                'title'       => __('Keine Beiträge gefunden', 'wp-starter'),
                'text'        => __('In diesem Archiv sind noch keine Beiträge vorhanden.', 'wp-starter'),
                'buttonLabel' => __('Zur Startseite', 'wp-starter'),
                'buttonUrl'   => home_url('/'),
            ])
        @endif
    </x-section>
@endsection
