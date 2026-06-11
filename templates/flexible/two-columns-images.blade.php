{{--
    Two Columns Images - Flexible Content Layout

    Uses shared components: x-section, x-grid, x-prose, x-card, x-section-header
    ACF Fields: show_section_header, section_chip, section_headline, section_description, image_1, accordion_1, image_2, accordion_2, background_color
--}}

@php
    $showHeader = get_sub_field('show_section_header');
    $chip = $showHeader ? get_sub_field('section_chip') : null;
    $headline = $showHeader ? \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('section_headline')) : null;
    $description = $showHeader ? \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('section_description')) : null;
    $alignment = $showHeader ? (get_sub_field('section_alignment') ?: 'center') : 'center';
    $label_1 = get_sub_field('label_1');
    $image_1 = get_sub_field('image_1');
    $column_1 = get_sub_field('column_1');
    $accordion_1 = get_sub_field('accordion_1') ?: [];
    $label_2 = get_sub_field('label_2');
    $image_2 = get_sub_field('image_2');
    $column_2 = get_sub_field('column_2');
    $accordion_2 = get_sub_field('accordion_2') ?: [];
    $background = get_sub_field('background_color') ?: 'primary';
    $layoutId = uniqid();

    // Preserve numeric IDs for wp_get_attachment_image; also build array fallback
    $imageIds = [];
    foreach (['image_1', 'image_2'] as $var) {
        $imageIds[$var] = is_numeric($$var) ? (int) $$var : null;
        if (is_numeric($$var)) {
            $imgSrc = wp_get_attachment_image_src($$var, 'hero-split');
            $$var = [
                'url' => $imgSrc ? $imgSrc[0] : wp_get_attachment_url($$var),
                'alt' => get_post_meta($$var, '_wp_attachment_image_alt', true) ?: '',
                'width' => $imgSrc ? $imgSrc[1] : '',
                'height' => $imgSrc ? $imgSrc[2] : '',
            ];
        }
    }
@endphp

<x-section :anchor="$sectionAnchor" :background="$background" class="two-columns-images">
    <x-section-header :chip="$chip" :headline="$headline" :description="$description" :alignment="$alignment" />
    <x-grid cols="2" gap="xl" align="items-stretch">
        @foreach([1, 2] as $col)
            @php
                $lbl = ${'label_' . $col};
                $img = ${'image_' . $col};
                $imgId = $imageIds['image_' . $col];
                $text = ${'column_' . $col};
                $acc = ${'accordion_' . $col};
            @endphp
            @if($lbl || ($img && !empty($img['url'])) || $text || !empty($acc))
            <x-card variant="outlined" padding="none" class="overflow-hidden">
                @if($lbl)
                    <div class="p-6 lg:p-8 pb-0 lg:pb-0">
                        <p class="text-sm font-bold uppercase tracking-wider text-content-secondary mb-4">{{ $lbl }}</p>
                    </div>
                @endif
                @if($imgId)
                    {!! wp_get_attachment_image($imgId, 'hero-split', false, [
                        'class' => 'w-full object-cover',
                        'alt' => $img['alt'] ?? '',
                        'loading' => 'lazy',
                        'decoding' => 'async',
                    ]) !!}
                @elseif($img && !empty($img['url']))
                    <img src="{{ $img['url'] }}"
                         alt="{{ $img['alt'] ?? '' }}"
                         @if(!empty($img['width']) && !empty($img['height']))width="{{ $img['width'] }}" height="{{ $img['height'] }}"@endif
                         class="w-full object-cover"
                         loading="lazy">
                @endif
                @if($text)
                    <div class="p-6 lg:p-8">
                        <x-prose>@kses($text)</x-prose>
                    </div>
                @endif
                @if(!empty($acc))
                    @include('partials.inline-accordion', [
                        'items' => $acc,
                        'idPrefix' => 'acc-tci-' . $layoutId . '-' . $col,
                    ])
                @endif
            </x-card>
            @endif
        @endforeach
    </x-grid>
</x-section>
