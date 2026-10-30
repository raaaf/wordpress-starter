{{--
    Template Name: Design System

    Public version of the styleguide's design-system view: tokens and
    components, rendered from the active tokens and the real Blade components
    (templates/styleguide/). Unlike page-styleguide.blade.php it is visible to
    everyone and leaves out the module gallery, which is an editing tool.
    The page content, if any, shows above the reference as an intro.
--}}

@extends('layouts.app')

@section('content')
    @while(have_posts()) @php(the_post())
    <article class="page page-design-system">
        @if(post_password_required())
            @include('partials.password-form')
        @else
            {{-- Own flat surface: the body carries the noise texture and page sheen,
                 a transparent header would show them above the flat sections. --}}
            <div class="page-header-band bg-surface">
                <header class="page-header max-w-[var(--container-max)] mx-auto px-4 sm:px-6 lg:px-8 pt-12">
                    <div>
                        <h1 class="m-0">{{ get_the_title() }}</h1>
                        @if(get_the_content())
                            <x-prose class="mt-4 max-w-[58ch] text-content-secondary">
                                @php(the_content())
                            </x-prose>
                        @endif
                    </div>
                </header>
            </div>

            {{-- The page h1 already names the design system, so no overline per section. --}}
            @include('styleguide.playground')
            @include('styleguide.tokens', ['overline' => false])
            @include('styleguide.components', ['overline' => false])
        @endif
    </article>
    @endwhile
@endsection
