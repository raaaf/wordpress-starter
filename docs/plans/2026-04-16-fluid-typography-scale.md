# Fluid Typography Scale

## Problem

Font-sizes skalieren aktuell nicht fluid: alle `--font-size-*` Primitives in `resources/css/tokens.css` sind statische rem-Werte (Figma-Export aus `primitives.tokens.json`). Headlines wie Display (60px) und H1 (36px) brechen auf 375px-Smartphones zu gross und unharmonisch um. Betrifft Starter + alle 5 Client-Themes, weil sie dieselbe Token-Pipeline teilen.

## Ziel

Jede Headline-Groesse skaliert fluid zwischen einer level-abhaengigen Mobile-Untergrenze und dem Desktop-Zielwert, den Figma vorgibt. Gemessen:

- Bei Viewport 320px: Display = 38px (statt 60), H1 = 28px (statt 36), H2 = 24px (statt 30)
- Bei Viewport >= 1920px: unveraendert Figma-Werte
- Body (16px) und darunter: konstant (Lesbarkeit)
- Zero Changes im Figma-Workflow: Designer pflegt weiter nur Desktop-Werte

## Nicht-Ziele

- Figma-Modes (mobile/desktop als zwei separate Variable-Sets) — zu viel Designer-Overhead
- Neue Token-Ebenen oder Umbenennung bestehender Tokens
- Aenderungen an Blade-Templates oder TailwindCSS-Config — laufen ueber die CSS-Variablen
- Anpassung der Scale-Ratio (bleibt ~Major Third)
- Shared-Package fuer `transform-tokens.js` (eigener Scope, separater Plan — siehe Follow-up)

## Loesung

### Ansatz

`scripts/transform-tokens.js` wird um eine `fluidClamp()`-Funktion erweitert. Beim Schreiben der `--font-size-*` Primitives in `tokens.css` generiert das Script automatisch eine `clamp(min, fluid, max)`-Formel statt eines statischen rem-Werts.

Min/Max-Werte kommen aus einer expliziten Lookup-Table `FLUID_SIZES` (nicht Faktor-basiert, sondern absolut in Pixeln — klarer beim Code-Lesen):

```js
const FLUID_SIZES = {
  xs: { min: 12, max: 12 },
  sm: { min: 14, max: 14 },
  base: { min: 16, max: 16 },
  lg: { min: 17, max: 18 },
  xl: { min: 18, max: 20 },
  '2xl': { min: 20, max: 24 },
  '3xl': { min: 24, max: 30 },
  '4xl': { min: 28, max: 36 },
  '5xl': { min: 32, max: 48 },
  '6xl': { min: 38, max: 60 },
};
```

Ergibt strikt monotone Hierarchie auf allen Viewports:

| Level | Desktop (1920+) | Mobile (320) | Gap vs. Level darunter |
| ----- | --------------- | ------------ | ---------------------- |
| xs    | 12              | 12           | -                      |
| sm    | 14              | 14           | 2                      |
| base  | 16              | 16           | 2                      |
| lg    | 18              | 17           | 1                      |
| xl    | 20              | 18           | 1                      |
| 2xl   | 24              | 20           | 2                      |
| 3xl   | 30              | 24           | 4                      |
| 4xl   | 36              | 28           | 4                      |
| 5xl   | 48              | 32           | 4                      |
| 6xl   | 60              | 38           | 6                      |

Der `max`-Wert wird beim Build-Run aus `primitives.tokens.json` ueberschrieben, falls Figma dort abweicht. Die Lookup-Table definiert nur die **Mobile-Min-Werte**; Max bleibt ledlich Figma-authoritativ.

Interpolation linear zwischen Viewport **320px und 1920px** (grosse Desktops/Office-Monitore abgedeckt).

```js
function fluidClamp(minPx, maxPx, minVw = 320, maxVw = 1920, rootPx = 16) {
  if (minPx === maxPx) return `${(minPx / rootPx).toFixed(4)}rem`;
  const vwCoef = (100 * (maxPx - minPx)) / (maxVw - minVw);
  const remIntercept = (minPx - (vwCoef / 100) * minVw) / rootPx;
  return `clamp(${(minPx / rootPx).toFixed(4)}rem, calc(${remIntercept.toFixed(4)}rem + ${vwCoef.toFixed(4)}vw), ${(maxPx / rootPx).toFixed(4)}rem)`;
}
```

Beispiel 6xl (Min 38, Max 60):

```
clamp(2.375rem, calc(2.1rem + 1.375vw), 3.75rem)
```

Verifikation: bei Viewport 320 liefert `2.1rem + 1.375 * 3.2 = 33.6 + 4.4 = 38px`; bei 1920 liefert `2.1rem + 1.375 * 19.2 = 33.6 + 26.4 = 60px`.

### Line-Height skaliert fuer grosse Headlines mit

Enge Line-Heights (1.1-1.2) funktionieren auf Desktop, aber schneiden Absteiger deutscher Compound-Woerter auf 320px ab. Fluid line-height fuer Composite-Tokens, deren Font-Size-Gap (max−min) >= 4px ist — kleinere Gaps rechtfertigen keine Line-Height-Anpassung:

| Composite | Font-Size Gap | Line-Height Mobile → Desktop |
| --------- | ------------- | ---------------------------- |
| display   | 38 → 60 (22)  | 1.5 → 1.1                    |
| h1        | 28 → 36 (8)   | 1.4 → 1.2                    |
| h2        | 24 → 30 (6)   | 1.35 → 1.25                  |
| h3        | 20 → 24 (4)   | 1.4 → 1.3                    |
| h4        | 18 → 20 (2)   | **statisch 1.4**             |
| h5        | 17 → 18 (1)   | **statisch 1.4**             |
| body-\*   | ≤ 1           | **statisch 1.5 / 1.6**       |

Implementierung identisch zur font-size: `fluidClamp()` mit den line-height-Zielwerten statt Pixeln, aber Output als unitless Zahl (CSS erlaubt `line-height: clamp(1.2, calc(...), 1.5)` ohne Einheit).

Konkrete Werte werden im `transform-tokens.js` in der Section "Composite Tokens" (Zeile ca. 175-250) gesetzt.

### validateAndFix absichern (MUSS vor `npm run tokens` fertig sein)

Die existierende `validateAndFix`-Funktion bearbeitet in Zeile ~389 Font-Size-Werte per `fixedCss.replace(match[0], ...)`. Wenn das Script unveraendert auf clamp()-Werte losgelassen wird, wird der ganze Token-Output zerstoert (alle 6 Themes gleichzeitig). Fix ist zweigeteilt:

1. **Regex um negativen Lookahead erweitern**, damit `clamp(`-Werte niemals matchen:
   ```js
   // vorher:
   const fontSizeRegex = /--font-size-\w+:\s*(\d+)px;/g;
   // nachher:
   const fontSizeRegex = /--font-size-\w+:\s*(?!clamp)(\d+)px;/g;
   ```
2. **Replace robuster**: statt `fixedCss.replace(match[0], …)` die Offset-basierte Substitution via `match.index + match[0].length`. Behebt den latenten Substring-Bug dauerhaft (relevant auch fuer zukuenftige token-transformationen).

Diese Aenderung ist die erste Implementierungsaktion (Schritt 1 unten), damit beim ersten `npm run tokens`-Lauf keine zerstoerten Werte in `tokens.css` landen.

### Schritte

1. **validateAndFix absichern** (Zeile ~389): negativer Lookahead im Regex + Offset-basierter Replace (Snippet oben). Bevor irgendetwas anderes am Script geaendert wird — sonst zerstoert der naechste `npm run tokens`-Lauf die neuen clamp()-Werte sofort.
2. **Fluid-Helper in `scripts/transform-tokens.js`**: `fluidClamp()` + `FLUID_SIZES` + Konstanten `VIEWPORT_MIN=320`, `VIEWPORT_MAX=1920`, `ROOT_PX=16`
3. **Token-Generator anpassen** (Zeile ~462): `--font-size-*` Ausgabe ueber `fluidClamp(FLUID_SIZES[key].min, primitives.fontSize[key])`. Fallback auf statisch, falls ein Key nicht in `FLUID_SIZES` steht.
4. **Composite-Tokens mit Line-Height-clamp** fuer `display`, `h1`, `h2`, `h3` (Zeile ~175-200). `h4`, `h5` und body-\* bleiben statisch (Font-Size-Gap ≤ 2px — Line-Height-Anpassung optisch irrelevant).
5. **Baseline-Screenshots**: vor Rollout Screenshots von SIERA-Homepage auf 320/375/768/1280/1920 (Chrome DevTools oder Playwright). Ablage in `docs/plans/typography-baseline/`
6. **Tokens regenerieren**: `npm run tokens` fuer Starter-Theme
7. **Editor-Kontext pruefen**: Gutenberg ist per CLAUDE.md deaktiviert, stattdessen ACF Flexible Content. Pruefen:
   - `resources/css/tokens-editor.css` (wird parallel generiert) — fluid-Werte drin oder statische? Falls fluid: OK, weil ACF-Preview im echten Viewport laeuft, nicht im iFrame.
   - ACF-Extended-Layout-Preview in der Admin-UI visuell abgleichen (kein Vollbild-Editor, sondern ACF-Modal) — clamp() sollte normal skalieren.
   - Falls Abweichung: statische `--font-size-*`-Overrides in `editor-style.css` nur fuer den `admin-body`-Scope als Fallback.
8. **Styleguide-Page pruefen**: Styleguide-Template im Browser auf 320/768/1280/1920
9. **Vitest-Test** fuer `fluidClamp()`: `tests/js/transform-tokens.test.ts` mit Boundary-Cases (min===max, ordinary interpolation, Viewport-Overflow). Grund: `tokens.css` landet auf 6 Live-Sites — eine Formel-Regression faehrt alle gleichzeitig gegen die Wand.
10. **Starter-Theme commit + push**: semantic-release erzeugt Minor-Version, Theme-Updater zeigt Update im WP-Dashboard
11. **Post-Deploy: Cache-Flush dokumentieren**: im Update-Release-Notes explizit hinzufuegen, dass nach dem WP-Update Autoptimize + WP-Rocket Cache geleert werden muessen (CSS-Cache-Invalidation haengt nicht am File-Mtime)
12. **Propagation auf 5 Client-Themes**: identischer Patch auf `transform-tokens.js` + `npm run tokens` + commit + push. Parallel-Agents wie bei GEO-Foundations — identischer Code, nur Wiederholungsarbeit
13. **Post-Rollout-Check**: SIERA-Vergleich Baseline-Screenshots vs. Live bei 320/375/768/1280/1920

### Betroffene Dateien

- `scripts/transform-tokens.js` — fluidClamp + FLUID_SIZES + Composite-Line-Heights + validateAndFix-Fix (~80 Zeilen)
- `resources/css/tokens.css` — regeneriert, `--font-size-*` und line-heights fuer display/h1/h2/h3 sind clamp()
- `resources/css/tokens-editor.css` — parallel generiert mit denselben Werten (sofern Editor-Check clean durchlaeuft)
- `tests/js/transform-tokens.test.ts` — NEU: Unit-Tests fuer fluidClamp
- `docs/plans/typography-baseline/` — NEU: Before/After-Screenshots fuer SIERA
- Pro Client-Theme: gleicher Satz (transform-tokens.js, tokens.css)

**Nicht angefasst (sofern Editor-Check OK):**

- `theme.json`, `resources/css/app.css`
- `resources/css/editor-style.css` — nur als Fallback-Target, falls der Editor-Kontext in Schritt 7 eine abweichende Darstellung zeigt
- Blade-Templates
- `config/design-tokens/*.tokens.json` (Figma-Werte unveraendert)
- `scripts/package-theme.js` (out of scope)

## Edge Cases

- **Nutzer ueberschreibt Browser-Schriftgroesse** (z.B. 20px Root): clamp-Min/Max in rem skalieren mit; vw-Anteil bleibt absolut. Akzeptabel.
- **Viewport < 320px** (Foldables gefaltet): clamp-Min greift, Schrift bleibt stabil.
- **Viewport > 1920px** (4K-Office-Monitor): clamp-Max greift. Abdeckung verdoppelt gegenueber v1 (1280px).
- **ACF-Preview / Editor-Kontext**: Gutenberg ist deaktiviert, ACF Flexible Content laeuft im echten Frontend-Kontext (Preview-Link). `tokens-editor.css` wird parallel generiert — wenn dort dieselben clamp()-Werte landen, passt's. Falls ein Spezial-iFrame-Kontext im Admin abweichend rendert: statische Overrides in `editor-style.css` im `admin-body`-Scope.
- **Cache-Plugins** (Autoptimize/WP-Rocket): nicht auto-invalidated beim Theme-Update; Schritt 11 dokumentiert manuellen Flush.
- **Hierarchie-Inversion auf Mobile**: Tabelle oben zeigt strikt monotone Groessen auf allen Viewports — verifiziert.

## Rollback-Plan

1. WordPress-Dashboard → Theme-Updates → voriges Release reinstallieren (Theme-Updater mu-plugin Downgrade)
2. Optional: `git revert <commit>` + `npm run tokens` + re-release (cleaner Path)
3. Cache-Flush (Autoptimize, WP-Rocket, Browser)
4. Fuer akute Regressionen: lokale `tokens.css` via SFTP restoren (File ist committed, Hot-Patch geht)

## Follow-ups (separate Plans)

- **Shared-Package fuer `transform-tokens.js`**: 6 identische Kopien sind Divergenz-Zeitbombe. Optionen: (a) NPM-Workspace mit `@rafaelalex/theme-tokens`, (b) Symlink auf Shared-Dir, (c) CI-Hash-Check als Minimum-Schutz. Eigener Plan, separate Entscheidung.

## Offene Fragen

Keine.
