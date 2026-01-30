# Testplan: WordPress Starter Theme

**Version:** Nach Commit `d5496d7` + offene Änderungen
**Datum:** 2026-01-21
**Testumgebung:** Neue Local by Flywheel Instanz

---

## Voraussetzungen

- [ ] Local by Flywheel mit PHP 8.4+
- [ ] Neue WordPress-Instanz (leer)
- [ ] ACF Pro Lizenz verfügbar
- [ ] Node.js 20.19+ oder 22.12+

---

## Phase 1: Setup Wizard (NEU)

### 1.1 Wizard starten

```bash
cd wp-content/themes/your-theme
php bin/setup.php
```

| #     | Test                     | Erwartet                           | ✓   |
| ----- | ------------------------ | ---------------------------------- | --- |
| 1.1.1 | Wizard startet           | ASCII-Banner, Willkommensnachricht |     |
| 1.1.2 | Theme-Name eingeben      | Validierung (nicht leer)           |     |
| 1.1.3 | Theme-Slug generiert     | Automatisch aus Name (kebab-case)  |     |
| 1.1.4 | PHP Namespace eingeben   | Validierung (PascalCase)           |     |
| 1.1.5 | Text Domain              | Automatisch = Slug                 |     |
| 1.1.6 | Author, URI, Description | Optional, Defaults akzeptierbar    |     |

### 1.2 Plugin-Auswahl

| #     | Test                   | Erwartet                    | ✓   |
| ----- | ---------------------- | --------------------------- | --- |
| 1.2.1 | Plugin-Liste angezeigt | ACF Pro, Yoast, CF7, etc.   |     |
| 1.2.2 | Plugins auswählen      | Mehrfachauswahl mit Nummern |     |
| 1.2.3 | "0" = Keine Plugins    | Überspringt Installation    |     |

### 1.3 Dark Mode Option (NEU)

| #     | Test              | Erwartet                           | ✓   |
| ----- | ----------------- | ---------------------------------- | --- |
| 1.3.1 | Dark Mode Abfrage | "Soll Dark Mode aktiviert werden?" |     |
| 1.3.2 | "y" auswählen     | Dark Mode wird konfiguriert        |     |
| 1.3.3 | Theme Options     | Dark Mode Toggle erscheint         |     |

### 1.4 Automatische Installation

| #     | Test                | Erwartet                           | ✓   |
| ----- | ------------------- | ---------------------------------- | --- |
| 1.4.1 | Namespace-Ersetzung | Alle PHP-Dateien aktualisiert      |     |
| 1.4.2 | composer.json       | Namespace in autoload aktualisiert |     |
| 1.4.3 | style.css           | Theme-Header generiert             |     |
| 1.4.4 | `composer install`  | Läuft automatisch, keine Fehler    |     |
| 1.4.5 | `npm install`       | Läuft automatisch, keine Fehler    |     |
| 1.4.6 | `npm run build`     | Assets kompiliert                  |     |

### 1.5 Nach Setup verifizieren

```bash
# Namespace prüfen
grep -r "namespace YourNamespace" src/
grep -r "use YourNamespace" src/

# Build prüfen
ls -la dist/
```

---

## Phase 2: Theme-Aktivierung

| #   | Test               | Erwartet                                    | ✓   |
| --- | ------------------ | ------------------------------------------- | --- |
| 2.1 | Theme aktivieren   | Keine PHP-Fehler                            |     |
| 2.2 | Welcome Notice     | Admin-Notice mit "Styleguide erstellen"     |     |
| 2.3 | Plugin-Notices     | ACF Pro Fehler (rot) wenn nicht installiert |     |
| 2.4 | Empfohlene Plugins | Gelbe Warnungen (dismissible)               |     |

---

## Phase 3: Plugin Auto-Installer (NEU)

### 3.1 WordPress.org Plugins

| #     | Test                          | Erwartet                                | ✓   |
| ----- | ----------------------------- | --------------------------------------- | --- |
| 3.1.1 | Notice "Plugins installieren" | Button erscheint nach Theme-Aktivierung |     |
| 3.1.2 | Klick auf "Installieren"      | Yoast SEO installiert + aktiviert       |     |
| 3.1.3 | Contact Form 7                | Installiert + aktiviert                 |     |
| 3.1.4 | Fortschrittsanzeige           | Status-Updates während Installation     |     |

### 3.2 Premium Plugins (manuell)

| #     | Test                      | Erwartet             | ✓   |
| ----- | ------------------------- | -------------------- | --- |
| 3.2.1 | ACF Pro Notice            | Link zur ACF-Website |     |
| 3.2.2 | Nach ACF Pro Installation | Notice verschwindet  |     |

---

## Phase 4: Theme Options

### 4.1 Allgemein

| #     | Test                | Erwartet                       | ✓   |
| ----- | ------------------- | ------------------------------ | --- |
| 4.1.1 | Menüpunkt vorhanden | "Theme-Einstellungen" im Admin |     |
| 4.1.2 | Logo-Upload         | Funktioniert, Vorschau         |     |
| 4.1.3 | Favicon-Upload      | Funktioniert                   |     |
| 4.1.4 | Kontaktdaten        | Firma, Adresse, Telefon, Email |     |

### 4.2 Dark Mode (NEU)

| #     | Test                          | Erwartet                            | ✓   |
| ----- | ----------------------------- | ----------------------------------- | --- |
| 4.2.1 | Dark Mode Tab                 | Sichtbar wenn aktiviert             |     |
| 4.2.2 | Toggle "Dark Mode aktivieren" | Schalter vorhanden                  |     |
| 4.2.3 | Mode-Auswahl                  | System / Hell / Dunkel              |     |
| 4.2.4 | Frontend Test                 | `<html class="dark">` bei Dunkel    |     |
| 4.2.5 | System-Präferenz              | Reagiert auf `prefers-color-scheme` |     |

### 4.3 Header & Footer

| #     | Test                 | Erwartet                   | ✓   |
| ----- | -------------------- | -------------------------- | --- |
| 4.3.1 | Sticky Header Toggle | Funktioniert               |     |
| 4.3.2 | Header CTA Button    | Link + Text konfigurierbar |     |
| 4.3.3 | Footer Text          | WYSIWYG Editor             |     |
| 4.3.4 | Copyright            | Jahr + Text                |     |

### 4.4 Social Media & Analytics

| #     | Test                  | Erwartet                            | ✓   |
| ----- | --------------------- | ----------------------------------- | --- |
| 4.4.1 | Social Links Repeater | Hinzufügen/Entfernen                |     |
| 4.4.2 | Plattform-Auswahl     | Facebook, Instagram, LinkedIn, etc. |     |
| 4.4.3 | Rybbit Analytics      | Link zu Plugin-Einstellungen        |     |

---

## Phase 5: Styleguide Generator

| #   | Test                           | Erwartet               | ✓   |
| --- | ------------------------------ | ---------------------- | --- |
| 5.1 | "Styleguide erstellen" klicken | Seite wird erstellt    |     |
| 5.2 | Seite als Entwurf              | Status = Draft         |     |
| 5.3 | Farben-Sektion                 | Alle Design Tokens     |     |
| 5.4 | Typography-Sektion             | Display, H1-H5, Body   |     |
| 5.5 | Spacing-Sektion                | Alle Abstände          |     |
| 5.6 | Block-Übersicht                | Tabelle mit 27+ Blocks |     |
| 5.7 | Notice verschwindet            | Nach Erstellung        |     |

---

## Phase 6: Block Editor - Hero Block (3 Varianten NEU)

### 6.1 Variante: Centered

| #     | Test                 | Erwartet                            | ✓   |
| ----- | -------------------- | ----------------------------------- | --- |
| 6.1.1 | Block einfügen       | "Hero" unter Theme Blocks           |     |
| 6.1.2 | Variante "Zentriert" | Default-Auswahl                     |     |
| 6.1.3 | Badge-Feld           | Optional, Text                      |     |
| 6.1.4 | Titel                | Pflichtfeld                         |     |
| 6.1.5 | Copy (Beschreibung)  | Optional                            |     |
| 6.1.6 | Primärer CTA         | Link-Feld                           |     |
| 6.1.7 | Sekundärer CTA       | Link-Feld                           |     |
| 6.1.8 | Hintergrundfarbe     | Dropdown (primary, secondary, etc.) |     |
| 6.1.9 | Frontend             | Zentrierter Inhalt, volle Höhe      |     |

### 6.2 Variante: Split

| #     | Test                    | Erwartet                  | ✓   |
| ----- | ----------------------- | ------------------------- | --- |
| 6.2.1 | Variante "Split" wählen | Bild-Feld erscheint       |     |
| 6.2.2 | Bild hochladen          | Rechte Spalte             |     |
| 6.2.3 | Frontend Desktop        | 50/50 Grid, Content links |     |
| 6.2.4 | Frontend Mobile         | Bild oben, Content unten  |     |

### 6.3 Variante: Background

| #     | Test                          | Erwartet                             | ✓   |
| ----- | ----------------------------- | ------------------------------------ | --- |
| 6.3.1 | Variante "Hintergrund" wählen | Hintergrundbild-Feld                 |     |
| 6.3.2 | Hintergrundbild hochladen     | Vollbild                             |     |
| 6.3.3 | Overlay-Slider                | 0-100% Deckkraft                     |     |
| 6.3.4 | Frontend                      | Bild + Overlay + zentrierter Content |     |
| 6.3.5 | Dark Mode                     | Overlay-Farbe passt sich an          |     |

---

## Phase 7: Block Editor - CTA Block (aktualisiert)

| #   | Test             | Erwartet                                | ✓   |
| --- | ---------------- | --------------------------------------- | --- |
| 7.1 | Block einfügen   | "Handlungsaufforderung"                 |     |
| 7.2 | Titel eingeben   | Pflichtfeld                             |     |
| 7.3 | Beschreibung     | WYSIWYG                                 |     |
| 7.4 | Button           | Link-Feld                               |     |
| 7.5 | Hintergrundfarbe | brand / brand-secondary                 |     |
| 7.6 | Frontend         | Zentrierte Karte mit abgerundeten Ecken |     |
| 7.7 | Button-Variante  | "inverse" (weiß auf Farbe)              |     |

---

## Phase 8: Block Editor - Button Block (NEU)

| #   | Test            | Erwartet                                    | ✓   |
| --- | --------------- | ------------------------------------------- | --- |
| 8.1 | Block einfügen  | "Button" unter Theme Blocks                 |     |
| 8.2 | Text eingeben   | Pflichtfeld                                 |     |
| 8.3 | Link-Feld       | URL + Target                                |     |
| 8.4 | Variante        | primary, secondary, outline, ghost, inverse |     |
| 8.5 | Größe           | sm, md, lg                                  |     |
| 8.6 | Icon (optional) | Links/Rechts                                |     |
| 8.7 | Frontend        | Korrekte Styles pro Variante                |     |

---

## Phase 9: Alle Blocks testen

| #       | Block         | Felder prüfen        | Frontend               | ✓   |
| ------- | ------------- | -------------------- | ---------------------- | --- |
| 9.1     | accordion     | Items, FAQ Schema    | Auf/Zuklappen          |     |
| 9.2     | tabs          | Tabs + Inhalte       | Tab-Wechsel            |     |
| 9.3     | cards         | Icon, Titel, Link    | Grid-Layout            |     |
| 9.4     | testimonials  | Zitat, Autor, Bild   | Slider/Grid            |     |
| 9.5     | gallery       | Bilder               | Lightbox (medium-zoom) |     |
| 9.6     | stats         | Zahlen, Labels       | Animation beim Scroll  |     |
| 9.7     | team          | Foto, Name, Position | Grid                   |     |
| 9.8     | pricing-table | Pakete, Features     | Vergleichstabelle      |     |
| 9.9     | timeline      | Events, Daten        | Vertikale Timeline     |     |
| 9.10    | posts         | Kategorie, Anzahl    | Dynamische Posts       |     |
| 9.11    | before-after  | 2 Bilder             | Slider                 |     |
| 9.12    | table         | Header, Zeilen       | Responsive Tabelle     |     |
| 9.13    | map           | Adresse              | DSGVO-Consent → Maps   |     |
| 9.14    | logo-slider   | Logos                | Carousel               |     |
| 9.15    | contact-form  | CF7 ID               | Formular (mit CF7)     |     |
| 9.16    | video         | YouTube/Mediathek    | Player                 |     |
| 9.17    | image         | Bild, Caption        | Responsive             |     |
| 9.18    | divider       | Höhe, Farbe          | Abstand                |     |
| 9.19-27 | Layout-Blocks | Spalten              | InnerBlocks            |     |

---

## Phase 10: Komponenten (Figma Design Tokens)

### 10.1 Button Component

| #      | Test                | Erwartet                          | ✓   |
| ------ | ------------------- | --------------------------------- | --- |
| 10.1.1 | Variante: primary   | Orange Hintergrund                |     |
| 10.1.2 | Variante: secondary | Grauer Hintergrund                |     |
| 10.1.3 | Variante: outline   | Transparenter Hintergrund, Border |     |
| 10.1.4 | Variante: ghost     | Nur Text                          |     |
| 10.1.5 | Variante: inverse   | Weiß (für dunkle Hintergründe)    |     |
| 10.1.6 | Größen: sm/md/lg    | Unterschiedliche Padding          |     |
| 10.1.7 | Hover-States        | Farbänderung                      |     |
| 10.1.8 | Disabled-State      | Ausgegraut                        |     |

### 10.2 Input Component

| #      | Test      | Erwartet                         | ✓   |
| ------ | --------- | -------------------------------- | --- |
| 10.2.1 | Default   | Grauer Border, subtiler Schatten |     |
| 10.2.2 | Hover     | Stärkerer Border                 |     |
| 10.2.3 | Focus     | Orange Border, Focus Ring        |     |
| 10.2.4 | Error     | Roter Border                     |     |
| 10.2.5 | Disabled  | Grauer Hintergrund               |     |
| 10.2.6 | Clearable | X-Button erscheint bei Eingabe   |     |
| 10.2.7 | Icons     | Links/Rechts möglich             |     |

### 10.3 Select, Textarea, Checkbox, Radio, Toggle

| #      | Test     | Erwartet                       | ✓   |
| ------ | -------- | ------------------------------ | --- |
| 10.3.1 | Select   | Dropdown mit Chevron           |     |
| 10.3.2 | Textarea | Mehrzeilig, gleiche States     |     |
| 10.3.3 | Checkbox | Custom Styling, Checked-State  |     |
| 10.3.4 | Radio    | Custom Styling, Gruppenauswahl |     |
| 10.3.5 | Toggle   | Switch-Animation               |     |

### 10.4 Badge Component

| #      | Test      | Erwartet                             | ✓   |
| ------ | --------- | ------------------------------------ | --- |
| 10.4.1 | Varianten | gray, brand, success, warning, error |     |
| 10.4.2 | Styles    | filled, outline                      |     |
| 10.4.3 | Größen    | sm, md, lg                           |     |
| 10.4.4 | Dot       | Status-Punkt                         |     |
| 10.4.5 | Icons     | Links/Rechts                         |     |

---

## Phase 11: Dark Mode Frontend

| #    | Test             | Erwartet                    | ✓   |
| ---- | ---------------- | --------------------------- | --- |
| 11.1 | System-Modus     | Folgt OS-Einstellung        |     |
| 11.2 | Hell erzwingen   | Immer heller Modus          |     |
| 11.3 | Dunkel erzwingen | Immer dunkler Modus         |     |
| 11.4 | Toggle im Header | Umschalten möglich          |     |
| 11.5 | Farben           | Tokens wechseln korrekt     |     |
| 11.6 | Bilder           | Keine Invertierung          |     |
| 11.7 | Hero Background  | Overlay-Farbe passt sich an |     |

---

## Phase 12: Responsive & Accessibility

| #    | Test                | Erwartet             | ✓   |
| ---- | ------------------- | -------------------- | --- |
| 12.1 | Mobile Navigation   | Hamburger-Menü       |     |
| 12.2 | Focus Trap          | Escape schließt Menü |     |
| 12.3 | Keyboard Navigation | Tab durch alle Links |     |
| 12.4 | Screen Reader       | aria-labels korrekt  |     |
| 12.5 | WCAG Kontrast       | 4.5:1 für Text       |     |

---

## Phase 13: Development Workflow

| #    | Test            | Erwartet                 | ✓   |
| ---- | --------------- | ------------------------ | --- |
| 13.1 | `npm run dev`   | Vite HMR auf :5173       |     |
| 13.2 | CSS ändern      | Hot Reload               |     |
| 13.3 | `npm run build` | Minifiziert, Hashes      |     |
| 13.4 | `npm run lint`  | Keine Fehler             |     |
| 13.5 | `composer lint` | PHPStan + PHPCS bestehen |     |
| 13.6 | `npm test`      | Vitest Tests bestehen    |     |
| 13.7 | `composer test` | PHPUnit Tests bestehen   |     |

---

## Phase 14: Sicherheit

| #    | Test             | Erwartet                            | ✓   |
| ---- | ---------------- | ----------------------------------- | --- |
| 14.1 | CSP Headers      | Vorhanden in DevTools               |     |
| 14.2 | XSS Test         | `<script>alert(1)</script>` escaped |     |
| 14.3 | Nonce in Scripts | `nonce` Attribut vorhanden          |     |
| 14.4 | REST API         | /theme/v1/options nur für Admins    |     |

---

## Abschluss-Checkliste

| #   | Prüfung                        | Status | ✓   |
| --- | ------------------------------ | ------ | --- |
| A   | Alle Phasen durchlaufen        |        |     |
| B   | Keine PHP-Fehler im Debug-Log  |        |     |
| C   | Keine JS-Fehler in Console     |        |     |
| D   | Responsive auf Mobile getestet |        |     |
| E   | Dark Mode funktioniert         |        |     |
| F   | Alle 27+ Blocks funktionieren  |        |     |

---

## Bekannte Einschränkungen

1. **Contact Form Block:** Benötigt Contact Form 7 Plugin
2. **Map Block:** Google Maps API-Key nicht enthalten
3. **Premium Plugins:** Manuelle Installation erforderlich

---

## Fehler melden

Bei Problemen:

1. PHP Debug-Log prüfen (`wp-content/debug.log`)
2. Browser Console prüfen
3. Issue erstellen mit:
   - Schritte zur Reproduktion
   - Erwartetes vs. tatsächliches Verhalten
   - Screenshots
