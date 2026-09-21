# Design Tokens

Design Tokens definieren Farben, Abstände, Schriftgrößen und andere visuelle Eigenschaften als wiederverwendbare Variablen. Quelle ist seit der Design-System-Adoption `raaaf/rafael-design-system` (siehe [DESIGN.md](../DESIGN.md)), nicht mehr Figma direkt; Figma ist ein Empfänger, kein Herkunftsort.

## Übersicht

```
Design System (tokens/src) → theme-hub design-system → config/design-tokens/*.tokens.json → transform-tokens.js → tokens.css → TailwindCSS
```

## Dateien

| Datei                                         | Beschreibung                                                                               |
| --------------------------------------------- | ------------------------------------------------------------------------------------------ |
| `config/design-tokens/primitives.tokens.json` | Basis-Werte: Farben, Spacing, Radius, Typography, Border Width, Opacity, Sizing, Gradients |
| `config/design-tokens/light.tokens.json`      | Semantische Tokens für Light Mode                                                          |
| `config/design-tokens/dark.tokens.json`       | Semantische Tokens für Dark Mode                                                           |
| `scripts/transform-tokens.js`                 | Konvertiert JSON → CSS                                                                     |
| `resources/css/tokens.css`                    | Generierte CSS Custom Properties                                                           |

## Workflow

Die Kette der fünf Befehle (theme-hub, aus dem theme-hub-Repository heraus):

### 1. Design-System-Werte in den Export schreiben

```bash
theme-hub design-system --theme <pfad-zu-diesem-theme> --source <design-system>/tokens/src
```

Schreibt Farbe, Spacing, Radius, Typo-Skala, Gewichte, Motion und A11y aus dem Design System in `config/design-tokens/*.tokens.json`. Legt vorher ein Backup in `config/design-tokens/backups/` an.

### 2. CSS generieren

```bash
npm run tokens
```

Konvertiert den Export nach `resources/css/tokens.css` (und `tokens-editor.css`).

### 3. Redundante app.css-Korrekturen entfernen

```bash
theme-hub thin --from <pfad-zu-diesem-theme> --write
```

Entfernt `app.css`-Korrekturzeilen, die der Export inzwischen selbst richtig liefert, ohne das gerenderte Ergebnis zu verändern.

### 4. Kontrastvertrag prüfen

```bash
theme-hub check --from <pfad-zu-diesem-theme>
```

Misst jede Text-/Icon-/UI-Paarung in Hell, Dunkel und System-Dunkel gegen WCAG 1.4.3/1.4.11.

### 5. Figma-Importpaket erzeugen (optional, Figma ist Empfänger)

```bash
theme-hub figma --from <pfad-zu-diesem-theme> --out <verzeichnis>
```

Erzeugt ein Importpaket für Figma aus dem aktuellen Export — der umgekehrte Weg zum alten "Tokens aus Figma exportieren": Figma zeigt danach, was der Browser bereits rendert, statt umgekehrt.

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
  --color-gray-50: #f9fafb;
  --color-gray-100: #f3f4f6;
  --spacing-1: 4px;
  --spacing-2: 8px;
}
```

### Semantische Tokens (Light/Dark)

Semantische Tokens referenzieren Primitives via Figma-Alias-Daten. Der Transformer generiert `var()`-Referenzen statt aufgelöster Hex-Werte, sodass Änderungen an Primitives automatisch kaskadieren.

```css
:root,
[data-theme='light'] {
  --bg-primary: var(--color-white);
  --bg-secondary: var(--color-gray-50);
  --bg-brand: var(--color-accent-500);
  --text-primary: var(--color-gray-900);
}

[data-theme='dark'] {
  --bg-primary: var(--color-gray-900);
  --bg-secondary: var(--color-gray-800);
  --bg-brand: var(--color-accent-500);
  --text-primary: var(--color-white);
}
```

## Fluide Zeilenhöhen

`fluidLineHeight()` in `scripts/transform-tokens.js` berechnet die Zeilenhöhe für `display`, `h1`, `h2` und `h3` als `clamp()`, synchron mit der fluiden Schriftgröße derselben Stufe.

Der Wert ist als **Länge** (`px`, fluid zwischen `VIEWPORT_MIN` und `VIEWPORT_MAX`) ausgegeben, nicht als unitless Verhältnis (`1.1`, `1.5`). Grund: `calc(1.44 - 0.0125vw)` zieht eine Länge von einer unitless-Zahl ab, das ist ungültiges CSS und lässt die ganze Deklaration fallen — zwischen 2026-04-16 und der Reparatur rendete dadurch jede `display`/`h1`/`h2`/`h3`-Zeile mit der geerbten Body-Zeilenhöhe von 1.5 statt der beabsichtigten 1.1 bis 1.35. `fluidLineHeight()` skaliert stattdessen `mobileLh`/`desktopLh` mit den bekannten Pixel-Endpunkten der Schriftgröße und gibt `calc()` bereits in `px` zurück.

Details zur Herleitung: `.claude/plans/logs/2026-04-16-fluid-typography-scale.md`.

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
  background: var(--bg-primary);
  color: var(--text-primary);
  border-color: var(--border-default);
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
- `border-line-subtle` - Dezenter Rahmen
- `border-line-brand` - Markenfarbe

### Icons (`icon-*`)

- `icon` - Standard-Icon-Farbe
- `icon-secondary` - Gedämpft
- `icon-brand` - Markenfarbe

## Neu hinzugekommene Tokens

Tokens nur in `resources/css/app.css`, vom Figma-Export nicht geliefert (Begründung in `docs/DESIGN-TOKEN-GAPS.md` Abschnitt B):

- `--bg-brand-tint` - Ruhezustand-Füllung des Primary-Buttons, gleich `--bg-brand-subtle`
- `--surface-sheen` - 135°-Tuscheverlauf auf Cards und Panels (Hell-/Dunkelwert), über `.card` und die `surface-sheen`-Utility
- `--page-sheen` - 160°-Verlauf über der Seite, deckt die erste Bildschirmhöhe des Body ab
- `--noise-texture` - Noise-SVG im Body-Hintergrund
- `--color-icon-disabled` - Alias auf `--icon-disabled` im Tailwind-`@theme`-Block

Neue Primitives aus dem Figma-Export, generiert in `resources/css/tokens.css`:

- `--color-ash-lifted`, `--color-white-alpha-10`, `--color-black-alpha-8` - zusätzliche Grundfarben
- `--font-weight-light` (300) - zusätzliche Schriftstärke

## Dark Mode

Dark Mode wird automatisch unterstützt:

1. **System-Präferenz:** `prefers-color-scheme: dark`
2. **Manuell:** `data-theme="dark"` auf `<html>`

```blade
{{-- In header.blade.php --}}
<html data-theme="{{ get_field('color_scheme', 'option') ?: 'system' }}">
```

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
