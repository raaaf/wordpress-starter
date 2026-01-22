@extends('layouts.app')

@section('content')
    @if (have_posts())
        @while (have_posts()) @php(the_post())
            <article class="single-post container mx-auto max-w-3xl px-4 py-16 md:py-24">
                <header class="mb-8">
                    <h1 class="text-4xl font-bold mb-4">{{ get_the_title() }}</h1>
                    <div class="flex items-center gap-3">
                        <x-badge variant="gray" style="outline">{{ get_the_date() }}</x-badge>
                        <x-badge variant="gray" style="outline">{{ get_reading_time() }}</x-badge>
                    </div>
                </header>

                @if (has_post_thumbnail())
                    <figure class="mb-8">
                        {!! get_the_post_thumbnail(null, 'large', ['class' => 'w-full h-auto rounded-lg']) !!}
                    </figure>
                @endif

                <div class="prose max-w-none">
                    @php(the_content())
                </div>
            </article>

            {{-- Post Navigation --}}
            @if (get_previous_post_link() || get_next_post_link())
                <nav class="single-post container mx-auto max-w-7xl px-4 pb-16 md:pb-24 pt-8 border-t border-line" aria-label="Beitragsnavigation">
                    <div class="flex justify-between items-start gap-8">
                        <div class="flex-1">
                            @if (get_previous_post())
                                <span class="text-body-small text-content-secondary mb-2 block">Vorheriger Beitrag</span>
                                {!! get_previous_post_link('%link', '← %title', false) !!}
                            @endif
                        </div>
                        <div class="flex-1 text-right">
                            @if (get_next_post())
                                <span class="text-body-small text-content-secondary mb-2 block">Nächster Beitrag</span>
                                {!! get_next_post_link('%link', '%title →', false) !!}
                            @endif
                        </div>
                    </div>
                </nav>
            @endif
        @endwhile
    @endif
@endsection
