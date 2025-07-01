@php
    $image = $fields['image'] ?? null;
    $image_2 = $fields['image_2'] ?? null;
    $content_1 = $fields['content'] ?? '';
    $content_2 = $fields['content_2'] ?? '';
    $bgColor = $fields['background_color'] ?? 'gray-200';
    $size = 'large';
@endphp

<section class="{{ $classes }} flex flex-col gap-8 px-6 two-columns-images lg:gap-24 lg:px-8 text-blue-700"
         @if($anchor) id="{{ $anchor }}" @endif>
    {{-- First Row: Content Left, Image Right --}}
    <div class="relative grid items-stretch mx-auto overflow-hidden border rounded-lg border-gray-200 max-w-6xl lg:grid-cols-2">
        <div class="order-last h-full lg:overflow-hidden lg:order-first">
            <div class="relative h-full transition duration-200 bg-gray-200/10">
                <div class="relative z-10 p-8 max-w-none lg:p-16 lg:pb-12 prose prose-lg">
                    {!! $content_1 !!}
                </div>
            </div>
        </div>
        <div class="order-first h-full lg:order-last">
            @if($image)
                {!! \WordpressStarter\Acf\Fields::responsiveImage('image', $size, [
                    'class' => 'w-full object-cover h-full bg-center !rounded-none'
                ]) !!}
            @endif
        </div>
    </div>
    
    {{-- Second Row: Image Left, Content Right --}}
    <div class="relative grid items-stretch mx-auto overflow-hidden border rounded-lg border-gray-200 max-w-6xl lg:grid-cols-2">
        <div class="h-full">
            @if($image_2)
                {!! \WordpressStarter\Acf\Fields::responsiveImage('image_2', $size, [
                    'class' => 'w-full h-full object-cover bg-center !rounded-none'
                ]) !!}
            @endif
        </div>
        <div class="h-full lg:overflow-hidden">
            <div class="relative h-full transition duration-200 bg-gray-200/10">
                <div class="relative z-10 p-8 max-w-none lg:p-16 lg:pb-12 prose prose-lg">
                    {!! $content_2 !!}
                </div>
            </div>
        </div>
    </div>
</section>