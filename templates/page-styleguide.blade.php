{{--
    Template Name: Styleguide

    Die Design-System-Referenz wird hier aus den echten Komponenten und den aktiven
    Tokens gerendert, nicht aus HTML im Inhaltsfeld. Darunter laeuft die normale
    Flexible-Content-Schleife weiter, damit die Galerie der Layouts genau so
    entsteht wie auf jeder anderen Seite: durch die Layout-Templates selbst.

    Zwei Teile, zwei Gruende:
    - Referenz im Template, weil eine Kopie im Inhaltsfeld unweigerlich abdriftet.
    - Galerie im Inhalt, weil ACF-Layouts nur aus ACF-Daten entstehen koennen und
      dort ohnehin ihre echten Templates rendern.
--}}

@extends('layouts.app')

@section('content')
    <article class="page page-styleguide">
        @if(post_password_required())
            @include('partials.password-form')
        @else
            <header class="page-header max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <h1>{{ get_the_title() }}</h1>
            </header>

            @include('styleguide.tokens')
            @include('styleguide.components')

            @if(have_rows('page_sections'))
                <x-section anchor="layouts" background="primary" padding="lg">
                    <x-section-header
                        chip="Flexible Content"
                        headline="Layouts"
                        description="Jedes Layout unten wird von seinem eigenen Template aus ACF-Daten gerendert, genau wie auf einer Kundenseite."
                    />
                </x-section>

                {{--
                    $layoutCounters is the same variable name hero.blade.php reads (see
                    hero.blade.php:17) to decide h1 vs h2 -- page.blade.php seeds it at
                    zero because there it owns the page's only h1. Here the header above
                    already rendered the page's h1 (the post title), so no hero in this
                    gallery may claim h1 too: pre-seeding 'hero' at 1 makes the first hero
                    read as the *second* one and render h2, like every other hero would.
                    Anchor numbering must not shift because of that pre-seed, so it gets
                    its own independent counter instead of reusing $layoutCounters.
                --}}
                @php($layoutCounters = ['hero' => 1])
                @php($anchorCounters = [])
                @while(have_rows('page_sections'))
                    @php(the_row())
                    @php($layout = get_row_layout())
                    @php($layoutCounters[$layout] = ($layoutCounters[$layout] ?? 0) + 1)
                    @php($anchorCounters[$layout] = ($anchorCounters[$layout] ?? 0) + 1)
                    @php($customAnchor = get_sub_field('section_anchor'))
                    @php($sectionAnchor = $customAnchor ?: str_replace('_', '-', $layout) . '-' . $anchorCounters[$layout])
                    @includeIf('flexible.' . str_replace('_', '-', $layout))
                @endwhile
            @endif
        @endif
    </article>
@endsection
