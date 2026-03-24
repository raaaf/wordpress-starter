{{--
    Section Header Component

    Renders an optional chip, H2 headline, and description above column layouts.
    Only renders if at least one of chip, headline, or description is non-empty.

    @param string|null $chip        - Optional badge text above the headline
    @param string|null $headline    - H2 headline (HTML allowed for [br] replacements)
    @param string|null $description - Optional description paragraph
    @param string $alignment        - 'center' (default) or 'left'
    @param string $class            - Additional CSS classes
--}}

@props([
    'chip' => null,
    'headline' => null,
    'description' => null,
    'alignment' => 'center',
    'class' => '',
])

@php
    $isCenter = $alignment !== 'left';
    $alignClass = $isCenter ? 'text-center' : 'text-left';
    $descClass  = $isCenter ? 'max-w-3xl mx-auto' : '';
@endphp

@if($chip || $headline || $description)
    <div class="section-header {{ $alignClass }} mb-12 {{ $class }}">
        @if($chip)
            <x-badge variant="brand" size="sm" class="mb-4">{{ $chip }}</x-badge>
        @endif

        @if($headline)
            <h2 class="m-0 mt-4">{!! $headline !!}</h2>
        @endif

        @if($description)
            <p class="mt-4 text-body-large text-content-secondary {{ $descClass }}">{{ $description }}</p>
        @endif
    </div>
@endif
