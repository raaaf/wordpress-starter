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

    // Die Textspalte folgt der Ausrichtung des Kopfes. Vorher stand der Kopf
    // links und der Text darunter trotzdem mittig, zwei Kanten fuer denselben
    // Abschnitt. Breite in ch statt px: 68 Zeichen liegen in der bequemen
    // Spanne, die 42rem davor kamen auf gemessene 84.
    $spalte = $alignment === 'left' ? 'max-w-[58ch]' : 'max-w-[58ch] mx-auto';
@endphp

@if($chip || $headline || $description || $content)
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" class="one-column">
    <x-section-header :chip="$chip" :headline="$headline" :description="$description" :alignment="$alignment" :class="$spalte" />
    <div class="{{ $spalte }}">
        <x-prose>@kses($content)</x-prose>
    </div>
</x-section>
@endif
