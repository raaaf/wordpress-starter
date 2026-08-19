{{--
    Einzelzitat - Flexible Content Layout

    Uses shared components: x-section
    Fields: quote, author, role, image, size, background_color

    Bewusst kein Kartenraster wie bei den Kundenstimmen: hier steht ein einzelner
    Satz fuer sich, deshalb blockquote statt Karte.
--}}

@php
    $quote = get_sub_field('quote');
    $author = get_sub_field('author');
    $role = get_sub_field('role');
    $imageId = (int) (get_sub_field('image') ?: 0);
    $size = get_sub_field('size') ?: 'md';
    $background = get_sub_field('background_color') ?: 'primary';

    $quoteClass = $size === 'lg' ? 'text-h2' : 'text-h4';
@endphp

@if($quote)
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" class="quote">
    <figure class="max-w-3xl mx-auto text-center">
        @if($imageId)
            <img
                src="{{ esc_url(wp_get_attachment_image_url($imageId, 'thumbnail')) }}"
                alt="{{ esc_attr(\WordpressStarter\Helpers\Text::imageAlt($imageId, $author ?: '')) }}"
                class="w-20 h-20 mx-auto mb-6 rounded-full object-cover"
                loading="lazy"
                width="80"
                height="80"
            />
        @endif

        <blockquote class="{{ $quoteClass }} text-balance">
            {{-- Die Anfuehrungszeichen kommen aus dem Layout, nicht aus dem Feld:
                 sonst haengt es vom Tippen der Redaktion ab, ob sie typografisch
                 korrekt sind. --}}
            &bdquo;{!! \WordpressStarter\Helpers\Text::lineBreaks($quote) !!}&ldquo;
        </blockquote>

        @if($author || $role)
            <figcaption class="mt-6 text-content-secondary">
                @if($author)<span class="font-bold text-content">{{ $author }}</span>@endif
                @if($author && $role) <span aria-hidden="true">&middot;</span> @endif
                @if($role){{ $role }}@endif
            </figcaption>
        @endif
    </figure>
</x-section>
@endif
