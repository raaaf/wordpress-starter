{{--
    Video - Flexible Content Layout

    Uses shared components: x-section, x-button, x-link
    ACF Fields: source, video, video_url, background_color
--}}

@php
    $source = get_sub_field('source') ?: 'wordpress';
    $video = get_sub_field('video'); // URL string for self-hosted
    $video_url = get_sub_field('video_url'); // YouTube/Vimeo URL
    $background = get_sub_field('background_color') ?: 'primary';

    // Detect video type from URL for external videos
    $video_type = 'self';
    $video_id = '';

    if ($source === 'external' && $video_url) {
        // Check for YouTube
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $video_url, $matches)) {
            $video_type = 'youtube';
            $video_id = $matches[1];
        }
        // Check for Vimeo
        elseif (preg_match('/vimeo\.com\/(\d+)/', $video_url, $matches)) {
            $video_type = 'vimeo';
            $video_id = $matches[1];
        }
    }

    // Determine privacy policy link based on video type
    $privacyLink = match($video_type) {
        'youtube' => 'https://policies.google.com/privacy',
        'vimeo' => 'https://vimeo.com/privacy',
        default => '',
    };
    $providerName = match($video_type) {
        'youtube' => 'YouTube (Google)',
        'vimeo' => 'Vimeo',
        default => '',
    };

    // Check if we have a valid video
    $hasVideo = ($source === 'external' && $video_id) ||
                ($source === 'wordpress' && $video);
@endphp

<x-section :background="$background" class="video">
    @if($hasVideo)
        <div class="max-w-6xl mx-auto">
            <div
                class="relative overflow-hidden rounded-lg aspect-video bg-surface-secondary"
                x-data="{ loaded: {{ $source === 'wordpress' ? 'true' : 'false' }}, iframeLoaded: false, iframeError: false }"
                x-ref="videoContainer"
            >
                @if($source === 'external' && $video_id)
                    {{-- Consent notice for GDPR compliance --}}
                    <div
                        x-show="!loaded"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center video-consent-notice"
                    >
                        <svg class="w-16 h-16 mb-4 text-content-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="mb-4 text-content-secondary">
                            {{ __('Zum Abspielen des Videos wird ein externer Dienst geladen.', 'wp-starter') }}<br>
                            @if($privacyLink)
                                {{ __('Es gelten die', 'wp-starter') }} <x-link url="{{ $privacyLink }}" target="_blank">{{ __('Datenschutzbestimmungen von', 'wp-starter') }} {{ $providerName }}</x-link>.
                            @endif
                        </p>
                        <x-button
                            :title="__('Video laden', 'wp-starter')"
                            variant="primary"
                            size="md"
                            x-on:click="loaded = true; $nextTick(() => $refs.videoContainer.scrollIntoView({ behavior: 'smooth', block: 'center' }))"
                            class="video-consent-btn"
                        />
                    </div>

                    {{-- Loading indicator --}}
                    <div
                        x-show="loaded && !iframeLoaded && !iframeError"
                        class="absolute inset-0 flex flex-col items-center justify-center bg-surface-secondary"
                        role="status"
                        aria-live="polite"
                    >
                        <div class="animate-spin rounded-full h-12 w-12 border-4 border-line border-t-line-brand mb-4"></div>
                        <span class="text-content-secondary">{{ __('Video wird geladen...', 'wp-starter') }}</span>
                    </div>

                    {{-- Error state --}}
                    <div
                        x-show="iframeError"
                        x-cloak
                        class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center bg-surface-secondary"
                    >
                        <svg class="w-16 h-16 mb-4 text-content-error" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <p class="mb-4 text-content-secondary">{{ __('Das Video konnte nicht geladen werden.', 'wp-starter') }}</p>
                        <x-button
                            :title="__('Erneut versuchen', 'wp-starter')"
                            variant="secondary"
                            size="md"
                            x-on:click="iframeError = false; iframeLoaded = false"
                        />
                    </div>

                    {{-- Video iframe (loaded after consent) --}}
                    <template x-if="loaded && !iframeError">
                        @if($video_type === 'youtube')
                            <iframe
                                src="https://www.youtube-nocookie.com/embed/{{ $video_id }}?dnt=1&autoplay=0"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                                loading="lazy"
                                class="absolute inset-0 w-full h-full"
                                title="{{ __('YouTube-Video', 'wp-starter') }}"
                                x-on:load="iframeLoaded = true"
                                x-on:error="iframeError = true"
                            ></iframe>
                        @elseif($video_type === 'vimeo')
                            <iframe
                                src="https://player.vimeo.com/video/{{ $video_id }}?dnt=1&autoplay=0"
                                frameborder="0"
                                allow="autoplay; fullscreen; picture-in-picture"
                                allowfullscreen
                                loading="lazy"
                                class="absolute inset-0 w-full h-full"
                                title="{{ __('Vimeo-Video', 'wp-starter') }}"
                                x-on:load="iframeLoaded = true"
                                x-on:error="iframeError = true"
                            ></iframe>
                        @endif
                    </template>
                @elseif($source === 'wordpress' && $video)
                    {{-- Self-hosted video - no consent needed --}}
                    <video
                        controls
                        preload="metadata"
                        class="w-full aspect-video object-cover rounded-lg"
                    >
                        <source src="{{ $video }}" type="video/mp4">
                        Ihr Browser unterstützt das Video-Tag nicht.
                    </video>
                @endif
            </div>
        </div>
    @else
        <div class="p-8 text-center rounded-lg bg-surface-secondary">
            <p class="text-content-secondary">Bitte füge eine Video-URL ein oder lade eine Videodatei hoch.</p>
        </div>
    @endif
</x-section>
