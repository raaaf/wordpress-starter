@extends('layouts.app')

@section('content')
    @if (have_posts())
        @while (have_posts()) @php(the_post())
            <article class="page">
                @unless(is_front_page())
                    <header class="page-header container mx-auto px-4 py-8">
                        <h1 class="text-4xl font-bold">{{ get_the_title() }}</h1>
                    </header>
                @endunless

                {{-- Render ACF Flexible Content if available --}}
                @if(have_rows('page_sections'))
                    @while(have_rows('page_sections'))
                        @php(the_row())
                        @php($layout = get_row_layout())
                        @includeIf('flexible.' . str_replace('_', '-', $layout))
                    @endwhile
                @endif

                {{-- Render standard WordPress content if available --}}
                @if(get_the_content())
                    <div class="page-content container mx-auto px-4">
                        @php(the_content())
                    </div>
                @endif
            </article>
        @endwhile
    @endif
@endsection
