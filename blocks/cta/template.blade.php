@php
    $title = $fields['title'] ?? '';
    $trimmed = str_replace(' ', '', $title);
    $cta = $fields['cta'] ?? null;
    $bgColor = $fields['background_color'] ?? 'green-500';
    $content = $fields['content'] ?? '';
    
    $bgImage = 'bg-[url(./img/bg-effects-01.png)]';
    if (in_array($bgColor, ['purple-500', 'blue-700'])) {
        $bgImage = 'bg-[url(./img/bg-effects-03.png)]';
    }
@endphp

<section class="{{ $classes }} cta-block bg-{{ $bgColor }} relative overflow-hidden"
         @if($anchor) id="{{ $anchor }}" @endif>
    <div class="relative z-10 grid items-center gap-12 px-8 py-20 mx-auto overflow-hidden max-w-7xl md:grid-cols-2 isolate md:px-16" 
         role="region" 
         @if($trimmed) aria-labelledby="cta-title-{{ $trimmed }}" @endif>
        <div @if($trimmed) id="{{ $trimmed }}" @endif>
            @if($title)
                <h2 id="cta-title-{{ $trimmed }}" class="!text-4xl/10 md:!text-5xl/14">
                    {{ $title }}
                </h2>
            @endif
            @if($cta)
                <div>
                    <a pirsch-event="CTA" 
                       pirsch-meta-key="cta_block"
                       class="w-full lg:w-auto !no-underline inline-flex mt-4 items-center justify-center gap-2 px-6 py-2.5 border rounded-md bg-yellow-500 border-yellow-500 shadow-yellow-500/20 hover:bg-yellow-600 relative transition ease-in-out"
                       href="{{ $cta['url'] }}" 
                       target="{{ $cta['target'] }}" 
                       title="{{ $cta['title'] }}">
                        <span class="text-base font-bold leading-none no-underline text-blue-700">{{ $cta['title'] }}</span>
                    </a>
                </div>
            @endif
        </div>
        <div>
            @if($content)
                {!! $content !!}
            @endif
        </div>
    </div>
    <div class="absolute -z-0 inset-0 {{ $bgImage }} bg-no-repeat bg-auto bg-center pointer-events-none"></div>
</section>