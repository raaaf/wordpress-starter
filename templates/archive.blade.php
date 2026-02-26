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
                    {!! get_the_archive_description() !!}
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
            {{-- FEATURED POST (first/newest post) --}}
            @php the_post(); @endphp
            <article class="mb-12">
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

            {{-- BENTO GRID: Posts 2-3 (medium cards) --}}
            @if (have_posts())
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    @for ($i = 0; $i < 2 && have_posts(); $i++)
                        @php the_post(); @endphp
                        <article>
                            <x-card variant="filled" hoverable padding="none" class="group relative h-full">
                                @if (has_post_thumbnail())
                                    <div class="aspect-[16/10] overflow-hidden">
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
            @endif

            {{-- STANDARD GRID: Remaining posts --}}
            @if (have_posts())
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @while (have_posts())
                        @php the_post(); @endphp
                        <article>
                            <x-card variant="filled" hoverable padding="none" class="group relative h-full">
                                @if (has_post_thumbnail())
                                    <div class="aspect-video overflow-hidden">
                                        {!! get_the_post_thumbnail(null, 'card-video', [
                                            'class' => 'w-full h-full object-cover transition-transform duration-300 group-hover:scale-105',
                                            'loading' => 'lazy',
                                        ]) !!}
                                    </div>
                                @endif

                                <div class="p-5">
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        @if (has_category())
                                            @php $firstCategory = get_the_category()[0]; @endphp
                                            <x-badge variant="brand" size="sm">{{ $firstCategory->name }}</x-badge>
                                        @endif
                                        <x-badge variant="gray" style="outline" size="sm">{{ get_reading_time() }}</x-badge>
                                    </div>

                                    <h3 class="text-h5 mb-2 transition-colors group-hover:text-content-brand line-clamp-2">
                                        {{ get_the_title() }}
                                    </h3>

                                    <p class="text-sm text-content-secondary line-clamp-2 mb-3">
                                        {!! wp_trim_words(get_the_excerpt(), 15) !!}
                                    </p>

                                    <div class="flex items-center justify-between text-sm text-content-tertiary pt-3 border-t border-line">
                                        <time datetime="{{ get_the_date('c') }}">{{ get_the_date() }}</time>
                                    </div>
                                </div>

                                {{-- Stretched link --}}
                                <a href="{{ get_permalink() }}" class="absolute inset-0 z-10" aria-label="{{ __('Weiterlesen:', 'wp-starter') }} {{ get_the_title() }}">
                                    <span class="sr-only">{{ get_the_title() }}</span>
                                </a>
                            </x-card>
                        </article>
                    @endwhile
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
            @if ($pagination)
                <nav class="mt-16" aria-label="{{ __('Archiv-Navigation', 'wp-starter') }}">
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
                <h2 class="text-h3 mb-4">
                    {{ __('Keine Beiträge gefunden', 'wp-starter') }}
                </h2>
                <p class="text-content-secondary mb-8 max-w-md mx-auto">
                    {{ __('In diesem Archiv sind noch keine Beiträge vorhanden.', 'wp-starter') }}
                </p>
                <x-button :url="home_url('/')" :title="__('Zur Startseite', 'wp-starter')" variant="primary" size="lg" />
            </div>
        @endif
    </x-section>
@endsection
