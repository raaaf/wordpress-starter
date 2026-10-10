{{--
    Shared ACF Flexible Content loop for page.blade.php and page-member-area.blade.php.
--}}
@if(have_rows('page_sections'))
    @php($layoutCounters = [])
    @while(have_rows('page_sections'))
        @php(the_row())
        @php($layout = get_row_layout())
        @php($layoutCounters[$layout] = ($layoutCounters[$layout] ?? 0) + 1)
        @php($customAnchor = get_sub_field('section_anchor'))
        @php($sectionSpacing = get_sub_field('section_spacing') ?: null)
        @php($sectionWidth = get_sub_field('section_width') ?: null)
        @php($sectionAnchor = $customAnchor ?: str_replace('_', '-', $layout) . '-' . $layoutCounters[$layout])
        @includeIf('flexible.' . str_replace('_', '-', $layout))
    @endwhile
@endif
