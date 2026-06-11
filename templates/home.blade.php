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
            {{-- FEATURED POST --}}
            @php the_post(); @endphp
            <div class="mb-16">
                <article>
                    <x-card variant="filled" hoverable padding="none" class="group relative overflow-hidden">
                        <div class="grid md:grid-cols-2">
                            {{-- Image --}}
                            <div class="aspect-[4/3] md:aspect-auto md:min-h-[400px] overflow-hidden">
                                @if (has_post_thumbnail())
                                    {!! get_the_post_thumbnail(null, 'large', [
                                        'class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105',
                                        'loading' => 'eager',
                                    ]) !!}
                                @else
                                    <div class="w-full h-full bg-surface-tertiary flex items-center justify-center">
                                        <x-icon name="eye" class="w-16 h-16 text-content-tertiary" />
                                    </div>
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="p-8 md:p-12 flex flex-col justify-center">
                                <div class="flex flex-wrap gap-2 mb-4">
                                    @if (has_category())
                                        @php $firstCategory = get_the_category()[0]; @endphp
                                        <x-badge variant="brand">{{ $firstCategory->name }}</x-badge>
                                    @endif
                                    <x-badge variant="gray" style="outline">{{ get_reading_time() }}</x-badge>
                                </div>

                                <h2 class="mb-4 transition-colors group-hover:text-content-brand">
                                    {{ get_the_title() }}
                                </h2>

                                <p class="text-content-secondary mb-6 line-clamp-3">
                                    {!! get_the_excerpt() !!}
                                </p>

                                <div class="flex items-center justify-between mt-auto">
                                    <x-link :url="get_permalink()" iconRight="chevron-right" class="relative z-20">
                                        {{ __('Weiterlesen', 'wp-starter') }}
                                    </x-link>
                                    <time datetime="{{ get_the_date('c') }}" class="text-sm text-content-tertiary">
                                        {{ get_the_date() }}
                                    </time>
                                </div>
                            </div>
                        </div>

                        {{-- Stretched link --}}
                        <a href="{{ get_permalink() }}" class="absolute inset-0 z-10" aria-label="{{ __('Weiterlesen:', 'wp-starter') }} {{ get_the_title() }}">
                            <span class="sr-only">{{ get_the_title() }}</span>
                        </a>
                    </x-card>
                </article>
            </div>

            {{-- RECENT POSTS (with images) --}}
            @if (have_posts())
                <div class="mb-16">
                    <div class="grid md:grid-cols-2 gap-6">
                        @for ($i = 0; $i < 2 && have_posts(); $i++)
                            @php the_post(); @endphp
                            <article>
                                <x-card variant="filled" hoverable padding="none" class="group relative h-full">
                                    @if (has_post_thumbnail())
                                        <div class="aspect-[2/1] overflow-hidden">
                                            {!! get_the_post_thumbnail(null, 'card-video', [
                                                'class' => 'w-full h-full object-cover transition-transform duration-300 group-hover:scale-105',
                                                'loading' => 'lazy',
                                            ]) !!}
                                        </div>
                                    @endif

                                    <div class="p-6">
                                        <div class="flex flex-wrap gap-2 mb-3">
                                            @if (has_category())
                                                @php $firstCategory = get_the_category()[0]; @endphp
                                                <x-badge variant="brand" size="sm">{{ $firstCategory->name }}</x-badge>
                                            @endif
                                            <x-badge variant="gray" style="outline" size="sm">{{ get_reading_time() }}</x-badge>
                                        </div>

                                        <h3 class="text-h4 mb-3 transition-colors group-hover:text-content-brand">
                                            {{ get_the_title() }}
                                        </h3>

                                        <p class="text-content-secondary line-clamp-2 mb-4">
                                            {!! wp_trim_words(get_the_excerpt(), 20) !!}
                                        </p>

                                        <div class="flex items-center justify-between pt-4 border-t border-line">
                                            <x-link :url="get_permalink()" iconRight="chevron-right" size="sm" class="relative z-20">
                                                {{ __('Weiterlesen', 'wp-starter') }}
                                            </x-link>
                                            <time datetime="{{ get_the_date('c') }}" class="text-sm text-content-tertiary">
                                                {{ get_the_date() }}
                                            </time>
                                        </div>
                                    </div>

                                    {{-- Stretched link --}}
                                    <a href="{{ get_permalink() }}" class="absolute inset-0 z-10" aria-label="{{ __('Weiterlesen:', 'wp-starter') }} {{ get_the_title() }}">
                                        <span class="sr-only">{{ get_the_title() }}</span>
                                    </a>
                                </x-card>
                            </article>
                        @endfor
                    </div>
                </div>
            @endif

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
                                            {!! wp_trim_words(get_the_excerpt(), 30) !!}
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
