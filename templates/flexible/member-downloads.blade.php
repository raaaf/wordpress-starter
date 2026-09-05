{{--
    Member Downloads Flexible Content Layout

    Only available on pages with page_is_member_area = true.
    Renders the downloads table component (Alpine.js + AJAX).
--}}

@php
    $background = get_sub_field('background_color') ?: 'primary';
    $isMemberAreaPage = (bool) get_field('page_is_member_area', get_the_ID());
    // page_is_member_area is only an ACF flag, not tied to a specific page
    // template - this layout can land on a page rendered without the
    // page-member-area.blade.php auth gate, so it must check itself too.
    $isAuthenticated = \WordpressStarter\MemberArea\Auth::isAuthenticated();
@endphp

@if($isMemberAreaPage && $isAuthenticated)
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background">
    @include('member-area.downloads')
</x-section>
@elseif($isMemberAreaPage)
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background">
    <div class="p-6 rounded-[var(--card-radius)] bg-surface-secondary surface-sheen text-center">
        <p class="mb-4 text-content-secondary">{{ __('Bitte melde dich an, um diese Downloads zu sehen.', 'wp-starter') }}</p>
        <x-link :url="get_permalink()">{{ __('Zur Anmeldung', 'wp-starter') }}</x-link>
    </div>
</x-section>
@elseif(current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background">
    <div class="p-6 rounded-[var(--card-radius)] bg-surface-secondary surface-sheen">
        <p class="text-content-secondary">{{ __('Dieser Block wird nur auf Seiten mit "page_is_member_area" angezeigt.', 'wp-starter') }}</p>
    </div>
</x-section>
@endif
