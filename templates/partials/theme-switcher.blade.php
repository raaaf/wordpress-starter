{{--
    Farbschema-Umschalter: mit Beschriftung auf der Styleguide-Seite, als
    Symbolleiste ($compact = true) im Header.

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
    $compact = $compact ?? false;
    // Line icons, 24px grid, same stroke as the theme icons.
    $symbole = [
        'system' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
        'light' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>',
        'dark' => '<path d="M20.5 14.1A8.5 8.5 0 1 1 9.9 3.5a6.6 6.6 0 0 0 10.6 10.6z"/>',
    ];
@endphp

@if($colorScheme === 'system')
    {{-- Aeusserer Wrapper traegt KEIN x-cloak: er bleibt vom ersten Rendern an
         im Flex-Layout des Headers und reserviert die Groesse der Kontrolle,
         damit deren Erscheinen beim Alpine-Hydrieren die Nachbarn nicht
         verschiebt (CLS). min-h/min-w naehern die gerenderte Groesse an
         (drei Buttons "System"/"Hell"/"Dunkel" plus Innenabstand). --}}
    {{-- Kompakt: drei 40px-Knoepfe, 2px Innenabstand und 1px Rahmen je Seite (126 x 46 px). --}}
    <div class="{{ $compact ? 'min-h-[2.875rem] min-w-[7.875rem]' : 'min-h-9 min-w-[13.5rem]' }}">
    <noscript>
        <span class="sr-only">{{ __('Der Design-Umschalter benötigt JavaScript.', 'wp-starter') }}</span>
    </noscript>
    <div
        class="flex items-center {{ $compact ? 'gap-0 p-0.5' : 'gap-1 p-1' }} border rounded-full border-line bg-surface-secondary"
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
                @if($compact)
                    {{-- Symbol only: the name goes to aria-label and the tooltip.
                         The selected option is a filled, outlined disc, not colour alone. --}}
                    aria-label="{{ $label }}"
                    title="{{ $label }}"
                    x-bind:class="mode === '{{ $value }}' ? 'bg-surface text-content ring-1 ring-inset ring-line-strong' : 'text-content-secondary hover:text-content'"
                    class="inline-flex items-center justify-center w-10 h-10 transition-colors rounded-full cursor-pointer focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]"
                >
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $symbole[$value] !!}</svg>
                </button>
                @else
                x-bind:class="mode === '{{ $value }}' ? 'bg-[var(--bg-brand-tint)] text-content-brand ring-1 ring-inset ring-[var(--border-brand)] font-semibold' : 'text-content-secondary hover:text-content'"
                class="px-3 py-1 text-sm font-normal transition-colors rounded-full cursor-pointer focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]"
            >{{ $label }}</button>
                @endif
        @endforeach
    </div>
    </div>
@endif