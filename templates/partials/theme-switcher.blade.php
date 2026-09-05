{{--
    Farbschema-Umschalter, vorerst nur auf der Styleguide-Seite.

    Warum drei Zustaende und kein Sonne-Mond-Knopf: mit zwei Zustaenden kommt man
    nie zu "folgt dem System" zurueck, sobald man einmal geklickt hat.

    Warum ein Inline-Skript: die Auswahl muss stehen, bevor das erste Pixel
    gemalt wird, sonst blitzt bei jedem Laden der falsche Modus auf. Das ist der
    einzige Ort im Theme, an dem ein Inline-Skript diesen Aufwand wert ist.

    Der Umschalter erscheint nur, wenn die Theme-Option auf "System" steht. Hat
    der Betreiber sich fuer hell oder dunkel entschieden, waere eine Kontrolle
    hier eine Falle: sie wuerde diese Entscheidung stillschweigend uebergehen.
--}}
@php
    $colorScheme = \WordpressStarter\Acf\Fields::option('color_scheme', 'system');
@endphp

@if($colorScheme === 'system')
    {{-- Aeusserer Wrapper traegt KEIN x-cloak: er bleibt vom ersten Rendern an
         im Flex-Layout des Headers und reserviert die Groesse der Kontrolle,
         damit deren Erscheinen beim Alpine-Hydrieren die Nachbarn nicht
         verschiebt (CLS). min-h/min-w naehern die gerenderte Groesse an
         (drei Buttons "System"/"Hell"/"Dunkel" plus Innenabstand). --}}
    <div class="min-h-9 min-w-[13.5rem]">
    <noscript>
        <span class="sr-only">{{ __('Der Design-Umschalter benötigt JavaScript.', 'wp-starter') }}</span>
    </noscript>
    <div
        class="flex items-center gap-1 p-1 border rounded-full border-line bg-surface-secondary"
        role="radiogroup"
        aria-label="{{ __('Farbschema', 'wp-starter') }}"
        {{-- Stabiler Testanker: die Klassen sind Utility-Klassen und aendern sich. --}}
        data-theme-switcher
        {{-- Ohne JS bliebe die Kontrolle sichtbar, aber wirkungslos (Klicks
             loesen nichts aus). x-cloak haelt sie verborgen, bis Alpine sie
             uebernimmt. --}}
        x-cloak
        x-ref="optionen"
        x-data="{
            {{-- Storage key comes from data-theme-storage-key on <html> (set in
                 header.blade.php), the single source shared with its inline
                 anti-flash script. --}}
            storageKey: document.documentElement.dataset.themeStorageKey || 'wp-starter-theme',
            mode: localStorage.getItem(document.documentElement.dataset.themeStorageKey || 'wp-starter-theme') || 'system',
            werte: ['system', 'light', 'dark'],
            apply(next) {
                this.mode = next;
                if (next === 'system') {
                    localStorage.removeItem(this.storageKey);
                    document.documentElement.removeAttribute('data-theme');
                } else {
                    localStorage.setItem(this.storageKey, next);
                    document.documentElement.setAttribute('data-theme', next);
                }
            },
            waehlen(index) {
                this.apply(this.werte[index]);
                this.$refs.optionen.children[index].focus();
            }
        }"
        {{-- Pfeiltasten mit Umlauf statt nur Klick: sonst ist per Tastatur nur
             die gerade aktive Option erreichbar (WCAG 2.1.1). --}}
        x-on:keydown.arrow-right.prevent="waehlen((werte.indexOf(mode) + 1) % werte.length)"
        x-on:keydown.arrow-down.prevent="waehlen((werte.indexOf(mode) + 1) % werte.length)"
        x-on:keydown.arrow-left.prevent="waehlen((werte.indexOf(mode) - 1 + werte.length) % werte.length)"
        x-on:keydown.arrow-up.prevent="waehlen((werte.indexOf(mode) - 1 + werte.length) % werte.length)"
        x-on:keydown.home.prevent="waehlen(0)"
        x-on:keydown.end.prevent="waehlen(werte.length - 1)"
    >
        @foreach(['system' => __('System', 'wp-starter'), 'light' => __('Hell', 'wp-starter'), 'dark' => __('Dunkel', 'wp-starter')] as $value => $label)
            <button
                type="button"
                role="radio"
                {{-- Serverseitiger Startwert vor Alpine-Boot: System ist der
                     Default vor dem localStorage-Lesen, Alpine ueberschreibt danach. --}}
                aria-checked="{{ $value === 'system' ? 'true' : 'false' }}"
                tabindex="{{ $value === 'system' ? '0' : '-1' }}"
                x-bind:aria-checked="mode === '{{ $value }}' ? 'true' : 'false'"
                x-bind:tabindex="mode === '{{ $value }}' ? '0' : '-1'"
                x-on:click="apply('{{ $value }}')"
                {{-- font-semibold ist der farbunabhaengige Hinweis auf die
                     ausgewaehlte Option, aria-checked traegt sie fuer AT. --}}
                x-bind:class="mode === '{{ $value }}' ? 'bg-[var(--bg-brand-tint)] text-content-brand ring-1 ring-inset ring-[var(--border-brand)] font-semibold' : 'text-content-secondary hover:text-content'"
                class="px-3 py-1 text-sm font-normal transition-colors rounded-full cursor-pointer focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]"
            >{{ $label }}</button>
        @endforeach
    </div>
    </div>
@endif