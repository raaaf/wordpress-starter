{{--
    Video - Flexible Content Layout

    Uses shared components: x-section
    Fields: video_type, video_url, video_file, poster_image
--}}

@php
    $video_type = get_sub_field('video_type');
    $video_url = get_sub_field('video_url');
    $video_file = get_sub_field('video_file');
    $poster_image = get_sub_field('poster_image');

    // Extract video ID for YouTube/Vimeo
    $video_id = '';
    if ($video_type === 'youtube' && $video_url) {
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $video_url, $matches);
        $video_id = $matches[1] ?? '';
    } elseif ($video_type === 'vimeo' && $video_url) {
        preg_match('/vimeo\.com\/(\d+)/', $video_url, $matches);
        $video_id = $matches[1] ?? '';
    }
@endphp

<x-section padding="md">
    <div class="max-w-6xl mx-auto">
        <div class="aspect-video rounded-lg overflow-hidden bg-surface-inverse shadow-xl border-4 border-line">
            @if($video_type === 'youtube' && $video_id)
                <iframe
                    src="https://www.youtube-nocookie.com/embed/{{ $video_id }}?dnt=1"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    class="w-full h-full"
                ></iframe>
            @elseif($video_type === 'vimeo' && $video_id)
                <iframe
                    src="https://player.vimeo.com/video/{{ $video_id }}?dnt=1"
                    frameborder="0"
                    allow="autoplay; fullscreen; picture-in-picture"
                    allowfullscreen
                    class="w-full h-full"
                ></iframe>
            @elseif($video_type === 'self' && $video_file)
                <video
                    controls
                    class="w-full h-full object-cover"
                    @if($poster_image) poster="{{ $poster_image['url'] }}" @endif
                >
                    <source src="{{ $video_file['url'] }}" type="{{ $video_file['mime_type'] }}">
                    Ihr Browser unterstützt das Video-Tag nicht.
                </video>
            @endif
        </div>
    </div>
</x-section>
