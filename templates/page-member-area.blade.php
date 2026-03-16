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
            @php($layoutCounters = [])
            @while(have_rows('page_sections'))
                @php(the_row())
                @php($layout = get_row_layout())
                @php($layoutCounters[$layout] = ($layoutCounters[$layout] ?? 0) + 1)
                @php($customAnchor = get_sub_field('section_anchor'))
                @php($sectionAnchor = $customAnchor ?: str_replace('_', '-', $layout) . '-' . $layoutCounters[$layout])
                @includeIf('flexible.' . str_replace('_', '-', $layout))
            @endwhile
        @endif
    @endif
@endsection
