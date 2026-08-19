{{--
    One Column - Flexible Content Layout

    Uses shared components: x-section, x-prose, x-section-header
    Fields: show_section_header, section_chip, section_headline, section_description, content, background_color
--}}

@php
    ['chip' => $chip, 'headline' => $headline, 'description' => $description, 'alignment' => $alignment]
        = \WordpressStarter\Helpers\SectionHeader::fields();
    $content = get_sub_field('content');
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

@if($chip || $headline || $description || $content)
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :background="$background" class="one-column">
    <x-section-header :chip="$chip" :headline="$headline" :description="$description" :alignment="$alignment" class="max-w-2xl mx-auto" />
    <div class="max-w-2xl mx-auto">
        <x-prose>@kses($content)</x-prose>
    </div>
</x-section>
@endif
