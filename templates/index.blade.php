@extends('layouts.app')

@section('content')
    <h1 class="mb-4 text-3xl font-bold">Welcome to {{ get_bloginfo('name') }}</h1>
    @if (have_posts())
        <div class="space-y-6">
            @while (have_posts()) @php(the_post())
                <article class="p-4 bg-surface rounded shadow">
                    <h2 class="text-2xl font-semibold">
                        <a href="{{ get_permalink() }}">{{ get_the_title() }}</a>
                    </h2>
                    <div class="text-sm text-content-secondary">
                        Published on {{ get_the_date() }} by {{ get_the_author() }}
                    </div>
                    <div class="mt-2">
                        {!! get_the_excerpt() !!}
                    </div>
                </article>
            @endwhile
        </div>
    @else
        <p>No posts found.</p>
    @endif
@endsection
