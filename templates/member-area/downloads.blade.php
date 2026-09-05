{{-- Member Area Downloads --}}
@php
    $perPageOptions = ['20' => '20', '50' => '50', '100' => '100'];

    // Suchfeld und Auswahl bekamen ihre id aus dem name-Attribut, also "search"
    // und "per_page" ohne Instanzpraefix. Zwei Downloadtabellen auf einer Seite
    // erzeugten damit doppelte IDs; alle anderen Layouts praefixen mit uniqid().
    $instanceId = uniqid('downloads-');
@endphp

{{-- Alpine calls init() on Alpine.data components automatically; an
     explicit x-init="init()" here would run it a second time (double
     fetch, double $watch registration). --}}
<div x-data="downloadTable">

    {{-- Toolbar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-5">

        {{-- Search --}}
        <div class="sm:max-w-xs w-full">
            <x-input
                name="search"
                :id="$instanceId . '-search'"
                type="search"
                placeholder="{{ __('Suchen…', 'wp-starter') }}"
                aria-label="{{ __('Downloads durchsuchen', 'wp-starter') }}"
                iconLeft="search"
                x-model.debounce.350ms="search"
            />
        </div>

        {{-- Category filter, dynamic from facets --}}
        <div class="sm:w-52">
            @include('partials.member-area-filter-select', [
                'xModel' => 'category',
                'optionsVar' => 'categories',
                'ariaLabel' => __('Kategorie filtern', 'wp-starter'),
                'placeholder' => __('Alle Kategorien', 'wp-starter'),
                'valueExpr' => 'opt.slug',
                'textExpr' => "opt.label + ' (' + opt.count + ')'",
                'keyExpr' => 'opt.slug',
            ])
        </div>

        {{-- Extension filter, dynamic from facets --}}
        <div class="sm:w-40">
            @include('partials.member-area-filter-select', [
                'xModel' => 'ext',
                'optionsVar' => 'extensions',
                'ariaLabel' => __('Dateityp filtern', 'wp-starter'),
                'placeholder' => __('Alle Typen', 'wp-starter'),
                'valueExpr' => 'opt.value',
                'textExpr' => "opt.label + ' (' + opt.count + ')'",
                'keyExpr' => 'opt.value',
            ])
        </div>

        {{-- Per-page --}}
        <div class="sm:w-24 sm:ml-auto">
            <x-select
                name="per_page"
                :id="$instanceId . '-per-page'"
                :options="$perPageOptions"
                :aria-label="__('Einträge pro Seite', 'wp-starter')"
                x-model="perPage"
            />
        </div>

    </div>

    {{-- Loading state --}}
    <div x-show="loading" x-cloak class="space-y-2" role="status" aria-label="{{ __('Dokumente werden geladen...', 'wp-starter') }}">
        @foreach(range(1, 6) as $i)
            <div class="h-12 bg-surface-secondary rounded-lg animate-pulse"></div>
        @endforeach
    </div>

    {{-- Error state --}}
    <div x-show="!loading && error" x-cloak>
        <x-alert variant="error">
            <span x-text="error"></span>
        </x-alert>
    </div>

    {{-- Empty state --}}
    <div x-show="!loading && !error && items.length === 0" x-cloak>
        <x-card variant="default" padding="lg">
            <div class="text-center py-8 text-content-secondary">
                <x-icon name="download" class="w-12 h-12 mx-auto mb-3 text-icon-tertiary" />
                <p>{{ __('Keine Dokumente gefunden.', 'wp-starter') }}</p>
                <div x-show="search || category || ext" class="mt-4">
                    <x-button
                        type="button"
                        x-on:click="search = ''; category = ''; ext = ''"
                        :title="__('Filter zurücksetzen', 'wp-starter')"
                        variant="secondary"
                        size="sm"
                    />
                </div>
            </div>
        </x-card>
    </div>

    {{-- Table --}}
    <div x-show="!loading && !error && items.length > 0" x-cloak>
        <div class="overflow-x-auto rounded-lg border border-line">
            <table class="w-full text-sm">
                <caption class="sr-only">{{ __('Verfügbare Dokumente', 'wp-starter') }}</caption>
                <thead>
                    <tr class="bg-surface-secondary border-b border-line">
                        <th scope="col" class="px-4 py-3 text-left font-normal text-content-secondary text-xs uppercase tracking-wide">
                            {{ __('Dateiname', 'wp-starter') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-normal text-content-secondary text-xs uppercase tracking-wide whitespace-nowrap">
                            {{ __('Typ', 'wp-starter') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-normal text-content-secondary text-xs uppercase tracking-wide whitespace-nowrap">
                            {{ __('Kategorie', 'wp-starter') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-normal text-content-secondary text-xs uppercase tracking-wide whitespace-nowrap">
                            {{ __('Datum', 'wp-starter') }}
                        </th>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap"><span class="sr-only">{{ __('Aktionen', 'wp-starter') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in items" :key="item.id">
                        <tr class="border-t border-line hover:bg-surface-secondary transition-colors">

                            {{-- Title + "Neu"-Badge. safeUrl() (member-area.ts) returns an empty
                                 string for rejected URLs; a rejected URL renders the title as
                                 plain text instead of a dead link (template x-if keeps the unused
                                 branch out of the DOM, so no anchor without a working href ever
                                 announces "opens in new tab" to assistive tech). --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <template x-if="item.download_url">
                                        <a
                                            :href="item.download_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="font-normal text-content hover:text-content-accent transition-colors"
                                        ><span x-text="item.title"></span><span class="sr-only">{{ __('(öffnet in neuem Tab)', 'wp-starter') }}</span></a>
                                    </template>
                                    <template x-if="!item.download_url">
                                        <span class="font-normal text-content" x-text="item.title"></span>
                                    </template>
                                    {{-- x-badge never echoes $attributes, so x-show has to sit on a
                                         wrapping span; the badge itself stays a plain static x-badge. --}}
                                    <span x-show="item.is_updated">
                                        <x-badge variant="brand" size="sm">{{ __('Neu', 'wp-starter') }}</x-badge>
                                    </span>
                                </div>
                            </td>

                            {{-- Extension badge: standardised to gray/outline via x-badge instead of
                                 the former per-extension colour map; x-badge cannot forward
                                 :class either, so the wrapping span carries x-show, x-text goes on
                                 an inner span for the slot content. --}}
                            <td class="px-4 py-3">
                                <span x-show="item.ext">
                                    <x-badge variant="gray" style="outline" size="sm">
                                        <span x-text="item.ext"></span>
                                    </x-badge>
                                </span>
                            </td>

                            <td class="px-4 py-3 text-content-secondary" x-text="item.category_label"></td>
                            <td class="px-4 py-3 text-content-secondary tabular-nums" x-text="item.last_modified"></td>

                            {{-- Download button (ghost, not <x-button>: needs a per-row dynamic
                                 :href/x-if that the component's static props cannot express).
                                 safeUrl() (member-area.ts) returns an empty string for rejected
                                 URLs; item.available alone is not enough (a rejected URL can still
                                 be "available"), so the link branch requires both, and the
                                 "Nicht verfügbar" branch covers either failing. template x-if
                                 keeps the unused branch out of the DOM. --}}
                            <td class="px-4 py-3 text-right">
                                <template x-if="item.available && item.download_url">
                                    <a
                                        :href="item.download_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="button inline-flex items-center justify-center font-normal transition-[color,background,border-color,box-shadow,scale] duration-200 no-underline cursor-pointer select-none active:scale-[0.98] bg-transparent text-content border border-transparent hover:bg-surface-tertiary active:bg-surface-secondary active:border-line focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)] px-[var(--button-sm-padding-x)] py-[var(--button-sm-padding-y)] text-xs min-h-[var(--button-sm-min-height)] gap-[var(--button-sm-gap)] rounded-[var(--button-sm-radius)]"
                                    >{{ __('Herunterladen', 'wp-starter') }}<span class="sr-only">{{ __('(öffnet in neuem Tab)', 'wp-starter') }}</span></a>
                                </template>
                                <template x-if="!item.available || !item.download_url">
                                    <span class="text-xs text-content-disabled">{{ __('Nicht verfügbar', 'wp-starter') }}</span>
                                </template>
                            </td>

                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Footer: pagination. Die Trefferzeile lebt jetzt ausserhalb dieses
             x-show-Containers, siehe Kommentar unten. Nur noch ein Kind im
             Flex-Layout -> justify-end statt justify-between, damit die
             Pagination weiterhin rechts ausgerichtet bleibt. --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 mt-4">

            <div x-show="pages > 1" class="flex items-center gap-1">

                {{-- min-h-11!/min-w-11! statt fixer w-8 h-8: 44px Hit-Flaeche wie bei den
                     Icon-Buttons in team.blade.php, das Icon bleibt 16px. --}}
                <button
                    type="button"
                    x-on:click="setPage(currentPage - 1)"
                    :disabled="currentPage === 1"
                    class="inline-flex items-center justify-center min-h-11! min-w-11! rounded-md border border-line text-content-secondary hover:bg-surface-secondary disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    aria-label="{{ __('Vorherige Seite', 'wp-starter') }}"
                >
                    <x-icon name="chevron-left" class="w-4 h-4" />
                </button>

                <template x-for="(n, i) in pageNumbers()" :key="i">
                    <span>
                        <button
                            x-show="n !== '...'"
                            type="button"
                            x-on:click="setPage(n)"
                            :class="n === currentPage
                                ? 'bg-surface-brand text-content-on-brand border-line'
                                : 'text-content-secondary hover:bg-surface-secondary border-line'"
                            class="inline-flex items-center justify-center min-h-11! min-w-11! rounded-md border text-sm font-normal transition-colors"
                            {{-- Raw sink: wp_json_encode() with the JSON_HEX_* flags produces a
                                 JS string literal whose internal quotes/tags/amp are all
                                 \u-escaped, so it is safe both as JS and inside this
                                 single-quoted HTML attribute; esc_js() alone only targets
                                 single-quoted strings and broke inside the previous backtick
                                 template literal. --}}
                            :aria-label='{!! wp_json_encode(__('Seite', 'wp-starter'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!} + " " + n'
                            :aria-current="n === currentPage ? 'page' : false"
                            x-text="n"
                        ></button>
                        <span
                            x-show="n === '...'"
                            class="inline-flex items-center justify-center w-8 h-8 text-content-disabled text-sm"
                        >…</span>
                    </span>
                </template>

                <button
                    type="button"
                    x-on:click="setPage(currentPage + 1)"
                    :disabled="currentPage === pages"
                    class="inline-flex items-center justify-center min-h-11! min-w-11! rounded-md border border-line text-content-secondary hover:bg-surface-secondary disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    aria-label="{{ __('Nächste Seite', 'wp-starter') }}"
                >
                    <x-icon name="chevron-right" class="w-4 h-4" />
                </button>

            </div>
        </div>
    </div>

    {{-- aria-live: Suche und Kategoriefilter tauschen die Tabelle aus, ohne
         dass die Seite neu laedt. Die Zeile stand vorher IM x-show-Container
         der Tabelle und war bei 0 Treffern damit display:none -- der
         Leerzustand wurde nie angesagt (WCAG 4.1.3). Deshalb hier bewusst
         AUSSERHALB jedes x-show gezogen: sie bleibt in jedem Zustand
         (loading/error/leer/Tabelle) im DOM und zeigt bei 0 Treffern ehrlich
         "0 Dokumente" statt gar nichts. Einzige Live-Region fuer die
         Trefferzahl, keine zweite Ansage an anderer Stelle.

         polite statt assertive, damit die Meldung das Tippen nicht
         unterbricht. Waehrend loading bleibt der Text leer, sonst wuerde
         beim Hydrieren kurz "0 Dokumente" angesagt, bevor der erste fetch
         die echte Zahl liefert. Waehrend error ebenfalls leer, sonst wuerde
         neben der Fehlermeldung im Alert faelschlich "0 Dokumente" angesagt --
         die Fehlermeldung selbst uebernimmt die Ansage. --}}
    <p class="text-sm text-content-secondary mt-4" aria-live="polite" aria-atomic="true">
        <span x-text="(loading || error) ? '' : total + ' ' + '{{ esc_js(__('Dokumente', 'wp-starter')) }}'"></span>
    </p>

</div>
