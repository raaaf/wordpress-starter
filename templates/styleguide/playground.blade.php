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
    $labels = [
        'black' => __('Schwarzer', 'wp-starter'),
        'white' => __('Weißer', 'wp-starter'),
        /* translators: 1: "Schwarzer" or "Weißer", 2: contrast ratio such as 4.5 */
        'contrast' => __('%1$s Text, Kontrast %2$s:1', 'wp-starter'),
        'square' => __('Eckig', 'wp-starter'),
        'pill' => __('Pille', 'wp-starter'),
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
                    <label class="token-swatch token-swatch--custom">
                        <span class="sr-only">{{ __('Eigene Farbe', 'wp-starter') }}</span>
                        <input type="color" value="#FF6B35" x-model="accent" x-on:input="update()">
                    </label>
                </div>
                <p class="mt-3 mb-0 text-body-small text-content-secondary" aria-live="polite" x-text="contrast"></p>
            </fieldset>

            <div class="mt-8">
                <div class="flex items-baseline justify-between gap-4 mb-2">
                    <label for="token-radius" class="text-body-small text-content-secondary">{{ __('Ecken', 'wp-starter') }}</label>
                    <output for="token-radius" class="text-body-small text-content tabular-nums" x-text="radiusLabel">100 %</output>
                </div>
                <input id="token-radius" type="range" min="0" max="4" step="0.25" value="1" class="token-range" x-model.number="radius" x-on:input="update()" :aria-valuetext="radiusLabel">
            </div>

            <div class="mt-8">
                <div class="flex items-baseline justify-between gap-4 mb-2">
                    <label for="token-scale" class="text-body-small text-content-secondary">{{ __('Schriftgröße', 'wp-starter') }}</label>
                    <output for="token-scale" class="text-body-small text-content tabular-nums" x-text="scaleLabel">16 px</output>
                </div>
                <input id="token-scale" type="range" min="0.875" max="1.25" step="0.025" value="1" class="token-range" x-model.number="scale" x-on:input="update()" :aria-valuetext="scaleLabel">
            </div>

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
