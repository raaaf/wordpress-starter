{{--
    Two Thirds / One Third - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose, x-card, x-section-header
    ACF Fields: show_section_header, section_chip, section_headline, section_description, column_1, column_2, background_color
--}}

@php
    ['chip' => $chip, 'headline' => $headline, 'description' => $description, 'alignment' => $alignment]
        = \WordpressStarter\Helpers\SectionHeader::fields();
    $column_1 = get_sub_field('column_1');
    $column_2 = get_sub_field('column_2');
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

@if($chip || $headline || $description || $column_1 || $column_2)
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :background="$background" class="two-thirds-one-third">
    <x-section-header :chip="$chip" :headline="$headline" :description="$description" :alignment="$alignment" />
    <x-grid cols="2/3-1/3" gap="lg" align="items-center">
        <x-card variant="outlined" padding="lg">
            <x-prose>@kses($column_1)</x-prose>
        </x-card>
        <x-prose>@kses($column_2)</x-prose>
    </x-grid>
</x-section>
@endif
