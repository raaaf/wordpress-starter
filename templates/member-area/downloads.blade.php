{{-- Member Area Downloads --}}
@php
    $perPageOptions = ['20' => '20', '50' => '50', '100' => '100'];

    // Suchfeld und Auswahl bekamen ihre id aus dem name-Attribut, also "search"
    // und "per_page" ohne Instanzpraefix. Zwei Downloadtabellen auf einer Seite
    // erzeugten damit doppelte IDs; alle anderen Layouts praefixen mit uniqid().
    $instanceId = uniqid('downloads-');
@endphp

<div x-data="downloadTable" x-init="init()">

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

        {{-- Category filter — dynamic from facets --}}
        <div class="sm:w-52">
            <div class="select relative">
                <select
                    x-model="category"
                    aria-label="{{ __('Kategorie filtern', 'wp-starter') }}"
                    class="w-full border bg-surface text-content appearance-none cursor-pointer transition-all duration-200 focus:outline-none h-10 text-base pl-4 pr-10 rounded-[var(--input-md-radius)] border-line shadow-[var(--shadow-input)] hover:border-line-strong hover:shadow-[var(--shadow-input-hover)] focus:border-line-focus focus:shadow-[var(--shadow-focus-ring)]"
                >
                    <option value="">{{ __('Alle Kategorien', 'wp-starter') }}</option>
                    <template x-for="cat in categories" :key="cat.slug">
                        <option :value="cat.slug" x-text="cat.label + ' (' + cat.count + ')'"></option>
                    </template>
                </select>
            </div>
        </div>

        {{-- Extension filter — dynamic from facets --}}
        <div class="sm:w-40">
            <div class="select relative">
                <select
                    x-model="ext"
                    aria-label="{{ __('Dateityp filtern', 'wp-starter') }}"
                    class="w-full border bg-surface text-content appearance-none cursor-pointer transition-all duration-200 focus:outline-none h-10 text-base pl-4 pr-10 rounded-[var(--input-md-radius)] border-line shadow-[var(--shadow-input)] hover:border-line-strong hover:shadow-[var(--shadow-input-hover)] focus:border-line-focus focus:shadow-[var(--shadow-focus-ring)]"
                >
                    <option value="">{{ __('Alle Typen', 'wp-starter') }}</option>
                    <template x-for="e in extensions" :key="e.value">
                        <option :value="e.value" x-text="e.label + ' (' + e.count + ')'"></option>
                    </template>
                </select>
            </div>
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
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-content-secondary text-xs uppercase tracking-wide">
                            {{ __('Dateiname', 'wp-starter') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-content-secondary text-xs uppercase tracking-wide whitespace-nowrap">
                            {{ __('Typ', 'wp-starter') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-content-secondary text-xs uppercase tracking-wide whitespace-nowrap">
                            {{ __('Kategorie', 'wp-starter') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-content-secondary text-xs uppercase tracking-wide whitespace-nowrap">
                            {{ __('Datum', 'wp-starter') }}
                        </th>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap"><span class="sr-only">{{ __('Aktionen', 'wp-starter') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in items" :key="item.id">
                        <tr class="border-t border-line hover:bg-surface-secondary transition-colors">

                            {{-- Title + "Neu"-Badge --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a
                                        :href="item.download_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="font-medium text-content hover:text-content-accent transition-colors"
                                    ><span x-text="item.title"></span><span class="sr-only">{{ __('(öffnet in neuem Tab)', 'wp-starter') }}</span></a>
                                    <span
                                        x-show="item.is_updated"
                                        class="badge inline-flex w-fit items-center font-medium text-xs px-[var(--badge-sm-padding-x)] py-[var(--badge-sm-padding-y)] gap-[var(--badge-sm-gap)] rounded-full bg-transparent text-content border border-line"
                                    >{{ __('Neu', 'wp-starter') }}</span>
                                </div>
                            </td>

                            {{-- Extension badge: dynamic variant, use badge token classes directly --}}
                            <td class="px-4 py-3">
                                <span
                                    x-show="item.ext"
                                    class="badge inline-flex w-fit items-center font-medium px-[var(--badge-sm-padding-x)] py-[var(--badge-sm-padding-y)] gap-[var(--badge-sm-gap)] text-xs rounded-md"
                                    :class="badgeClass()"
                                    x-text="item.ext"
                                ></span>
                            </td>

                            <td class="px-4 py-3 text-content-secondary" x-text="item.category_label"></td>
                            <td class="px-4 py-3 text-content-secondary tabular-nums" x-text="item.last_modified"></td>

                            {{-- Download button (ghost) --}}
                            <td class="px-4 py-3 text-right">
                                <a
                                    x-show="item.available"
                                    :href="item.download_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="button inline-flex items-center justify-center font-semibold transition-[color,background,border-color,box-shadow,scale] duration-200 no-underline cursor-pointer select-none focus-visible:outline-none active:scale-[0.98] bg-transparent text-content border border-transparent hover:bg-surface-tertiary active:bg-surface-secondary active:border-line focus-visible:shadow-[var(--shadow-focus-ring-ghost)] px-[var(--button-sm-padding-x)] py-[var(--button-sm-padding-y)] text-xs min-h-[var(--button-sm-min-height)] gap-[var(--button-sm-gap)] rounded-[var(--button-sm-radius)]"
                                >{{ __('Herunterladen', 'wp-starter') }}<span class="sr-only">{{ __('(öffnet in neuem Tab)', 'wp-starter') }}</span></a>
                                <span
                                    x-show="!item.available"
                                    class="text-xs text-content-disabled"
                                >{{ __('Nicht verfügbar', 'wp-starter') }}</span>
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
                                ? 'bg-gradient-to-b from-[var(--gradient-primary-start)] to-[var(--gradient-primary-end)] text-content-inverse border-line'
                                : 'text-content-secondary hover:bg-surface-secondary border-line'"
                            class="inline-flex items-center justify-center min-h-11! min-w-11! rounded-md border text-sm font-medium transition-colors"
                            :aria-label="`{{ __('Seite', 'wp-starter') }} ${n}`"
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
        <span x-text="(loading || error) ? '' : total + ' ' + '{{ __('Dokumente', 'wp-starter') }}'"></span>
    </p>

</div>
