{{--
    Newsletter-Anmeldung - Flexible Content Layout

    Uses shared components: x-section, x-button
    Fields: title, content, action_url, email_field, button_label, note, background_color

    Das Formular postet direkt an den Anbieter und oeffnet dessen Seite in einem
    neuen Tab. Damit laufen Bestaetigungsmail, Verteiler und Abmeldung dort, wo
    sie hingehoeren, und diese Seite speichert keine Adresse.

    Kein type="email" ohne novalidate: die Browserpruefung faengt Tippfehler ab,
    bevor der Anbieter eine unbrauchbare Adresse bekommt.
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $content = get_sub_field('content');
    $actionUrl = (string) get_sub_field('action_url');
    $emailField = (string) (get_sub_field('email_field') ?: 'EMAIL');
    $buttonLabel = (string) (get_sub_field('button_label') ?: __('Anmelden', 'wp-starter'));
    $note = get_sub_field('note');
    $background = get_sub_field('background_color') ?: 'primary';
    $privacyPolicyUrl = (string) get_privacy_policy_url();

    $isHttps = $actionUrl !== '' && wp_parse_url($actionUrl, PHP_URL_SCHEME) === 'https';
    $inputId = 'newsletter-email-' . uniqid();

    // Ein Feldname aus dem Backend landet in name="", deshalb auf das eingedampft,
    // was Anbieter dort ueberhaupt verwenden.
    $emailField = preg_replace('/[^A-Za-z0-9_\-\[\]]/', '', $emailField) ?: 'EMAIL';
@endphp

@if($isHttps || current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" padding="md" class="newsletter">
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            @if($title || $content)
                <div class="md:max-w-md">
                    @if($title)
                        <h2 class="text-h4 mb-1">{!! $title !!}</h2>
                    @endif

                    @if($content)
                        <p class="text-content-secondary">{!! \WordpressStarter\Helpers\Text::lineBreaks($content) !!}</p>
                    @endif
                </div>
            @endif

            @if($isHttps)
                <form
                    action="{{ esc_url($actionUrl) }}"
                    method="post"
                    target="_blank"
                    rel="noopener"
                    class="flex flex-col gap-3 sm:flex-row sm:items-end md:shrink-0"
                >
                    <div class="sm:w-72">
                        <x-input
                            type="email"
                            id="{{ $inputId }}"
                            name="{{ $emailField }}"
                            :label="__('E-Mail-Adresse', 'wp-starter')"
                            :required="true"
                            autocomplete="email"
                            :placeholder="__('deine@adresse.de', 'wp-starter')"
                        />
                    </div>

                    <x-button
                        type="submit"
                        :title="$buttonLabel"
                        variant="primary"
                        class="shrink-0"
                    />
                </form>
            @elseif(current_user_can('edit_posts'))
                <div class="p-6 rounded-[var(--card-radius)] bg-surface-secondary surface-sheen">
                    <p class="text-content-secondary">{{ __('Bitte trage die https-Adresse aus dem Einbettungscode deines Newsletter-Anbieters ein.', 'wp-starter') }}</p>
                </div>
            @endif
        </div>

        @if($isHttps)
            <p class="mt-4 max-w-[60ch] text-body-small text-content-secondary">
                @if($note)
                    {{ $note }}
                @else
                    {{ __('Mit dem Absenden stimmst du der Verarbeitung deiner E-Mail-Adresse zu.', 'wp-starter') }}
                    @if($privacyPolicyUrl)
                        <a href="{{ esc_url($privacyPolicyUrl) }}" class="underline hover:no-underline">{{ __('Hinweise in der Datenschutzerklärung.', 'wp-starter') }}</a>
                    @else
                        {{ __('Es gilt unsere Datenschutzerklärung.', 'wp-starter') }}
                    @endif
                @endif
            </p>
        @endif
    </div>
</x-section>
@endif
