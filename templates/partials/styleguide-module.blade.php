{{--
    Ein Modul der Styleguide-Galerie: Kopfzeile mit Namen, darunter je Instanz
    ein Schalter, darunter die Instanzen selbst.

    Bewusst keine `role="tablist"`: direkt in dieser Galerie steht das Modul
    "Tabs" mit echten Tabs. Zwei ineinanderliegende Tablists im Baum waeren fuer
    Screenreader-Nutzer nicht auseinanderzuhalten. Eine Radiogruppe beschreibt
    ohnehin genauer, was hier passiert, naemlich eine Auswahl unter Alternativen.

    Die Schalter umbrechen statt zu scrollen. Eine scrollende Leiste braeuchte
    Kantenverblendung, und die muesste ueber sechs Flaechenfarben in zwei Modi
    funktionieren. Umbrechen loest dasselbe Problem ohne diese Klasse von
    Fehlern.

    @var string $layout          Layout-Name, z.B. 'cards'
    @var array  $modul           ['label' => string, 'instanzen' => [['anchor' => string, 'html' => string]]]
    @var array  $varianten       Beschriftung je Instanz, gleiche Reihenfolge
    @var array  $variantenTitel  Ausfuehrliche Fassung fuers title-Attribut
    @var bool   $alleVarianten   Vollansicht (?variants=all): nichts ausblenden
--}}
{{-- TemplateRenderTest rendert jedes Template einmal ohne Variablen, um
     Syntaxfehler zu finden. Ohne Standardwerte scheitert es hier an count(). --}}
@php($layout = $layout ?? '')
@php($modul = $modul ?? ['label' => '', 'instanzen' => []])
@php($varianten = $varianten ?? [])
@php($variantenTitel = $variantenTitel ?? [])
@php($alleVarianten = $alleVarianten ?? false)

@php($istZustand = $istZustand ?? array_fill(0, count($modul['instanzen']), false))
@php($anzahl = count($modul['instanzen']))
@php($umschaltbar = $anzahl > 1 && !$alleVarianten)

<div
    class="styleguide-module"
    @if($umschaltbar)
        x-data="{
            aktiv: 0,
            waehlen(index) {
                this.aktiv = index;
                this.$refs.chips.children[index].focus();
            },
            init() {
                {{-- Deep-Link: der Browser springt auf einen Anker, dessen Instanz
                     ausgeblendet sein kann. Dann diese aktivieren und erneut
                     anspringen, weil der erste Sprung ins Leere lief. --}}
                const ziel = window.location.hash.slice(1);
                if (!ziel) return;

                const index = Array.from(this.$el.querySelectorAll('[data-variant]'))
                    .findIndex((panel) => panel.dataset.variant === ziel);

                if (index < 1) return;

                this.aktiv = index;
                this.$nextTick(() => document.getElementById(ziel)?.scrollIntoView());
            }
        }"
    @endif
>
    <div class="border-b bg-surface-secondary border-line">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <p class="flex flex-wrap items-baseline gap-2 m-0 text-body-small text-content-secondary">
                <span class="font-medium text-content">{{ $modul['label'] }}</span>
                <span class="text-code">{{ $layout }}</span>
                @if($anzahl > 1 && $alleVarianten)
                    <span>{{ sprintf(_n('%d Variante', '%d Varianten', $anzahl, 'wp-starter'), $anzahl) }}</span>
                @endif
            </p>

            @if($umschaltbar)
                <div
                    x-ref="chips"
                    role="radiogroup"
                    aria-label="{{ __('Variante', 'wp-starter') }}"
                    class="flex flex-wrap gap-1 mt-2"
                    x-on:keydown.arrow-right.prevent="waehlen((aktiv + 1) % {{ $anzahl }})"
                    x-on:keydown.arrow-down.prevent="waehlen((aktiv + 1) % {{ $anzahl }})"
                    x-on:keydown.arrow-left.prevent="waehlen((aktiv - 1 + {{ $anzahl }}) % {{ $anzahl }})"
                    x-on:keydown.arrow-up.prevent="waehlen((aktiv - 1 + {{ $anzahl }}) % {{ $anzahl }})"
                >
                        @foreach($modul['instanzen'] as $index => $instanz)
                        {{-- Zustaende hinter einem Trenner: sie sind Randfaelle fuer
                             die Abnahme, keine Einstellungen, die ein Redakteur
                             waehlen wuerde. Ohne die Trennung liest sich "Ohne Bild"
                             wie eine dritte Spaltenvariante. --}}
                        @if($istZustand[$index] && !($istZustand[$index - 1] ?? true))
                            <span class="self-center mx-2 text-body-small text-content-tertiary" aria-hidden="true">{{ __('Zustände', 'wp-starter') }}</span>
                        @endif
                        <button
                            type="button"
                            role="radio"
                            x-bind:aria-checked="aktiv === {{ $index }} ? 'true' : 'false'"
                            x-bind:tabindex="aktiv === {{ $index }} ? '0' : '-1'"
                            x-on:click="waehlen({{ $index }})"
                            @if(!empty($variantenTitel[$index]) && $variantenTitel[$index] !== ($varianten[$index] ?? '')) title="{{ $variantenTitel[$index] }}" @endif
                            x-bind:class="aktiv === {{ $index }}
                                ? 'bg-surface text-content border-line shadow-[var(--shadow-button)]'
                                : 'border-transparent text-content-secondary hover:text-content'"
                            class="px-3 py-1 text-sm font-medium border rounded-full cursor-pointer focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring)]"
                        >{{ $varianten[$index] ?? '' }}</button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @foreach($modul['instanzen'] as $index => $instanz)
        <div
            data-variant="{{ $instanz['anchor'] }}"
            @if($umschaltbar)
                x-show="aktiv === {{ $index }}"
                {{-- Serverseitig ausgeblendet, damit vor Alpines Start nicht
                     kurz alle Varianten uebereinander stehen. --}}
                @if($index > 0) style="display: none" @endif
            @endif
        >{!! $instanz['html'] !!}</div>
    @endforeach
</div>
