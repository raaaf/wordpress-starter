{{--
    CTA Block

    Uses shared components: x-button, x-prose
    Fields: title, content, cta
--}}

@php
    $title = $fields['title'] ?? '';
    $blockId = $block['id'] ?? uniqid('cta-');
    $cta = $fields['cta'] ?? null;
    $content = $fields['content'] ?? '';
@endphp

<section {!! $wrapper_attributes !!}
         class="cta {{ $classes }} py-16 lg:py-24"
         @if($anchor && !$wrapper_attributes) id="{{ esc_attr($anchor) }}" @endif>
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-surface-brand relative overflow-hidden rounded-2xl text-content-inverse max-w-3xl mx-auto"
             role="region"
             @if($title) aria-labelledby="cta-title-{{ esc_attr($blockId) }}" @endif>
            <div class="relative z-10 px-8 py-12 sm:px-12 sm:py-16 lg:px-16 lg:py-20 text-center">
                @if($title)
                    <h2 id="cta-title-{{ esc_attr($blockId) }}" class="text-display mb-6">
                        {{ $title }}
                    </h2>
                @endif
                @if($content)
                    <div class="text-content-inverse max-w-xl mx-auto">
                        <x-prose :inherit="true">{!! $content !!}</x-prose>
                    </div>
                @endif
                @if($cta)
                    <div class="mt-8">
                        <x-button
                            :url="$cta['url']"
                            :title="$cta['title']"
                            :target="$cta['target']"
                            variant="inverse"
                            size="lg"
                            :analytics="['event' => 'CTA', 'meta' => 'cta_block']"
                        />
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
