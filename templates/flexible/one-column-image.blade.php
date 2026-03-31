{{--
    One Column Image - Flexible Content Layout

    Uses shared components: x-section, x-prose, x-card, x-section-header
    ACF Fields: show_section_header, section_chip, section_headline, section_description, image, accordion, background_color
--}}

@php
    $showHeader = get_sub_field('show_section_header');
    $chip = $showHeader ? get_sub_field('section_chip') : null;
    $headline = $showHeader ? \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('section_headline')) : null;
    $description = $showHeader ? \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('section_description')) : null;
    $alignment = $showHeader ? (get_sub_field('section_alignment') ?: 'center') : 'center';
    $image = get_sub_field('image');
    $content = get_sub_field('content');
    $accordion = get_sub_field('accordion') ?: [];
    $background = get_sub_field('background_color') ?: 'primary';

    // Handle ID vs array format for image with proper sizing
    if (is_numeric($image)) {
        $imgSrc = wp_get_attachment_image_src($image, 'hero-split');
        $image = [
            'url' => $imgSrc ? $imgSrc[0] : wp_get_attachment_url($image),
            'alt' => get_post_meta($image, '_wp_attachment_image_alt', true) ?: '',
            'width' => $imgSrc ? $imgSrc[1] : '',
            'height' => $imgSrc ? $imgSrc[2] : '',
        ];
    }
@endphp

<x-section :anchor="$sectionAnchor" :background="$background" class="one-column-image">
    <x-section-header :chip="$chip" :headline="$headline" :description="$description" :alignment="$alignment" />
    <div class="mx-auto max-w-3xl">
        <x-card variant="outlined" padding="none" class="overflow-hidden">
            @if($image && !empty($image['url']))
                <img src="{{ $image['url'] }}"
                     alt="{{ $image['alt'] ?? '' }}"
                     @if(!empty($image['width']) && !empty($image['height']))width="{{ $image['width'] }}" height="{{ $image['height'] }}"@endif
                     class="w-full object-cover"
                     loading="lazy">
            @endif
            @if($content)
                <div class="p-6 lg:p-8">
                    <x-prose>{!! $content !!}</x-prose>
                </div>
            @endif
            @if(!empty($accordion))
                <div class="p-6 lg:p-8" x-data="{ active: null }">
                    @foreach($accordion as $aIdx => $aItem)
                        <div class="border-b border-line last:border-b-0">
                            <button id="acc-btn-{{ $aIdx }}"
                                    @click="active = active === {{ $aIdx }} ? null : {{ $aIdx }}"
                                    :aria-expanded="active === {{ $aIdx }}"
                                    aria-controls="acc-{{ $aIdx }}"
                                    class="group flex items-center justify-between w-full py-3 font-bold text-left cursor-pointer transition-colors hover:text-content-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-line-focus focus-visible:ring-offset-2"
                                    :class="{ 'text-content-brand': active === {{ $aIdx }} }">
                                {{ $aItem['title'] }}
                                <svg class="w-4 h-4 shrink-0 transition-transform duration-200"
                                     :class="{ 'rotate-180': active === {{ $aIdx }} }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="active === {{ $aIdx }}"
                                 x-collapse
                                 id="acc-{{ $aIdx }}"
                                 role="region"
                                 aria-labelledby="acc-btn-{{ $aIdx }}"
                                 class="pb-4">
                                <x-prose class="text-sm">{!! $aItem['content'] !!}</x-prose>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-section>
