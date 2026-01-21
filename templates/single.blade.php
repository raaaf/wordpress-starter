@extends('layouts.app')

@section('content')
    @if (have_posts())
        @while (have_posts()) @php(the_post())
            <article class="single-post container mx-auto px-4 py-8">
                <header class="mb-8">
                    <h1 class="text-4xl font-bold mb-4">{{ get_the_title() }}</h1>
                    <div class="text-sm text-content-secondary">
                        <span>{{ get_the_date() }}</span>
                        <span class="mx-2">|</span>
                        <span>{{ get_the_author() }}</span>
                        @if (has_category())
                            <span class="mx-2">|</span>
                            <span>{!! get_the_category_list(', ') !!}</span>
                        @endif
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

                @if (get_the_tags())
                    <footer class="mt-8 pt-8 border-t border-line">
                        <div class="flex flex-wrap gap-2">
                            @foreach (get_the_tags() as $tag)
                                <x-link :url="get_tag_link($tag)" variant="dark" class="no-underline hover:opacity-80 transition-opacity">
                                    <x-badge variant="gray" style="outline">{{ $tag->name }}</x-badge>
                                </x-link>
                            @endforeach
                        </div>
                    </footer>
                @endif
            </article>
        @endwhile
    @endif
@endsection
