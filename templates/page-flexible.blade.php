{{-- 
Template Name: Flexible Content
--}}

@extends('layouts.app')

@section('content')
    @if(have_rows('page_sections'))
        @while(have_rows('page_sections'))
            @php(the_row())
            @php($layout = get_row_layout())
            
            <section class="layout-{{ $layout }} py-8 md:py-12">
                @includeIf('flexible.' . str_replace('_', '-', $layout))
            </section>
        @endwhile
    @else
        <div class="container mx-auto px-4 py-12">
            <p class="text-center text-content-tertiary">{{ __('No content sections have been added yet.', 'wp-starter') }}</p>
        </div>
    @endif
@endsection