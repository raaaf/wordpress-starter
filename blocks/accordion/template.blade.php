{{--
    Accordion Block

    Uses shared components: x-section, x-prose, x-icon
    Fields: accordion (repeater), background_color
    Includes: FAQ Schema for SEO, aria-live for accessibility
--}}

@php
    $accordion_items = $fields['accordion'] ?? [];
    $background = $fields['background_color'] ?? 'primary';

    // Build FAQ Schema
    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => []
    ];

    foreach ($accordion_items as $item) {
        $itemTitle = $item['title'] ?? '';
        $itemContent = $item['content'] ?? '';
        if ($itemTitle && $itemContent) {
            $faqSchema['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $itemTitle,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => wp_strip_all_tags($itemContent)
                ]
            ];
        }
    }
@endphp

@if($accordion_items)
    {{-- FAQ Schema for SEO --}}
    <script type="application/ld+json" nonce="{{ $GLOBALS['csp_nonce'] ?? '' }}">
        {!! wp_json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <x-section :background="$background" :anchor="$anchor" :wrapperAttributes="$wrapper_attributes" padding="md" class="accordion {{ $classes }}">
        <div class="max-w-2xl mx-auto">
            {{-- aria-live region announces changes to screen readers --}}
            <div class="flex flex-col overflow-hidden"
                 @if(!$is_preview) x-data="{ active: null }" @endif
                 role="region"
                 aria-label="FAQ Accordion">
                @foreach($accordion_items as $index => $item)
                    @php
                        $title = $item['title'] ?? '';
                        $content = $item['content'] ?? '';
                        $icon = $item['icon'] ?? '';
                        $itemId = 'accordion-' . $block['id'] . '-' . $index;
                    @endphp
                    <div class="w-full px-6 mx-auto overflow-hidden border-b border-line last:border-b-0">
                        <h3 class="m-0">
                            <button
                                    @if(!$is_preview)
                                        @click="active = active === {{ $index }} ? null : {{ $index }}"
                                        :aria-expanded="active === {{ $index }}"
                                    @endif
                                    aria-controls="content-{{ esc_attr($itemId) }}"
                                    class="group flex items-center justify-between w-full py-4 pr-10 mb-0 font-bold text-left cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-line-focus focus-visible:ring-offset-2 rounded"
                                    pirsch-event="Accordion_Toggle"
                                    pirsch-meta-key="accordion_block"
                                    pirsch-meta-item="{{ esc_attr($title) }}">
                                <span class="inline-flex items-center gap-3">
                                    @if($icon)
                                        <x-icon :name="$icon" size="lg" class="text-content-brand" />
                                    @endif
                                    {{ $title }}
                                </span>
                                <svg
                                    class="w-5 h-5 transition-all duration-200 shrink-0 text-content-secondary group-hover:text-content-accent"
                                    @if(!$is_preview) :class="{ 'rotate-180 text-content-accent!': active === {{ $index }} }" @endif
                                    fill="currentColor"
                                    viewBox="0 0 16 16"
                                    aria-hidden="true"
                                >
                                    <path d="M15.2226 6.08403C15.4524 5.93085 15.7628 5.99294 15.916 6.2227C16.0691 6.45246 16.0071 6.76288 15.7773 6.91605L8.27734 11.916C8.10939 12.028 7.89061 12.028 7.72266 11.916L0.2227 6.91605C-0.00706345 6.76288 -0.0691469 6.45246 0.0840285 6.2227C0.237204 5.99294 0.547621 5.93085 0.777384 6.08403L8 10.8985L15.2226 6.08403Z"/>
                                </svg>
                            </button>
                        </h3>
                        <div @if(!$is_preview)
                                 x-show="active === {{ $index }}"
                                 x-collapse
                                 x-cloak
                                 :aria-hidden="active !== {{ $index }}"
                             @endif
                             id="content-{{ esc_attr($itemId) }}"
                             role="region"
                             class="mb-8">
                            <x-prose>@kses($content)</x-prose>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-section>
@endif
