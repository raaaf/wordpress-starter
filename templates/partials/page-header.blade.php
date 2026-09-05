{{--
    Shared page header for page.blade.php and page-member-area.blade.php.

    Only show page header if a hero layout actually carries a title (title is in Hero block).
    Der Seitenkopf entfaellt nur, wenn ein Hero-Layout tatsaechlich einen
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
    bei einem Leerraum-Titel voneinander ab).
--}}
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
{{-- Der Kopf uebernimmt das Mass der ersten Sektion. one_column setzt
     seinen Inhalt in max-w-2xl mittig; eine h1 ueber die volle
     Containerbreite stand dann weit links neben ihrem eigenen Text.
     Alle anderen Layouts nutzen die volle Breite und bekommen den Kopf
     wie bisher. --}}
@php($firstLayout = $sectionLayouts[0] ?? null)
@php($headerMeasure = $firstLayout === 'one_column' ? 'max-w-2xl mx-auto' : '')
@unless(is_front_page() || $hasTitledHero)
    <header class="page-header max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="{{ $headerMeasure }}">
            <h1>{{ get_the_title() }}</h1>
        </div>
    </header>
@endunless
