{{--
    One Third / Two Thirds - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose, x-card, x-section-header
    ACF Fields: show_section_header, section_chip, section_headline, section_description, column_1, column_2, vertical_alignment, background_color
--}}

@php
    ['chip' => $chip, 'headline' => $headline, 'description' => $description, 'alignment' => $alignment]
        = \WordpressStarter\Helpers\SectionHeader::fields();
    $column_1 = get_sub_field('column_1');
    $column_2 = get_sub_field('column_2');
    $background = get_sub_field('background_color') ?: 'primary';
    $verticalAlignment = get_sub_field('vertical_alignment');
    $verticalAlignment = in_array($verticalAlignment, ['top', 'center', 'bottom'], true) ? $verticalAlignment : 'top';
    $gridAlign = match ($verticalAlignment) {
        'center' => 'items-center',
        'bottom' => 'items-end',
        default => 'items-start',
    };
    $topOffset = $verticalAlignment === 'top' ? 'md:pt-[calc(2rem+1px)]' : '';
@endphp

@if($chip || $headline || $description || $column_1 || $column_2)
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" class="one-third-two-thirds">
    <x-section-header :chip="$chip" :headline="$headline" :description="$description" :alignment="$alignment" />
    <x-grid cols="1/3-2/3" gap="lg" :align="$gridAlign">
        {{-- Top offset = card padding (p-8) + border, so both headings share a line. Only applied for top alignment. --}}
        <div class="max-w-[58ch] {{ $topOffset }}">
            <x-prose>@kses($column_1)</x-prose>
        </div>
        <x-card variant="outlined" padding="lg">
            <div class="max-w-[58ch]">
                <x-prose>@kses($column_2)</x-prose>
            </div>
        </x-card>
    </x-grid>
</x-section>
@endif
