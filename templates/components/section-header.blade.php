{{--
    Section Header Component

    Renders an optional chip, headline, and description above column layouts.
    Only renders if at least one of chip, headline, or description is non-empty.

    @param string|null $chip        - Optional badge text above the headline
    @param string|null $headline    - Headline (HTML allowed for [br] replacements)
    @param string|null $description - Optional description paragraph (HTML allowed for [br] replacements)
    @param string $alignment        - 'center' (default) or 'left'
    @param int $level               - Heading level: 2, 3, or 4 (default: 2)
    @param string $class            - Additional CSS classes
--}}

@props([
    'chip' => null,
    'headline' => null,
    'description' => null,
    'alignment' => 'center',
    'level' => 2,
    'class' => '',
])

@php
    $isCenter = $alignment !== 'left';
    $alignClass = $isCenter ? 'text-center' : 'text-left';
    $descClass  = $isCenter ? 'max-w-3xl mx-auto' : '';
    $headingTag = in_array((int) $level, [2, 3, 4], true) ? 'h' . (int) $level : 'h2';
@endphp

@if($chip || $headline || $description)
    <div class="section-header {{ $alignClass }} mb-12 {{ $class }}">
        @if($chip)
            <x-badge variant="brand" size="sm" class="mb-4">{{ $chip }}</x-badge>
        @endif

        @if($headline)
            <{{ $headingTag }} class="m-0 mt-4">{!! wp_kses_post($headline) !!}</{{ $headingTag }}>
        @endif

        @if($description)
            <p class="mt-4 text-body-large text-content-secondary {{ $descClass }}">{!! wp_kses_post($description) !!}</p>
        @endif
    </div>
@endif
