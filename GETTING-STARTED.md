# Schnellstart-Anleitung

## Voraussetzungen

Stelle sicher, dass folgende Software installiert ist:

- **PHP 8.4+** (mit Composer)
- **Node.js 20.19+ oder 22.12+** (mit npm)
- **WordPress 6.8+**
- **Local by Flywheel** (empfohlen für Mac-Entwicklung)

## Installation

### 1. Theme-Dateien kopieren

Kopiere den Theme-Ordner nach `wp-content/themes/` deiner WordPress-Installation.

### 2. Setup-Wizard ausführen (empfohlen)

Das Theme enthält einen interaktiven Setup-Wizard, der alles für dich konfiguriert:

```bash
php bin/setup.php
```

Der Setup-Wizard:

1. Konfiguriert Theme-Name, Autor und Metadaten
2. Aktualisiert den PHP-Namespace in allen Dateien
3. Lässt dich Plugins zur automatischen Installation auswählen
4. Konfiguriert Startinhalte (Seiten, Permalinks)
5. Installiert Abhängigkeiten automatisch (composer install, npm install, npm run build)
6. Erstellt `.env`-Datei aus der Vorlage

### 3. Manuelle Installation (Alternative)

Falls du den Setup-Wizard nicht nutzen möchtest:

```bash
# PHP-Abhängigkeiten installieren
composer install

# JavaScript-Abhängigkeiten installieren
npm install

# Assets für Produktion bauen
npm run build

# Erstelle eine .env-Datei aus der Vorlage
cp .env.example .env
```

Öffne `.env` und passe die Werte an, falls nötig.

### 4. Theme aktivieren

1. Gehe zu WordPress Admin → Design → Themes
2. Aktiviere "WP-Starter"
3. Du siehst eine Willkommensnachricht mit der Option, eine Styleguide-Seite zu erstellen

### 5. ACF Pro aktivieren

Das Theme benötigt **Advanced Custom Fields PRO**:

1. Installiere und aktiviere ACF Pro
2. Nach der Aktivierung werden alle Blöcke und Theme-Optionen verfügbar

## Entwicklung starten

### Vite Dev Server starten

```bash
npm run dev
```

Dies startet den Entwicklungsserver auf `http://localhost:5180` mit Hot Module Replacement (HMR).

### Prüfen ob alles funktioniert

1. Öffne deine WordPress-Seite im Browser
2. Erstelle eine neue Seite mit der Vorlage "Flexibler Seiteninhalt"
3. Im Classic Editor siehst du den "Sektion hinzufügen" Button
4. Wähle eines der 32 verfügbaren Layouts aus

## Erste Schritte nach der Installation

### Theme-Einstellungen konfigurieren

1. Gehe zu **Theme-Einstellungen** im Admin-Menü
2. Konfiguriere unter "Allgemein":
   - Logo und Favicon
   - Kontaktdaten (Firma, Adresse, Telefon, E-Mail)
3. Konfiguriere unter "Footer":
   - Footer-Text und Copyright
4. Optional: Social Media Links und Analytics

### Ersten Inhalt erstellen

1. Erstelle eine neue Seite
2. Füge einen **Hero-Bereich** Block hinzu
3. Gib Titel und Hintergrundbild ein
4. Füge weitere Blöcke hinzu (z.B. Zwei Spalten, Handlungsaufforderung)
5. Veröffentliche die Seite

## Verfügbare Layouts

Das Theme enthält 32 vorgefertigte Flexible Content Layouts:

| Kategorie  | Layouts                                                                                                                                               |
| ---------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| Header     | Hero-Bereich                                                                                                                                          |
| Layout     | Eine Spalte, Zwei Spalten, Drei Spalten, Vier Spalten, 1/3+2/3, 2/3+1/3, Zwei Spalten mit Bildern, Drei Spalten mit Bildern, Vier Spalten mit Bildern |
| Inhalte    | Akkordeon, Tabs, CTA, Button                                                                                                                          |
| Medien     | Bild, Video, Galerie, Vorher/Nachher                                                                                                                  |
| Interaktiv | Kundenstimmen, Karten, Statistiken, Zeitstrahl, Team, Preistabelle, Downloads (Interner Bereich)                                                      |
| Formulare  | Kontaktformular, Google Maps                                                                                                                          |
| Beiträge   | Posts, Tabelle                                                                                                                                        |
| Sonstiges  | Trenner/Abstand, Logo-Slider                                                                                                                          |

## Wichtige Dateien

| Datei/Ordner                   | Beschreibung                                   |
| ------------------------------ | ---------------------------------------------- |
| `templates/`                   | Blade-Templates                                |
| `templates/flexible/`          | Flexible Content Layout-Templates (32 Layouts) |
| `src/Acf/FlexibleContent.php`  | Layout-Definitionen                            |
| `src/Acf/FieldDefinitions.php` | Feld-Definitionen                              |
| `resources/css/`               | CSS-Quelldateien (TailwindCSS)                 |
| `resources/js/`                | JavaScript/TypeScript-Quelldateien             |
| `src/`                         | PHP-Klassen und Service Provider               |
| `config/`                      | Konfigurationsdateien                          |
| `bin/setup.php`                | Interaktiver Setup-Wizard                      |

## Nächste Schritte

- Lies die vollständige [README.MD](README.MD) für detaillierte Dokumentation
- Schau dir [TROUBLESHOOTING.md](TROUBLESHOOTING.md) an bei Problemen
- Erstelle eine Styleguide-Seite über die Willkommensnachricht im Dashboard

## Hilfe benötigt?

Bei Problemen prüfe zuerst [TROUBLESHOOTING.md](TROUBLESHOOTING.md). Die häufigsten Probleme und Lösungen findest du dort.
