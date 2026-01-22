{{--
    Accordion - Flexible Content Layout

    Uses shared components: x-section, x-prose
    Fields: accordion (repeater with icon, title, content), background_color
--}}

@php
    $items = get_sub_field('accordion') ?: [];
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

<x-section :background="$background" padding="md" class="accordion">
    <div class="max-w-2xl mx-auto">
        <div class="flex flex-col overflow-hidden" x-data="{ active: null }">
            @if(!empty($items))
                @foreach($items as $index => $item)
                    <div class="w-full overflow-hidden border-b border-line last:border-b-0">
                        <button @click="active = active === {{ $index }} ? null : {{ $index }}"
                                :aria-expanded="active === {{ $index }}"
                                aria-controls="accordion-content-{{ $index }}"
                                class="group flex items-center justify-between w-full py-4 pr-10 mb-0 font-bold text-left cursor-pointer transition-colors hover:text-content-brand"
                                :class="{ 'text-content-brand': active === {{ $index }} }">
                            <span class="flex items-center gap-3">
                                @if(!empty($item['icon']))
                                    <x-icon :name="$item['icon']" class="w-5 h-5" />
                                @endif
                                {{ $item['title'] }}
                            </span>
                            <svg class="w-5 h-5 transition-all duration-200"
                                 :class="{ 'rotate-180': active === {{ $index }} }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="active === {{ $index }}"
                             x-collapse
                             id="accordion-content-{{ $index }}"
                             class="mb-8">
                            <x-prose>{!! $item['content'] !!}</x-prose>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-section>
