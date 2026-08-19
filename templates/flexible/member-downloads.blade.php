{{--
    Member Downloads Flexible Content Layout

    Only available on pages with page_is_member_area = true.
    Renders the downloads table component (Alpine.js + AJAX).
--}}

@php($background = get_sub_field('background_color') ?: 'primary')

<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background">
    @include('member-area.downloads')
</x-section>
