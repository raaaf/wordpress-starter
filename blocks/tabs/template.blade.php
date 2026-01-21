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

<x-section :background="$background" :anchor="$anchor" :wrapperAttributes="$wrapper_attributes" class="tabs {{ $classes }}">
    @if($title)
        <h2 class="text-h2 mb-8 text-center text-content">{{ $title }}</h2>
    @endif

    @if(!empty($tabs))
        <div
            id="{{ esc_attr($uniqueId) }}"
            @if(!$is_preview) x-data="{ activeTab: 0 }" @endif
            class="w-full max-w-3xl mx-auto"
        >
            {{-- Tab Navigation --}}
            <div class="flex flex-wrap gap-6 mb-6 border-b border-line" role="tablist">
                @foreach($tabs as $index => $tab)
                    <button
                        @if(!$is_preview)
                            @click="activeTab = {{ $index }}"
                            :class="activeTab === {{ $index }}
                                ? 'border-line-accent text-content-accent'
                                : 'border-transparent text-content-secondary hover:text-content hover:border-line'"
                            :aria-selected="activeTab === {{ $index }}"
                            class="inline-flex items-center gap-2 px-1 py-3 font-medium border-b-2 -mb-px transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-line-focus focus-visible:ring-offset-2"
                        @else
                            class="inline-flex items-center gap-2 px-1 py-3 font-medium border-b-2 -mb-px transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-line-focus focus-visible:ring-offset-2 {{ $index === 0 ? 'border-line-accent text-content-accent' : 'border-transparent text-content-secondary' }}"
                        @endif
                        role="tab"
                        aria-controls="{{ esc_attr($uniqueId) }}-panel-{{ $index }}"
                    >
                        @if(!empty($tab['icon']))
                            <x-icon :name="$tab['icon']" size="md" />
                        @endif
                        {{ $tab['title'] ?? 'Tab ' . ($index + 1) }}
                    </button>
                @endforeach
            </div>

            {{-- Tab Panels with aria-live for screen readers --}}
            <div aria-live="polite" aria-atomic="true">
                @foreach($tabs as $index => $tab)
                    <div
                        @if(!$is_preview)
                            x-show="activeTab === {{ $index }}"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-cloak
                            :aria-hidden="activeTab !== {{ $index }}"
                            :tabindex="activeTab === {{ $index }} ? 0 : -1"
                        @endif
                        id="{{ esc_attr($uniqueId) }}-panel-{{ $index }}"
                        role="tabpanel"
                        class="prose max-w-2xl text-content {{ $is_preview && $index > 0 ? 'hidden' : '' }}"
                    >
                        @kses($tab['content'] ?? '')
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-section>
