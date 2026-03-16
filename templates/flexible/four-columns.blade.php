{{--
    Four Columns - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose, x-section-header
    Fields: show_section_header, section_chip, section_headline, section_description, column_1, column_2, column_3, column_4, background_color
--}}

@php
    $showHeader = get_sub_field('show_section_header');
    $chip = $showHeader ? get_sub_field('section_chip') : null;
    $headline = $showHeader ? str_replace('[br]', '<br>', get_sub_field('section_headline') ?: '') : null;
    $description = $showHeader ? get_sub_field('section_description') : null;
    $alignment = $showHeader ? (get_sub_field('section_alignment') ?: 'center') : 'center';
    $column_1 = get_sub_field('column_1');
    $column_2 = get_sub_field('column_2');
    $column_3 = get_sub_field('column_3');
    $column_4 = get_sub_field('column_4');
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

<x-section :anchor="$sectionAnchor" :background="$background" class="four-columns">
    <x-section-header :chip="$chip" :headline="$headline" :description="$description" :alignment="$alignment" />
    <x-grid cols="4" gap="lg">
        <x-prose>{!! $column_1 !!}</x-prose>
        <x-prose>{!! $column_2 !!}</x-prose>
        <x-prose>{!! $column_3 !!}</x-prose>
        <x-prose>{!! $column_4 !!}</x-prose>
    </x-grid>
</x-section>
