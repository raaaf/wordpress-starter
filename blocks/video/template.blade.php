@php
    $source = $fields['source'] ?? 'wordpress';
    $bgColor = $fields['background_color'] ?? 'gray-200';
    
    if ($source === 'wordpress') {
        $video = $fields['video'] ?? '';
    } elseif ($source === 'external') {
        $video = $fields['url_to_video'] ?? '';
        // Replace youtube.com with youtube-nocookie.com for privacy
        $video = str_replace('youtube.com', 'youtube-nocookie.com', $video);
    }
@endphp

<section class="{{ $classes }} video relative flex items-center justify-between"
         @if($anchor) id="{{ $anchor }}" @endif>
    <div class="flex flex-col items-center justify-center w-full max-w-6xl px-6 mx-auto md:px-8">
        @if($source === 'wordpress' && $video)
            <div class="flex items-center justify-center w-full overflow-hidden border-4 rounded-lg shadow-xl bg-gray-900 aspect-video border-gray-200">
                <video preload="preload" controls loop autoplay playsinline class="w-full">
                    <source src="{{ $video }}" type="video/mp4" />
                    Ihr Browser unterstützt das Video-Tag nicht.
                </video>
            </div>
        @elseif($source === 'external' && $video)
            <div class="flex items-center justify-center w-full overflow-hidden border-4 rounded-lg shadow-xl bg-gray-900 md:aspect-video border-gray-200">
                <iframe width="100%" 
                        height="100%"
                        class="w-full video-iframe aspect-video [&:not([src])]:hidden [&[src]+.video-notice]:hidden"
                        data-src="{{ $video }}?autoplay=0&mute=0&controls=1&modestbranding=0&rel=0&showinfo=0&playsinline=1&dnt=1"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
                <form class="p-8 lg:px-16 video-notice">
                    <p class="text-center text-blue-700">
                        Da YouTube persönliche Daten sammeln und Ihr Sehverhalten verfolgen kann, laden wir das Video
                        nur, wenn Sie der Verwendung von Cookies und
                        ähnlichen Technologien zustimmen, wie in der <a href="https://www.youtube.com/t/privacy"
                            target="_blank"
                            pirsch-event="Privacy_Policy_Click"
                            pirsch-meta-key="video_block"
                            pirsch-meta-link-type="youtube_privacy">Datenschutzrichtlinie</a> beschrieben.
                    </p>
                    <div class="mt-8 mx-auto w-full flex justify-center">
                        <button class="w-full lg:w-auto !no-underline inline-flex items-center justify-center gap-2 px-6 py-2.5 border rounded-md bg-yellow-500 border-yellow-500 shadow-yellow-500/20 hover:bg-yellow-600 relative transition ease-in-out hover:cursor-pointer"
                                pirsch-event="Video_Consent"
                                pirsch-meta-key="video_block"
                                pirsch-meta-video-url="{{ $video }}">
                            <span class="text-base text-blue-700 font-bold leading-none no-underline">Allow YouTube Content</span>
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</section>