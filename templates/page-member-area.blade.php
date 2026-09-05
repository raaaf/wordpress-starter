@extends('layouts.app')

@section('content')
    @php
        $isAuthenticated = \WordpressStarter\MemberArea\Auth::isAuthenticated();
    @endphp

    @if(!$isAuthenticated)
        @include('member-area.login')
    @elseif (have_posts())
        @while (have_posts()) @php(the_post())
            {{-- ACF sections bypass the_content(), so the password gate has to
                 be checked explicitly or protected pages would render in full. --}}
            @if(post_password_required())
                @include('partials.password-form')
            @else
                @include('partials.page-header')
                @include('partials.page-sections-loop')

                {{-- Render standard WordPress content if available --}}
                @if(get_the_content())
                    <div class="page-content max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
                        <x-prose>
                            @php(the_content())
                        </x-prose>
                    </div>
                @endif
            @endif
        @endwhile
    @endif
@endsection
