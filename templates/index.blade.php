@extends('layouts.app')

@section('content')

<main class="p-4 font-sans bg-gray-100 md:p-8">
    <section>
        <div class="flex flex-col justify-center">

            {{-- DELETE THE NEXT TWO LINES AND ADD YOUR MARKUP --}}
            <p class="mb-2 opacity-50 has-overline">Welcome</p>
            <h1 class="has-h1">Your Content Goes Here</h1>
            {{-- DELETE THE NEXT TWO LINES AND ADD YOUR MARKUP --}}

            @php
            if ( have_posts() ) : while ( have_posts() ) : the_post();
            the_content();
            endwhile;
            endif;
            @endphp
        </div>
    </section>
</main>

@endsection
