@extends('layouts.app')

@section('content')
    @php
        $isAuthenticated = \WordpressStarter\MemberArea\Auth::isAuthenticated();
    @endphp

    @if(!$isAuthenticated)
        @include('member-area.login')
    @else
        <x-section padding="lg">
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-h2">{{ __('Interner Bereich', 'wp-starter') }}</h1>
                <x-button
                    url="{{ wp_nonce_url(home_url('/?member_logout=1'), 'member_area_logout') }}"
                    :title="__('Abmelden', 'wp-starter')"
                    variant="secondary"
                    size="sm"
                />
            </div>
            @include('member-area.downloads')
        </x-section>
    @endif
@endsection
