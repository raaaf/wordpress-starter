{{-- Member Area Downloads --}}
@php
    $perPageOptions = ['20' => '20', '50' => '50', '100' => '100'];
@endphp

<div x-data="downloadTable" x-init="init()">

    {{-- Toolbar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-5">

        {{-- Search --}}
        <div class="sm:max-w-xs w-full relative">
            <div class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none text-icon-secondary">
                <x-icon name="search" class="w-4 h-4" />
            </div>
            <input
                type="search"
                name="search"
                placeholder="{{ __('Suchen…', 'wp-starter') }}"
                x-model.debounce.350ms="search"
                class="input w-full border bg-surface text-content placeholder:text-content-tertiary transition-all duration-200 focus:outline-none rounded-[var(--input-md-radius)] border-line shadow-[var(--shadow-input)] hover:border-line-strong hover:shadow-[var(--shadow-input-hover)] focus:border-line-focus focus:shadow-[var(--shadow-focus-ring)] h-10 text-base pl-10 pr-4"
            />
        </div>

        {{-- Category filter — dynamic from facets --}}
        <div class="sm:w-52">
            <div class="select relative">
                <select
                    x-model="category"
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
                :options="$perPageOptions"
                x-model="perPage"
            />
        </div>

    </div>

    {{-- Loading state --}}
    <div x-show="loading" x-cloak class="space-y-2">
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
                <thead>
                    <tr class="bg-surface-secondary border-b border-line">
                        <th class="px-4 py-3 text-left font-semibold text-content-secondary text-xs uppercase tracking-wide">
                            {{ __('Dateiname', 'wp-starter') }}
                        </th>
                        <th class="px-4 py-3 text-left font-semibold text-content-secondary text-xs uppercase tracking-wide whitespace-nowrap">
                            {{ __('Typ', 'wp-starter') }}
                        </th>
                        <th class="px-4 py-3 text-left font-semibold text-content-secondary text-xs uppercase tracking-wide whitespace-nowrap">
                            {{ __('Kategorie', 'wp-starter') }}
                        </th>
                        <th class="px-4 py-3 text-left font-semibold text-content-secondary text-xs uppercase tracking-wide whitespace-nowrap">
                            {{ __('Datum', 'wp-starter') }}
                        </th>
                        <th class="px-4 py-3 whitespace-nowrap"></th>
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
                                        x-text="item.title"
                                    ></a>
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
                                    :class="badgeClass(item.ext_variant)"
                                    x-text="item.ext"
                                ></span>
                            </td>

                            <td class="px-4 py-3 text-content-secondary" x-text="item.category_label"></td>
                            <td class="px-4 py-3 text-content-secondary" x-text="item.last_modified"></td>

                            {{-- Download button (ghost) --}}
                            <td class="px-4 py-3 text-right">
                                <a
                                    x-show="item.available"
                                    :href="item.download_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="button inline-flex items-center justify-center font-semibold transition-all duration-200 no-underline cursor-pointer select-none focus-visible:outline-none bg-transparent text-content border border-transparent hover:bg-surface-tertiary active:bg-surface-secondary px-[var(--button-sm-padding-x)] py-[var(--button-sm-padding-y)] text-xs min-h-[var(--button-sm-min-height)] gap-[var(--button-sm-gap)] rounded-[var(--button-sm-radius)]"
                                >{{ __('Herunterladen', 'wp-starter') }}</a>
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

        {{-- Footer: total + pagination --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mt-4">

            <p class="text-sm text-content-secondary">
                <span x-text="total"></span> {{ __('Dokumente', 'wp-starter') }}
            </p>

            <div x-show="pages > 1" class="flex items-center gap-1">

                <button
                    type="button"
                    x-on:click="setPage(currentPage - 1)"
                    :disabled="currentPage === 1"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-line text-content-secondary hover:bg-surface-secondary disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
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
                            class="inline-flex items-center justify-center w-8 h-8 rounded-md border text-sm font-medium transition-colors"
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
                    class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-line text-content-secondary hover:bg-surface-secondary disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    aria-label="{{ __('Nächste Seite', 'wp-starter') }}"
                >
                    <x-icon name="chevron-right" class="w-4 h-4" />
                </button>

            </div>
        </div>
    </div>

</div>
