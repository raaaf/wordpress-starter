# ACF Block Entwicklung

Diese Anleitung erklärt Schritt für Schritt, wie du einen neuen ACF-Block erstellst.

## Block-Struktur

Jeder Block besteht aus mindestens zwei Dateien:

```
blocks/
└── mein-block/
    ├── block.json      # Block-Definition
    └── template.blade.php  # Template
```

## Schritt 1: Block-Ordner erstellen

Erstelle einen neuen Ordner in `blocks/` mit dem Block-Namen (kebab-case):

```bash
mkdir blocks/mein-block
```

## Schritt 2: block.json erstellen

Erstelle `blocks/mein-block/block.json`:

```json
{
    "name": "mein-block",
    "title": "Mein Block",
    "description": "Beschreibung was der Block macht",
    "category": "theme",
    "icon": "admin-post",
    "keywords": ["mein", "block", "beispiel"],
    "supports": {
        "align": ["full", "wide"],
        "mode": true,
        "jsx": true,
        "anchor": true
    }
}
```

### Wichtige Felder

| Feld | Beschreibung |
|------|--------------|
| `name` | Eindeutiger Name (ohne `acf/` Prefix) |
| `title` | Anzeigename im Editor (Deutsch) |
| `description` | Kurze Beschreibung (Deutsch) |
| `category` | `theme` für Theme-Blöcke |
| `icon` | [Dashicon-Name](https://developer.wordpress.org/resource/dashicons/) |
| `keywords` | Suchbegriffe für Block-Einfüger |

### Verfügbare Icons

Häufig verwendete Icons:
- `admin-post` - Beitrag
- `format-image` - Bild
- `columns` - Spalten
- `list-view` - Liste
- `megaphone` - Aufforderung
- `video-alt3` - Video
- `groups` - Team

## Schritt 3: Felder in FieldDefinitions.php definieren

Öffne `src/Acf/FieldDefinitions.php` und füge eine neue Methode hinzu:

```php
/**
 * Mein Block Fields
 */
public static function meinBlockFields(string $prefix): array
{
    return [
        self::textField(
            "field_{$prefix}_title",
            'Überschrift',
            'title',
            true,  // required
            'Die Hauptüberschrift des Blocks.',
            'z.B. Willkommen'
        ),
        self::wysiwygField(
            "field_{$prefix}_content",
            'Inhalt',
            'content',
            false,  // not required
            null,
            'Der Haupttext des Blocks.'
        ),
        self::imageField(
            "field_{$prefix}_image",
            'Bild',
            'image',
            false,
            'array',
            null,
            'Optionales Bild.'
        ),
        self::backgroundColorField($prefix),
    ];
}
```

### Verfügbare Feld-Methoden

| Methode | Beschreibung |
|---------|--------------|
| `textField()` | Einzeiliges Textfeld |
| `textareaField()` | Mehrzeiliges Textfeld |
| `wysiwygField()` | WYSIWYG-Editor |
| `imageField()` | Bildauswahl |
| `linkField()` | Link mit URL, Text, Target |
| `selectField()` | Dropdown-Auswahl |
| `trueFalseField()` | Ja/Nein Toggle |
| `numberField()` | Zahlenfeld |
| `urlField()` | URL-Feld |
| `repeaterField()` | Wiederholbare Felder |
| `backgroundColorField()` | Standard-Hintergrundfarbe |

## Schritt 4: Block in BlockFields.php registrieren

Öffne `src/Acf/BlockFields.php` und füge den Block zur `register()`-Methode hinzu:

```php
public static function register(): void
{
    // ... andere Blöcke ...

    self::registerBlockFields('mein-block', FieldDefinitions::meinBlockFields('mein_block'));
}
```

## Schritt 5: Template erstellen

Erstelle `blocks/mein-block/template.blade.php`:

```blade
{{--
    Mein Block

    Uses shared components: x-section
    Fields: title, content, image, background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $content = $fields['content'] ?? '';
    $image = $fields['image'] ?? null;
    $background = $fields['background_color'] ?? 'primary';
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} mein-block">
    @if($title)
        <h2 class="text-h2 mb-6 text-content">{{ $title }}</h2>
    @endif

    @if($content)
        <x-prose>
            {!! $content !!}
        </x-prose>
    @endif

    @if($image)
        <img
            src="{{ esc_url($image['url']) }}"
            alt="{{ esc_attr($image['alt']) }}"
            class="mt-8 rounded-lg"
        >
    @endif
</x-section>
```

### Template-Variablen

Diese Variablen stehen automatisch zur Verfügung:

| Variable | Beschreibung |
|----------|--------------|
| `$fields` | Array mit allen Feldwerten |
| `$anchor` | Block-Anker (für Sprungmarken) |
| `$classes` | Zusätzliche CSS-Klassen |
| `$is_preview` | `true` wenn im Editor |

### Wichtige Komponenten

- `<x-section>` - Wrapper mit Hintergrundfarbe und Padding
- `<x-prose>` - Für WYSIWYG-Inhalte mit Typografie-Stilen
- `<x-button>` - Buttons in verschiedenen Varianten
- `<x-grid>` - CSS-Grid Container

## Schritt 6: Block testen

1. Speichere alle Dateien
2. Leere den Cache: `rm -rf compiled/*`
3. Öffne den WordPress-Editor
4. Suche nach deinem Block unter "Theme Blocks"

## Beispiel: Kompletter Block mit Repeater

### block.json

```json
{
    "name": "features",
    "title": "Features",
    "description": "Liste von Features mit Icons",
    "category": "theme",
    "icon": "star-filled",
    "keywords": ["features", "liste", "vorteile"]
}
```

### FieldDefinitions.php

```php
public static function featuresFields(string $prefix): array
{
    return [
        self::textField(
            "field_{$prefix}_title",
            'Überschrift',
            'title',
            false,
            'Optionale Überschrift über den Features.'
        ),
        self::repeaterField(
            "field_{$prefix}_items",
            'Features',
            'items',
            [
                self::textField(
                    "field_{$prefix}_item_icon",
                    'Icon (Emoji)',
                    'icon',
                    false,
                    'Emoji als Icon. Mac: Ctrl+Cmd+Leertaste',
                    'z.B. ✓, ⭐, 🚀'
                ),
                self::textField(
                    "field_{$prefix}_item_title",
                    'Titel',
                    'title',
                    true,
                    'Feature-Titel'
                ),
                self::textareaField(
                    "field_{$prefix}_item_text",
                    'Beschreibung',
                    'text',
                    false,
                    2,
                    'Kurze Beschreibung'
                ),
            ],
            'Feature hinzufügen',
            1,
            'block'
        ),
        self::backgroundColorField($prefix),
    ];
}
```

### template.blade.php

```blade
@php
    $title = $fields['title'] ?? '';
    $items = $fields['items'] ?? [];
    $background = $fields['background_color'] ?? 'primary';
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }}">
    @if($title)
        <h2 class="text-h2 mb-8 text-center text-content">{{ $title }}</h2>
    @endif

    @if(!empty($items))
        <div class="grid gap-6 md:grid-cols-3">
            @foreach($items as $item)
                <div class="p-6 rounded-lg bg-surface-secondary">
                    @if($item['icon'] ?? false)
                        <span class="text-3xl">{{ $item['icon'] }}</span>
                    @endif
                    @if($item['title'] ?? false)
                        <h3 class="text-h4 mt-4 text-content">{{ $item['title'] }}</h3>
                    @endif
                    @if($item['text'] ?? false)
                        <p class="mt-2 text-content-secondary">{{ $item['text'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="p-8 text-center rounded-lg bg-surface-secondary">
            <p class="text-content-secondary">Bitte füge mindestens ein Feature hinzu.</p>
        </div>
    @endif
</x-section>
```

## Plugin-Abhängigkeiten

Falls dein Block ein Plugin benötigt, füge `requires` zur block.json hinzu:

```json
{
    "name": "mein-form-block",
    "requires": ["contact-form-7"]
}
```

Verfügbare Requirement-Keys:
- `contact-form-7`
- `woocommerce`
- `class:ClassName` - Prüft ob Klasse existiert
- `function:function_name` - Prüft ob Funktion existiert

## Tipps

1. **Immer Empty States:** Zeige hilfreiche Meldungen wenn Felder leer sind
2. **Escaping:** Verwende `{{ }}` für Text, `{!! !!}` nur für vertrauenswürdigen HTML
3. **Komponenten nutzen:** Verwende `<x-section>`, `<x-button>` etc.
4. **Deutsche Texte:** Alle Labels und Instructions auf Deutsch
5. **Testen:** Prüfe Block im Editor UND im Frontend
