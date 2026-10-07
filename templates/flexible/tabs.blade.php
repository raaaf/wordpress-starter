{{--
    Tabs Flexible Content Layout

    Uses shared components: x-section, x-section-header
    Uses Alpine.js for tab switching
    Fields: title, tabs (repeater: title, icon, modules), background_color
    The tab text lives in a nested one_column module.
    Nested modules render through their own flexible templates without section chrome.
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $kopf = \WordpressStarter\Helpers\SectionHeader::extras($title);
    // Nur Titel und Icon fuer die Buttons: get_sub_field('tabs') laedt jede Zeile samt
    // Modulen, die unten nochmal per have_rows() geladen werden. Eine durchlaufene
    // Schleife raeumt ihren ACF-Loop selbst weg, reset_rows() danach wuerde den
    // Loop der uebergeordneten Sektion entfernen.
    $tabs = [];
    while (have_rows('tabs')) {
        the_row();
        $tabs[] = ['title' => get_sub_field('title'), 'icon' => get_sub_field('icon')];
    }
    $background = get_sub_field('background_color') ?: 'primary';
    $uniqueId = 'tabs-' . uniqid();
@endphp

@if(!empty($tabs) || $title || current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" class="tabs">
    <x-section-header :chip="$kopf['chip']" :headline="$kopf['headline']" :description="$kopf['description']" :alignment="$kopf['alignment']" />

    @if(!empty($tabs))
        @php $tabCount = count($tabs); @endphp
        <div
            id="{{ esc_attr($uniqueId) }}"
            x-data="{
                activeTab: 0,
                tabCount: {{ $tabCount }},
                run: 0,
                onEnd: null,
                timer: null,
                select(index) {
                    if (index === this.activeTab) return;
                    const el = this.$refs.panels;
                    const from = el.offsetHeight;
                    const run = ++this.run;
                    clearTimeout(this.timer);
                    if (this.onEnd) {
                        el.removeEventListener('transitionend', this.onEnd);
                        el.removeEventListener('transitioncancel', this.onEnd);
                    }
                    this.onEnd = null;
                    this.activeTab = index;
                    this.$nextTick(() => {
                        el.style.height = '';
                        el.style.overflow = '';
                        const to = el.offsetHeight;
                        if (from === to || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                        el.style.overflow = 'hidden';
                        el.style.height = from + 'px';
                        el.offsetHeight;
                        el.style.height = to + 'px';
                        const finish = () => {
                            clearTimeout(this.timer);
                            el.removeEventListener('transitionend', this.onEnd);
                            el.removeEventListener('transitioncancel', this.onEnd);
                            this.onEnd = null;
                            if (run === this.run) {
                                el.style.height = '';
                                el.style.overflow = '';
                            }
                        };
                        this.onEnd = (e) => {
                            if (e.target !== el || e.propertyName !== 'height') return;
                            finish();
                        };
                        el.addEventListener('transitionend', this.onEnd);
                        el.addEventListener('transitioncancel', this.onEnd);
                        // Fallback, falls weder transitionend noch transitioncancel kommt (Dauer 250ms + 100ms).
                        this.timer = setTimeout(finish, 350);
                    });
                },
                focusTab(index) {
                    this.select(index);
                    this.$nextTick(() => {
                        this.$refs['tab' + index]?.focus();
                    });
                }
            }"
            class="w-full"
        >
            {{-- Tab Navigation with ARIA keyboard pattern --}}
            <div
                class="flex flex-wrap gap-6 mb-6 border-b border-line"
                role="tablist"
                aria-label="{{ $title ? \WordpressStarter\Helpers\Text::plain($title) : __('Tabs', 'wp-starter') }}"
            >
                @foreach($tabs as $index => $tab)
                    <button
                        id="{{ esc_attr($uniqueId) }}-tab-{{ $index }}"
                        x-ref="tab{{ $index }}"
                        @click="select({{ $index }})"
                        @keydown.right.prevent="focusTab((activeTab + 1) % tabCount)"
                        @keydown.left.prevent="focusTab((activeTab - 1 + tabCount) % tabCount)"
                        @keydown.home.prevent="focusTab(0)"
                        @keydown.end.prevent="focusTab(tabCount - 1)"
                        :class="activeTab === {{ $index }}
                            ? 'border-line-accent text-content-accent'
                            : 'border-transparent text-content-secondary hover:text-content hover:border-line'"
                        :aria-selected="activeTab === {{ $index }}"
                        :tabindex="activeTab === {{ $index }} ? 0 : -1"
                        {{-- Radius nur oben: der Fokusring bleibt weich, die Unterstreichung des aktiven
                             Tabs bleibt flach. Ein umlaufender Radius rundete auch sie ab. --}}
                        class="inline-flex items-center gap-2 px-1 py-3 font-normal border-b-2 -mb-px transition-colors cursor-pointer rounded-t-[var(--rafael-radius-sm)] focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]"
                        role="tab"
                        aria-controls="{{ esc_attr($uniqueId) }}-panel-{{ $index }}"
                    >
                        @if(!empty($tab['icon']))
                            <x-icon :name="$tab['icon']" size="md" />
                        @endif
                        {{ $tab['title'] ?? __('Tab', 'wp-starter') . ' ' . ($index + 1) }}
                    </button>
                @endforeach
            </div>

            {{-- Tab Panels - no aria-live; focus moves to panel via tabindex.
                 Iterated via ACF row context so nested templates can use get_sub_field(). --}}
            {{-- Nur das aktive Panel nimmt Platz, ein kurzer Tab laesst keine Luft vor der
                 naechsten Sektion. Die Hoehe des Bereichs faehrt beim Wechsel von alt nach neu,
                 damit nichts springt. Per JS (select()), weil CSS nicht zwischen auto-Hoehen
                 uebergehen kann; overflow nur waehrend der Fahrt, sonst schnitte es Fokusringe und
                 Schatten ab. Der Zustand haengt allein an `inert`: Alpines :class entfernt
                 keine Klassen aus dem statischen class-Attribut. Anfangszustand serverseitig. --}}
            <div x-ref="panels" class="transition-[height] duration-[250ms] ease-[var(--ease-standard)] motion-reduce:transition-none">
                @php $index = -1; @endphp
                @while(have_rows('tabs'))
                    @php
                        the_row();
                        $index++;
                        $isFirst = $index === 0;
                    @endphp
                    <div
                        class="transition-opacity duration-150 ease-[var(--motion-enter-ease)] motion-reduce:transition-none inert:hidden"
                        :class="{ 'starting:opacity-0': run > 0 }"
                        @unless($isFirst) inert aria-hidden="true" @endunless
                        :inert="activeTab !== {{ $index }}"
                        :aria-hidden="activeTab !== {{ $index }}"
                        :tabindex="activeTab === {{ $index }} ? 0 : -1"
                        id="{{ esc_attr($uniqueId) }}-panel-{{ $index }}"
                        role="tabpanel"
                        aria-labelledby="{{ esc_attr($uniqueId) }}-tab-{{ $index }}"
                    >
                        @if(have_rows('modules'))
                            <div class="flex flex-col gap-10">
                                @php
                                    \WordpressStarter\Helpers\SectionNesting::enter();
                                    try {
                                @endphp
                                @while(have_rows('modules'))
                                    @php
                                        the_row();
                                        $nestedLayout = get_row_layout();
                                        $sectionAnchor = null;
                                        $sectionSpacing = null;
                                        $sectionWidth = null;
                                    @endphp
                                    @includeIf('flexible.' . str_replace('_', '-', $nestedLayout))
                                @endwhile
                                @php
                                    } finally {
                                        \WordpressStarter\Helpers\SectionNesting::leave();
                                    }
                                @endphp
                            </div>
                        @endif
                    </div>
                @endwhile
            </div>
        </div>
    @elseif(current_user_can('edit_posts'))
        <div class="p-8 text-center rounded-lg bg-surface-secondary surface-sheen">
            <p class="text-content-secondary">{{ __('Bitte füge mindestens einen Tab hinzu.', 'wp-starter') }}</p>
        </div>
    @endif
</x-section>
@endif
