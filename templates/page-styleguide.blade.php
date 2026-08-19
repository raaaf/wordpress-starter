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

    Die Galerie laeuft in zwei Durchgaengen. Der erste durchlaeuft die Zeilen wie
    bisher und faengt das HTML jeder Instanz auf; der zweite gibt es gruppiert
    nach Layout aus, mit einem Schalter je Variante. Grund: der Varianten-Katalog
    liegt in den ACF-Daten hinter der Uebersicht, die dritte `cards`-Instanz steht
    also 40 Sektionen unter der ersten. Zusammengefasst schrumpft die Seite von
    81 sichtbaren Sektionen auf 31 Module, und ein Vergleich zweier Varianten
    passt in einen Bildschirm statt in zwei Scrollminuten.

    Nicht per Nachladen: die Instanzen sind ohnehin echte ACF-Zeilen derselben
    Seite. Ein Endpoint muesste den Schleifenkontext nachbauen, den der erste
    Durchgang gratis hat, und `?variants=all` waere dann ein zweiter Renderpfad
    statt derselben Seite ohne Ausblenden.
--}}

@extends('layouts.app')

@section('content')
    <article class="page page-styleguide">
        @if(post_password_required())
            @include('partials.password-form')
        @else
            <header class="page-header max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-wrap items-center justify-between gap-4">
                <h1 class="m-0">{{ get_the_title() }}</h1>
                @include('partials.theme-switcher')
            </header>

            {{--
                Zwei Ansichten statt einer Seite mit 31.842px: Tokens und
                Komponenten schlaegt man nach, Module vergleicht man. Der
                Parameter entscheidet serverseitig, was ueberhaupt gerendert
                wird, nicht bloss was sichtbar ist.
            --}}
            @php($ansicht = sanitize_key(wp_unslash($_GET['ansicht'] ?? '')) === 'design-system' ? 'design-system' : 'module')

            @include('partials.styleguide-views', ['ansicht' => $ansicht])

            @if($ansicht === 'design-system')
                @include('styleguide.tokens')
                @include('styleguide.components')
            @endif

            @if($ansicht === 'module' && have_rows('page_sections'))
                @include('partials.styleguide-nav')

                <x-section anchor="layouts" background="primary" padding="lg">
                    <x-section-header
                        chip="Flexible Content"
                        headline="Layouts"
                        description="Jedes Layout unten wird von seinem eigenen Template aus ACF-Daten gerendert, genau wie auf einer Kundenseite."
                    />
                </x-section>

                {{--
                    Vollansicht fuer Werkzeuge: Kontrastscanner, Screenshot-Diffs
                    und Lighthouse ueberspringen alles, was `display: none` traegt.
                    Ohne diesen Schalter wuerde der Umschalter die alte Blindheit
                    der Endlosliste durch eine neue ersetzen.
                --}}
                @php($alleVarianten = sanitize_key(wp_unslash($_GET['variants'] ?? '')) === 'all')

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
                {{--
                    Module labels come from the registered field group at runtime, not
                    from a second hand-maintained list, so they can never drift from
                    what FlexibleContent.php registers.
                --}}
                @php($sectionsField = get_field_object('page_sections'))
                @php($layoutLabels = array_column($sectionsField['layouts'] ?? [], 'label', 'name'))
                @php($layoutSubFields = array_column($sectionsField['layouts'] ?? [], 'sub_fields', 'name'))

                @php($module = [])
                @php($reihenfolge = [])

                {{-- Durchgang 1: rendern und einsammeln. --}}
                @while(have_rows('page_sections'))
                    @php(the_row())
                    @php($layout = get_row_layout())
                    @php($layoutCounters[$layout] = ($layoutCounters[$layout] ?? 0) + 1)
                    @php($anchorCounters[$layout] = ($anchorCounters[$layout] ?? 0) + 1)
                    @php($customAnchor = get_sub_field('section_anchor'))
                    @php($sectionSpacing = get_sub_field('section_spacing') ?: null)
                    @php($sectionAnchor = $customAnchor ?: str_replace('_', '-', $layout) . '-' . $anchorCounters[$layout])

                    @php(ob_start())
                    @includeIf('flexible.' . str_replace('_', '-', $layout))
                    @php($instanzHtml = ob_get_clean())

                    {{--
                        The intermission "one_column" rows (e.g. "Flexible Content
                        Layouts", "Layout & Text") only exist to carry an <h2> between
                        groups of real layouts -- StyleguideLayoutData builds them with
                        an empty 'label' and the heading baked into 'content'. Labelling
                        them as "Eine Spalte" would be noise, so they're detected by
                        that leading <h2> and skipped.
                    --}}
                    @php($isIntermissionHeading = $layout === 'one_column' && preg_match('/^\s*<h2[\s>]/i', (string) get_sub_field('content')) === 1)

                    @if($isIntermissionHeading || !isset($layoutLabels[$layout]))
                        @php($reihenfolge[] = ['typ' => 'zwischentitel', 'html' => $instanzHtml])
                    @else
                        @if(!isset($module[$layout]))
                            @php($module[$layout] = ['label' => $layoutLabels[$layout], 'felder' => \WordpressStarter\Content\StyleguideVariantLabels::choiceFields($layoutSubFields[$layout] ?? []), 'instanzen' => [], 'werte' => []])
                            @php($reihenfolge[] = ['typ' => 'modul', 'layout' => $layout])
                        @endif

                        @php($werte = [])
                        @foreach(array_keys($module[$layout]['felder']) as $feldName)
                            @php($werte[$feldName] = get_sub_field($feldName))
                        @endforeach

                        @php($module[$layout]['werte'][] = $werte)
                        @php($module[$layout]['instanzen'][] = ['anchor' => $sectionAnchor, 'html' => $instanzHtml])
                    @endif
                @endwhile

                {{--
                    Ein Zwischentitel, auf den kein neues Modul mehr folgt, ist nach
                    dem Zusammenfassen sinnlos geworden: "Varianten-Katalog" leitete
                    frueher die Katalog-Instanzen ein, die jetzt in ihren Modulen
                    weiter oben stecken. Datengetrieben erkannt statt am Titel, damit
                    ein spaeter umbenannter Abschnitt nicht als leere Ueberschrift
                    stehen bleibt.
                --}}
                @php($gefiltert = [])
                @foreach($reihenfolge as $i => $eintrag)
                    @if($eintrag['typ'] === 'modul' || ($reihenfolge[$i + 1]['typ'] ?? '') === 'modul')
                        @php($gefiltert[] = $eintrag)
                    @endif
                @endforeach

                {{-- Durchgang 2: ausgeben. --}}
                @foreach($gefiltert as $eintrag)
                    @if($eintrag['typ'] === 'zwischentitel')
                        {!! $eintrag['html'] !!}
                    @else
                        @php($layout = $eintrag['layout'])
                        @php($m = $module[$layout])
                        {{-- Zustaende bleiben aus der Merkmalsableitung heraus. Sonst
                             hiesse jeder Schalter nach dem Feld, in dem der Randfall
                             zufaellig abweicht, statt nach dem, was das Modul
                             unterscheidet. --}}
                        @php($varianteninfo = \WordpressStarter\Content\StyleguideVariantLabels::forInstances($m['instanzen'], $m['werte'], $m['felder']))
                        @php($varianten = $varianteninfo['labels'])
                        @php($variantenTitel = $varianteninfo['tooltips'])
                        @php($istZustand = $varianteninfo['istZustand'])
                        @include('partials.styleguide-module', ['layout' => $layout, 'modul' => $m, 'varianten' => $varianten, 'variantenTitel' => $variantenTitel, 'istZustand' => $istZustand, 'alleVarianten' => $alleVarianten])
                    @endif
                @endforeach
            @endif
        @endif
    </article>
@endsection
