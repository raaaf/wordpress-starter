{{--
    Newsletter-Anmeldung - Flexible Content Layout

    Uses shared components: x-section
    Fields: title, content, form_id, width, background_color

    Das Formular kommt aus Contact Form 7, wie beim Kontaktlayout. Ein eigenes
    Formular haette einen zweiten Spamschutz und eine zweite Einwilligung
    gebraucht, fuer dieselbe Aufgabe.
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $content = get_sub_field('content');
    $formId = (int) (get_sub_field('form_id') ?: 0);
    $width = get_sub_field('width') ?: 'narrow';
    $background = get_sub_field('background_color') ?: 'primary';

    $hasForm = $formId > 0 && shortcode_exists('contact-form-7');
@endphp

@if($title || $content || $hasForm || current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :background="$background" class="newsletter">
    <div class="{{ $width === 'narrow' ? 'max-w-2xl mx-auto text-center' : '' }}">
        @if($title)
            <h2 class="mb-4">{!! $title !!}</h2>
        @endif

        @if($content)
            <p class="mb-8 text-content-secondary">{!! \WordpressStarter\Helpers\Text::lineBreaks($content) !!}</p>
        @endif

        @if($hasForm)
            <div class="newsletter-form {{ $width === 'narrow' ? 'text-left' : '' }}">
                {!! do_shortcode('[contact-form-7 id="' . $formId . '"]') !!}
            </div>
        @elseif(current_user_can('edit_posts'))
            <div class="p-8 text-center rounded-[var(--card-radius)] bg-surface-secondary">
                <p class="text-content-secondary">{{ __('Bitte trage eine gültige Formular-ID ein. Contact Form 7 muss aktiv sein.', 'wp-starter') }}</p>
            </div>
        @endif
    </div>
</x-section>
@endif
