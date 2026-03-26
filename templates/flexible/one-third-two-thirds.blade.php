{{--
    One Third / Two Thirds - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose, x-card, x-section-header
    ACF Fields: show_section_header, section_chip, section_headline, section_description, column_1, column_2, background_color
--}}

@php
    $showHeader = get_sub_field('show_section_header');
    $chip = $showHeader ? get_sub_field('section_chip') : null;
    $headline = $showHeader ? \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('section_headline')) : null;
    $description = $showHeader ? \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('section_description')) : null;
    $alignment = $showHeader ? (get_sub_field('section_alignment') ?: 'center') : 'center';
    $column_1 = get_sub_field('column_1');
    $column_2 = get_sub_field('column_2');
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

<x-section :anchor="$sectionAnchor" :background="$background" class="one-third-two-thirds">
    <x-section-header :chip="$chip" :headline="$headline" :description="$description" :alignment="$alignment" />
    <x-grid cols="1/3-2/3" gap="lg" align="items-center">
        <x-prose>{!! $column_1 !!}</x-prose>
        <x-card variant="outlined" padding="lg">
            <x-prose>{!! $column_2 !!}</x-prose>
        </x-card>
    </x-grid>
</x-section>
