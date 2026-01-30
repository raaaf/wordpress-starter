# Beitragsrichtlinien

Danke, dass du zum WP-Starter Theme beitragen möchtest! Diese Richtlinien helfen, die Code-Qualität hoch zu halten.

## Entwicklungsumgebung einrichten

1. Klone das Repository
2. Installiere Abhängigkeiten:
   ```bash
   composer install
   npm install
   ```
3. Kopiere die Umgebungsvariablen:
   ```bash
   cp .env.example .env
   ```
4. Starte den Entwicklungsserver:
   ```bash
   npm run dev
   ```

## Code-Stil

### PHP

- **PHP 8.2+** mit `declare(strict_types=1)`
- **PSR-4** Autoloading unter `WordpressStarter\` Namespace
- Folge den WordPress Coding Standards (mit Anpassungen, siehe `phpcs.xml`)

Prüfe deinen Code:

```bash
composer phpcs      # Fehler anzeigen
composer phpcs:fix  # Automatisch korrigieren
composer phpstan    # Statische Analyse
```

### JavaScript / TypeScript

- **TypeScript** bevorzugt
- **ESLint** + **Prettier** für Formatierung
- **Alpine.js** für Interaktivität (kein jQuery)

Prüfe deinen Code:

```bash
npm run lint        # Fehler anzeigen
npm run lint:fix    # Automatisch korrigieren
npm run format      # Mit Prettier formatieren
```

### CSS

- **TailwindCSS v4** für Styling
- Verwende die **Design-Token** aus `theme.json`
- Eigene Styles in `resources/css/` (sparsam verwenden)

### Blade Templates

- Dateien in `templates/` mit `.blade.php` Endung
- Komponenten in `templates/components/`
- Immer Props dokumentieren

## Commit-Nachrichten

Verwende aussagekräftige Commit-Nachrichten:

```
feat: Neuen Testimonials-Block hinzufügen
fix: Button-Hover-Farbe korrigieren
docs: README um Analytics-Abschnitt erweitern
refactor: BladeServiceProvider aufräumen
style: Formatierung in FieldDefinitions.php
```

### Präfixe

| Präfix      | Verwendung                      |
| ----------- | ------------------------------- |
| `feat:`     | Neue Funktion                   |
| `fix:`      | Bugfix                          |
| `docs:`     | Dokumentation                   |
| `style:`    | Formatierung (kein Code-Change) |
| `refactor:` | Code-Umbau ohne neue Funktion   |
| `test:`     | Tests hinzufügen/ändern         |
| `chore:`    | Wartung (Dependencies etc.)     |

## Branch-Strategie

- `main` - Produktionsreifer Code
- `feature/name` - Neue Features
- `fix/name` - Bugfixes
- `docs/name` - Dokumentation

Beispiel:

```bash
git checkout -b feature/team-block
# ... Änderungen ...
git commit -m "feat: Team-Block mit Foto und Bio hinzufügen"
git push -u origin feature/team-block
```

## Pull Requests

1. Erstelle einen Branch für deine Änderung
2. Schreibe Tests wenn möglich
3. Stelle sicher, dass alle Checks grün sind:
   ```bash
   npm run lint
   composer lint
   ```
4. Erstelle einen Pull Request mit:
   - Klarer Beschreibung was und warum
   - Screenshots bei UI-Änderungen
   - Hinweis auf Breaking Changes

## Neue Blöcke hinzufügen

Siehe [docs/BLOCK-DEVELOPMENT.md](docs/BLOCK-DEVELOPMENT.md) für eine vollständige Anleitung.

Checkliste:

- [ ] `block.json` mit deutschem Titel und Beschreibung
- [ ] Felder in `FieldDefinitions.php` definiert
- [ ] Block in `BlockFields.php` registriert
- [ ] Template mit `<x-section>` Wrapper
- [ ] Empty State für leere Inhalte
- [ ] Im Editor und Frontend getestet

## Neue Komponenten hinzufügen

Siehe [docs/COMPONENT-DEVELOPMENT.md](docs/COMPONENT-DEVELOPMENT.md) für eine vollständige Anleitung.

Checkliste:

- [ ] Datei in `templates/components/` erstellt
- [ ] Props mit `@props` definiert
- [ ] Dokumentations-Header mit Beispiel
- [ ] In `BladeServiceProvider.php` registriert
- [ ] Design-Token verwendet (keine hardcoded Farben)

## Datenschutz

- **Keine Cookies** ohne Consent
- **Keine externen Requests** ohne DSGVO-Consent (z.B. Google Maps, YouTube)
- **Nur Rybbit Analytics** (Cookie-frei, via Plugin)
- Keine Google Analytics, Tag Manager etc.

## Barrierefreiheit

- Semantisches HTML verwenden
- ARIA-Attribute wo nötig
- Fokus-States für Tastaturnavigation
- Ausreichende Farbkontraste

## Fragen?

Bei Fragen oder Problemen:

1. Prüfe zuerst [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
2. Schaue in die bestehende Dokumentation
3. Öffne ein Issue mit detaillierter Beschreibung
