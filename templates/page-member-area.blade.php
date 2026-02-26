@extends('layouts.app')

@section('content')
    @php
        $isAuthenticated = \WordpressStarter\MemberArea\Auth::isAuthenticated();
    @endphp

    @if(!$isAuthenticated)
        @include('member-area.login')
    @else
        <x-section padding="lg">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-h2 mb-0">{{ __('Interner Bereich', 'wp-starter') }}</h1>
                <x-button
                    url="{{ wp_nonce_url(home_url('/?member_logout=1'), 'member_area_logout') }}"
                    :title="__('Abmelden', 'wp-starter')"
                    variant="secondary"
                    size="sm"
                    class="self-center shrink-0"
                />
            </div>
            @php $memberAreaIntro = function_exists('get_field') ? get_field('member_area_intro', 'option') : ''; @endphp
            @if($memberAreaIntro)
                <p class="text-content-secondary mb-8">{{ $memberAreaIntro }}</p>
            @else
                <div class="mb-8"></div>
            @endif
            @include('member-area.downloads')
        </x-section>
    @endif
@endsection
