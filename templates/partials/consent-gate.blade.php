{{--
    Consent Gate Partial

    Shared "click to load" consent notice for content that only appears
    after the visitor agrees to load a third-party iframe (used by
    templates/flexible/embed.blade.php and templates/flexible/video.blade.php).
    DSGVO-konform: nothing external loads until the click, and the parent
    container's Alpine scope (`loaded`) plus its `x-ref` drive the visibility
    and focus restore.

    Parameters:
      $containerRef  — x-ref name of the surrounding container (focus target after load).
                       Must be a literal identifier: it is interpolated unescaped into an
                       Alpine expression below, so it never carries user input.
      $icon          — icon name (e.g. 'info', 'play')
      $iconClass     — extra classes for the icon
      $wrapperClass  — extra classes for the outer notice div (e.g. background, poster text colour)
      $textClass     — extra classes for the message paragraph
      $message       — main notice text
      $buttonLabel   — button text (e.g. 'Inhalt laden', 'Video laden')
      $buttonClass   — extra classes on the button, '' to omit
      $providerName  — provider label for the privacy sentence, '' to omit the sentence
      $privacyLink   — privacy policy URL, '' to omit the privacy sentence
      $ownPrivacyPolicy — true if $privacyLink points to this site's own privacy
                          policy rather than the provider's (embed.blade.php: the
                          host is arbitrary, so there is no provider-specific policy
                          to link); switches the sentence wording, default false
--}}

@php
    $buttonClass ??= '';
    $providerName ??= '';
    $privacyLink ??= '';
    $ownPrivacyPolicy ??= false;
    // $containerRef is interpolated raw into the Alpine click expression below,
    // so it must be a literal identifier, never user input.
    $containerRef = preg_match('/^[A-Za-z0-9_-]+$/', (string) $containerRef) ? $containerRef : 'consentGateFallback';
@endphp

<div
    x-show="!loaded"
    x-transition:leave="transition ease-in duration-150 motion-reduce:transition-none"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center {{ $wrapperClass }}"
>
    <x-icon name="{{ $icon }}" class="w-16 h-16 mb-4 {{ $iconClass }}" />
    <p class="mb-4 {{ $textClass }}">
        {{ $message }}
        @if($privacyLink)
            <br>
            {{-- Der Punkt steht im Linktext, weil ein Satzzeichen
                 direkt hinter <x-link> mit einer Luecke davor
                 rendert (siehe Hinweis in link.blade.php). --}}
            @if($ownPrivacyPolicy)
                {{ __('Es gelten unsere', 'wp-starter') }} <x-link url="{{ $privacyLink }}" target="_blank">{{ __('Datenschutzerklärung', 'wp-starter') }}.</x-link>
            @else
                {{ __('Es gelten die', 'wp-starter') }} <x-link url="{{ $privacyLink }}" target="_blank">{{ __('Datenschutzbestimmungen von', 'wp-starter') }} {{ $providerName }}.</x-link>
            @endif
        @endif
    </p>
    <x-button
        :title="$buttonLabel"
        variant="primary"
        size="md"
        :class="$buttonClass"
        {{-- scrollIntoView nur ohne reduced-motion-Praeferenz: sonst laeuft
             nach focus() eine zweite, ungewollte Bewegung. --}}
        x-on:click="loaded = true; $nextTick(() => { if (!$refs.{{ $containerRef }}) { return } $refs.{{ $containerRef }}.focus(); if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) { $refs.{{ $containerRef }}.scrollIntoView({ behavior: 'smooth', block: 'center' }) } })"
    />
</div>
