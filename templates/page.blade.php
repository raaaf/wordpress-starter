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

                <div class="page-content container mx-auto px-4">
                    @php(the_content())
                </div>
            </article>
        @endwhile
    @endif
@endsection
