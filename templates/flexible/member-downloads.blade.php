{{--
    Member Downloads Flexible Content Layout

    Only available on pages with page_is_member_area = true.
    Renders the downloads table component (Alpine.js + AJAX).
--}}

@php
    $background = get_sub_field('background_color') ?: 'primary';
    $isMemberAreaPage = (bool) get_field('page_is_member_area', get_the_ID());
@endphp

@if($isMemberAreaPage)
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background">
    @include('member-area.downloads')
</x-section>
@elseif(current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background">
    <div class="p-6 rounded-[var(--card-radius)] bg-surface-secondary surface-sheen">
        <p class="text-content-secondary">{{ __('Dieser Block wird nur auf Seiten mit "page_is_member_area" angezeigt.', 'wp-starter') }}</p>
    </div>
</x-section>
@endif
