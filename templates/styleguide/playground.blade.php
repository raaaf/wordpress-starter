{{--
    Design-System-Referenz: Token-Spielwiese

    Akzentfarbe, Eckenradius und Schriftgröße live umstellen. Die Komponente
    (resources/js/token-playground.ts) schreibt die Werte als CSS-Variablen
    inline auf <html>, deshalb folgt die ganze Seite, nicht nur die Vorschau.
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
            'label' => __('Eckenradius der Karten', 'wp-starter'),
            'options' => [0 => '0 px', 6 => '6 px', 12 => '12 px', 24 => '24 px', 48 => '48 px'],
            'default' => 12,
        ],
        'buttonRadius' => [
            'label' => __('Eckenradius der Buttons', 'wp-starter'),
            'options' => [0 => '0 px', 6 => '6 px', 12 => '12 px', 34 => __('Pille', 'wp-starter')],
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
    $labels = [
        'black' => __('Schwarzer', 'wp-starter'),
        'white' => __('Weißer', 'wp-starter'),
        /* translators: 1: "Schwarzer" or "Weißer", 2: contrast ratio such as 4.5 */
        'contrast' => __('%1$s Text, Kontrast %2$s:1', 'wp-starter'),
    ];
@endphp

<x-section anchor="playground" background="primary" padding="lg" class="styleguide-playground">
    <x-section-header
        :headline="__('Spielwiese', 'wp-starter')"
        :description="__('Akzentfarbe, Ecken und Schriftgröße live umstellen. Die ganze Seite folgt, weil alles an denselben Tokens hängt.', 'wp-starter')"
    />

    <div
        x-data="tokenPlayground(@js($labels))"
        class="grid grid-cols-1 gap-6 lg:grid-cols-2"
    >
        <x-card variant="outlined" padding="none" class="p-6">
            <fieldset class="m-0 p-0 border-0">
                <legend class="mb-3 text-body-small text-content-secondary">{{ __('Akzentfarbe', 'wp-starter') }}</legend>
                <div class="flex flex-wrap items-center gap-3">
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
                <fieldset class="mx-0 mb-0 mt-8 p-0 border-0">
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

            <div class="flex flex-wrap items-center justify-between gap-4 mt-8">
                <p class="m-0 text-body-small text-content-secondary">{{ __('Ändert nur deine Ansicht, gespeichert wird nichts.', 'wp-starter') }}</p>
                <x-button :title="__('Zurücksetzen', 'wp-starter')" variant="secondary" size="sm" x-on:click="reset()" />
            </div>
        </x-card>

        {{-- Vorschau: dieselben Flächen und Bausteine wie auf der Seite. --}}
        <div class="token-preview flex flex-col gap-6 p-6 rounded-[var(--card-radius)] bg-surface-brand text-content-on-brand">
            <div class="flex items-center gap-4">
                <span class="flex items-center justify-center w-12 h-12 rounded-[var(--card-radius)] bg-surface text-content">
                    <x-icon name="lightning" class="w-6 h-6" />
                </span>
                <p class="m-0 text-overline">{{ __('Vorschau', 'wp-starter') }}</p>
            </div>
            <p class="m-0 text-h3">{{ __('Ein Akzent, viele Flächen.', 'wp-starter') }}</p>
            <p class="m-0">{{ __('Buttons, Karten, Badges und Ecken lesen alle dieselben Tokens.', 'wp-starter') }}</p>
            <div class="flex flex-wrap items-center gap-3">
                <x-badge variant="gray">{{ __('Badge', 'wp-starter') }}</x-badge>
                <x-button :title="__('Button', 'wp-starter')" variant="secondary" />
            </div>
        </div>
    </div>
</x-section>
