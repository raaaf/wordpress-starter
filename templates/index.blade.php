@extends('layouts.app')

@section('content')
    <x-section padding="lg">
        <h1 class="mb-8 text-3xl font-bold">{{ __('Willkommen bei', 'wp-starter') }} {{ get_bloginfo('name') }}</h1>
        @if (have_posts())
            <div class="space-y-6">
                @while (have_posts()) @php(the_post())
                    <x-card variant="default" hoverable :url="get_permalink()" padding="md">
                        <h2 class="text-2xl font-semibold text-content mb-2">
                            {{ get_the_title() }}
                        </h2>
                        <div class="text-sm text-content-secondary mb-3">
                            {{ sprintf(__('Veröffentlicht am %s von %s', 'wp-starter'), get_the_date(), get_the_author()) }}
                        </div>
                        <div class="text-content-secondary">
                            {!! get_the_excerpt() !!}
                        </div>
                    </x-card>
                @endwhile
            </div>
        @else
            <p class="text-content-secondary">{{ __('Keine Beiträge gefunden.', 'wp-starter') }}</p>
        @endif
    </x-section>
@endsection
