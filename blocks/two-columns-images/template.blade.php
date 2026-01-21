{{--
    Two Columns Images Block

    Uses shared components: x-section, x-prose
    Fields: image_1, image_2, column_1, column_2, background_color
--}}

@php
    $image_1 = $fields['image_1'] ?? null;
    $image_2 = $fields['image_2'] ?? null;
    $column_1 = $fields['column_1'] ?? '';
    $column_2 = $fields['column_2'] ?? '';
    $background = $fields['background_color'] ?? 'primary';
    $size = 'large';
@endphp

<x-section :background="$background" :anchor="$anchor" :wrapperAttributes="$wrapper_attributes" padding="lg" class="{{ $classes }} two-columns-images">
    <div class="flex flex-col gap-8 lg:gap-24">
        {{-- First Row: Content Left, Image Right --}}
        <div class="relative grid items-stretch mx-auto overflow-hidden border rounded-lg border-line max-w-6xl lg:grid-cols-2">
            <div class="order-last h-full lg:overflow-hidden lg:order-first">
                <div class="relative h-full transition duration-200 bg-surface-secondary/10">
                    <div class="relative z-10 p-8 lg:p-16 lg:pb-12">
                        <x-prose>{!! $column_1 !!}</x-prose>
                    </div>
                </div>
            </div>
            <div class="order-first h-full lg:order-last">
                @if($image_1)
                    @php
                        $imgId1 = is_array($image_1) ? ($image_1['ID'] ?? $image_1['id'] ?? null) : $image_1;
                    @endphp
                    @if($imgId1)
                        {!! wp_get_attachment_image($imgId1, $size, false, [
                            'class' => 'w-full object-cover h-full bg-center !rounded-none',
                            'loading' => 'lazy'
                        ]) !!}
                    @endif
                @endif
            </div>
        </div>

        {{-- Second Row: Image Left, Content Right --}}
        <div class="relative grid items-stretch mx-auto overflow-hidden border rounded-lg border-line max-w-6xl lg:grid-cols-2">
            <div class="h-full">
                @if($image_2)
                    @php
                        $imgId2 = is_array($image_2) ? ($image_2['ID'] ?? $image_2['id'] ?? null) : $image_2;
                    @endphp
                    @if($imgId2)
                        {!! wp_get_attachment_image($imgId2, $size, false, [
                            'class' => 'w-full h-full object-cover bg-center !rounded-none',
                            'loading' => 'lazy'
                        ]) !!}
                    @endif
                @endif
            </div>
            <div class="h-full lg:overflow-hidden">
                <div class="relative h-full transition duration-200 bg-surface-secondary/10">
                    <div class="relative z-10 p-8 lg:p-16 lg:pb-12">
                        <x-prose>{!! $column_2 !!}</x-prose>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-section>
