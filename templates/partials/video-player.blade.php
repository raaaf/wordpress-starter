{{--
    Video Player Partial

    Shared self-hosted <video> markup for self-hosted uploads and direct
    file URLs (used by templates/flexible/video.blade.php, source=wordpress
    and source=url branches — both need no consent gate and were otherwise
    two near-identical <video> blocks).

    Parameters:
      $url              — video source URL (already un-escaped, escaped below)
      $mimeType         — MIME type for the <source> tag, '' to omit type=""
      $poster           — poster image URL, '' to omit the poster attribute
      $captions         — caption track URL, '' to omit the <track>
      $captionsLanguage — srclang for the caption track
      $captionsLabel    — label for the caption track
      $autoplay         — bool, adds autoplay muted playsinline (paused again under prefers-reduced-motion)
      $loop             — bool, adds loop
      $ariaLabel        — accessible label for the <video> element
      $aspectClass      — Tailwind aspect-ratio class
--}}

<video
    controls
    @if($autoplay)
        autoplay muted playsinline
        {{-- prefers-reduced-motion: das autoplay-Attribut startet vor Alpine, deshalb
             hier anhalten und auf den Anfang zuruecksetzen. Bedienung bleibt ueber controls. --}}
        x-data
        x-init="if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { $el.pause(); $el.currentTime = 0 }"
    @endif
    @if($loop) loop @endif
    preload="metadata"
    aria-label="{{ $ariaLabel }}"
    class="w-full {{ $aspectClass }} object-cover rounded-lg"
    @if($poster) poster="{{ esc_url($poster) }}" @endif
>
    <source src="{{ esc_url($url) }}"@if($mimeType) type="{{ esc_attr($mimeType) }}"@endif>
    @if($captions)
        <track
            kind="captions"
            src="{{ esc_url($captions) }}"
            srclang="{{ esc_attr($captionsLanguage) }}"
            label="{{ esc_attr($captionsLabel) }}"
            default
        >
    @endif
    {{ __('Dein Browser kann dieses Video nicht abspielen.', 'wp-starter') }}
    <a href="{{ esc_url($url) }}">{{ __('Video herunterladen', 'wp-starter') }}</a>
</video>
