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

<div class="container mx-auto px-4">
    <div class="aspect-w-16 aspect-h-9 rounded-lg overflow-hidden bg-gray-100">
        @if($video_type === 'youtube' && $video_id)
            <iframe 
                src="https://www.youtube.com/embed/{{ $video_id }}"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
                class="w-full h-full"
            ></iframe>
        @elseif($video_type === 'vimeo' && $video_id)
            <iframe 
                src="https://player.vimeo.com/video/{{ $video_id }}"
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
                Your browser does not support the video tag.
            </video>
        @endif
    </div>
</div>