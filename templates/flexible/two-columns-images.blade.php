{{--
    Two Columns Images - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose, x-card, x-section-header
    ACF Fields: show_section_header, section_chip, section_headline, section_description, image_1, accordion_1, image_2, accordion_2, background_color
--}}

@php
    ['chip' => $chip, 'headline' => $headline, 'description' => $description, 'alignment' => $alignment]
        = \WordpressStarter\Helpers\SectionHeader::fields();
    $background = get_sub_field('background_color') ?: 'primary';
    $layoutId = uniqid();

    $columns = [];
    $hasAnyColumn = false;
    foreach ([1, 2] as $col) {
        $label = get_sub_field("label_{$col}");
        $imageValue = get_sub_field("image_{$col}");
        $text = get_sub_field("column_{$col}");
        $accordion = get_sub_field("accordion_{$col}") ?: [];

        // image_N is registered with return_format 'id' (see
        // FieldDefinitions::buildColumnImageBlock), so this is always an int or empty.
        $imgId = is_numeric($imageValue) ? (int) $imageValue : null;

        if ($label || $imgId || $text || !empty($accordion)) {
            $hasAnyColumn = true;
        }

        $columns[$col] = [
            'label' => $label,
            'imgId' => $imgId,
            'text' => $text,
            'accordion' => $accordion,
        ];
    }
@endphp

@if($chip || $headline || $description || $hasAnyColumn)
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" class="two-columns-images">
    <x-section-header :chip="$chip" :headline="$headline" :description="$description" :alignment="$alignment" />
    <x-grid cols="2" gap="xl" align="items-stretch">
        @foreach($columns as $col => $data)
            @if($data['label'] || $data['imgId'] || $data['text'] || !empty($data['accordion']))
            <x-card variant="outlined" padding="none" class="overflow-hidden">
                @if($data['imgId'])
                    {!! wp_get_attachment_image($data['imgId'], 'hero-split', false, [
                        'class' => 'w-full aspect-[16/10] object-cover',
                        'alt' => \WordpressStarter\Helpers\Text::imageAlt($data['imgId'], $data['label'] ?: (string) $data['text']),
                        'decoding' => 'async',
                    ]) !!}
                @endif
                @if($data['label'] || $data['text'])
                    <div class="p-6 lg:p-8 {{ !empty($data['accordion']) ? 'pb-0 lg:pb-0' : '' }}">
                        @if($data['label'])
                            <p class="text-overline text-content-secondary mb-2">{{ $data['label'] }}</p>
                        @endif
                        @if($data['text'])
                            <x-prose>@kses($data['text'])</x-prose>
                        @endif
                    </div>
                @endif
                @if(!empty($data['accordion']))
                    @include('partials.inline-accordion', [
                        'items' => $data['accordion'],
                        'idPrefix' => 'acc-tci-' . $layoutId . '-' . $col,
                    ])
                @endif
            </x-card>
            @endif
        @endforeach
    </x-grid>
</x-section>
@endif
