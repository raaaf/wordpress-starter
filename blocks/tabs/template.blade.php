{{--
    Tabs Block

    Uses shared components: x-section
    Uses Alpine.js for tab switching
    Fields: title, tabs (repeater: title, content), background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $tabs = $fields['tabs'] ?? [];
    $background = $fields['background_color'] ?? 'primary';
    $uniqueId = 'tabs-' . uniqid();
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} tabs-block">
    @if($title)
        <h2 class="text-h2 mb-8 text-center text-content">{{ $title }}</h2>
    @endif

    @if(!empty($tabs))
        <div
            id="{{ esc_attr($uniqueId) }}"
            x-data="{ activeTab: 0 }"
            class="w-full"
        >
            {{-- Tab Navigation --}}
            <div class="flex flex-wrap gap-2 pb-4 mb-6 border-b border-line" role="tablist">
                @foreach($tabs as $index => $tab)
                    <button
                        @click="activeTab = {{ $index }}"
                        :class="activeTab === {{ $index }}
                            ? 'bg-surface-brand text-content-inverse'
                            : 'bg-surface-secondary text-content hover:bg-surface-tertiary'"
                        class="px-5 py-2.5 font-medium rounded-lg transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-line-focus focus-visible:ring-offset-2"
                        role="tab"
                        :aria-selected="activeTab === {{ $index }}"
                        aria-controls="{{ esc_attr($uniqueId) }}-panel-{{ $index }}"
                    >
                        {{ $tab['title'] ?? 'Tab ' . ($index + 1) }}
                    </button>
                @endforeach
            </div>

            {{-- Tab Panels with aria-live for screen readers --}}
            <div aria-live="polite" aria-atomic="true">
                @foreach($tabs as $index => $tab)
                    <div
                        x-show="activeTab === {{ $index }}"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-cloak
                        id="{{ esc_attr($uniqueId) }}-panel-{{ $index }}"
                        role="tabpanel"
                        :aria-hidden="activeTab !== {{ $index }}"
                        :tabindex="activeTab === {{ $index }} ? 0 : -1"
                        class="prose max-w-none text-content"
                    >
                        @kses($tab['content'] ?? '')
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-section>
