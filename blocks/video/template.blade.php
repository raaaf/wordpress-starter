{{--
    Video Block

    Uses shared components: x-section, x-button
    Fields: source, video, url_to_video, background_color
--}}

@php
    $source = $fields['source'] ?? 'wordpress';
    $background = $fields['background_color'] ?? 'primary';
    $video = '';

    if ($source === 'wordpress') {
        $video = $fields['video'] ?? '';
    } elseif ($source === 'external') {
        $video = $fields['url_to_video'] ?? '';
        $video = str_replace('youtube.com', 'youtube-nocookie.com', $video);
    }
@endphp

<x-section :background="$background" :anchor="$anchor" padding="md" class="{{ $classes }} video">
    <div class="flex flex-col items-center justify-center w-full max-w-6xl mx-auto">
        @if($source === 'wordpress' && $video)
            <div class="flex items-center justify-center w-full overflow-hidden border-4 rounded-lg shadow-xl bg-surface-inverse aspect-video border-line">
                <video preload="preload" controls loop autoplay playsinline class="w-full">
                    <source src="{{ $video }}" type="video/mp4" />
                    Ihr Browser unterstützt das Video-Tag nicht.
                </video>
            </div>
        @elseif($source === 'external' && $video)
            <div class="flex items-center justify-center w-full overflow-hidden border-4 rounded-lg shadow-xl bg-surface-inverse md:aspect-video border-line">
                <iframe width="100%"
                        height="100%"
                        class="w-full video-iframe aspect-video [&:not([src])]:hidden [&[src]+.video-notice]:hidden"
                        data-src="{{ $video }}?autoplay=0&mute=0&controls=1&modestbranding=0&rel=0&showinfo=0&playsinline=1&dnt=1"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
                <form class="p-8 lg:px-16 video-notice">
                    <p class="text-center text-content">
                        Da YouTube persönliche Daten sammeln und Ihr Sehverhalten verfolgen kann, laden wir das Video
                        nur, wenn Sie der Verwendung von Cookies und
                        ähnlichen Technologien zustimmen, wie in der <a href="https://www.youtube.com/t/privacy"
                            target="_blank"
                            class="text-content-link hover:text-content-link-hover"
                            pirsch-event="Privacy_Policy_Click"
                            pirsch-meta-key="video_block"
                            pirsch-meta-link-type="youtube_privacy">Datenschutzrichtlinie</a> beschrieben.
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
