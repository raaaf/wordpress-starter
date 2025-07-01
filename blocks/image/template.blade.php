@php
    $bgColor = $fields['background_color'] ?? 'gray-200';
    $image = $fields['image'] ?? null;
    $showBorder = $fields['show_border'] ?? true;
    $showCaption = $fields['show_caption'] ?? true;
@endphp

@if($image)
    <section class="{{ $classes }} relative flex items-center justify-between image"
             @if($anchor) id="{{ $anchor }}" @endif>
        <div class="flex flex-col items-center justify-center max-w-6xl px-6 mx-auto md:px-8 group">
            <div class="relative overflow-hidden shadow-xl will-change-transform transition @if($showBorder) border-4 rounded-md border-gray-200 @endif ease-in-out group lg:group-hover:shadow-2xl">
                @php
                    $size = 'full';
                    $alt = get_post_meta($image, '_wp_attachment_image_alt', true);
                    
                    echo wp_get_attachment_image($image, $size, '', [
                        'class' => 'w-full relative z-0 !rounded-none',
                        'loading' => 'lazy',
                        'aria-describedby' => $alt && $showCaption ? "img-desc-{$image}" : null
                    ]);
                @endphp
                @if ($alt && $showCaption)
                    <p id="img-desc-{{ $image }}"
                        class="absolute inset-0 z-20 flex items-end justify-center mx-4 my-0 bottom-[5%] lg:opacity-0 lg:group-hover:opacity-100 lg:transition lg:duration-1000 lg:ease-in-out lg:transform-gpu lg:translate-y-4 lg:group-hover:translate-y-0 pointer-events-none">
                        <span class="max-w-xs px-3 py-1 text-white border-2 rounded-md bg-primary/90 has-small-bold border-blue-700/20">
                            {{ $alt }}
                        </span>
                    </p>
                @endif
            </div>
        </div>
    </section>
@endif