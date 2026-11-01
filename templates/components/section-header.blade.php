{{--
    Section Header Component

    Renders an optional chip, headline, and description above column layouts.
    Only renders if at least one of chip, headline, or description is non-empty.

    @param string|null $chip        - Optional overline text above the headline
    @param string|null $headline    - Headline (HTML allowed for [br] replacements)
    @param string|null $description - Optional description paragraph (HTML allowed for [br] replacements)
    @param int $level               - Heading level: 2, 3, or 4 (default: 2)
    @param string $alignment        - 'left' (default) or 'center'
    @param string $class            - Additional CSS classes
--}}

@props([
    'chip' => null,
    'headline' => null,
    'description' => null,
    'level' => 2,
    'alignment' => 'left',
    'class' => '',
])

@php
    $isCenter = $alignment !== 'left';
    // One flex column owns every gap in the header, so no child carries a margin
    // that could add up with its neighbour's (badge mb-4 + headline mt-4 used to
    // give 32px between chip and title). Rhythm: chip to title 12, title to
    // description 12, header to content 40 (mobile) / 64 (md+).
    $alignClass = $isCenter ? 'text-center items-center' : 'text-left items-start';
    // Also limited when left-aligned: without the width the description ran across the
    // whole container, 136 characters measured.
    $descClass  = $isCenter ? 'mx-auto' : '';
    $headingTag = in_array((int) $level, [2, 3, 4], true) ? 'h' . (int) $level : 'h2';
@endphp

@if($chip || $headline || $description)
    <div {{ $attributes->merge(['class' => "section-header flex flex-col gap-3 {$alignClass} mb-10 md:mb-16 {$class}"]) }}>
        @if($chip)
            <span class="section-header__overline text-overline text-content-secondary">{{ $chip }}</span>
        @endif

        @if($headline)
            <{{ $headingTag }} class="section-title m-0">{!! wp_kses_post($headline) !!}</{{ $headingTag }}>
        @endif

        @if($description)
            <p class="section-header__description m-0 text-content-secondary {{ $descClass }}">{!! wp_kses_post($description) !!}</p>
        @endif
    </div>
@endif
