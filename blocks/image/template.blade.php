{{--
    Image Block

    Uses shared components: x-section, x-card
    Fields: image, show_border, show_caption, background_color
--}}

@php
    $imageField = $fields['image'] ?? null;
    $showBorder = $fields['show_border'] ?? true;
    $showCaption = $fields['show_caption'] ?? true;
    $background = $fields['background_color'] ?? 'primary';

    // Handle both ID and array format
    $imageId = null;
    $alt = '';
    if (is_array($imageField)) {
        $imageId = $imageField['ID'] ?? $imageField['id'] ?? null;
        $alt = $imageField['alt'] ?? '';
    } else {
        $imageId = $imageField;
    }

    // Get alt from postmeta if not already set and we have an ID
    if ($imageId && !$alt) {
        $alt = get_post_meta($imageId, '_wp_attachment_image_alt', true);
    }
@endphp

@if($imageId)
    <x-section :background="$background" :anchor="$anchor" :wrapperAttributes="$wrapper_attributes" padding="md" class="image-block {{ $classes }}">
        <div class="flex flex-col items-center justify-center max-w-6xl mx-auto group">
            <div class="relative overflow-hidden shadow-xl will-change-transform transition {{ $showBorder ? 'border-4 rounded-md border-line' : '' }} ease-in-out group lg:group-hover:shadow-2xl">
                @php
                    $size = 'full';
                    echo wp_get_attachment_image($imageId, $size, '', [
                        'class' => 'w-full relative z-0 !rounded-none',
                        'loading' => 'lazy',
                        'aria-describedby' => $alt && $showCaption ? "img-desc-{$imageId}" : null
                    ]);
                @endphp
                @if ($alt && $showCaption)
                    <p id="img-desc-{{ $imageId }}"
                        class="absolute inset-0 z-20 flex items-end justify-center mx-4 my-0 bottom-[5%] lg:opacity-0 lg:group-hover:opacity-100 lg:transition lg:duration-1000 lg:ease-in-out lg:transform-gpu lg:translate-y-4 lg:group-hover:translate-y-0 pointer-events-none">
                        <span class="max-w-xs px-3 py-1 text-content-inverse border-2 rounded-md bg-surface-brand border-line-brand has-small-bold">
                            {{ $alt }}
                        </span>
                    </p>
                @endif
            </div>
        </div>
    </x-section>
@endif
