# DESIGN.md

Herkunft und Regeln der visuellen Werte dieses Themes. Werte selbst stehen nicht hier: sie stehen in `resources/css/tokens.css` (generiert) und `config/design-tokens/*.tokens.json` (Export). Diese Datei kopiert keinen Wert, sie sagt, woher er kommt und warum er von der Design-System-Vorgabe abweicht, wo er abweicht.

## Herkunft der Werte

Seit der Design-System-Adoption ist `raaaf/rafael-design-system` (`tokens/src`) die Quelle, nicht mehr Figma. `theme-hub design-system` schreibt Farbe, Spacing, Radius, Typo-Skala, Gewichte, Motion und A11y aus dem Design System in `config/design-tokens/*.tokens.json`; `scripts/transform-tokens.js` erzeugt daraus `resources/css/tokens.css`. Figma ist seither ein Empfänger (`theme-hub figma`), kein Herkunftsort mehr.

## Kette der fünf Befehle

Ausgeführt aus dem theme-hub-Repository, mit `--theme`/`--from <pfad-zu-diesem-theme>`:

1. `theme-hub design-system --theme <theme> --source <design-system>/tokens/src` — schreibt die Design-System-Werte in den Export, mit Backup unter `config/design-tokens/backups/`.
2. `npm run tokens` — erzeugt `resources/css/tokens.css` und `tokens-editor.css` aus dem Export.
3. `theme-hub thin --from <theme> --write` — entfernt `app.css`-Korrekturen, die der Export inzwischen selbst richtig liefert.
4. `theme-hub check --from <theme>` — misst den Kontrastvertrag (WCAG 1.4.3/1.4.11) in Hell, Dunkel und System-Dunkel.
5. `theme-hub figma --from <theme> --out <verzeichnis>` — optional: Importpaket für Figma aus dem aktuellen Export.

Details je Befehl: [docs/DESIGN-TOKENS.md](docs/DESIGN-TOKENS.md).

## Mapping-Tabelle: Starter-Rolle → DS-Token

### Farben

| Starter-Rolle(n)                                                                                        | DS-Token                                                                                                                       |
| ------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| `bg-primary`                                                                                            | `surface.ground`                                                                                                               |
| `bg-secondary`                                                                                          | `surface.elevated`                                                                                                             |
| `bg-tertiary`                                                                                           | `surface.stone`                                                                                                                |
| `bg-tertiary-elevated`                                                                                  | `surface.stone-deep`                                                                                                           |
| `text-primary`, `icon-primary`                                                                          | `text.primary`                                                                                                                 |
| `text-secondary`, `icon-secondary`                                                                      | `text.secondary`                                                                                                               |
| `text-tertiary`, `text-placeholder`                                                                     | `text.tertiary`                                                                                                                |
| `text-disabled`, `icon-disabled`                                                                        | `text.muted`                                                                                                                   |
| `border-subtle`                                                                                         | `border.subtle`                                                                                                                |
| `border-default`, `border-control`                                                                      | `border.default`                                                                                                               |
| `text-link`, `text-accent`, `border-focus`, `ring-focus`, `icon-accent`, `border-accent`                | `accent.text`                                                                                                                  |
| `text-on-brand`, `text-on-accent`                                                                       | `accent.ink`                                                                                                                   |
| `icon-on-accent`                                                                                        | `accent.ink` (eigene Rolle, siehe unten)                                                                                       |
| `bg-accent`                                                                                             | `accent.text` (siehe unten)                                                                                                    |
| `bg-accent-hover`                                                                                       | eine Stufe kräftiger als `accent.text` je Modus: hell → `accent.deep`, dunkel → `accent.base`                                  |
| `bg-success`, `border-success`, `icon-success` (Border/Icon), `text-success`/`icon-success` (Text/Icon) | `semantic.available` (Border), `semantic.available-text` (Text/Icon), `semantic.available` 16-%-Abtönung (Fläche, siehe unten) |
| entsprechend `warning`, `error`                                                                         | `semantic.warning`/`semantic.warning-text`, `semantic.danger`/`semantic.danger-text`, jeweils mit 16-%-Abtönung für die Fläche |
| `bg-button-primary`                                                                                     | `button.primary-fill`                                                                                                          |
| `bg-button-primary-hover`                                                                               | `button.primary-fill-hover`                                                                                                    |
| `text-button-primary`                                                                                   | `button.primary-label`                                                                                                         |

Unverändert: `bg-brand`, `bg-brand-hover`, `bg-brand-active`, `*-brand-secondary-*` (Marken-Slot, siehe unten), `*-strong`, `inverse`, `on-color`, `bg-disabled`/`border-disabled`, `border-strong`, `bg-accent-subtle`, `bg-brand-subtle`, `bg-overlay`, `text-link-hover`, Gradients, Opacity, `borderWidth`.

**`bg-accent` und `icon-on-accent`:** `bg-accent` ist die Füllung angekreuzter Controls (Checkbox, Radio, Toggle) und damit ein bedeutungstragender Zustand, keine Dekoration — das Design System legt solche Zustände auf `accent-text`, nicht auf das rohe `accent` (rohes Orange erreicht nur ~2,7:1 auf hellem Grund, unter der 3:1-Schwelle für Nicht-Text-Elemente). Das Häkchen/der Punkt auf dieser Fläche braucht eine eigene Rolle statt `text-on-accent`: `text-on-accent` erscheint an anderer Stelle noch als echter Text (z. B. `label on a brand fill`), und eine Paarung bei der 3:1-UI-Schwelle hätte eine zu schwache Textfarbe verdecken können. Deshalb `icon-on-accent`, eine eigene, mode-lose Rolle auf `accent.ink`, ausschließlich für Nicht-Text-Markierungen auf `bg-accent`.

**Status-Flächen sind Abtönungen, keine Volltonflächen:** `bg-success`/`bg-warning`/`bg-error` sind getönte Badge-Flächen (Designsystem, Abschnitt „Destructive confirmation“: „`<status>`-text auf einer `<status>`-16-%-Abtönung, nie eine Volltonfläche“). Die Abtönung wird pro Modus als `round(basis * 0.16 + grund * 0.84)` je Kanal berechnet, `grund` = `surface.ground` desselben Modus, `basis` = die Statusfarbe desselben Modus. Diese Werte sind abgeleitet, nicht in Figma gepflegt, und werden bei jedem `design-system`-Lauf neu berechnet.

### Nicht-Farben

- Spacing: DS-Skala in Starter-Namen (1, 1-5, 2, 2-5, 3, 3-5, 4, 5, 6, 8, 10, 12, 14, 16, 20, 24, 28, 32).
- Radius: `sm` = 6, `md` = 8, `lg` = 12, `full` = 999.
- `fontFamily`: `headline` = Colaborate, `body` = Inter, `mono` bleibt JetBrains Mono.
- `fontSize`: `xs` = 13, `sm` = 14, `base` = 16, `lg` = 18, `xl` = 20, `2xl` = 24, `3xl` = 36, `4xl` = 48.
- `fontWeight`: `regular` = 400, `medium` = 500, `light` = 300.
- Neu: `motion/ease/standard` (String, `cubic-bezier(...)`), `motion/duration/{fast,base,medium,slow,reveal}` (ms) → `--ease-standard`, `--dur-fast`…`--dur-reveal`.
- Neu: `a11y/tap-target`, `a11y/focus-ring/width`, `a11y/focus-ring/offset` (px) → `--a11y-tap-target`, `--a11y-focus-ring-width`, `--a11y-focus-ring-offset`.
- Neu: `lineHeight/{display,section,title,lead,body,small,micro}` (unitless) und `tracking/{default,heading,label}` (em/px-String) im Export. `transform-tokens.js` liest beide: Line-Heights `display`/`h1` = `lineHeight.display`, `h2` = `lineHeight.section`, `h3`/`h4` = `lineHeight.title`, `h5` = `lineHeight.lead`, `body` = `lineHeight.body`, `caption` = `lineHeight.micro`; Letter-Spacing `display`/`h1`/`h2`/`h3` = `tracking.heading` (−0.02em, enger als der Rest), `h4`/`h5`/Body-Rollen/`caption`/`code` = `tracking.default` (0px), `overline` = `tracking.label` (0.08em). Fehlt eine Gruppe im Export (älterer Export, Test-Fixtures), fällt der Transform auf die bisherigen Konstanten zurück. `--button-*-radius` von `var(--radius-full)` auf `var(--radius-md)`.

## Dokumentierte Abweichungen

- **Spacing `0` und `0-5`:** bleiben, das Design System kennt sie nicht als eigene Stufe.
- **Radius `xl`/`2xl`/`3xl`:** bleiben unverändert, das Design System definiert nur `sm`/`md`/`lg`/`full`.
- **`fontSize` `5xl`/`6xl`:** bleiben unverändert, außerhalb der DS-Skala.
- **`fontWeight` `semibold`/`bold`:** bleiben bei 400 (der Starter kennt keine echte Fettschrift für die Headline-Schriftart; eine Anpassung ist eine Typografie-Entscheidung, kein Mapping-Fehler).
- **Checkbox-/Radio-Häkchen (`:checked`-SVG in app.css):** trägt `#1C1917` literal, weil ein data URI keine CSS-Custom-Property lesen kann. Wert = `accent.ink`, in beiden Modi identisch, deshalb unkritisch als Literal. Bei einer Änderung von `accent.ink` muss dieser Wert von Hand nachgezogen werden.

## Marken-Slots

Vier zusätzliche, optionale Brand-Slots in theme-hub's `generate`/`serve`-Konfigurator, angewendet nach der Akzentfarbe und vor dem Kontrastvertrag:

- **`--neutral <hex>`:** verschiebt den Farbton (Hue) der zehn neutralen DS-Primitiven (vier Flächen, vier Textstufen, zwei Ränder) auf den Zielton, Helligkeit und Sättigung jeder einzelnen Stufe bleiben unverändert. Bricht eine Paarung dadurch knapp, versucht das System, sie über eine kleine Helligkeitskorrektur derselben Stufe zu retten; bleibt keine Paarung >= Schwelle erreichbar, bricht der Lauf mit „Neutralton `<hex>` erreicht keinen Kontrast, anderen Ton wählen“ ab, statt eine schlecht lesbare Seite auszuliefern.
- **`--radius <tight|standard|soft>`:** skaliert `radius/sm`, `/md`, `/lg` mit Faktor 0.5/1/1.5, gerundet auf ganze Pixel. `full` und die größeren Stufen (`xl`/`2xl`/`3xl`) bleiben unverändert.
- **Headline-Zeilenhöhe und -Tracking (`--headline-line-height <n>`, 0.8–1.6; `--headline-tracking <em>`, -0.1em–0.1em):** setzt `lineHeight/display`, `/section`, `/title` und `tracking/heading` gemeinsam. Das Design System hat diese Werte auf seine eigene Display-Schrift (Colaborate) abgestimmt; nennt die Marke eine andere Headline-Schrift, sind beide Slots gemeinsam Pflicht (Sichtprüfung im Konfigurator anhand einer Live-Probe), sonst bleiben die DS-Werte unverändert stehen.

## Verweis

Werte stehen in `resources/css/tokens.css` (generiert) und `config/design-tokens/*.tokens.json` (Export). Nie hier kopieren — sie veralten beim nächsten Lauf, ohne dass es hier auffällt.
