@extends('layouts.app')

@section('content')
    @php
        $authorName = get_field('author_name');
        $authorPosition = get_field('author_position');
        $content = get_field('content');
        $rating = get_field('rating');
        $authorImage = get_field('author_image');
        $sourceUrl = get_field('source_url');
    @endphp

    <x-section background="primary">
        <article class="max-w-3xl mx-auto">
            {{-- Testimonial Card --}}
            <div class="bg-surface-secondary rounded-xl p-8 md:p-12">
                {{-- Quote Icon --}}
                <svg class="w-12 h-12 text-content-brand opacity-30 mb-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                </svg>

                {{-- Testimonial Content --}}
                <blockquote class="text-xl md:text-2xl text-content leading-relaxed mb-8">
                    {{ $content }}
                </blockquote>

                {{-- Rating Stars --}}
                @if($rating)
                    <div class="flex gap-1 mb-6" role="img" aria-label="{{ sprintf(__('%d von 5 Sternen', 'wp-starter'), $rating) }}">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-6 h-6 {{ $i <= $rating ? 'text-yellow-400' : 'text-content-tertiary' }}" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                @endif

                {{-- Author Info --}}
                <footer class="flex items-center gap-4">
                    @if($authorImage)
                        <img
                            src="{{ $authorImage['sizes']['thumbnail'] ?? $authorImage['url'] }}"
                            alt="{{ $authorName }}"
                            class="w-16 h-16 rounded-full object-cover"
                            width="64"
                            height="64"
                        >
                    @else
                        <div class="w-16 h-16 rounded-full bg-surface-tertiary flex items-center justify-center">
                            <span class="text-2xl font-bold text-content-secondary">
                                {{ mb_substr($authorName, 0, 1) }}
                            </span>
                        </div>
                    @endif

                    <div>
                        <cite class="text-lg font-semibold text-content not-italic">
                            {{ $authorName }}
                        </cite>
                        @if($authorPosition)
                            <p class="text-content-secondary">
                                {{ $authorPosition }}
                            </p>
                        @endif
                    </div>
                </footer>

                {{-- Source Link --}}
                @if($sourceUrl)
                    <div class="mt-8 pt-6 border-t border-line">
                        <a
                            href="{{ $sourceUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 text-sm text-content-secondary hover:text-content-brand transition-colors"
                        >
                            <span>{{ __('Original-Bewertung ansehen', 'wp-starter') }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </div>
                @endif
            </div>

            {{-- Back Link --}}
            <div class="mt-8 text-center">
                <a href="{{ home_url() }}" class="inline-flex items-center gap-2 text-content-secondary hover:text-content-brand transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>{{ __('Zurück zur Startseite', 'wp-starter') }}</span>
                </a>
            </div>
        </article>
    </x-section>
@endsection
