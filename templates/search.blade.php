@extends('layouts.app')

@php
    global $wp_query;
@endphp

@section('content')
    <x-section background="secondary" padding="lg">
        <div class="max-w-2xl mx-auto text-center">
            {{-- Search Icon --}}
            <div class="mb-6">
                <svg class="w-16 h-16 mx-auto text-content-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            {{-- Search Title --}}
            <h1 class="text-h1 text-content mb-2">
                {{ __('Suchergebnisse', 'wp-starter') }}
            </h1>
            <p class="text-lg text-content-secondary mb-6">
                {{ __('Ergebnisse für:', 'wp-starter') }} "{{ get_search_query() }}"
            </p>

            {{-- Search Form --}}
            <form role="search" method="get" action="{{ esc_url(home_url('/')) }}" class="flex gap-2 max-w-md mx-auto">
                <div class="flex-1">
                    <x-input
                        type="search"
                        name="s"
                        id="search-results"
                        :value="get_search_query()"
                        :placeholder="__('Suchen...', 'wp-starter')"
                        size="lg"
                    />
                </div>
                <x-button type="submit" variant="primary" size="lg" :title="__('Suchen', 'wp-starter')" />
            </form>
        </div>
    </x-section>

    <x-section padding="lg">
        @if (have_posts())
            {{-- Results Count --}}
            <p class="text-content-secondary mb-8">
                {{ sprintf(_n('%d Ergebnis gefunden', '%d Ergebnisse gefunden', $wp_query->found_posts, 'wp-starter'), $wp_query->found_posts) }}
            </p>

            {{-- Search Results --}}
            <div class="space-y-6">
                @while (have_posts())
                    @php the_post(); @endphp
                    <article class="p-6 bg-surface-secondary rounded-lg hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-4">
                            @if (has_post_thumbnail())
                                <a href="{{ get_permalink() }}" class="shrink-0">
                                    {!! get_the_post_thumbnail(null, 'avatar', ['class' => 'w-24 h-24 object-cover rounded-lg']) !!}
                                </a>
                            @endif
                            <div class="flex-1 min-w-0">
                                {{-- Post Type Badge --}}
                                @php
                                    $post_type_obj = get_post_type_object(get_post_type());
                                    $post_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : get_post_type();
                                @endphp
                                <x-badge variant="gray" size="sm" class="mb-2">{{ $post_type_label }}</x-badge>

                                {{-- Title --}}
                                <h2 class="text-h4 mb-2">
                                    <a href="{{ get_permalink() }}" class="text-content hover:text-content-brand transition-colors">
                                        {{ get_the_title() }}
                                    </a>
                                </h2>

                                {{-- Meta --}}
                                <div class="text-sm text-content-tertiary mb-3">
                                    <time datetime="{{ get_the_date('c') }}">{{ get_the_date() }}</time>
                                    @if (get_post_type() === 'post')
                                        <span class="mx-2">|</span>
                                        <span>{{ get_the_author() }}</span>
                                    @endif
                                </div>

                                {{-- Excerpt --}}
                                <p class="text-content-secondary line-clamp-2">
                                    {!! get_the_excerpt() !!}
                                </p>
                            </div>
                        </div>
                    </article>
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
                <nav class="mt-12" aria-label="{{ __('Suchergebnis-Navigation', 'wp-starter') }}">
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
            {{-- No Results --}}
            <div class="text-center py-12">
                <svg class="w-16 h-16 mx-auto text-content-tertiary mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-h3 text-content mb-4">
                    {{ __('Keine Ergebnisse gefunden', 'wp-starter') }}
                </h2>
                <p class="text-content-secondary mb-8 max-w-md mx-auto">
                    {{ __('Für Ihre Suche konnten leider keine passenden Inhalte gefunden werden. Versuchen Sie es mit anderen Suchbegriffen.', 'wp-starter') }}
                </p>
                <x-button :url="home_url('/')" variant="primary" size="lg" iconLeft="home" :title="__('Zur Startseite', 'wp-starter')" />
            </div>
        @endif
    </x-section>
@endsection
