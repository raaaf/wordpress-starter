@extends('layouts.app')

@section('content')

<main class="p-4 bg-gray-100 md:p-8">

    <section>

        <div class="flex flex-col justify-center">
            <p class="mb-2 opacity-50 has-overline-font-size">Welcome</p>
            <h1 class="has-h1-font-size">Your Content Goes Here</h1>
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
