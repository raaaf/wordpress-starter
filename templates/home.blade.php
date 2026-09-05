@extends('layouts.app')

@php
    global $wp_query;
@endphp

@section('content')
    {{-- Blog Header --}}
    <x-section background="primary" padding="lg">
        <div class="max-w-2xl mx-auto text-center">
            <h1 class="mb-4">
                {{ get_field('blog_title', 'option') ?: __('Blog', 'wp-starter') }}
            </h1>
            @if ($blogDescription = get_field('blog_description', 'option'))
                <p class="text-lg text-content-secondary">
                    {{ $blogDescription }}
                </p>
            @endif
        </div>
    </x-section>

    <x-section padding="lg">
        @if (have_posts())
            @include('partials.post-loop', [
                'bentoAspect' => 'aspect-[2/1]',
                'wrapSections' => true,
                'standardGrid' => false,
            ])

            {{-- MORE POSTS (text-only list for faster scanning) --}}
            @if (have_posts())
                <div>
                    <div class="grid gap-4">
                        @while (have_posts())
                            @php the_post(); @endphp
                            <article>
                                <x-card variant="filled" hoverable padding="none" class="group relative">
                                    <a href="{{ get_permalink() }}" class="block p-5 md:p-6">
                                        <div class="flex flex-wrap items-center gap-2 mb-2">
                                            @if (has_category())
                                                @php $firstCategory = get_the_category()[0]; @endphp
                                                <x-badge variant="brand" size="sm">{{ $firstCategory->name }}</x-badge>
                                            @endif
                                            <x-badge variant="gray" style="outline" size="sm">{{ get_reading_time() }}</x-badge>
                                            <span class="text-sm text-content-tertiary">{{ get_the_date() }}</span>
                                        </div>
                                        <h3 class="text-h5 mb-2 transition-colors group-hover:text-content-brand">
                                            {{ get_the_title() }}
                                        </h3>
                                        <p class="text-content-secondary line-clamp-2">
                                            {{ wp_trim_words(get_the_excerpt(), 30) }}
                                        </p>
                                    </a>
                                </x-card>
                            </article>
                        @endwhile
                    </div>
                </div>
            @endif

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
                'ariaLabel'  => __('Blog-Navigation', 'wp-starter'),
                'navClass'   => 'mt-16 pt-8 border-t border-line',
            ])
        @else
            {{-- No Posts --}}
            @include('partials.empty-state', [
                'title'       => __('Keine Beiträge gefunden', 'wp-starter'),
                'text'        => __('Der Blog enthält noch keine Beiträge.', 'wp-starter'),
                'buttonLabel' => __('Zur Startseite', 'wp-starter'),
                'buttonUrl'   => home_url('/'),
            ])
        @endif
    </x-section>
@endsection
