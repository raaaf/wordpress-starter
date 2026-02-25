{{--
    One Column - Flexible Content Layout

    Uses shared components: x-section, x-prose, x-section-header
    Fields: show_section_header, section_chip, section_headline, section_description, content
--}}

@php
    $showHeader = get_sub_field('show_section_header');
    $chip = $showHeader ? get_sub_field('section_chip') : null;
    $headline = $showHeader ? str_replace('[br]', '<br>', get_sub_field('section_headline') ?: '') : null;
    $description = $showHeader ? get_sub_field('section_description') : null;
    $alignment = $showHeader ? (get_sub_field('section_alignment') ?: 'center') : 'center';
    $content = get_sub_field('content');
@endphp

<x-section class="one-column">
    <x-section-header :chip="$chip" :headline="$headline" :description="$description" :alignment="$alignment" class="max-w-2xl mx-auto" />
    <div class="max-w-2xl mx-auto">
        <x-prose>{!! $content !!}</x-prose>
    </div>
</x-section>
