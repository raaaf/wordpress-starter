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
                {{-- Only show page header if a hero layout actually carries a title (title is in Hero block) --}}
                {{-- Der Seitenkopf entfaellt nur, wenn ein Hero-Layout tatsaechlich einen
                     Titel traegt - hero.blade.php rendert seine Ueberschrift nur @if($title),
                     ein Hero ohne Titel liefert also keine h1. Die is_front_page()-Ausnahme
                     ist bewusst entfernt: jede Seite braucht genau eine h1, wer sie visuell
                     nicht will, gibt dem Hero einen Titel statt die Startseite pauschal
                     auszunehmen. Bewusst get_post_meta() statt get_field('page_sections'):
                     get_field() formatiert bei ACF-Flexible-Content ALLE Zeilen samt
                     Sub-Fields eager (Bilder ueber acf_get_attachment, WYSIWYG-Filter) -
                     fuer eine reine h1-Entscheidung auf jeder Seite unnoetige Mehrarbeit.
                     Das rohe Meta zur page_sections-Feld selbst ist nur ein flaches Array
                     aus Layoutnamen-Strings, daraus wird per Schleife (kein collect(), um
                     nicht doch wieder alle Zeilen zu materialisieren) der Index der ERSTEN
                     hero-Zeile gesucht. Nur die ERSTE Hero-Zeile kann ueberhaupt eine h1
                     werden (siehe hero.blade.php: $layoutCounters['hero'] === 1), jede
                     weitere Hero-Zeile rendert immer h2. Der Titel wird darueber gezielt per
                     get_post_meta($id, "page_sections_{$i}_title", true) gelesen - das ist
                     das reguläre ACF-Meta-Schema fuer Sub-Fields, geprimt durch WordPress'
                     Meta-Cache, also keine zusaetzliche Query. Truthy-Check mit demselben
                     Massstab wie hero.blade.php (kein trim, sonst weichen beide Templates
                     bei einem Leerraum-Titel voneinander ab). --}}
                @php($pageId = get_the_ID())
                @php($sectionLayouts = (array) get_post_meta($pageId, 'page_sections', true))
                @php($firstHeroIndex = null)
                @foreach($sectionLayouts as $sectionIndex => $sectionLayout)
                    @if($sectionLayout === 'hero')
                        @php($firstHeroIndex = $sectionIndex)
                        @break
                    @endif
                @endforeach
                @php($firstHeroTitle = $firstHeroIndex !== null ? get_post_meta($pageId, "page_sections_{$firstHeroIndex}_title", true) : null)
                @php($hasTitledHero = !empty($firstHeroTitle))
                {{-- Die Startseite bleibt ausgenommen: ihr Seitentitel ist ein
                     Verwaltungsname ("Startseite") und gehoert nicht auf die Seite. --}}
                @unless(is_front_page() || $hasTitledHero)
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
