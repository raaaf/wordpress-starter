{{--
    Jump menu panel results: hit list and section list (the search field is partials/jump-menu-search.blade.php, fixed above the scroll area).
    Included by flexible/jump-menu.blade.php inside the `jumpMenu` Alpine scope.

    @param bool $showSearch
    @param string $title - accessible name of the section list
--}}

{{-- Container queries, not viewport breakpoints: the panel width is fixed, not the viewport's. --}}
<div class="@container">
    @if($showSearch)
        <template x-if="query.trim().length >= 2">
            <div>
                <p class="m-0 text-sm text-content-secondary" x-show="hits.length === 0">{{ __('Keine Treffer', 'wp-starter') }}</p>
                <ul class="m-0 list-none">
                    <template x-for="hit in hits" :key="hit.id">
                        <li class="border-b border-line last:border-b-0">
                            <button type="button" x-on:click="goHit(hit)" class="jump-menu-row block w-full px-2 py-3 text-left cursor-pointer rounded-[var(--rafael-radius-sm)] focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]">
                                <span class="flex items-baseline justify-between gap-3" x-show="hit.label || hit.count > 1">
                                    <span class="text-sm font-normal text-content" x-text="hit.label"></span>
                                    <span class="text-xs shrink-0 text-content-secondary" x-show="hit.count > 1" x-text="hit.count + ' {{ esc_js(__('Treffer', 'wp-starter')) }}'"></span>
                                </span>
                                <span class="block text-sm text-content-secondary line-clamp-2"><span x-text="hit.before"></span><span class="rounded-sm bg-[var(--color-accent-100)] text-[var(--color-accent-900)]" x-text="hit.match"></span><span x-text="hit.after"></span></span>
                            </button>
                        </li>
                    </template>
                </ul>
            </div>
        </template>
    @endif

    <nav aria-label="{{ $title }}" @if($showSearch) x-show="query.trim().length < 2" @endif>
        <ul class="m-0 list-none columns-1 gap-x-8 @md:columns-2">
            <template x-for="entry in entries" :key="entry.id">
                <li class="break-inside-avoid break-words">
                    <a :href="'#' + entry.id"
                       x-on:click.prevent="go(entry.id)"
                       :aria-current="aktiv === entry.id ? 'location' : null"
                       :class="aktiv === entry.id ? 'font-normal text-content' : 'text-content-secondary hover:text-content'"
                       class="jump-menu-row block px-2 py-1.5 text-sm no-underline rounded-[var(--rafael-radius-sm)] focus-visible:text-content focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]"
                       x-text="entry.label"></a>
                </li>
            </template>
        </ul>
    </nav>
</div>
