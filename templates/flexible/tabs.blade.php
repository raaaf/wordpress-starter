{{--
    Tabs Flexible Content Layout

    Uses shared components: x-section
    Uses Alpine.js for tab switching
    Fields: title, tabs (repeater: title, content), background_color
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $tabs = get_sub_field('tabs') ?: [];
    $background = get_sub_field('background_color') ?: 'primary';
    $uniqueId = 'tabs-' . uniqid();
@endphp

<x-section :anchor="$sectionAnchor" :background="$background" class="tabs">
    @if($title)
        <h2 class="mb-8 text-center">{!! $title !!}</h2>
    @endif

    @if(!empty($tabs))
        @php $tabCount = count($tabs); @endphp
        <div
            id="{{ esc_attr($uniqueId) }}"
            x-data="{
                activeTab: 0,
                tabCount: {{ $tabCount }},
                focusTab(index) {
                    this.activeTab = index;
                    this.$nextTick(() => {
                        this.$refs['tab' + index]?.focus();
                    });
                }
            }"
            class="w-full max-w-3xl mx-auto"
        >
            {{-- Tab Navigation with ARIA keyboard pattern --}}
            <div
                class="flex flex-wrap gap-6 mb-6 border-b border-line"
                role="tablist"
                aria-label="{{ $title ?: __('Tabs', 'wp-starter') }}"
            >
                @foreach($tabs as $index => $tab)
                    <button
                        id="{{ esc_attr($uniqueId) }}-tab-{{ $index }}"
                        x-ref="tab{{ $index }}"
                        @click="activeTab = {{ $index }}"
                        @keydown.right.prevent="focusTab((activeTab + 1) % tabCount)"
                        @keydown.left.prevent="focusTab((activeTab - 1 + tabCount) % tabCount)"
                        @keydown.home.prevent="focusTab(0)"
                        @keydown.end.prevent="focusTab(tabCount - 1)"
                        :class="activeTab === {{ $index }}
                            ? 'border-line-accent text-content-accent'
                            : 'border-transparent text-content-secondary hover:text-content hover:border-line'"
                        :aria-selected="activeTab === {{ $index }}"
                        :tabindex="activeTab === {{ $index }} ? 0 : -1"
                        class="inline-flex items-center gap-2 px-1 py-3 font-medium border-b-2 -mb-px transition-colors cursor-pointer rounded-[var(--radius-sm)] focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring-ghost)]"
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

            {{-- Tab Panels - no aria-live; focus moves to panel via tabindex --}}
            <div>
                @foreach($tabs as $index => $tab)
                    <div
                        x-show="activeTab === {{ $index }}"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-cloak
                        :aria-hidden="activeTab !== {{ $index }}"
                        :tabindex="activeTab === {{ $index }} ? 0 : -1"
                        id="{{ esc_attr($uniqueId) }}-panel-{{ $index }}"
                        role="tabpanel"
                        aria-labelledby="{{ esc_attr($uniqueId) }}-tab-{{ $index }}"
                        class="prose max-w-2xl text-content"
                    >
                        @kses($tab['content'] ?? '')
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-section>
