@extends('layouts.app')

@php
    global $wp_query;
@endphp

@section('content')
    <x-section background="secondary" padding="lg">
        <div class="max-w-2xl mx-auto text-center">
            {{-- Archive Title --}}
            <h1 class="text-3xl md:text-4xl font-bold text-content mb-4">
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
                    {{ __('Archiv', 'wp-starter') }}
                @endif
            </h1>

            {{-- Archive Description --}}
            @if (get_the_archive_description())
                <div class="text-lg text-content-secondary prose max-w-none">
                    {!! get_the_archive_description() !!}
                </div>
            @endif
        </div>
    </x-section>

    <x-section padding="lg">
        @if (have_posts())
            {{-- Results Count --}}
            <p class="text-content-secondary mb-8">
                {{ sprintf(_n('%d Beitrag', '%d Beiträge', $wp_query->found_posts, 'wp-starter'), $wp_query->found_posts) }}
            </p>

            {{-- Archive Posts --}}
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                @while (have_posts())
                    @php the_post(); @endphp
                    <x-card variant="filled" hoverable :url="get_permalink()" class="flex flex-col">
                        @if (has_post_thumbnail())
                            <div class="aspect-video overflow-hidden">
                                {!! get_the_post_thumbnail(null, 'card-video', ['class' => 'w-full h-full object-cover hover:scale-105 transition-transform duration-300']) !!}
                            </div>
                        @endif
                        <div class="p-6 flex flex-col flex-1">
                            {{-- Categories --}}
                            @if (has_category())
                                <div class="flex flex-wrap gap-2 mb-3">
                                    @foreach (get_the_category() as $category)
                                        <span class="text-xs font-medium text-content-brand">
                                            {{ $category->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Title --}}
                            <h2 class="text-xl font-semibold mb-3 text-content">
                                {{ get_the_title() }}
                            </h2>

                            {{-- Excerpt --}}
                            <p class="text-content-secondary line-clamp-3 mb-4 flex-1">
                                {!! get_the_excerpt() !!}
                            </p>

                            {{-- Meta --}}
                            <div class="flex items-center justify-between text-sm text-content-tertiary pt-4 border-t border-line">
                                <time datetime="{{ get_the_date('c') }}">{{ get_the_date() }}</time>
                                <span>{{ get_the_author() }}</span>
                            </div>
                        </div>
                    </x-card>
                @endwhile
            </div>

            {{-- Pagination --}}
            @php
                $pagination = paginate_links([
                    'type' => 'array',
                    'prev_text' => __('Zurück', 'wp-starter'),
                    'next_text' => __('Weiter', 'wp-starter'),
                ]);
            @endphp
            @if ($pagination)
                <nav class="mt-12" aria-label="{{ __('Archiv-Navigation', 'wp-starter') }}">
                    <ul class="flex flex-wrap justify-center gap-2">
                        @foreach ($pagination as $link)
                            <li>{!! str_replace(
                                ['page-numbers', 'current'],
                                ['px-4 py-2 rounded-lg border border-line text-content hover:bg-surface-secondary transition-colors', 'bg-surface-brand text-content-inverse border-surface-brand hover:bg-surface-brand'],
                                $link
                            ) !!}</li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        @else
            {{-- No Posts --}}
            <div class="text-center py-12">
                <svg class="w-16 h-16 mx-auto text-content-tertiary mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <h2 class="text-2xl font-semibold text-content mb-4">
                    {{ __('Keine Beiträge gefunden', 'wp-starter') }}
                </h2>
                <p class="text-content-secondary mb-8 max-w-md mx-auto">
                    {{ __('In diesem Archiv sind noch keine Beiträge vorhanden.', 'wp-starter') }}
                </p>
                <x-button :url="home_url('/')" :title="__('Zur Startseite', 'wp-starter')" variant="primary" size="lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </x-button>
            </div>
        @endif
    </x-section>
@endsection
