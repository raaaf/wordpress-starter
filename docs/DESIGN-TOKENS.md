# Design Tokens

Design Tokens sind die Brücke zwischen Figma-Design und Code. Sie definieren Farben, Abstände, Schriftgrößen und andere visuelle Eigenschaften als wiederverwendbare Variablen.

## Übersicht

```
Figma Variables → JSON Export → transform-tokens.js → tokens.css → TailwindCSS
```

## Dateien

| Datei | Beschreibung |
|-------|--------------|
| `config/design-tokens/primitives.tokens.json` | Basis-Werte: Farben, Spacing, Radius, Typography |
| `config/design-tokens/light.tokens.json` | Semantische Tokens für Light Mode |
| `config/design-tokens/dark.tokens.json` | Semantische Tokens für Dark Mode |
| `config/design-tokens/brand.json` | Vereinfachte Brand-Konfiguration (Fallback) |
| `scripts/transform-tokens.js` | Konvertiert JSON → CSS |
| `resources/css/tokens.css` | Generierte CSS Custom Properties |

## Workflow

### 1. Tokens aus Figma exportieren

1. Öffne deine Figma-Datei
2. Gehe zu **Local Variables** (Rechtsklick → "Edit variables")
3. Klicke auf das **⚙️ Einstellungen-Icon** → **Export**
4. Wähle **JSON** als Format
5. Exportiere drei Dateien:
   - `primitives.tokens.json` - Collection mit Basis-Werten
   - `light.tokens.json` - Mode "Light"
   - `dark.tokens.json` - Mode "Dark"

### 2. Tokens ins Theme kopieren

```bash
# Kopiere die exportierten Dateien nach:
config/design-tokens/
```

### 3. CSS generieren

```bash
# Einmalig
npm run tokens

# Oder mit Watch-Mode während der Entwicklung
npm run tokens:watch
```

### 4. Ergebnis prüfen

Die generierten CSS Custom Properties findest du in `resources/css/tokens.css`.

## Token-Struktur

### Primitives (Basis-Werte)

```json
{
  "color": {
    "gray": {
      "50": { "$type": "color", "$value": { "hex": "#F9FAFB" } },
      "100": { "$type": "color", "$value": { "hex": "#F3F4F6" } }
    }
  },
  "spacing": {
    "1": { "$type": "number", "$value": 4 },
    "2": { "$type": "number", "$value": 8 }
  }
}
```

Wird zu:
```css
:root {
  --color-gray-50: #F9FAFB;
  --color-gray-100: #F3F4F6;
  --spacing-1: 4px;
  --spacing-2: 8px;
}
```

### Semantische Tokens (Light/Dark)

```json
{
  "bg": {
    "surface": { "$type": "color", "$value": { "hex": "#FFFFFF" } },
    "surface-secondary": { "$type": "color", "$value": { "hex": "#F9FAFB" } }
  },
  "text": {
    "content": { "$type": "color", "$value": { "hex": "#1F2937" } }
  }
}
```

Wird zu:
```css
:root, [data-theme="light"] {
  --bg-surface: #FFFFFF;
  --bg-surface-secondary: #F9FAFB;
  --text-content: #1F2937;
}

[data-theme="dark"] {
  --bg-surface: #111827;
  --bg-surface-secondary: #1F2937;
  --text-content: #F9FAFB;
}
```

## Verwendung in Templates

### Mit TailwindCSS (empfohlen)

```html
<div class="bg-surface text-content border-line">
  <h2 class="text-content-brand">Titel</h2>
  <p class="text-content-secondary">Beschreibung</p>
</div>
```

### Mit CSS Custom Properties

```css
.custom-element {
  background: var(--bg-surface);
  color: var(--text-content);
  border-color: var(--border-line);
}
```

## Verfügbare Tokens

### Hintergründe (`bg-*`)
- `bg-surface` - Standard-Hintergrund
- `bg-surface-secondary` - Sekundärer Hintergrund
- `bg-surface-tertiary` - Tertiärer Hintergrund
- `bg-surface-brand` - Markenfarbe
- `bg-surface-brand-subtle` - Dezente Markenfarbe
- `bg-surface-inverse` - Invertierter Hintergrund

### Text (`text-*`)
- `text-content` - Standard-Textfarbe
- `text-content-secondary` - Gedämpfter Text
- `text-content-tertiary` - Noch dezenter
- `text-content-brand` - Markenfarbe
- `text-content-inverse` - Auf dunklem Hintergrund
- `text-content-link` - Link-Farbe

### Rahmen (`border-*`)
- `border-line` - Standard-Rahmen
- `border-line-secondary` - Dezenter Rahmen
- `border-line-brand` - Markenfarbe

### Icons (`icon-*`)
- `icon-default` - Standard-Icon-Farbe
- `icon-secondary` - Gedämpft
- `icon-brand` - Markenfarbe

## Dark Mode

Dark Mode wird automatisch unterstützt:

1. **System-Präferenz:** `prefers-color-scheme: dark`
2. **Manuell:** `data-theme="dark"` auf `<html>`

```blade
{{-- In header.blade.php --}}
<html data-theme="{{ get_field('color_scheme', 'option') ?: 'system' }}">
```

## Ohne Figma (brand.json)

Falls du keine Figma-Tokens hast, kannst du `config/design-tokens/brand.json` direkt bearbeiten:

```json
{
  "colors": {
    "primary": { "value": "#0066CC" },
    "secondary": { "value": "#00A0D2" },
    "accent": { "value": "#FF6B35" }
  },
  "typography": {
    "fontFamily": {
      "sans": "Lato, ui-sans-serif, system-ui, sans-serif"
    }
  }
}
```

> **Hinweis:** Diese Datei wird aktuell nicht automatisch verarbeitet. Für volle Token-Unterstützung verwende die Figma-Export-Methode.

## Tipps

1. **Semantische Namen:** Verwende `bg-surface` statt `bg-gray-100`
2. **Keine Hardcoded Farben:** Immer Tokens verwenden für konsistentes Theming
3. **Dark Mode testen:** Prüfe alle Komponenten in beiden Modi
4. **Kontrast prüfen:** Stelle sicher, dass Text auf Hintergründen lesbar ist

## Troubleshooting

### Tokens werden nicht aktualisiert

```bash
# Cache leeren und neu generieren
rm resources/css/tokens.css
npm run tokens
```

### Farben stimmen nicht

Prüfe, ob die Figma-Export-Dateien das richtige Format haben. Der Transformer erwartet das native Figma Variables JSON-Format.

### TailwindCSS zeigt keine Änderungen

```bash
# Blade-Cache leeren
rm -rf compiled/*

# Seite neu laden
```
