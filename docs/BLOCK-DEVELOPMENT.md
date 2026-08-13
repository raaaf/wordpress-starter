# Flexible Content Layout Entwicklung

Diese Anleitung erklärt Schritt für Schritt, wie du ein neues ACF Flexible Content Layout erstellst.

Gutenberg ist deaktiviert. Es gibt keine ACF-Blocks (kein `blocks/`-Verzeichnis, kein `block.json`, kein `acf_register_block_type`). Der Page-Builder basiert ausschließlich auf ACF Flexible Content: ein Layout ist eine PHP-Methode in `FlexibleContent.php`, eine Feld-Definition in `FieldDefinitions.php` und ein Blade-Template in `templates/flexible/`.

## Layout-Struktur

Ein Layout besteht aus drei Teilen:

```
src/Acf/FlexibleContent.php        # Layout-Definition (key, name, label, sub_fields, category)
src/Acf/FieldDefinitions.php       # Feld-Definitionen für das Layout
templates/flexible/mein-layout.blade.php  # Template
```

Alle Layouts werden zentral in `FlexibleContent::getLayouts()` registriert und stehen im Feld `page_sections` (Flexible Content, `group_page_builder`) zur Verfügung.

## Schritt 1: Felder in FieldDefinitions.php definieren

Öffne `src/Acf/FieldDefinitions.php` und füge eine neue Methode hinzu, die ein Array von ACF-Sub-Fields zurückgibt. Nutze dafür die vorhandenen Feld-Helper:

| Methode                  | Beschreibung                                                                                                            |
| ------------------------ | ----------------------------------------------------------------------------------------------------------------------- |
| `textField()`            | Einzeiliges Textfeld                                                                                                    |
| `textareaField()`        | Mehrzeiliges Textfeld                                                                                                   |
| `wysiwygField()`         | WYSIWYG-Editor                                                                                                          |
| `imageField()`           | Bildauswahl                                                                                                             |
| `linkField()`            | Link mit URL, Text, Target                                                                                              |
| `urlField()`             | URL-Feld                                                                                                                |
| `selectField()`          | Dropdown-Auswahl                                                                                                        |
| `colorPickerField()`     | Farbauswahl                                                                                                             |
| `buttonGroupField()`     | Button-Gruppe (Auswahl)                                                                                                 |
| `iconRadioField()`       | Icon-Auswahl (Radio)                                                                                                    |
| `fileField()`            | Datei-Upload                                                                                                            |
| `trueFalseField()`       | Ja/Nein Toggle                                                                                                          |
| `numberField()`          | Zahlenfeld                                                                                                              |
| `radioField()`           | Radio-Auswahl                                                                                                           |
| `checkboxField()`        | Checkbox-Liste                                                                                                          |
| `rangeField()`           | Zahlen-Slider                                                                                                           |
| `repeaterField()`        | Wiederholbare Felder                                                                                                    |
| `postObjectField()`      | Auswahl eines Beitrags/einer Seite                                                                                      |
| `backgroundColorField()` | Standard-Hintergrundfarbe (`background_color`)                                                                          |
| `sectionHeaderFields()`  | Standard-Header (`show_section_header`, `section_chip`, `section_headline`, `section_description`, `section_alignment`) |
| `sectionAnchorField()`   | Anker-ID-Override (`section_anchor`)                                                                                    |

Fast jedes Layout endet mit `backgroundColorField($prefix)` und `sectionAnchorField($prefix)`, damit Hintergrundfarbe und manuelle Sprungmarken-IDs konsistent verfügbar sind. Layouts mit optionaler Überschrift/Beschreibung nutzen zusätzlich `sectionHeaderFields($prefix)`.

Beispiel (angelehnt an `statsFields()`):

```php
public static function myNewFields(string $prefix): array
{
    return [
        self::textField(
            "field_{$prefix}_title",
            __('Überschrift', 'wp-starter'),
            'title',
            false,
            __('Optionale Überschrift.', 'wp-starter'),
            __('z.B. Willkommen', 'wp-starter'),
        ),
        self::repeaterField(
            "field_{$prefix}_items",
            __('Einträge', 'wp-starter'),
            'items',
            [
                self::textField(
                    "field_{$prefix}_item_label",
                    __('Beschriftung', 'wp-starter'),
                    'label',
                    true,
                ),
            ],
            __('Eintrag hinzufügen', 'wp-starter'),
            1,
            'table',
        ),
        self::backgroundColorField($prefix),
        self::sectionAnchorField($prefix),
    ];
}
```

## Schritt 2: Layout in FlexibleContent.php registrieren

Öffne `src/Acf/FlexibleContent.php` und füge eine private static Methode hinzu, die `key`, `name`, `label`, `sub_fields` und `acfe_flexible_category` zurückgibt:

```php
/**
 * My New layout
 *
 * @return array<string, mixed>
 */
private static function myNewLayout(): array
{
    return [
        'key' => 'layout_my_new',
        'name' => 'my_new',
        'label' => __('Mein neues Layout', 'wp-starter'),
        'display' => 'block',
        'sub_fields' => FieldDefinitions::myNewFields('flex_my_new'),
        'acfe_flexible_category' => self::getCategories()['content'],
        'acfe_flexible_thumbnail' => 'my_new.png',
    ];
}
```

`acfe_flexible_thumbnail` ist bei allen Layouts gesetzt und referenziert ein PNG in `resources/images/layouts/`. Die Grafiken sind schematisch (Flaeche fuer Bild, Balken fuer Ueberschrift, duenne Linien fuer Text, Pille fuer Button) und werden nicht von Hand gezeichnet, sondern generiert: neues Layout in der `LAYOUTS`-Konstante in `scripts/generate-layout-thumbnails.php` mit denselben Bausteinen beschreiben, dann `php scripts/generate-layout-thumbnails.php` laufen lassen. Das Script schreibt das passende PNG nach `resources/images/layouts/`.

Trage die neue Methode danach in `getLayouts()` ein (Liste der Layout-Aufrufe, sortiert nach Kategorie-Kommentaren):

```php
private static function getLayouts(): array
{
    return [
        // ...
        self::myNewLayout(),
        // ...
    ];
}
```

### Kategorien (`getCategories()`)

`acfe_flexible_category` ordnet das Layout einer Kategorie im ACF-Extended-Auswahl-Modal zu. Verfügbare Kategorien (`self::getCategories()[...]`):

| Key           | Label            |
| ------------- | ---------------- |
| `header`      | Header           |
| `layout`      | Layout           |
| `content`     | Inhalte          |
| `media`       | Medien           |
| `interactive` | Interaktiv       |
| `forms`       | Formulare        |
| `posts`       | Beiträge         |
| `internal`    | Interner Bereich |
| `misc`        | Sonstiges        |

## Schritt 3: Template erstellen

Erstelle `templates/flexible/my-new.blade.php` (kebab-case, `name` mit `_` durch `-` ersetzt). Layouts lesen ihre Felder direkt über die native ACF-Funktion `get_sub_field()`, nicht über die `@field`-Blade-Direktiven (die sind für einzelne Felder außerhalb der Flexible-Content-Schleife gedacht). Die Variable `$sectionAnchor` steht automatisch zur Verfügung, sie wird pro Zeile in `templates/page.blade.php` aus `section_anchor` oder einem generierten Fallback (`{layout}-{n}`) berechnet.

```blade
{{--
    My New - Flexible Content Layout

    Uses shared components: x-section
    Fields: title, items (repeater: label), background_color
--}}

@php
    $title = get_sub_field('title');
    $items = get_sub_field('items') ?: [];
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

<x-section :anchor="$sectionAnchor" :background="$background" class="my-new">
    @if($title)
        <h2 class="text-h2 mb-6 text-content">{{ $title }}</h2>
    @endif

    @if(!empty($items))
        <ul>
            @foreach($items as $item)
                <li>{{ $item['label'] ?? '' }}</li>
            @endforeach
        </ul>
    @endif
</x-section>
```

Escaping: `{{ }}` für Text (auto-escaped), `{!! !!}` nur für vertrauenswürdigen HTML-Inhalt (z.B. WYSIWYG-Felder), `@kses($content)` als Blade-Direktive für `wp_kses_post()`.

## Schritt 4: Testen

1. Speichere alle Dateien
2. Leere den Blade-Cache bei Problemen: `rm -rf compiled/*`
3. Öffne den WordPress-Editor auf einer Seite, füge eine neue Sektion hinzu
4. Suche das neue Layout im Modal (ggf. über die zugewiesene Kategorie)
5. Prüfe das Layout im Editor UND im Frontend
6. Seede jeden Wert jedes neuen Auswahlfeldes in `src/Content/StyleguideLayoutData.php`: `tests/Unit/Content/StyleguideVariantCoverageTest.php` erzwingt maschinell, dass jede Auswahlmöglichkeit im Styleguide vorkommt, sonst wird `composer test` rot. Neue Choice und Seed gehören in denselben Commit.

## Tipps

1. **Immer Empty States:** Zeige hilfreiche Meldungen oder verstecke Ausgaben, wenn Felder leer sind (`@if($title) ... @endif`).
2. **Deutsche Texte:** Alle Labels und Instructions in `FieldDefinitions.php` nutzen `__('...', 'wp-starter')`.
3. **Komponenten nutzen:** `<x-section>`, `<x-button>`, `<x-prose>` etc. statt raues HTML, siehe `docs/COMPONENT-DEVELOPMENT.md`.
4. **Hintergrundfarben:** `primary`, `secondary`, `tertiary`, `brand`, `brand-subtle`, `inverse` (siehe `FieldDefinitions::getBackgroundColors()`).
5. **Layout-Anzahl:** Aktuell 32 Layouts in `templates/flexible/`, gepflegt in `FlexibleContent::getLayouts()`.
