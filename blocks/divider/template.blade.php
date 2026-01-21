{{--
    Divider Block

    Variants: line, logo, dots
    Fields: variant
--}}

@php
    $variant = $fields['variant'] ?? 'line';
    $logo = get_field('logo', 'option');
    $logoUrl = $logo ? wp_get_attachment_image_url($logo, 'thumbnail') : null;
@endphp

<div {!! $wrapper_attributes !!}
     class="divider {{ $classes }} py-8 lg:py-12"
     role="separator"
     aria-orientation="horizontal"
     @if($anchor && !$wrapper_attributes) id="{{ esc_attr($anchor) }}" @endif>
    @switch($variant)
        @case('line')
            {{-- Simple line --}}
            <div class="max-w-2xl mx-auto px-4">
                <div class="w-full border-t border-line"></div>
            </div>
            @break

        @case('logo')
            {{-- Line with elevated logo --}}
            <div class="relative max-w-2xl mx-auto px-4">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-line"></div>
                </div>
                <div class="relative flex justify-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-surface rounded-full border border-line shadow-sm">
                        @if($logoUrl)
                            <img src="{{ esc_url($logoUrl) }}" alt="" class="w-6 h-6 object-contain dark:invert" aria-hidden="true">
                        @else
                            {{-- Fallback icon if no logo --}}
                            <svg class="w-5 h-5 text-content-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                            </svg>
                        @endif
                    </div>
                </div>
            </div>
            @break

        @case('dots')
            {{-- Decorative dots --}}
            <div class="flex items-center justify-center gap-3 px-4">
                <span class="w-1.5 h-1.5 rounded-full bg-content-tertiary"></span>
                <span class="w-2 h-2 rounded-full bg-content-secondary"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-content-tertiary"></span>
            </div>
            @break
    @endswitch
</div>
