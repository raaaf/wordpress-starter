{{--
    CTA Block

    Uses shared components: x-button, x-prose
    Fields: title, content, cta, background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $blockId = $block['id'] ?? uniqid('cta-');
    $cta = $fields['cta'] ?? null;
    $content = $fields['content'] ?? '';
    $background = $fields['background_color'] ?? 'brand';

    $backgrounds = [
        'brand' => 'bg-surface-brand',
        'brand-secondary' => 'bg-surface-brand-secondary',
    ];
    $bgClass = $backgrounds[$background] ?? 'bg-surface-brand';

    $bgImage = $background === 'brand-secondary'
        ? 'bg-[url(./img/bg-effects-03.png)]'
        : 'bg-[url(./img/bg-effects-01.png)]';
@endphp

<section class="{{ $classes }} cta-block {{ $bgClass }} relative overflow-hidden text-content-inverse"
         @if($anchor) id="{{ esc_attr($anchor) }}" @endif>
    <div class="relative z-10 grid items-center gap-12 px-8 py-20 mx-auto overflow-hidden max-w-7xl md:grid-cols-2 isolate md:px-16"
         role="region"
         @if($title) aria-labelledby="cta-title-{{ esc_attr($blockId) }}" @endif>
        <div>
            @if($title)
                <h2 id="cta-title-{{ esc_attr($blockId) }}" class="text-display">
                    {{ $title }}
                </h2>
            @endif
            @if($cta)
                <div class="mt-6">
                    <x-button
                        :url="$cta['url']"
                        :title="$cta['title']"
                        :target="$cta['target']"
                        variant="primary"
                        size="lg"
                        :analytics="['event' => 'CTA', 'meta' => 'cta_block']"
                    />
                </div>
            @endif
        </div>
        <div>
            @if($content)
                <x-prose>{!! $content !!}</x-prose>
            @endif
        </div>
    </div>
    <div class="absolute -z-0 inset-0 {{ $bgImage }} bg-no-repeat bg-auto bg-center pointer-events-none"></div>
</section>
