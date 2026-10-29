# DESIGN.md

Herkunft und Regeln der visuellen Werte dieses Themes. Werte selbst stehen nicht hier: sie stehen in `resources/css/tokens.css` (generiert) und `config/design-tokens/*.tokens.json` (Export). Diese Datei kopiert keinen Wert, sie sagt, woher er kommt und warum er von der Design-System-Vorgabe abweicht, wo er abweicht.

## Herkunft der Werte

Seit der Design-System-Adoption ist `raaaf/rafael-design-system` (`tokens/src`) die Quelle, nicht mehr Figma. `theme-hub design-system` schreibt Farbe, Spacing, Radius, Typo-Skala, Gewichte, Motion und A11y aus dem Design System in `config/design-tokens/*.tokens.json`; `scripts/transform-tokens.js` erzeugt daraus `resources/css/tokens.css`. Figma ist seither ein Empfänger (`theme-hub figma`), kein Herkunftsort mehr.

**Stand 2026-10-29:** theme-hub ist archiviert. Der Abgleich mit dem Design System lief deshalb über die generierten Werte im Design-Kit (`raaaf/design-kit`, `Sources/DesignTokens`, erzeugt aus `rafael-design-system` am 2026-10-19) und wurde direkt in den Export geschrieben, inklusive der Alias-Ziele (`com.figma.aliasData`), die `transform-tokens.js` vor `$value` liest. Übernommen: neutraler Dark Mode (Grund #000, Stein #070707, Stein tief #0E0E0E, erhöht #141414, Haarlinien Weiß 7/10 %), Primär-Button in Tinte (hell #1C1917 mit weißem Label, dunkel #E8E8E6 mit Tinte), angekreuzte Controls wie der Kit-Toggle (Füllung = Primär-Button), Fokusring Tinte im Hellen und Signal-Orange im Dunkeln, `border-control` #857F7A / #626262, Motion-Kurven `enter`/`exit`, Zeilenhöhen und die Schriften (unten). Die Typo-Skala und die Layout-Werte (Container 68rem, fließende Sektionsabstände) kommen aus dem Scape-Umbau der Kundenthemes, weil das Design-Kit nur App-Größen kennt.

## Kette der vier Befehle

Ausgeführt aus dem theme-hub-Repository, mit `--theme`/`--from <pfad-zu-diesem-theme>`:

1. `theme-hub design-system --theme <theme> --source <design-system>/tokens/src` — schreibt die Design-System-Werte in den Export, mit Backup unter `config/design-tokens/backups/`.
2. `npm run tokens` — erzeugt `resources/css/tokens.css` und `tokens-editor.css` aus dem Export.
3. `theme-hub thin --from <theme> --write` — entfernt `app.css`-Korrekturen, die der Export inzwischen selbst richtig liefert.
4. `theme-hub check --from <theme>` — misst den Kontrastvertrag (WCAG 1.4.3/1.4.11) in Hell, Dunkel und System-Dunkel.

Optional, nur in Richtung Figma: Figma ist eingefroren (Stand committet, nicht gepflegt), Werte entstehen dort nie. Wer den Stand dennoch nach Figma spiegeln will, nutzt das lokale Figma-Plugin (`figma/plugin/` im theme-hub-Repository, holt den Export über `theme-hub serve`; Details in `figma/plugin/README.md`) oder den Datei-Weg `theme-hub figma --from <theme> --out <verzeichnis>`, der ein Importpaket erzeugt.

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
- Radius: `sm` = 6, `md` = 8, `lg` = 12, `full` = 999. Buttons sind Pillen (`radius.button` = 999 im Design System), Karten und Medien 12, Chips 8.
- `fontFamily`: `headline` = Colaborate (Light 300, Regular 400), `body` = Cal Sans Text (Light 300, Regular 400, Medium 500), neu `ui` = Cal Sans UI (Regular 400, SemiBold 600), `mono` bleibt JetBrains Mono. Rollen wie im Design-Kit: Display, h1 und h2 Colaborate Light; h3 bis h6 Cal Sans Text Medium (Section/Title); Fließtext und Lead Cal Sans Text Light, `strong` Text Medium; Buttons, Badges, Navigation, Labels, Overline und Caption Cal Sans UI, Button-Labels SemiBold. Die Rolle steht je Stufe als `--typography-<rolle>-family` in `tokens.css`. Schriftdateien: Latin- und Latin-Extended-A-Teilmengen der Design-Kit-Dateien (`pyftsubset`, woff2), Lizenzen in `resources/fonts/OFL-*.txt`, Liste in `config/fonts.json`.
- `fontSize` (Desktop / Mobil, fließend bis 1440 px): `xs` 13, `sm` 14, `base` 16, `lg` 18/17 (Fließtext), `xl` 20/19, `2xl` 22/20 (h3), `3xl` 24/21 (Lead), `4xl` 48/29 (h2), `5xl` 56/34 (h1), `6xl` 64/38 (Display).
- `fontWeight`: `regular` = 400, `medium` = 500, `light` = 300, `semibold`/`bold` = 600 (nur Cal Sans UI hat 600, Text-Rollen bleiben bei höchstens 500).
- Neu: `motion/ease/standard` (String, `cubic-bezier(...)`), `motion/duration/{fast,base,medium,slow,reveal}` (ms) → `--ease-standard`, `--dur-fast`…`--dur-reveal`.
- Neu: `a11y/tap-target`, `a11y/focus-ring/width`, `a11y/focus-ring/offset` (px) → `--a11y-tap-target`, `--a11y-focus-ring-width`, `--a11y-focus-ring-offset`.
- Neu: `lineHeight/{display,section,title,lead,body,small,micro}` (unitless) und `tracking/{default,heading,label}` (em/px-String) im Export. `transform-tokens.js` liest beide: Line-Heights `display`/`h1` = `lineHeight.display`, `h2` = `lineHeight.section`, `h3`/`h4` = `lineHeight.title`, `h5` = `lineHeight.lead`, `body` = `lineHeight.body`, `caption` = `lineHeight.micro`; Letter-Spacing `display`/`h1`/`h2`/`h3` = `tracking.heading` (−0.02em, enger als der Rest), `h4`/`h5`/Body-Rollen/`caption`/`code` = `tracking.default` (0px), `overline` = `tracking.label` (0.08em). Fehlt eine Gruppe im Export (älterer Export, Test-Fixtures), fällt der Transform auf die bisherigen Konstanten zurück. Seit 2026-10-29: `h2` = `lineHeight.section`, `h3`/`h4`/`h5` = `lineHeight.title`; der exportierte Wert gilt am Desktop, mobil bleibt der lockerere Tabellenwert, `h1` behält die Untergrenze 1.12. `--button-*-radius` wieder `var(--radius-full)` (Pillen wie im Design System).

## Dokumentierte Abweichungen

- **Spacing `0` und `0-5`:** bleiben, das Design System kennt sie nicht als eigene Stufe.
- **Radius `xl`/`2xl`/`3xl`:** bleiben unverändert, das Design System definiert nur `sm`/`md`/`lg`/`full`.
- **`fontSize` `5xl`/`6xl`:** bleiben unverändert, außerhalb der DS-Skala.
- **`fontWeight` `semibold`/`bold`:** bleiben bei 400 (der Starter kennt keine echte Fettschrift für die Headline-Schriftart; eine Anpassung ist eine Typografie-Entscheidung, kein Mapping-Fehler).
- **Checkbox-/Radio-Häkchen (`:checked`-SVG in app.css):** zwei Literale, weil ein data URI keine CSS-Custom-Property lesen kann: `#FFFFFF` im Hellen (Label des Primär-Buttons auf Tinte), `#1C1917` im Dunkeln (Tinte auf #E8E8E6). Bei einer Änderung von `button.primary-label` müssen beide von Hand nachgezogen werden.
- **Sekundär-Button:** eigene Rollen `--bg-button-secondary(-hover)` und `--bg-button-secondary-on-stone(-hover)` in app.css, weil das Design-Kit ihn im Hellen mit Stein, im Dunkeln mit der erhöhten Fläche füllt; die Starter-Rollen `bg-secondary`/`bg-tertiary` bilden diesen Wechsel nicht ab.
- **Schwarz-Weiß mit vollem Orange (Rafael, 2026-10-29):** Flächen sind in beiden Modi rein neutral, es gibt keine orange Abtönung mehr (kein Seitenverlauf, `bg-brand-subtle`/`bg-accent-subtle` sind Stein hell #F4F3F2 bzw. erhöht dunkel #141414, `text-brand` ist Tinte bzw. #EDEDED). Orange erscheint nur als volle Signal-Orange-Füllung mit Tinte darauf: Markenfläche `bg-brand` (CTA-Modul) in beiden Modi und die Icon-Kacheln in Karten und Gate-Karte (`--rafael-color-ds-accent-base` mit `--rafael-color-ds-accent-ink`, ca. 6,2:1). Dazu im Dunkeln der Fokusring und Akzenttext, in beiden Modi die Link-Unterstreichung.
- **Orange-Regel R2 (Design-Kit):** kein oranger Text auf heller Fläche. Links und der aktuelle Menüpunkt sind Textfarbe mit 2-px-Unterstreichung in Signal-Orange (`--rafael-color-ds-accent-base`), Overlines grau, `text-accent` im Hellen Tinte.

## Marken-Slots

Vier zusätzliche, optionale Brand-Slots in theme-hub's `generate`/`serve`-Konfigurator, angewendet nach der Akzentfarbe und vor dem Kontrastvertrag:

- **`--neutral <hex>`:** verschiebt den Farbton (Hue) der zehn neutralen DS-Primitiven (vier Flächen, vier Textstufen, zwei Ränder) auf den Zielton, Helligkeit und Sättigung jeder einzelnen Stufe bleiben unverändert. Bricht eine Paarung dadurch knapp, versucht das System, sie über eine kleine Helligkeitskorrektur derselben Stufe zu retten; bleibt keine Paarung >= Schwelle erreichbar, bricht der Lauf mit „Neutralton `<hex>` erreicht keinen Kontrast, anderen Ton wählen“ ab, statt eine schlecht lesbare Seite auszuliefern.
- **`--radius <tight|standard|soft>`:** skaliert `radius/sm`, `/md`, `/lg` mit Faktor 0.5/1/1.5, gerundet auf ganze Pixel. `full` und die größeren Stufen (`xl`/`2xl`/`3xl`) bleiben unverändert.
- **Headline-Zeilenhöhe und -Tracking (`--headline-line-height <n>`, 0.8–1.6; `--headline-tracking <em>`, -0.1em–0.1em):** setzt `lineHeight/display`, `/section`, `/title` und `tracking/heading` gemeinsam. Das Design System hat diese Werte auf seine eigene Display-Schrift (Colaborate) abgestimmt; nennt die Marke eine andere Headline-Schrift, sind beide Slots gemeinsam Pflicht (Sichtprüfung im Konfigurator anhand einer Live-Probe), sonst bleiben die DS-Werte unverändert stehen.

## DS-Regeln

- **Ein Primaer-Knopf pro Preistabelle:** in `pricing-table.blade.php` traegt nur das hervorgehobene Paket (`is_featured`) einen primaeren Button, alle anderen sekundaer. Ohne hervorgehobenes Paket uebernimmt das erste Paket diese Rolle. Grund: die DS-Identitaetsregel, ein Primaer-Element pro Ansicht.
- **Ring bei deaktiviertem Primaer-Knopf auf der Karte im Dunkelmodus:** `[data-theme='dark'] .card .button--primary[aria-disabled='true']` (und `.button-primary` fuer die zweite Button-Klasse) traegt zusaetzlich `box-shadow: inset 0 0 0 1px var(--border-default)`, sonst verschwimmt die deaktivierte Fuellung mit der Kartenflaeche (components.md, Controls, Primary button, Disabled state).

## Verweis

Werte stehen in `resources/css/tokens.css` (generiert) und `config/design-tokens/*.tokens.json` (Export). Nie hier kopieren — sie veralten beim nächsten Lauf, ohne dass es hier auffällt.
