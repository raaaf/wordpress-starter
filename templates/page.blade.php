@extends('layouts.app')

@section('content')
    @if (have_posts())
        @while (have_posts()) @php(the_post())
            <article class="page">
                {{-- ACF sections bypass the_content(), so the password gate has to
                     be checked explicitly or protected pages would render in full. --}}
                @if(post_password_required())
                    @include('partials.password-form')
                @else
                {{-- Only show page header if no ACF sections (title is in Hero block) --}}
                {{-- Der Seitenkopf entfiel, sobald es Sektionen gab, weil die h1 im
                     Hero steckt. Eine Seite ohne Hero-Layout hatte damit gar keine h1.
                     Die rohe Meta-Zeile listet die Layoutnamen, das kostet keinen
                     zweiten Durchlauf. --}}
                @php($sectionLayouts = (array) get_post_meta(get_the_ID(), 'page_sections', true))
                @unless(is_front_page() || in_array('hero', $sectionLayouts, true))
                    <header class="page-header max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                        <h1>{{ get_the_title() }}</h1>
                    </header>
                @endunless

                {{-- Render ACF Flexible Content if available --}}
                @if(have_rows('page_sections'))
                    @php($layoutCounters = [])
                    @while(have_rows('page_sections'))
                        @php(the_row())
                        @php($layout = get_row_layout())
                        @php($layoutCounters[$layout] = ($layoutCounters[$layout] ?? 0) + 1)
                        @php($customAnchor = get_sub_field('section_anchor'))
                        @php($sectionAnchor = $customAnchor ?: str_replace('_', '-', $layout) . '-' . $layoutCounters[$layout])
                        @includeIf('flexible.' . str_replace('_', '-', $layout))
                    @endwhile
                @endif

                {{-- Render standard WordPress content if available --}}
                @if(get_the_content())
                    <div class="page-content max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        @php(the_content())
                    </div>
                @endif
                @endif
            </article>
        @endwhile
    @endif
@endsection
