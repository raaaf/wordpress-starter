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
      $autoplay         — bool, adds autoplay muted playsinline
      $loop             — bool, adds loop
      $ariaLabel        — accessible label for the <video> element
      $aspectClass      — Tailwind aspect-ratio class
      $id               — optional id attribute, '' to omit
--}}

@php
    $id ??= '';
@endphp

<video
    controls
    @if($autoplay) autoplay muted playsinline @endif
    @if($loop) loop @endif
    preload="metadata"
    aria-label="{{ $ariaLabel }}"
    class="w-full {{ $aspectClass }} object-cover rounded-lg"
    @if($poster) poster="{{ esc_url($poster) }}" @endif
    @if($id) id="{{ esc_attr($id) }}" @endif
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
    Ihr Browser unterstützt das Video-Tag nicht.
</video>
