{{--
    Accordion Block

    Uses shared components: x-section, x-prose
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
        $itemTitle = $item['titel'] ?? $item['title'] ?? '';
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
    <script type="application/ld+json">
        {!! wp_json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <x-section :background="$background" :anchor="$anchor" padding="md" class="{{ $classes }} accordion">
        <div class="max-w-2xl mx-auto">
            {{-- aria-live region announces changes to screen readers --}}
            <div class="flex flex-col overflow-hidden"
                 x-data="{ active: null }"
                 role="region"
                 aria-label="FAQ Accordion">
                @foreach($accordion_items as $index => $item)
                    @php
                        $title = $item['titel'] ?? $item['title'] ?? '';
                        $content = $item['content'] ?? '';
                        $itemId = 'accordion-' . $block['id'] . '-' . $index;
                    @endphp
                    <div class="w-full px-6 mx-auto overflow-hidden border-b border-line last:border-b-0">
                        <h3 class="m-0">
                            <button @click="active = active === {{ $index }} ? null : {{ $index }}"
                                    :aria-expanded="active === {{ $index }}"
                                    aria-controls="content-{{ esc_attr($itemId) }}"
                                    class="flex items-center justify-between w-full py-4 pr-10 mb-0 font-bold text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-line-focus focus-visible:ring-offset-2 rounded"
                                    pirsch-event="Accordion_Toggle"
                                    pirsch-meta-key="accordion_block"
                                    pirsch-meta-item="{{ esc_attr($title) }}">
                                <span>{{ $title }}</span>
                                <svg class="w-5 h-5 transition-transform duration-200"
                                     :class="{ 'rotate-180': active === {{ $index }} }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </h3>
                        <div x-show="active === {{ $index }}"
                             x-collapse
                             x-cloak
                             id="content-{{ esc_attr($itemId) }}"
                             role="region"
                             :aria-hidden="active !== {{ $index }}"
                             class="mb-8">
                            <x-prose>@kses($content)</x-prose>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-section>
@endif
