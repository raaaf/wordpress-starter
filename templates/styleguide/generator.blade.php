{{--
    Design-System-Referenz: Token-Generator

    Eigene Farben wählen, Paletten und Seite folgen live, die Token-Dateien
    gibt es als Download. Läuft komplett im Browser
    (resources/js/token-generator-component.ts): Ausgangspunkt sind die
    Token-Dateien dieser Website, geholt über die REST-Route aus
    DesignTokenRestProvider (config/ ist auf vielen Hostern fürs Web gesperrt),
    und erst bei der ersten Interaktion. Gespeichert oder an einen Server
    geschickt wird nichts.
--}}

@php
    $paletteNames = [
        'accent' => __('Akzent', 'wp-starter'),
        'primary' => __('Primär', 'wp-starter'),
        'secondary' => __('Sekundär', 'wp-starter'),
        'gray' => __('Grau', 'wp-starter'),
    ];
    $statusNames = [
        'success' => __('Erfolg', 'wp-starter'),
        'warning' => __('Warnung', 'wp-starter'),
        'error' => __('Fehler', 'wp-starter'),
    ];
    $config = [
        'endpoint' => \WordpressStarter\Providers\DesignTokenRestProvider::endpoint(),
        'initial' => \WordpressStarter\Helpers\DesignTokenFiles::baseColors(),
        'labels' => [
            'loadError' => __('Die Token-Dateien konnten nicht geladen werden. Bitte die Seite neu laden.', 'wp-starter'),
            'invalid' => __('Bitte eine Farbe als #RRGGBB eingeben.', 'wp-starter'),
            'resetDone' => __('Farben zurückgesetzt.', 'wp-starter'),
            'downloaded' => __('Download gestartet.', 'wp-starter'),
            'downloadError' => __('Der Download konnte nicht erstellt werden.', 'wp-starter'),
            'readmeTitle' => __('Design-Tokens aus dem Token-Generator.', 'wp-starter'),
            'readmeBody' => __('config/ und resources/ in den Theme-Ordner kopieren und npm run tokens ausführen, oder die drei JSON-Dateien im WordPress-Backend unter Design tokens hochladen.', 'wp-starter'),
        ],
    ];
    $previews = [
        'light' => __('Hell', 'wp-starter'),
        'dark' => __('Dunkel', 'wp-starter'),
    ];
    $files = [
        'primitives' => 'primitives.tokens.json',
        'light' => 'light.tokens.json',
        'dark' => 'dark.tokens.json',
        'css' => 'tokens.css',
    ];
@endphp

<x-section anchor="token-generator" background="secondary" padding="lg" class="styleguide-generator">
    <x-section-header
        :headline="__('Token-Generator', 'wp-starter')"
        :description="__('Eigene Farben wählen: Paletten, Vorschau und diese Seite folgen sofort. Danach die Token-Dateien für dein Theme herunterladen.', 'wp-starter')"
    />

    <div
        x-data="tokenGenerator(@js($config))"
        x-on:pointerenter.once="load()"
        x-on:focusin.once="load()"
        x-on:pointerdown.once="load()"
        class="flex flex-col gap-6"
    >
        <x-card variant="outlined" padding="none" class="p-6">
            @foreach(['palette' => [__('Paletten', 'wp-starter'), $paletteNames], 'status' => [__('Statusfarben', 'wp-starter'), $statusNames]] as $group => [$legend, $names])
                <fieldset class="{{ $loop->first ? 'm-0' : 'mx-0 mb-0 mt-8' }} p-0 border-0" x-bind:disabled="state === 'error'">
                    <legend class="mb-4 text-body-small text-content-secondary">{{ $legend }}</legend>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 {{ $group === 'palette' ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }}">
                        @foreach($names as $name => $label)
                            <div class="token-gen-color">
                                <div class="flex items-center gap-3">
                                    <label class="token-swatch token-gen-picker" x-bind:style="{ '--swatch': colors.{{ $name }} }">
                                        <span class="sr-only">{{ sprintf(/* translators: %s: colour name such as "Akzent" */ __('%s, Farbwähler', 'wp-starter'), $label) }}</span>
                                        <input type="color" x-bind:value="colors.{{ $name }}" x-on:input="setColor('{{ $name }}', $event.target.value)">
                                    </label>
                                    <div class="flex flex-col min-w-0">
                                        <label for="token-gen-{{ $name }}" class="text-body-small font-semibold">{{ $label }}</label>
                                        <input
                                            id="token-gen-{{ $name }}"
                                            type="text"
                                            class="token-gen-hex"
                                            inputmode="text"
                                            autocomplete="off"
                                            spellcheck="false"
                                            maxlength="7"
                                            x-bind:value="colors.{{ $name }}"
                                            x-on:change="setColor('{{ $name }}', $event.target.value, $event.target)"
                                        >
                                    </div>
                                </div>
                                <ul class="token-gen-shades" role="list" aria-label="{{ sprintf(/* translators: %s: colour name such as "Akzent" */ __('Abstufungen %s', 'wp-starter'), $label) }}">
                                    <template x-for="item in shades('{{ $name }}')" x-bind:key="item.shade">
                                        <li x-bind:style="{ '--swatch': item.hex }" x-bind:title="item.shade + ' ' + item.hex">
                                            <span class="sr-only" x-text="item.shade + ': ' + item.hex"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach

            <div class="flex flex-wrap items-center justify-between gap-4 mt-8">
                <p class="m-0 text-body-small text-content-secondary" aria-live="polite">
                    <span x-show="state === 'loading'">{{ __('Token-Dateien werden geladen …', 'wp-starter') }}</span>
                    <span x-text="message"></span>
                </p>
                <x-button :title="__('Zurücksetzen', 'wp-starter')" variant="secondary" size="sm" x-on:click="reset()" x-bind:disabled="changed.length === 0" />
            </div>
        </x-card>

        {{-- Light and dark side by side, coloured from the generated token files. --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @foreach($previews as $mode => $modeLabel)
                <div class="token-gen-preview" data-theme="{{ $mode }}" x-bind:style="preview.{{ $mode }}">
                    <p class="m-0 text-overline token-gen-preview__muted">{{ $modeLabel }}</p>
                    <div class="token-gen-preview__card">
                        <p class="m-0 text-h4">{{ __('Ein Akzent, viele Flächen.', 'wp-starter') }}</p>
                        <p class="m-0 text-body-small token-gen-preview__muted">{{ __('So sehen Flächen, Text und Statusfarben mit deinen Tokens aus.', 'wp-starter') }}</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="token-gen-pill token-gen-pill--brand">{{ __('Button', 'wp-starter') }}</span>
                            <span class="token-gen-pill token-gen-pill--brand-2">{{ __('Sekundär', 'wp-starter') }}</span>
                            <span class="token-gen-preview__accent">{{ __('Akzenttext', 'wp-starter') }}</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach($statusNames as $name => $label)
                                <span class="token-gen-pill token-gen-pill--sm token-gen-pill--{{ $name }}">{{ $label }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <x-card variant="outlined" padding="none" class="p-6">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-[52ch]">
                    <p class="m-0 text-h4">{{ __('Herunterladen', 'wp-starter') }}</p>
                    <p class="mt-2 mb-0 text-body-small text-content-secondary">
                        {!! wp_kses(
                            __('Die JSON-Dateien nach <code>config/design-tokens/</code> legen und <code>npm run tokens</code> ausführen, oder sie im WordPress-Backend unter <em>Design tokens</em> hochladen. Das ZIP enthält alles in der Ordnerstruktur des Themes.', 'wp-starter'),
                            ['code' => [], 'em' => []]
                        ) !!}
                    </p>
                </div>
                <div class="flex flex-col items-start gap-3 lg:items-end">
                    <x-button :title="__('Alles als ZIP', 'wp-starter')" x-on:click="download('zip')" x-bind:disabled="state === 'error'" />
                    <div class="flex flex-wrap gap-2 lg:justify-end">
                        @foreach($files as $kind => $fileName)
                            <x-button :title="$fileName" variant="secondary" size="sm" x-on:click="download('{{ $kind }}')" x-bind:disabled="state === 'error'" />
                        @endforeach
                    </div>
                </div>
            </div>
        </x-card>
    </div>
</x-section>
