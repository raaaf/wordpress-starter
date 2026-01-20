# Blade-Komponenten Entwicklung

Diese Anleitung erklärt, wie du neue Blade-Komponenten erstellst und verwendest.

## Was sind Blade-Komponenten?

Blade-Komponenten sind wiederverwendbare UI-Bausteine, die du in Templates und Blöcken nutzen kannst. Sie werden mit `<x-name>` aufgerufen.

```blade
{{-- Verwendung einer Komponente --}}
<x-button url="/kontakt" variant="primary">Kontakt</x-button>
```

## Schritt 1: Komponenten-Datei erstellen

Erstelle eine neue Datei in `templates/components/`:

```
templates/components/alert.blade.php
```

## Schritt 2: Komponente implementieren

Grundstruktur einer Komponente:

```blade
{{--
    Alert Component

    Zeigt eine Hinweismeldung in verschiedenen Varianten an.

    @props
    - variant: string (info|success|warning|error) - Farbvariante
    - title: string|null - Optionaler Titel
    - dismissible: bool - Kann geschlossen werden

    @example
    <x-alert variant="success" title="Erfolg!">
        Deine Nachricht wurde gesendet.
    </x-alert>
--}}

@props([
    'variant' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    $variantClasses = match($variant) {
        'success' => 'bg-green-50 border-green-500 text-green-800',
        'warning' => 'bg-yellow-50 border-yellow-500 text-yellow-800',
        'error' => 'bg-red-50 border-red-500 text-red-800',
        default => 'bg-blue-50 border-blue-500 text-blue-800',
    };
@endphp

<div
    {{ $attributes->merge(['class' => "p-4 border-l-4 rounded-r-lg {$variantClasses}"]) }}
    @if($dismissible) x-data="{ open: true }" x-show="open" @endif
>
    <div class="flex items-start gap-3">
        <div class="flex-1">
            @if($title)
                <h4 class="font-semibold mb-1">{{ $title }}</h4>
            @endif
            <div>{{ $slot }}</div>
        </div>
        @if($dismissible)
            <button @click="open = false" class="text-current opacity-50 hover:opacity-100">
                &times;
            </button>
        @endif
    </div>
</div>
```

## Schritt 3: Komponente registrieren

Öffne `src/Providers/BladeServiceProvider.php` und füge die Komponente zum Array hinzu:

```php
$components = [
    // ... bestehende Komponenten ...
    'components.alert' => 'alert',
];
```

## Schritt 4: Komponente verwenden

Jetzt kannst du die Komponente überall nutzen:

```blade
{{-- Einfache Verwendung --}}
<x-alert>Dies ist eine Info-Meldung.</x-alert>

{{-- Mit Variante --}}
<x-alert variant="success">Erfolgreich gespeichert!</x-alert>

{{-- Mit Titel --}}
<x-alert variant="warning" title="Achtung">
    Bitte prüfe deine Eingaben.
</x-alert>

{{-- Schließbar --}}
<x-alert variant="error" :dismissible="true">
    Ein Fehler ist aufgetreten.
</x-alert>

{{-- Mit zusätzlichen Attributen --}}
<x-alert variant="info" class="mb-4" id="my-alert">
    Zusätzliche Klassen werden gemergt.
</x-alert>
```

## Props vs. Slots

### Props
Props sind benannte Attribute, die du der Komponente übergibst:

```blade
@props([
    'variant' => 'primary',  // mit Standardwert
    'size' => 'md',
    'disabled' => false,
])
```

Verwendung:
```blade
<x-button variant="secondary" size="lg" :disabled="true">
    Klick mich
</x-button>
```

### Slots
Slots sind Inhaltsbereiche:

```blade
{{-- Standard-Slot --}}
{{ $slot }}

{{-- Benannte Slots --}}
{{ $header ?? '' }}
{{ $footer ?? '' }}
```

Verwendung:
```blade
<x-card>
    <x-slot:header>
        <h3>Kartentitel</h3>
    </x-slot:header>

    Karteninhalt hier...

    <x-slot:footer>
        <x-button>Aktion</x-button>
    </x-slot:footer>
</x-card>
```

## Attribute Merging

Mit `$attributes` kannst du zusätzliche Attribute akzeptieren:

```blade
{{-- Klassen werden gemergt --}}
<div {{ $attributes->merge(['class' => 'base-class']) }}>
    {{ $slot }}
</div>
```

```blade
{{-- Bei Verwendung --}}
<x-box class="extra-class">  {{-- Ergebnis: class="base-class extra-class" --}}
```

## Bestehende Komponenten

Das Theme enthält bereits diese Komponenten:

### Layout-Komponenten

**`<x-section>`** - Abschnitt mit Hintergrundfarbe
```blade
<x-section background="secondary" padding="lg">
    Inhalt
</x-section>
```

**`<x-grid>`** - CSS-Grid Container
```blade
<x-grid cols="3" gap="6">
    <div>Spalte 1</div>
    <div>Spalte 2</div>
    <div>Spalte 3</div>
</x-grid>
```

**`<x-prose>`** - Typografie für WYSIWYG-Inhalte
```blade
<x-prose>
    {!! $content !!}
</x-prose>
```

### UI-Komponenten

**`<x-button>`** - Button in verschiedenen Varianten
```blade
<x-button url="/kontakt" variant="primary" size="lg">
    Kontakt aufnehmen
</x-button>
```

Varianten: `primary`, `secondary`, `ghost`, `danger`
Größen: `sm`, `md`, `lg`

**`<x-card>`** - Karten-Container
```blade
<x-card variant="elevated">
    Karteninhalt
</x-card>
```

**`<x-badge>`** - Kleine Labels
```blade
<x-badge variant="success">Neu</x-badge>
```

**`<x-link>`** - Gestylte Links
```blade
<x-link url="/mehr" variant="underline">Mehr erfahren</x-link>
```

### Form-Komponenten

**`<x-input>`** - Textfeld
```blade
<x-input name="email" type="email" label="E-Mail" required />
```

**`<x-textarea>`** - Mehrzeiliges Textfeld
```blade
<x-textarea name="message" label="Nachricht" rows="5" />
```

**`<x-select>`** - Dropdown
```blade
<x-select name="country" label="Land" :options="$countries" />
```

**`<x-checkbox>`** / **`<x-radio>`** - Auswahl
```blade
<x-checkbox name="terms" label="AGB akzeptieren" />
```

**`<x-toggle>`** - Toggle-Switch
```blade
<x-toggle name="newsletter" label="Newsletter abonnieren" />
```

### Weitere Komponenten

**`<x-icon>`** - Icon-Komponente
```blade
<x-icon name="check" class="w-5 h-5" />
```

## Design-Token verwenden

Verwende die semantischen CSS-Klassen aus dem Design-System:

### Hintergründe
- `bg-surface` - Standard
- `bg-surface-secondary` - Sekundär (grau)
- `bg-surface-tertiary` - Tertiär
- `bg-surface-brand` - Markenfarbe
- `bg-surface-inverse` - Dunkel

### Textfarben
- `text-content` - Standard
- `text-content-secondary` - Gedämpft
- `text-content-brand` - Markenfarbe
- `text-content-inverse` - Auf dunklem Hintergrund

### Linien
- `border-line` - Standard-Rahmen
- `ring-line-focus` - Fokus-Ring

## Tipps

1. **Dokumentation:** Schreibe immer einen Kommentar-Header mit Props und Beispiel
2. **Standardwerte:** Gib sinnvolle Defaults für alle Props
3. **Attribute Merging:** Nutze `$attributes->merge()` für Flexibilität
4. **Design-Token:** Verwende die semantischen Klassen statt fester Farben
5. **Barrierefreiheit:** Füge ARIA-Attribute hinzu wo nötig

## Beispiel: Icon-Button Komponente

```blade
{{--
    Icon Button Component

    Button mit Icon und optionalem Text.

    @props
    - icon: string - Icon-Name
    - label: string|null - Sichtbarer Text (optional, für Screenreader)
    - variant: string - Button-Variante

    @example
    <x-icon-button icon="trash" label="Löschen" variant="danger" />
--}}

@props([
    'icon',
    'label' => null,
    'variant' => 'ghost',
])

<x-button :variant="$variant" {{ $attributes }}>
    <x-icon :name="$icon" class="w-5 h-5" />
    @if($label)
        <span class="{{ $slot->isEmpty() ? 'sr-only' : '' }}">{{ $label }}</span>
    @endif
    {{ $slot }}
</x-button>
```
