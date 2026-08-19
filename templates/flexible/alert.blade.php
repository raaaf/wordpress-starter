{{--
    Hinweis - Flexible Content Layout

    Uses shared components: x-section, x-alert, x-prose
    Fields: variant, title, content, dismissible, background_color
--}}

@php
    $variant = get_sub_field('variant') ?: 'info';
    $title = get_sub_field('title');
    $content = get_sub_field('content');
    $dismissible = (bool) get_sub_field('dismissible');
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

@if($title || $content)
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" padding="sm" class="alert-section">
    <div class="max-w-3xl mx-auto">
        <x-alert :variant="$variant" :dismissible="$dismissible">
            @if($title)
                <p class="font-bold mb-1">{{ $title }}</p>
            @endif

            @if($content)
                {{-- prose-sm: der Hinweis setzt seinen Text auf text-sm, die
                     Standardgroesse der Prosa waere hier eine Stufe zu gross. --}}
                <x-prose class="prose-sm">@kses($content)</x-prose>
            @endif
        </x-alert>
    </div>
</x-section>
@endif
