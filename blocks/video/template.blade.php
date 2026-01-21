{{--
    Video Block

    Uses shared components: x-section, x-button, x-link
    Fields: source, video, video_url, background_color
--}}

@php
    $source = $fields['source'] ?? 'wordpress';
    $background = $fields['background_color'] ?? 'primary';
    $video = '';

    if ($source === 'wordpress') {
        $video = $fields['video'] ?? '';
    } elseif ($source === 'external') {
        $video = $fields['video_url'] ?? '';
        $video = str_replace('youtube.com', 'youtube-nocookie.com', $video);
    }
@endphp

<x-section :background="$background" :anchor="$anchor" :wrapperAttributes="$wrapper_attributes" padding="md" class="{{ $classes }} video">
    <div class="flex flex-col items-center justify-center w-full max-w-6xl mx-auto">
        @if($source === 'wordpress' && $video)
            <div class="flex items-center justify-center w-full overflow-hidden border-4 rounded-lg shadow-xl bg-surface aspect-video border-line">
                <video preload="preload" controls loop autoplay playsinline class="w-full">
                    <source src="{{ esc_url($video) }}" type="video/mp4" />
                    Ihr Browser unterstützt das Video-Tag nicht.
                </video>
            </div>
        @elseif($source === 'external' && $video)
            <div class="flex items-center justify-center w-full overflow-hidden border-4 rounded-lg shadow-xl bg-surface md:aspect-video border-line">
                <iframe width="100%"
                        height="100%"
                        class="w-full video-iframe aspect-video [&:not([src])]:hidden [&[src]+.video-notice]:hidden"
                        data-src="{{ esc_url($video . '?autoplay=0&mute=0&controls=1&modestbranding=0&rel=0&showinfo=0&playsinline=1&dnt=1') }}"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
                <form class="p-8 lg:px-16 video-notice">
                    <p class="text-center text-content">
                        Da YouTube persönliche Daten sammeln und Ihr Sehverhalten verfolgen kann, laden wir das Video
                        nur, wenn Sie der Verwendung von Cookies und
                        ähnlichen Technologien zustimmen, wie in der <x-link
                            url="https://www.youtube.com/t/privacy"
                            target="_blank"
                            variant="accent"
                            size="md"
                            pirsch-event="Privacy_Policy_Click"
                            pirsch-meta-key="video_block"
                            pirsch-meta-link-type="youtube_privacy"
                        >Datenschutzrichtlinie</x-link> beschrieben.
                    </p>
                    <div class="mt-8 mx-auto w-full flex justify-center">
                        <x-button
                            url="#"
                            title="YouTube-Inhalte erlauben"
                            variant="warning"
                            size="md"
                            class="video-consent-btn"
                            :analytics="['event' => 'Video_Consent', 'meta' => 'video_block']"
                        />
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-section>
