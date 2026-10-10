{{--
    Sprungmenue - Flexible Content Layout

    Uses shared components: x-input, x-icon
    Uses Alpine.js (`jumpMenu`, resources/js/jump-menu.ts)
    Fields: title, show_search, position (the corner the pill floats in)

    Renders no section and nothing in the page flow: only a pill button and the
    panel it opens, fixed to the viewport. The list is built in the browser from
    the page's sections (label = first h2, fallback h3), so it cannot drift from
    what is on the page. Only one jump menu per page (layout max 1, and the first
    in DOM order wins). Without JavaScript, or on a page with nothing to list, it
    stays hidden.
--}}

@php
    $title = get_sub_field('title') ?: __('Springe zu', 'wp-starter');
    // Default on: ACF injects default_value 1 for an unsaved field; a saved off is '' or '0'.
    $showSearch = !\WordpressStarter\Helpers\JumpMenu::isHidden(get_sub_field('show_search', false));
    // bottom_right (default), bottom_left, top_right or top_left.
    $position = in_array(get_sub_field('position'), ['bottom_left', 'top_right', 'top_left'], true) ? get_sub_field('position') : 'bottom_right';
    $isTop = str_starts_with($position, 'top');
    $isLeft = str_ends_with($position, 'left');
    // Full class names so Tailwind finds them: the panel grows from the pill's corner.
    $origin = ['bottom_right' => 'origin-bottom-right', 'bottom_left' => 'origin-bottom-left', 'top_right' => 'origin-top-right', 'top_left' => 'origin-top-left'][$position];
    $uid = \WordpressStarter\Helpers\ComponentId::next('jump-menu');
@endphp

<div x-data="jumpMenu" data-jump-menu-id="{{ $uid }}" data-jump-menu-position="{{ $position }}" data-jump-menu-root>
    {{-- Teleported to <body> because the sections' reveal animation puts a
         transform on their ancestors, which would turn position:fixed into
         position:absolute. Teleported content keeps this Alpine scope. z-40 sits
         below the sticky header (z-50, which also holds the mobile nav) and the
         medium-zoom overlay (z-100). In the top corners jump-menu.ts keeps the pill
         below the header and publishes --jump-menu-offset for the scroll margins. --}}
    <template x-teleport="body">
        <div
            id="{{ $uid }}-ui"
            x-show="active && entries.length > 0"
            x-cloak
            data-jump-menu-ui
            {{-- One-shot entrance, 1.2 s after first shown (app.css), from the nearer screen edge. --}}
            data-position="{{ $position }}"
            x-on:keydown.escape.window="close()"
            class="jump-menu-pill-enter fixed inset-x-0 z-40 pointer-events-none {{ $isTop ? 'top-[calc(var(--header-height,0px)+1rem)]' : 'bottom-[calc(env(safe-area-inset-bottom,0px)+1rem)]' }}"
        >
            {{-- Same container as the header, so the pill lines up with the content edge.
                 The full-width wrapper ignores pointer events; only pill and panel take them. --}}
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex {{ $isLeft ? 'justify-start' : 'justify-end' }}">
                {{-- Pill first in the DOM (tab order); the bottom corners show the panel above it. --}}
                <div class="flex gap-2 pointer-events-auto {{ $isTop ? 'flex-col' : 'flex-col-reverse' }} {{ $isLeft ? 'items-start' : 'items-end' }}">
                    <button
                        type="button"
                        id="{{ $uid }}-pill"
                        x-on:click="toggle()"
                        :aria-expanded="open"
                        aria-controls="{{ $uid }}-panel"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-normal border rounded-full cursor-pointer select-none transition-transform duration-[var(--dur-fast)] ease-[var(--ease-enter)] active:scale-[0.97] border-line bg-surface text-content shadow-[var(--rafael-shadow-button)] hover:bg-surface-secondary focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]"
                    >
                        {{ $title }}
                        <span class="inline-block transition-transform duration-[var(--dur-base)] ease-[var(--ease-enter)]" :class="open ? 'rotate-180' : ''">
                            <x-icon :name="$isTop ? 'chevron-down' : 'chevron-up'" class="w-4 h-4" />
                        </span>
                    </button>
                    <div
                        id="{{ $uid }}-panel"
                        x-show="open"
                        x-transition:enter="transition-[opacity,scale] duration-[var(--dur-base)] ease-[var(--ease-enter)]"
                        x-transition:enter-start="opacity-0 scale-[0.96] motion-reduce:scale-100"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition-[opacity,scale] duration-[var(--dur-fast)] ease-[var(--ease-enter)]"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-[0.96] motion-reduce:scale-100"
                            role="dialog"
                        tabindex="-1"
                        aria-label="{{ $title }}"
                        class="flex flex-col overflow-hidden border outline-none w-[min(34rem,calc(100vw-2rem))] rounded-[var(--card-radius)] border-line bg-surface shadow-[var(--rafael-shadow-card)] {{ $isTop ? 'max-h-[calc(100dvh-var(--header-height,0px)-5rem)]' : 'max-h-[70dvh]' }} {{ $origin }}"
                    >
                        {{-- A flex column: the search stays fixed, the results scroll below it. The scroll
                             area is its own element so the fade mask (app.css) does not clip the panel's
                             border and shadow or cover the search. --}}
                        @if($showSearch)
                            @include('partials.jump-menu-search', ['idPrefix' => $uid])
                        @endif
                        <div
                            id="{{ $uid }}-scroll"
                            x-on:scroll.passive="refreshFade()"
                            class="jump-menu-scroll relative min-h-0 flex-1 p-5 overflow-y-auto overflow-x-hidden [scrollbar-width:none] [&::-webkit-scrollbar]:hidden {{ $showSearch ? 'pt-2' : '' }}"
                        >
                            @include('partials.jump-menu-panel', ['showSearch' => $showSearch, 'title' => $title])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
