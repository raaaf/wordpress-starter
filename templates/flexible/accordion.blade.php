{{--
    Accordion - Flexible Content Layout

    Uses shared components: x-section, x-prose
    Fields: title, section extras, accordion (repeater with icon, title, content), first_open,
    allow_multiple, faq_schema, background_color

    Includes FAQPage JSON-LD schema for SEO rich snippets, gated to singular,
    published, non-password-protected pages (see $canPublishFaqSchema below).
    The primary guard against leaking a password-protected preview's Q&A is
    that all three callers (page.blade.php, page-styleguide.blade.php,
    page-member-area.blade.php) already gate the whole page_sections loop
    behind post_password_required() before this template ever runs;
    $canPublishFaqSchema repeats that check here as defence in depth.
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $kopf = \WordpressStarter\Helpers\SectionHeader::extras($title);
    $items = get_sub_field('accordion') ?: [];
    $background = get_sub_field('background_color') ?: 'primary';
    $firstOpen = (bool) get_sub_field('first_open');
    $allowMultiple = (bool) get_sub_field('allow_multiple');

    // Only an accordion the editor marked as questions and answers may claim
    // FAQPage. get_sub_field returns null for rows saved before the field
    // existed, and those were all emitting the schema, so null keeps it on.
    $faqSchemaField = get_sub_field('faq_schema');
    $wantsFaqSchema = $faqSchemaField === null || (bool) $faqSchemaField;
    $accordionId = uniqid();

    // Checks that this is a singular, published, non-password-protected
    // page — schema is only emitted for content the public can actually
    // reach.
    $canPublishFaqSchema = is_singular() && get_post_status() === 'publish' && !post_password_required();

    // Build FAQPage schema for SEO
    $faqQuestions = [];
    if ($wantsFaqSchema && $canPublishFaqSchema && !empty($items)) {
        foreach ($items as $item) {
            if (!empty($item['title']) && !empty($item['content'])) {
                $faqQuestions[] = [
                    '@type' => 'Question',
                    'name' => wp_strip_all_tags($item['title']),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => wp_strip_all_tags($item['content']),
                    ],
                ];
            }
        }
    }
@endphp

@if($title || !empty($items) || current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" padding="md" class="accordion">
    <x-section-header :chip="$kopf['chip']" :headline="$kopf['headline']" :description="$kopf['description']" :alignment="$kopf['alignment']" />
    <div class="max-w-2xl mx-auto">
        @php $itemCount = count($items); @endphp
        <div
            class="flex flex-col"
            x-data="{
                open: {{ $firstOpen ? '[0]' : '[]' }},
                multiple: {{ $allowMultiple ? 'true' : 'false' }},
                itemCount: {{ $itemCount }},
                isOpen(index) {
                    return this.open.includes(index);
                },
                toggle(index) {
                    if (this.multiple) {
                        this.open = this.isOpen(index)
                            ? this.open.filter((i) => i !== index)
                            : [...this.open, index];

                        return;
                    }

                    this.open = this.isOpen(index) ? [] : [index];
                },
                focusItem(index) {
                    this.$nextTick(() => {
                        this.$refs['accordion' + index]?.focus();
                    });
                }
            }"
        >
            @foreach($items as $index => $item)
                {{-- Bewusst ohne overflow-hidden: weder an diesem Item-Wrapper noch am
                     aeusseren x-data-Container zwei Ebenen hoeher. Der Kopf ist w-full und
                     sitzt buendig an der Aussenkante, ein Zuschnitt haette den 2px-Fokusring
                     links/rechts bei jedem Eintrag und oben beim ersten Eintrag
                     weggeschnitten. x-collapse setzt overflow ohnehin selbst am Panel, nicht
                     an diesen Containern. --}}
                <div class="w-full border-b border-line last:border-b-0">
                    <button x-ref="accordion{{ $index }}"
                            id="accordion-header-{{ $accordionId }}-{{ $index }}"
                            @click="toggle({{ $index }})"
                            @keydown.down.prevent="focusItem(({{ $index }} + 1) % itemCount)"
                            @keydown.up.prevent="focusItem(({{ $index }} - 1 + itemCount) % itemCount)"
                            @keydown.home.prevent="focusItem(0)"
                            @keydown.end.prevent="focusItem(itemCount - 1)"
                            :aria-expanded="isOpen({{ $index }})"
                            aria-controls="accordion-content-{{ $accordionId }}-{{ $index }}"
                            class="group flex items-center justify-between w-full py-4 px-3 mb-0 font-bold text-left cursor-pointer transition-colors rounded-[var(--radius-sm)] hover:text-content-brand focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring-ghost)]"
                            :class="{ 'text-content-brand': isOpen({{ $index }}) }">
                        <span class="flex items-center gap-3">
                            @if(!empty($item['icon']))
                                <x-icon :name="$item['icon']" class="w-5 h-5" />
                            @endif
                            {{ $item['title'] }}
                        </span>
                        <x-rotating-chevron active="isOpen({{ $index }})" size="lg" />
                    </button>
                    <div x-show="isOpen({{ $index }})"
                         x-collapse
                         id="accordion-content-{{ $accordionId }}-{{ $index }}"
                         @if(count($items) < 7) role="region" @endif
                         :aria-labelledby="'accordion-header-{{ $accordionId }}-{{ $index }}'"
                         class="px-3 pt-1 mb-6">
                        <x-prose>@kses($item['content'])</x-prose>
                    </div>
                </div>
            @endforeach
        </div>

        @if(empty($items) && current_user_can('edit_posts'))
            <div class="p-8 text-center rounded-lg bg-surface-secondary">
                <p class="text-content-secondary">{{ __('Bitte füge mindestens einen Akkordeon-Eintrag hinzu.', 'wp-starter') }}</p>
            </div>
        @endif
    </div>
</x-section>
@endif

{{-- FAQPage JSON-LD Schema for SEO --}}
@if(!empty($faqQuestions))
    @php
        $faqSchema = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqQuestions];
        $nonce = $GLOBALS['csp_nonce'] ?? '';
    @endphp
    {{-- JSON_HEX_TAG als zweite Verteidigungslinie: escaped < und > in JSON-Strings
         (unsichtbar nach dem Parsen), damit eine spaetere Lockerung von
         wp_strip_all_tags oben nicht stillschweigend zu gespeichertem XSS ueber einen
         </script>-Ausbruch wird. --}}
    <script type="application/ld+json" @if($nonce) nonce="{{ $nonce }}" @endif>
        {!! wp_json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_HEX_TAG) !!}
    </script>
@endif
