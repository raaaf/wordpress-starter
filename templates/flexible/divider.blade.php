{{--
    Divider - Flexible Content Layout

    Uses shared components: x-section
    Fields: style, height
--}}

@php
    $style = get_sub_field('style') ?: 'line';
    $height = get_sub_field('height') ?: 50;
@endphp

<x-section padding="none" class="divider">
    <div class="py-4 flex items-center justify-center" style="height: {{ $height }}px">
        @switch($style)
            @case('line')
                <hr class="w-full border-t border-line">
                @break

            @case('dots')
                <span class="inline-flex space-x-2" aria-hidden="true">
                    <span class="w-2 h-2 bg-icon-secondary rounded-full"></span>
                    <span class="w-2 h-2 bg-icon-secondary rounded-full"></span>
                    <span class="w-2 h-2 bg-icon-secondary rounded-full"></span>
                </span>
                @break

            @case('wave')
                <svg class="w-32 h-4 text-content-tertiary" viewBox="0 0 100 20" fill="currentColor" aria-hidden="true">
                    <path d="M0,10 Q25,0 50,10 T100,10 L100,12 Q75,22 50,12 T0,12 Z"/>
                </svg>
                @break

            @case('space')
                {{-- Empty space for visual separation --}}
                @break
        @endswitch
    </div>
</x-section>
