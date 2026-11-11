{{--
    Design-System-Seite: Bedienfeld "Dein Look" plus Seiteninhalt als Vorschau

    Akzentfarbe, Eckenradius, Schriftgröße und Schriftpaar live umstellen. Die Komponente
    (resources/js/token-playground.ts) schreibt die Werte als CSS-Variablen
    inline auf <html>, deshalb folgt die ganze Seite: rechts die Abschnitte Tokens und
    Komponenten sind die Vorschau. Das Bedienfeld ist standardmäßig zu: ab lg eine Schublade
    von rechts (ohne Hintergrund, Scroll-Sperre oder Fokusfalle, die Seite bleibt bedienbar),
    darunter ein festes Bottom Sheet (Zustand panelOpen). Geöffnet wird es über den Button
    oberhalb der Tokens und ab lg über den schwebenden Button. Der Wrapper trägt die
    Alpine-Komponente, damit Bedienfeld, Auslöser und Leiste denselben Zustand teilen.
    Gespeichert wird nichts: Neu laden oder "Zurücksetzen" stellt das Theme her.
--}}

@php
    // INTENTIONAL: feste Demo-Palette statt Tokens. Die Spielwiese zeigt gerade,
    // wie das Theme mit fremden Akzentfarben aussieht; die erste ist der Standard.
    $swatches = [
        '#FF6B35' => __('Orange (Standard)', 'wp-starter'),
        '#2563EB' => __('Blau', 'wp-starter'),
        '#059669' => __('Grün', 'wp-starter'),
        '#7C3AED' => __('Violett', 'wp-starter'),
        '#E11D48' => __('Rot', 'wp-starter'),
        '#FACC15' => __('Gelb', 'wp-starter'),
    ];
    // Fixed steps instead of sliders: round values only. The keys are what the
    // component receives (px for radii, a factor for the type scale); 34 is the
    // pill step of the button radius (BUTTON_PILL in token-playground.ts).
    $segments = [
        'radius' => [
            'label' => __('Ecken der Karten (px)', 'wp-starter'),
            'options' => [0 => '0', 6 => '6', 12 => '12', 24 => '24', 48 => '48'],
            'default' => 12,
        ],
        'buttonRadius' => [
            'label' => __('Ecken der Buttons (px)', 'wp-starter'),
            'options' => [0 => '0', 6 => '6', 12 => '12', 34 => __('Pille', 'wp-starter')],
            'default' => 34,
        ],
        'scale' => [
            'label' => __('Schriftgröße', 'wp-starter'),
            // Steps, not px: the fluid type sizes are scaled, so a px label would
            // not match what is rendered at every viewport width.
            'options' => ['0.875' => 'S', '1' => 'M', '1.125' => 'L', '1.25' => 'XL'],
            'default' => 1,
        ],
    ];
    // Font pairings come from a plugin (filter wp_starter_styleguide_font_pairings),
    // already validated by FontPairings; without any the whole row is left out.
    $pairings = \WordpressStarter\Helpers\FontPairings::all();
    $generatorUrl = esc_url((string) apply_filters('wp_starter_theme_generator_url', ''));
    $config = ['fonts' => $pairings, 'generatorUrl' => $generatorUrl];
    $labels = [
        'black' => __('Schwarzer', 'wp-starter'),
        'white' => __('Weißer', 'wp-starter'),
        /* translators: 1: "Schwarzer" or "Weißer", 2: contrast ratio such as 4.5 */
        'contrast' => __('%1$s Text, Kontrast %2$s:1', 'wp-starter'),
    ];
@endphp

<div
    x-data="tokenPlayground(@js($labels), @js($config))"
    x-on:keydown.escape.window="if (panelOpen) close()"
    class="ds-layout"
>
    <aside x-ref="panel" aria-label="{{ __('Dein Look', 'wp-starter') }}" class="ds-panel" :class="{ 'is-open': panelOpen }">
        {{-- Below lg: the bar stays at the bottom, the body opens above it (CSS reverses the order). The bar comes first in the DOM, so keyboard users reach the toggle before the controls. --}}
        <div class="ds-panel__bar">
            <button type="button" class="ds-panel__toggle" x-ref="toggle" aria-controls="ds-panel-body" x-bind:aria-expanded="panelOpen.toString()" x-on:click="panelOpen ? close() : open($el)">
                <span x-show="! panelOpen">{{ __('Look anpassen', 'wp-starter') }}</span>
                <span x-show="panelOpen" x-cloak>{{ __('Fertig', 'wp-starter') }}</span>
            </button>
            @if($generatorUrl !== '')
                <x-button :url="$generatorUrl" :title="__('Theme bauen', 'wp-starter')" variant="primary" size="sm" :analytics="['event' => 'generator_playground_cta']" x-bind:href="href" />
            @endif
        </div>

        {{-- inert on the body only (not the aside), so the bar stays operable while the drawer is closed. --}}
        <div id="ds-panel-body" x-ref="panelBody" class="ds-panel__body" :class="{ 'is-open': panelOpen }" x-bind:inert="! panelOpen" inert>
            <div class="ds-panel__header">
                <div>
                    <h2 class="text-h4 m-0">{{ __('Dein Look', 'wp-starter') }}</h2>
                    <p class="mt-1 mb-0 text-body-small text-content-secondary">{{ __('Die ganze Seite folgt deiner Auswahl.', 'wp-starter') }}</p>
                </div>
                <button type="button" class="ds-panel__close" x-ref="close" aria-label="{{ __('Schließen', 'wp-starter') }}" x-on:click="close()">
                    <x-icon name="close" size="lg" />
                </button>
            </div>

            <fieldset class="mx-0 mb-0 mt-5 p-0 border-0">
                <legend class="mb-3 text-body-small text-content-secondary">{{ __('Akzentfarbe', 'wp-starter') }}</legend>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach($swatches as $hex => $name)
                        <label class="token-swatch" style="--swatch: {{ $hex }}">
                            <input type="radio" name="token-accent" value="{{ $hex }}" class="sr-only" x-model="accent" x-on:change="update()" @checked($loop->first)>
                            <span class="sr-only">{{ $name }}</span>
                        </label>
                    @endforeach
                    <label class="token-swatch token-swatch--custom" :class="{ 'is-selected': ! @js(array_keys($swatches)).includes(accent.toUpperCase()) }">
                        <span class="sr-only">{{ __('Eigene Farbe', 'wp-starter') }}</span>
                        <input type="color" value="#FF6B35" x-model="accent" x-on:input="update()">
                    </label>
                </div>
                <p class="mt-3 mb-0 text-body-small text-content-secondary" aria-live="polite" x-text="contrast"></p>
            </fieldset>

            @foreach($segments as $model => $segment)
                {{-- Schrift sits before the type scale, so the font row is emitted ahead of that segment. --}}
                @if($model === 'scale' && $pairings !== [])
                    <fieldset class="mx-0 mb-0 mt-5 p-0 border-0">
                        <legend class="mb-3 text-body-small text-content-secondary">{{ __('Schrift', 'wp-starter') }}</legend>
                        <div class="token-fonts">
                            {{-- The Standard sample pins the theme headline face read at init, so the playground's <html> font override does not change it. --}}
                            <label class="token-font">
                                <input type="radio" name="token-font" value="" class="sr-only" x-model="font" x-on:change="update()" checked>
                                <span class="token-font__body">
                                    <span class="token-font__sample" aria-hidden="true" x-bind:style="themeFace ? { '--pair-face': themeFace } : {}">Aa</span>
                                    <span class="token-font__name">{{ __('Standard', 'wp-starter') }} ({{ __('Theme-Schrift', 'wp-starter') }})</span>
                                </span>
                            </label>
                            @foreach($pairings as $index => $pairing)
                                <label class="token-font">
                                    <input type="radio" name="token-font" value="{{ $index }}" class="sr-only" x-model="font" x-on:change="update()">
                                    {{-- The face is a validated slug; the sample shows it in the headline face. --}}
                                    <span class="token-font__body">
                                        <span class="token-font__sample" aria-hidden="true" style="--pair-face: '{{ $pairing['headlineFace'] }}'">Aa</span>
                                        <span class="token-font__name">{{ $pairing['label'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
                <fieldset class="mx-0 mb-0 mt-5 p-0 border-0">
                    <legend class="mb-3 text-body-small text-content-secondary">{{ $segment['label'] }}</legend>
                    <div class="token-segments">
                        @foreach($segment['options'] as $value => $text)
                            <label class="token-segment">
                                <input type="radio" name="token-{{ $model }}" value="{{ $value }}" class="sr-only" x-model.number="{{ $model }}" x-on:change="update()" @checked($value === $segment['default'])>
                                <span>{{ $text }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach


            {{-- From lg the actions stick to the bottom of the scrolling drawer; below lg the bar carries the CTA. --}}
            <div class="ds-panel__actions mt-5 lg:mt-auto flex flex-col items-start gap-3">
                @if($generatorUrl !== '')
                    <x-button :url="$generatorUrl" :title="__('Mit diesem Look ein Theme bauen', 'wp-starter')" variant="primary" class="ds-panel__cta w-full" :analytics="['event' => 'generator_playground_cta']" x-bind:href="href" />
                @endif
                <x-button :title="__('Zurücksetzen', 'wp-starter')" variant="ghost" size="sm" x-on:click="reset()" />
                <p class="m-0 text-body-small text-content-secondary">{{ __('Ändert nur deine Ansicht, gespeichert wird nichts.', 'wp-starter') }}</p>
            </div>
        </div>
    </aside>

    {{-- Inline trigger above the tokens section, on the header band's surface so it reads as part of the page head. --}}
    <div class="bg-surface">
    <div class="max-w-[var(--container-max)] mx-auto px-4 sm:px-6 lg:px-8 pt-8 flex flex-wrap items-center gap-x-4 gap-y-2">
        <x-button :title="__('Look anpassen', 'wp-starter')" variant="secondary" aria-controls="ds-panel-body" x-bind:aria-expanded="panelOpen.toString()" x-on:click="panelOpen ? close() : open($el)">
            <x-icon name="sliders-horizontal" size="lg" class="order-first" />
        </x-button>
        <p class="m-0 text-body-small text-content-secondary">{{ __('Akzentfarbe, Ecken und Schrift live ausprobieren. Die ganze Seite folgt.', 'wp-starter') }}</p>
    </div>
    </div>

    {{-- From lg, once the drawer is closed: fixed pill with fixed styling (not radius tokens). The dot shows the current accent once the look differs from the defaults. --}}
    <button type="button" class="ds-fab" x-show="! panelOpen" aria-controls="ds-panel-body" x-bind:aria-expanded="panelOpen.toString()" x-bind:aria-label="changed ? @js(__('Look anpassen (geändert)', 'wp-starter')) : @js(__('Look anpassen', 'wp-starter'))" x-on:click="open($el)">
        <x-icon name="sliders-horizontal" size="lg" />
        <span>{{ __('Look anpassen', 'wp-starter') }}</span>
        <span class="ds-fab__dot" x-show="changed" x-cloak style="background: var(--rafael-color-accent-500)" aria-hidden="true"></span>
    </button>

    @include('styleguide.tokens', ['overline' => false])
    @include('styleguide.components', ['overline' => false])
</div>
