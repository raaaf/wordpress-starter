{{--
    Sprungnavigation fuer die Styleguide-Seite.

    Die Seite ist ueber 50.000 Pixel hoch. Ohne Sprungmarken kommt man nur per
    Scrollen oder Browsersuche zu einem Modul, und wer ein Layout abnehmen will,
    sucht laenger als er prueft.

    Die Liste entsteht aus denselben Ankern, die die Sektionen ohnehin tragen,
    und aus den Labels der registrierten Feldgruppe. Damit kann sie nicht von
    dem abweichen, was FlexibleContent.php kennt.

    Je Layouttyp nur der erste Treffer: der Katalog enthaelt viele Instanzen
    desselben Typs, und eine Liste mit 84 Eintraegen waere so unbrauchbar wie
    gar keine.
--}}
@php
    $sprungziele = [];

    if (have_rows('page_sections')) {
        $feld = get_field_object('page_sections');
        $labels = array_column($feld['layouts'] ?? [], 'label', 'name');
        $zaehler = [];

        while (have_rows('page_sections')) {
            the_row();
            $layout = get_row_layout();
            $zaehler[$layout] = ($zaehler[$layout] ?? 0) + 1;

            // Zwischentitel ueberspringen: der erste "one_column" traegt oft nur
            // eine <h2>-Ueberschrift zwischen Layoutgruppen, kein eigenes Modul
            // (dieselbe Erkennung wie in page-styleguide.blade.php).
            $istZwischentitel = $layout === 'one_column' && preg_match('/^\s*<h2[\s>]/i', (string) get_sub_field('content')) === 1;

            if ($istZwischentitel || isset($sprungziele[$layout]) || !isset($labels[$layout])) {
                continue;
            }

            $eigener = get_sub_field('section_anchor');
            $sprungziele[$layout] = [
                'anchor' => $eigener ?: str_replace('_', '-', $layout) . '-' . $zaehler[$layout],
                'label' => $labels[$layout],
            ];
        }

        // Die Schleife oben hat den internen Zeiger verbraucht.
        reset_rows();
    }

    ksort($sprungziele);
@endphp

@if(!empty($sprungziele))
    <details class="group sticky z-30 mx-auto mb-8 max-w-7xl px-4 sm:px-6 lg:px-8 top-[calc(var(--header-height,80px)+0.5rem)]">
        <summary class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border rounded-full cursor-pointer select-none border-line bg-surface text-content shadow-[var(--shadow-button)] hover:bg-surface-secondary focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring)]">
            {{ __('Springe zu', 'wp-starter') }}
            {{-- Dreht mit dem Aufklappzustand: vorher zeigte der Pfeil auch bei
                 offener Liste nach unten und behauptete damit das Gegenteil des
                 Zustands, in dem die Liste gerade war. Nutzt "group-open" statt
                 eines Alpine-Ausdrucks, deshalb kein x-rotating-chevron hier: das
                 dreht ueber :class, nicht ueber eine CSS-Gruppe. --}}
            <span class="inline-block transition-transform duration-[var(--motion-enter-duration)] ease-[var(--motion-enter-ease)] group-open:rotate-180">
                <x-icon name="chevron-down" class="w-4 h-4" />
            </span>
        </summary>

        {{-- Nach dem Sprung schliessen: die Liste hat ihren Zweck erfuellt und
             wuerde sonst als Kasten ueber dem Ziel stehen bleiben.
             Nur echte Anker-Klicks behandeln (closest('a')): sonst schliesst
             auch ein Klick in die Luecke daneben die Liste und klaut den
             Fokus. Der Fokus geht nach dem Sprung ans Ziel, nicht zurueck
             zum summary (WCAG 2.4.3) - ein focus() auf summary ohne
             preventScroll wuerde sonst zurueck zur Navigation scrollen und
             den Ankersprung rueckgaengig machen. Das Ziel bekommt
             tabindex="-1" per setAttribute, weil Sections normalerweise
             nicht fokussierbar sind. Kein preventDefault: die native
             Hash-Navigation soll laufen, focus() im setTimeout haelt danach
             nur die Scrollposition, ohne selbst zu scrollen. --}}
        {{-- Positionsanzeige: die Seite ist ueber 50.000px hoch, und ohne
             Markierung musste man die Liste aufklappen und Modulnamen aus dem
             Gedaechtnis abgleichen, um zu wissen, wo man gerade steht. Der
             Beobachter markiert den obersten Eintrag, der noch im Blick ist.
             Reines Lesen, kein Scrollen, kein Fokuswechsel. --}}
        <nav
            aria-label="{{ __('Module', 'wp-starter') }}"
            x-data="styleguideSprungnavigation"
            x-on:click="const link = $event.target.closest('a'); if (!link) return; const d = $el.closest('details'); d.open = false; const ziel = document.getElementById(link.getAttribute('href').slice(1)); if (!ziel) return; setTimeout(() => { ziel.setAttribute('tabindex', '-1'); ziel.focus(); })"
            class="p-5 mt-2 border rounded-[var(--card-radius)] border-line bg-surface shadow-[var(--shadow-card)]"
        >
            <ul class="m-0 list-none columns-2 gap-x-8 sm:columns-3 lg:columns-4">
                @foreach(array_column($sprungziele, 'label', 'anchor') as $anchor => $label)
                    <li class="break-inside-avoid">
                        <a href="#{{ $anchor }}"
                           data-anchor="{{ $anchor }}"
                           :aria-current="aktiv === '{{ $anchor }}' ? 'true' : null"
                           :class="aktiv === '{{ $anchor }}' ? 'font-medium text-content' : 'text-content-secondary'"
                           class="block py-1.5 text-sm no-underline hover:text-content focus-visible:outline-none focus-visible:text-content focus-visible:shadow-[var(--shadow-focus-ring)]">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </details>
@endif