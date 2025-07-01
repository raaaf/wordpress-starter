@php
    $content_1 = $fields['content'] ?? '';
    $content_2 = $fields['content_2'] ?? '';
    $bgColor = $fields['background_color'] ?? 'gray-200';
@endphp

<section class="{{ $classes }} px-6 one-third-columns md:px-8"
         @if($anchor) id="{{ $anchor }}" @endif>
    <div class="max-w-6xl mx-auto">
        <div class="grid items-center gap-8 md:grid-cols-1 lg:grid-cols-7">
            <div class="order-first px-6 lg:p-8 md:p-0 lg:order-first lg:col-span-3 max-w-none text-blue-700 prose prose-lg">
                {!! $content_1 !!}
            </div>
            <div class="order-last h-auto overflow-hidden rounded-lg lg:order-last lg:col-span-4">
                <div class="relative transition duration-200 border rounded-lg shadow-lg bg-gray-200/10 border-gray-200">
                    <div class="relative z-10 p-8 max-w-none lg:p-16 lg:pb-12 text-blue-700 prose prose-lg">
                        {!! $content_2 !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>