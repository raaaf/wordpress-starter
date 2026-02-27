@extends('layouts.app')

@section('content')
    @php
        $isAuthenticated = \WordpressStarter\MemberArea\Auth::isAuthenticated();
    @endphp

    @if(!$isAuthenticated)
        @include('member-area.login')
    @else
        {{-- ACF Flexible Content --}}
        @if(have_rows('page_sections'))
            @while(have_rows('page_sections'))
                @php(the_row())
                @php($layout = get_row_layout())
                @includeIf('flexible.' . str_replace('_', '-', $layout))
            @endwhile
        @endif
    @endif
@endsection
