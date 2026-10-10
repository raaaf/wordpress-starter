{{--
    Jump menu search field with its live result count. Sits above the scroll area of the panel,
    so it stays in view while the list scrolls.

    @param string $idPrefix - unique per instance, keeps the input id unique
--}}

<div class="shrink-0 p-5 pb-3">
    <x-input
        name="jump-menu-search"
        :id="$idPrefix . '-search'"
        type="search"
        placeholder="{{ __('Seite durchsuchen…', 'wp-starter') }}"
        aria-label="{{ __('Seite durchsuchen', 'wp-starter') }}"
        iconLeft="search"
        autocomplete="off"
        x-model.debounce.200ms="query"
    />
    <p class="sr-only" aria-live="polite" x-text="query.trim().length < 2 ? '' : (hits.length === 0 ? '{{ esc_js(__('Keine Treffer', 'wp-starter')) }}' : hits.length + ' {{ esc_js(__('Treffer', 'wp-starter')) }}')"></p>
</div>
