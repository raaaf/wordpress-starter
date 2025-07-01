@php
    $accordion_items = $fields['accordion'] ?? [];
    $bgColor = $fields['background_color'] ?? 'gray-200';
@endphp

@if($accordion_items)
    <section class="{{ $classes }} px-6 accordion md:px-8"
             @if($anchor) id="{{ $anchor }}" @endif>
        <div class="max-w-2xl mx-auto">
            <div class="flex flex-col overflow-hidden" x-data="{ active: null }">
                @foreach($accordion_items as $index => $item)
                    @php
                        $title = $item['titel'] ?? $item['title'] ?? '';
                        $content = $item['content'] ?? '';
                        $itemId = 'accordion-' . $block['id'] . '-' . $index;
                    @endphp
                    <div class="w-full px-6 mx-auto overflow-hidden border-b border-{{ $bgColor }} last:border-b-0">
                        <button @click="active = active === {{ $index }} ? null : {{ $index }}"
                                :aria-expanded="active === {{ $index }}"
                                aria-controls="content-{{ $itemId }}"
                                class="flex items-center justify-between w-full py-4 pr-10 mb-0 font-bold text-left">
                            <span>{{ $title }}</span>
                            <svg class="w-5 h-5 transition-transform duration-200"
                                 :class="{ 'rotate-180': active === {{ $index }} }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="active === {{ $index }}"
                             x-collapse
                             id="content-{{ $itemId }}"
                             class="max-w-2xl mb-8 content lg:max-w-6xl prose prose-lg">
                            {!! $content !!}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif