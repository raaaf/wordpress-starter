@php
    $style = get_sub_field('style') ?: 'line';
    $height = get_sub_field('height') ?: 50;
@endphp

<div class="container mx-auto px-4">
    <div class="py-4" style="height: {{ $height }}px">
        @switch($style)
            @case('line')
                <hr class="border-gray-300">
                @break
                
            @case('dots')
                <div class="flex justify-center items-center h-full">
                    <span class="inline-flex space-x-2">
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                    </span>
                </div>
                @break
                
            @case('wave')
                <div class="flex justify-center items-center h-full">
                    <svg class="w-32 h-4 text-gray-400" viewBox="0 0 100 20" fill="currentColor">
                        <path d="M0,10 Q25,0 50,10 T100,10 L100,12 Q75,22 50,12 T0,12 Z"/>
                    </svg>
                </div>
                @break
                
            @case('space')
                {{-- Empty space --}}
                @break
        @endswitch
    </div>
</div>