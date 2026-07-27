# Design-Token-Lücken

`resources/css/tokens.css` wird aus Figma generiert und deshalb von niemandem gelesen. Diese Datei hält fest, was der Export nicht liefert und was `resources/css/app.css` stattdessen kompensiert.

**Ziel: diese Liste schrumpft.** Jeder Eintrag gehört nach Figma, nicht ins Theme. Solange er hier steht, ist er ein Workaround.

Abgesichert ist das durch `resources/js/design-tokens.test.ts`. Der Test läuft in der CI und wird rot, sobald ein Export eine Lücke reisst.

## A. Referenzen ins Leere

Figma exportiert `var(--x)`, exportiert `--x` aber nicht. Ohne Fallback fällt die ganze Deklaration aus.

| Token                    | wird gebraucht von                       | Wirkung ohne Fix                    |
| ------------------------ | ---------------------------------------- | ----------------------------------- |
| `--spacing-1` (4px)      | `--badge-sm-gap`, `--badge-md-padding-y` | Badges vertikal ohne Innenabstand   |
| `--spacing-2` (8px)      | `--button-md-gap`, `--badge-lg-gap`      | Icon klebt am Beschriftungstext     |
| `--color-error-alpha-50` | `--shadow-focus-ring-error`              | greift auf einen Inline-Wert zurück |

Die Spacing-Skala definiert `0-5, 1-5, 2-5, 3, 4, 5, 6, 8, 10, 12, 16, 20, 24`, überspringt also ausgerechnet die beiden geradesten Stufen ihrer eigenen 4px-Logik.

## B. Rollen, die es in Figma nicht gibt

Für diese Aufgaben existiert keine Variable. Sie sind im Theme erfunden und sollten in Figma als Rolle angelegt werden.

| Token                | wofür                         | warum nicht vorhandene nutzen                                                                                                   |
| -------------------- | ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| `--text-placeholder` | Platzhalter in Eingabefeldern | `--text-tertiary` erreicht je nach Palette nur 2,2 bis 3,1:1, `--text-secondary` liegt in einer Palette zu nah an der Textfarbe |
| `--border-control`   | Rand von Formularfeldern      | `--border-default` erreicht 1,2 bis 1,5:1, WCAG 1.4.11 verlangt 3:1 für Bedienelement-Begrenzungen                              |
| `--ring-focus`       | Fokusring                     | der vorhandene Ring nutzt 50 Prozent Deckkraft und wäscht auf etwa 1,8:1 aus                                                    |

Beide Kontrastwerte sind rollenabhängig: Platzhalter und Text brauchen 4,5:1, Rand und Fokusring 3:1.

## C. Falsche Werte

Der Token existiert, sein Wert taugt nicht.

| Token                                                               | Problem                                                                                                                                                            |
| ------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `--text-success` / `--text-warning` / `--text-error` im Dunkelmodus | zeigen auf den mittleren Ton der Rampe, während `--bg-*` auf den dunklen zeigt. Zwei ähnliche Helligkeiten übereinander, 1,7 bis 3,0:1. Richtig ist der helle Ton. |

## Was der Test prüft

1. **Vollständigkeit** — jede `var(--x)`-Referenz ohne Fallback muss auflösbar sein, in `tokens.css` wie in den Theme-Overrides.
2. **Kontrast** — pro Farbmodus:
   - Text auf Fläche mindestens 4,5:1 (`--text-primary`, `--text-secondary`, `--text-placeholder`, sowie die drei Statuspaare)
   - Feldrand und Fokusring auf Fläche mindestens 3:1
3. **Deckkraft** — `--ring-focus` darf nicht halbtransparent sein.

`--text-tertiary` steht bewusst nicht im Vertrag. Es ist die zurückgenommene Rolle und trägt keinen Text, der gelesen werden muss.

## Wenn der Test rot wird

Er nennt Tokenname, aufgelösten Wert und gemessenes Verhältnis. Zwei Wege:

1. Der bessere: Wert in Figma korrigieren und neu exportieren.
2. Der schnelle: Override in `app.css` ergänzen und hier eintragen.

Weg 2 ohne Eintrag hier ist der Grund, warum diese Datei entstanden ist.
