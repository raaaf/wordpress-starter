@php
    $items = get_sub_field('items');
@endphp

<div class="container mx-auto px-4">
    <div class="space-y-4" x-data="{ active: null }">
        @if($items)
            @foreach($items as $index => $item)
                <div class="border rounded-lg">
                    <button 
                        class="w-full px-6 py-4 text-left font-semibold flex justify-between items-center hover:bg-gray-50 transition-colors"
                        @click="active = active === {{ $index }} ? null : {{ $index }}"
                    >
                        <span>{{ $item['title'] }}</span>
                        <svg class="w-5 h-5 transform transition-transform" 
                             :class="{ 'rotate-180': active === {{ $index }} }"
                             fill="none" 
                             stroke="currentColor" 
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div 
                        class="px-6 pb-4"
                        x-show="active === {{ $index }}"
                        x-transition
                        style="display: none;"
                    >
                        <div class="prose prose-lg max-w-none">
                            {!! $item['content'] !!}
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>