{{--
    Four Columns - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose, x-section-header
    Fields: show_section_header, section_chip, section_headline, section_description, column_1, column_2, column_3, column_4, background_color
--}}

@php
    ['chip' => $chip, 'headline' => $headline, 'description' => $description, 'alignment' => $alignment]
        = \WordpressStarter\Helpers\SectionHeader::fields();
    $column_1 = get_sub_field('column_1');
    $column_2 = get_sub_field('column_2');
    $column_3 = get_sub_field('column_3');
    $column_4 = get_sub_field('column_4');
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

@if($chip || $headline || $description || $column_1 || $column_2 || $column_3 || $column_4)
<x-section :anchor="$sectionAnchor" :background="$background" class="four-columns">
    <x-section-header :chip="$chip" :headline="$headline" :description="$description" :alignment="$alignment" />
    <x-grid cols="4" gap="md">
        <x-prose>@kses($column_1)</x-prose>
        <x-prose>@kses($column_2)</x-prose>
        <x-prose>@kses($column_3)</x-prose>
        <x-prose>@kses($column_4)</x-prose>
    </x-grid>
</x-section>
@endif
