# Design-Token-Lücken

`resources/css/tokens.css` wird aus Figma generiert und deshalb von niemandem gelesen. Diese Datei hält fest, was der Export nicht liefert und was `resources/css/app.css` stattdessen kompensiert.

**Ziel: diese Liste schrumpft.** Jeder Eintrag gehört nach Figma, nicht ins Theme. Solange er hier steht, ist er ein Workaround.

Abgesichert ist das durch `resources/js/design-tokens.test.ts`. Der Test läuft in der CI und wird rot, sobald ein Export eine Lücke reisst.

## A. Referenzen ins Leere

Figma exportiert `var(--x)`, exportiert `--x` aber nicht. Ohne Fallback fällt die ganze Deklaration aus.

| Token                                                             | wird gebraucht von                                       | Wirkung ohne Fix                                           |
| ----------------------------------------------------------------- | -------------------------------------------------------- | ---------------------------------------------------------- |
| `--spacing-1` (4px)                                               | `--badge-sm-gap`, `--badge-md-padding-y`                 | Badges vertikal ohne Innenabstand                          |
| `--spacing-2` (8px)                                               | `--button-md-gap`, `--badge-lg-gap`                      | Icon klebt am Beschriftungstext                            |
| `--color-error-alpha-50`                                          | `--shadow-focus-ring-error`                              | greift auf einen Inline-Wert zurück                        |
| `--shadow-color-sm` / `-default` / `-md` / `-lg` / `-xl` / `-2xl` | `--shadow-sm` bis `--shadow-2xl` im Tailwind-Theme-Block | jede blanke `shadow-*`-Utility rendert gar keinen Schatten |

Die Spacing-Skala definiert `0-5, 1-5, 2-5, 3, 4, 5, 6, 8, 10, 12, 16, 20, 24`, überspringt also ausgerechnet die beiden geradesten Stufen ihrer eigenen 4px-Logik.

Die Schatten-Lücke stand ausserhalb des Vertrags, weil der Vollständigkeitstest `app.css` nur bis zum Tailwind-Theme-Block gelesen hat und die kaputten Referenzen genau darin liegen. Der Test deckt den Block jetzt mit ab. Die Werte folgen der `rgba(23, 23, 23, a)`-Systematik, die die Komponenten-Schatten in `tokens.css` schon nutzen.

## B. Rollen, die es in Figma nicht gibt

Für diese Aufgaben existiert keine Variable. Sie sind im Theme erfunden und sollten in Figma als Rolle angelegt werden.

| Token                | wofür                         | warum nicht vorhandene nutzen                                                                                                                                       |
| -------------------- | ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `--text-placeholder` | Platzhalter in Eingabefeldern | `--text-tertiary` erreicht je nach Palette nur 2,2 bis 3,1:1, `--text-secondary` liegt in einer Palette zu nah an der Textfarbe                                     |
| `--border-control`   | Rand von Formularfeldern      | `--border-default` erreicht 1,2 bis 1,5:1, WCAG 1.4.11 verlangt 3:1 für Bedienelement-Begrenzungen                                                                  |
| `--ring-focus`       | Fokusring                     | der vorhandene Ring nutzt 50 Prozent Deckkraft und wäscht auf etwa 1,8:1 aus                                                                                        |
| `--text-on-brand`    | Text auf `--bg-brand`         | `--text-inverse` folgt der Seite, nicht der Markenfläche: im Hellmodus Weiss auf Accent-500 = 2,84:1, das geerbte Fast-Schwarz erreicht auf demselben Orange 6,32:1 |

Beide Kontrastwerte sind rollenabhängig: Platzhalter und Text brauchen 4,5:1, Rand und Fokusring 3:1.

## C. Falsche Werte

Der Token existiert, sein Wert taugt nicht.

| Token                                                               | Problem                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| ------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `--text-success` / `--text-warning` / `--text-error` im Dunkelmodus | zeigen auf den mittleren Ton der Rampe, während `--bg-*` auf den dunklen zeigt. Zwei ähnliche Helligkeiten übereinander, 1,7 bis 3,0:1. Richtig ist der helle Ton.                                                                                                                                                                                                                                                                                      |
| `--text-link` / `--text-brand` / `--text-accent` (Hellmodus)        | zeigen auf accent-600: 3,74:1 auf Weiss, 3,58:1 auf der Sekundärfläche, 3,46:1 im gefüllten Accent-Badge, alle unter 4,5:1. Der Ruhezustand war zudem schwächer als der eigene Hover (schon accent-700). Ein Schritt weiter auf der Rampe (accent-700) erreicht überall die Grenze (5,23 / 5,01 / 4,84:1), Hover rückt auf accent-800. Dunkelmodus unverändert: accent-400 auf gray-900 = 7,74:1.                                                       |
| `--gradient-primary-*`                                              | einzige Fläche im Theme, die nicht mit dem Farbschema kippt. Hell nutzt das dunkle Rampenende unter weissem Text (5,23:1 in Ruhe, 7,39 / 10,36:1 beim Drücken), Dunkel das helle Rampenende unter Fast-Schwarz (4,80:1 in Ruhe, 6,32 / 7,74:1). Beide Leitern bewegen sich vom Text weg, nicht darauf zu.                                                                                                                                               |
| `--bg-accent`                                                       | gecheckte Füllung von Checkbox/Radio/Toggle stand auf accent-500 = 2,84:1 auf Weiss, der weisse Haken darauf ebenso 2,84:1. Hell wechselt zu accent-700 (5,23:1 beidseitig), Dunkel bleibt accent-500 (6,32:1 auf der dunklen Fläche).                                                                                                                                                                                                                  |
| `--bg-error-strong` / `--bg-success-strong` / `--bg-warning-strong` | zeigten auf den mittleren Rampenton: 3,76:1 (error), 2,28:1 (success), 2,15:1 (warning) unter weissem Text. Hell wechselt auf den dunklen Ton (5,02 bis 6,47:1), Dunkel bleibt auf dem mittleren Ton, wo `--text-inverse` fast schwarz ist und 4,76 bis 8,35:1 erreicht.                                                                                                                                                                                |
| `--icon-secondary`, `--icon-brand`, `--icon-accent`, Status-Icons   | `--icon-secondary` war gray-400 (2,52:1 auf Weiss), trägt aber Select-Chevron und Eingabefeld-Löschsymbol. Die Status-Icons zeigten auf den mittleren Rampenton, sassen aber auf der hellen Fläche desselben Tons (2,07 bis 3,08:1); sie übernehmen jetzt den Ton, den die jeweilige `--text-*`-Rolle schon nutzt. `--icon-brand`/`--icon-accent` sowie die Markenränder (Spinner-Bogen, Card-Hover-Kante) folgen den Accent-Textrollen auf accent-700. |
| `--border-focus`                                                    | blieb nach der Korrektur von `--ring-focus` auf accent-400 (2,32:1) stehen. Zeigt jetzt auf `--ring-focus`.                                                                                                                                                                                                                                                                                                                                             |

## Was der Test prüft

1. **Vollständigkeit**: jede `var(--x)`-Referenz ohne Fallback muss auflösbar sein, in `tokens.css`, in den Theme-Overrides und im Tailwind-Theme-Block.
2. **Kontrast**: pro Farbmodus:
   - Text auf Fläche mindestens 4,5:1: `--text-primary`, `--text-secondary`, `--text-placeholder`, `--text-on-brand` auf `--bg-brand`, die drei Statuspaare, `--text-link` und `--text-link-hover`, `--text-accent` im gefüllten Badge, sowie `--text-inverse` auf den drei `--bg-*-strong`-Füllungen und auf `--bg-accent` (Checkbox-Haken)
   - Rand, Fokusring und UI-Flächen mindestens 3:1: Feldrand (`--border-control`), Fokusring (`--ring-focus`), fokussierter Feldrand (`--border-focus`), gecheckte Füllung (`--bg-accent`) je gegen die Seite, Markenrand (`--border-brand`), Sekundär- und Marken-Icon gegen die Seite, die drei Status-Icons je gegen ihre eigene Statusfläche
   - Gradient: `--text-inverse` klärt 4,5:1 gegen BEIDE Stopps von `--gradient-primary-*`, in Ruhe, Hover und beim Drücken
3. **Deckkraft**: `--ring-focus` darf nicht halbtransparent sein.

`--text-tertiary` steht bewusst nicht im Vertrag. Es ist die zurückgenommene Rolle und trägt keinen Text, der gelesen werden muss.

## Wenn der Test rot wird

Er nennt Tokenname, aufgelösten Wert und gemessenes Verhältnis. Zwei Wege:

1. Der bessere: Wert in Figma korrigieren und neu exportieren.
2. Der schnelle: Override in `app.css` ergänzen und hier eintragen.

Weg 2 ohne Eintrag hier ist der Grund, warum diese Datei entstanden ist.
