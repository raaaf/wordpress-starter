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
    <details class="sticky z-30 mx-auto mb-8 max-w-7xl px-4 sm:px-6 lg:px-8 top-[calc(var(--header-height,80px)+0.5rem)]">
        <summary class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border rounded-full cursor-pointer select-none border-line bg-surface text-content shadow-[var(--shadow-button)] hover:bg-surface-secondary focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring)]">
            {{ __('Springe zu', 'wp-starter') }}
            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path d="M5 7.5 10 12.5 15 7.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
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
        <nav
            aria-label="{{ __('Module', 'wp-starter') }}"
            x-data
            x-on:click="const link = $event.target.closest('a'); if (!link) return; const d = $el.closest('details'); d.open = false; const ziel = document.getElementById(link.getAttribute('href').slice(1)); if (!ziel) return; setTimeout(() => { ziel.setAttribute('tabindex', '-1'); ziel.focus(); })"
            class="p-5 mt-2 border rounded-[var(--card-radius)] border-line bg-surface shadow-[var(--shadow-card)]"
        >
            <ul class="m-0 list-none columns-2 gap-x-8 sm:columns-3 lg:columns-4">
                @foreach(array_column($sprungziele, 'label', 'anchor') as $anchor => $label)
                    <li class="break-inside-avoid">
                        <a href="#{{ $anchor }}" class="block py-1.5 text-sm no-underline text-content-secondary hover:text-content focus-visible:outline-none focus-visible:text-content focus-visible:shadow-[var(--shadow-focus-ring)]">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </details>
@endif