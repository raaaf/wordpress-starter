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

<x-section :background="$background" :anchor="$anchor" padding="lg" class="{{ $classes }} two-columns-images">
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
                    {!! \WordpressStarter\Acf\Fields::responsiveImage('image_1', $size, [
                        'class' => 'w-full object-cover h-full bg-center !rounded-none'
                    ]) !!}
                @endif
            </div>
        </div>

        {{-- Second Row: Image Left, Content Right --}}
        <div class="relative grid items-stretch mx-auto overflow-hidden border rounded-lg border-line max-w-6xl lg:grid-cols-2">
            <div class="h-full">
                @if($image_2)
                    {!! \WordpressStarter\Acf\Fields::responsiveImage('image_2', $size, [
                        'class' => 'w-full h-full object-cover bg-center !rounded-none'
                    ]) !!}
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
