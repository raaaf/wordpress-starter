{{--
    Accordion Block

    Uses shared components: x-section, x-prose
    Fields: accordion (repeater), background_color
--}}

@php
    $accordion_items = $fields['accordion'] ?? [];
    $background = $fields['background_color'] ?? 'primary';
@endphp

@if($accordion_items)
    <x-section :background="$background" :anchor="$anchor" padding="md" class="{{ $classes }} accordion">
        <div class="max-w-2xl mx-auto">
            <div class="flex flex-col overflow-hidden" x-data="{ active: null }">
                @foreach($accordion_items as $index => $item)
                    @php
                        $title = $item['titel'] ?? $item['title'] ?? '';
                        $content = $item['content'] ?? '';
                        $itemId = 'accordion-' . $block['id'] . '-' . $index;
                    @endphp
                    <div class="w-full px-6 mx-auto overflow-hidden border-b border-line last:border-b-0">
                        <button @click="active = active === {{ $index }} ? null : {{ $index }}"
                                :aria-expanded="active === {{ $index }}"
                                aria-controls="content-{{ esc_attr($itemId) }}"
                                class="flex items-center justify-between w-full py-4 pr-10 mb-0 font-bold text-left"
                                pirsch-event="Accordion_Toggle"
                                pirsch-meta-key="accordion_block"
                                pirsch-meta-item="{{ esc_attr($title) }}">
                            <span>{{ $title }}</span>
                            <svg class="w-5 h-5 transition-transform duration-200"
                                 :class="{ 'rotate-180': active === {{ $index }} }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="active === {{ $index }}"
                             x-collapse
                             id="content-{{ esc_attr($itemId) }}"
                             class="mb-8">
                            <x-prose>{!! $content !!}</x-prose>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-section>
@endif
