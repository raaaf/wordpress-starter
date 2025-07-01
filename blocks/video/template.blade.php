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
            <div class="flex items-center justify-center w-full overflow-hidden border-4 rounded-lg shadow-xl bg-night aspect-video border-{{ $bgColor }}">
                <video preload="preload" controls loop autoplay playsinline class="w-full">
                    <source src="{{ $video }}" type="video/mp4" />
                    Ihr Browser unterstützt das Video-Tag nicht.
                </video>
            </div>
        @elseif($source === 'external' && $video)
            <div class="flex items-center justify-center w-full overflow-hidden border-4 rounded-lg shadow-xl bg-night md:aspect-video border-{{ $bgColor }}">
                <iframe width="100%" 
                        height="100%"
                        class="w-full video-iframe aspect-video [&:not([src])]:hidden [&[src]+.video-notice]:hidden"
                        data-src="{{ $video }}?autoplay=0&mute=0&controls=1&modestbranding=0&rel=0&showinfo=0&playsinline=1&dnt=1"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
                <form class="p-8 lg:px-16 video-notice">
                    <p class="text-center text-marine">
                        Da YouTube persönliche Daten sammeln und Ihr Sehverhalten verfolgen kann, laden wir das Video
                        nur, wenn Sie der Verwendung von Cookies und
                        ähnlichen Technologien zustimmen, wie in der <a href="https://www.youtube.com/t/privacy"
                            target="_blank">Datenschutzrichtlinie</a> beschrieben.
                    </p>
                    <div class="mt-8 mx-auto w-full flex justify-center">
                        <button class="w-full lg:w-auto !no-underline inline-flex items-center justify-center gap-2 px-6 py-2.5 border rounded-md bg-senf border-senf shadow-senf hover:bg-senf/90 relative transition ease-in-out hover:cursor-pointer">
                            <span class="text-base text-marine font-bold leading-none no-underline">Einbindung von YouTube-Content erlauben</span>
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</section>