@extends('layouts.app')

@section('content')
    @if (have_posts())
        @while (have_posts()) @php(the_post())
            <article class="page">
                {{-- Only show page header if no ACF sections (title is in Hero block) --}}
                @unless(is_front_page() || have_rows('page_sections'))
                    <header class="page-header max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                        <h1 class="text-h1">{{ get_the_title() }}</h1>
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
                    <div class="page-content max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        @php(the_content())
                    </div>
                @endif
            </article>
        @endwhile
    @endif
@endsection
