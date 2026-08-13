{{--
    Two Columns - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose, x-section-header
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
<x-section :anchor="$sectionAnchor" :background="$background" class="two-columns">
    <x-section-header :chip="$chip" :headline="$headline" :description="$description" :alignment="$alignment" />
    <x-grid cols="2" gap="lg">
        <x-prose>@kses($column_1)</x-prose>
        <x-prose>@kses($column_2)</x-prose>
    </x-grid>
</x-section>
@endif
