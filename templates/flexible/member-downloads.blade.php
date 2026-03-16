{{--
    Member Downloads Flexible Content Layout

    Only available on pages with page_is_member_area = true.
    Renders the downloads table component (Alpine.js + AJAX).
    Background is fixed — no color picker exposed to editors.
--}}

<x-section :anchor="$sectionAnchor" background="primary">
    @include('member-area.downloads')
</x-section>
