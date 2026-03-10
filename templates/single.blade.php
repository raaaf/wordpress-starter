@extends('layouts.app')

@section('content')
    @if (have_posts())
        @while (have_posts()) @php the_post(); @endphp
            <article class="single-post container mx-auto max-w-3xl px-4 py-16 md:py-24">
                <header class="mb-8">
                    <x-link :url="get_post_type_archive_link('post')" variant="dark" iconLeft="chevron-left" size="sm" class="mb-4">
                        {{ __('Zurück zur Übersicht', 'wp-starter') }}
                    </x-link>
                    <h1 class="mb-4">{{ get_the_title() }}</h1>
                    <div class="flex items-center gap-3">
                        <x-badge variant="gray" style="outline">{{ get_the_date() }}</x-badge>
                        <x-badge variant="gray" style="outline">{{ get_reading_time() }}</x-badge>
                    </div>
                </header>

                @if (has_post_thumbnail())
                    <figure class="mb-8">
                        {!! get_the_post_thumbnail(null, 'content', ['class' => 'w-full h-auto rounded-lg']) !!}
                    </figure>
                @endif

                <div class="prose max-w-none">
                    @php the_content(); @endphp
                </div>
            </article>

            {{-- Post Navigation --}}
            @php
                $prevPost = get_previous_post();
                $nextPost = get_next_post();
                $hasPrev = !empty($prevPost);
                $hasNext = !empty($nextPost);
            @endphp
            @if ($hasPrev || $hasNext)
                <nav class="container mx-auto max-w-7xl px-4 pb-16 md:pb-24 pt-8 border-t border-line" aria-label="{{ __('Beitragsnavigation', 'wp-starter') }}">
                    <div class="flex justify-between items-start gap-8">
                        <div class="flex-1">
                            @if ($hasPrev)
                                <span class="text-body-small text-content-secondary mb-2 block">{{ __('Vorheriger Beitrag', 'wp-starter') }}</span>
                                <a href="{{ get_permalink($prevPost) }}" aria-label="{{ __('Vorheriger Beitrag', 'wp-starter') }}: {{ get_the_title($prevPost) }}" class="inline-flex items-center gap-1.5 text-content hover:text-content-brand transition-colors">
                                    <x-icon name="chevron-left" class="w-4 h-4" />
                                    {{ get_the_title($prevPost) }}
                                </a>
                            @endif
                        </div>
                        <div class="flex-1 text-right">
                            @if ($hasNext)
                                <span class="text-body-small text-content-secondary mb-2 block">{{ __('Nächster Beitrag', 'wp-starter') }}</span>
                                <a href="{{ get_permalink($nextPost) }}" aria-label="{{ __('Nächster Beitrag', 'wp-starter') }}: {{ get_the_title($nextPost) }}" class="inline-flex items-center justify-end gap-1.5 text-content hover:text-content-brand transition-colors">
                                    {{ get_the_title($nextPost) }}
                                    <x-icon name="chevron-right" class="w-4 h-4" />
                                </a>
                            @endif
                        </div>
                    </div>
                </nav>
            @endif
        @endwhile
    @endif
@endsection
