# WordPress Starter Theme

Eigenes WordPress-Theme-Framework als Basis für alle Client-Projekte. Kein Page-Builder, kein Gutenberg — stattdessen Classic Editor, ACF Flexible Content und ein sauberer Service-Provider-Stack.

## Stack

| Technologie                   | Version        | Zweck                              |
| ----------------------------- | -------------- | ---------------------------------- |
| PHP                           | 8.4+           | Strict types, moderne Features     |
| Laravel Blade                 | Illuminate v13 | Templating                         |
| TailwindCSS                   | v4.1           | Utility CSS, Design Tokens         |
| Alpine.js                     | v3.15          | Interaktive Komponenten, gebündelt |
| Vite                          | 7.3            | Build-Tool mit HMR                 |
| ACF Pro + ACF Extended (FREE) | —              | Flexible Content Page Builder      |
| TypeScript                    | —              | Type-safe JS                       |

Gutenberg ist deaktiviert. Content-Modell: Classic Editor + ACF Flexible Content.

## Voraussetzungen

- PHP 8.4+
- Composer
- Node.js 20.19+ / 22.12+
- WordPress 6.8+
- ACF Pro (manuell installieren — Premium-Plugin)
- Local by Flywheel

## Neues Client-Projekt anlegen

```bash
git clone https://github.com/raaaf/starter.git theme-name
cd theme-name
php bin/setup.php
```

Der Setup-Wizard:

1. Setzt Theme-Name, Autor, Text Domain
2. Ersetzt den Namespace `WordpressStarter\` in allen Dateien
3. Lässt Plugins auswählen (werden per Composer verwaltet)
4. Konfiguriert initiale Seiten und Permalinks
5. Führt `composer install`, `npm install`, `npm run build` aus
6. Erstellt `.env` aus `.env.example`

Danach Theme in WordPress aktivieren, dann:

```bash
npm run dev
```

## Verzeichnisstruktur

```
├── acf-json/              # ACF Field Group JSON (versioniert)
├── bin/
│   └── setup.php          # Setup-Wizard für neue Projekte
├── compiled/              # Blade-Cache (gitignored)
├── config/
│   ├── app.php            # Theme-Konfiguration
│   └── functions.php      # Bootstrap
├── dist/                  # Compiled Assets (gitignored)
├── docs/                  # Erweiterte Dokumentation
├── resources/
│   ├── css/
│   │   ├── app.css        # Haupt-Stylesheet
│   │   ├── tokens.css     # Design Tokens (aus Figma generiert)
│   │   └── editor-style.css
│   ├── js/
│   │   └── app.ts         # TypeScript-Einstiegspunkt
│   └── fonts/
├── src/
│   ├── Acf/
│   │   ├── AcfExtended.php      # ACF Extended Konfiguration
│   │   ├── FieldDefinitions.php # Zentrale Felddefinitionen
│   │   ├── Fields.php           # Feld-Helpers
│   │   ├── FlexibleContent.php  # 28 Flexible Content Layouts
│   │   └── Options.php          # Theme Options Pages
│   ├── MemberArea/              # Member-Bereich (optional)
│   ├── PostTypes/               # Custom Post Types
│   ├── Providers/               # Service Providers
│   ├── Taxonomies/              # Custom Taxonomies
│   ├── Application.php
│   ├── Config.php
│   ├── EditorConfig.php
│   ├── RateLimiter.php
│   ├── Security.php
│   ├── ThemeContext.php         # Multisite-Isolation
│   └── Vite.php
└── templates/
    ├── components/        # Blade-Komponenten (<x-button>, <x-section> etc.)
    ├── flexible/          # Flexible Content Templates (28 Layouts)
    ├── layouts/           # Base-Layouts
    └── partials/          # Wiederverwendbare Partials
```

## Entwicklung

```bash
npm run dev          # Vite Dev Server mit HMR (localhost:5180)
npm run build        # Production Build
npm run lint         # ESLint
composer lint        # PHPCS + PHPStan
composer test        # PHPUnit
npm run test:e2e     # Playwright E2E Tests
npm run test:a11y    # Accessibility Tests
```

## Service Provider

Alle Provider in `src/Providers/` werden automatisch registriert via `Application::boot()`.

| Provider                            | Aufgabe                                            |
| ----------------------------------- | -------------------------------------------------- |
| `ThemeServiceProvider`              | Theme Support, Favicon, Login-Logo, Script-Loading |
| `BladeServiceProvider`              | Blade Engine, Cache-Verzeichnis                    |
| `AcfServiceProvider`                | ACF Felder, Blade-Direktiven, REST API             |
| `MenuServiceProvider`               | Nav-Menus registrieren                             |
| `SecurityServiceProvider`           | CSP-Header, Nonces                                 |
| `ImageServiceProvider`              | Custom Image Sizes                                 |
| `PostTypeServiceProvider`           | CPTs und Taxonomien                                |
| `MemberAreaServiceProvider`         | Member-Bereich (Login, Downloads, SFTP-Sync)       |
| `PluginServiceProvider`             | Plugin-Setup bei Theme-Aktivierung                 |
| `PluginConfiguratorServiceProvider` | Auto-Konfiguration von Plugins                     |
| `WelcomeServiceProvider`            | Welcome-Notice, ACF Options Prefill                |
| `SeoServiceProvider`                | Schema.org, OG-Tags, Canonical                     |
| `DesignTokenServiceProvider`        | Token-Upload aus Figma                             |
| `CronServiceProvider`               | Transient- und Revision-Cleanup                    |
| `LogServiceProvider`                | Strukturiertes Logging in debug.log                |
| `ThemeUpdateProvider`               | GitHub-basierte Theme-Updates                      |

## Blade-Direktiven

```blade
@field('name')          {{-- escaped --}}
@fieldRaw('name')       {{-- wp_kses_post --}}
@option('name')         {{-- Theme Option, escaped --}}
@optionRaw('name')      {{-- Theme Option, HTML --}}

@hasfield('name') ... @endhasfield
@repeater('name') ... @endrepeater
@flexible('content') ... @endflexible
@layout('hero') ... @endlayout
```

## Flexible Content Layouts

28 Layouts in `src/Acf/FlexibleContent.php`, Templates in `templates/flexible/`.

**Struktur:** one-column, two-columns, three-columns, four-columns, one-third-two-thirds, two-thirds-one-third, two-columns-images

**Inhalte:** hero, accordion, cta, button, image, video, divider

**Interaktiv:** testimonials, cards, gallery, logo-slider, tabs, stats, timeline, team, before-after, pricing-table

**Formulare:** contact-form, map

**Beiträge:** posts, table

### Neues Layout hinzufügen

1. Methode in `src/Acf/FlexibleContent.php`:

```php
private static function myLayout(): array
{
    return [
        'key'                    => 'layout_my',
        'name'                   => 'my',
        'label'                  => 'Mein Layout',
        'display'                => 'block',
        'sub_fields'             => FieldDefinitions::myFields('flex_my'),
        'acfe_flexible_category' => self::CATEGORIES['content'],
    ];
}
```

2. Felddefinitionen in `src/Acf/FieldDefinitions.php`
3. Template `templates/flexible/my.blade.php`
4. In `getLayouts()` eintragen

## Design Tokens

Tokens werden aus Figma exportiert und via `npm run tokens` in `resources/css/tokens.css` generiert. Semantische Variablen:

```css
background: var(--bg-primary);
color: var(--text-primary);
border-color: var(--border-default);
```

Dark Mode via `<html data-theme="dark">` oder automatisch per `prefers-color-scheme`.

## Custom Post Types

```php
namespace WordpressStarter\PostTypes;

class Service extends AbstractPostType
{
    protected static string $postType = 'service';
    protected static string $singular = 'Leistung';
    protected static string $plural   = 'Leistungen';
    protected static string $menuIcon = 'dashicons-admin-generic';

    public static function registerFields(): void {}
}
```

Registrierung in `PostTypeServiceProvider::boot()`:

```php
Service::register();
```

## Member-Bereich

Optional aktivierbar. Features: ACF-gesteuerte Download-Verwaltung, SFTP-Sync, Passwort-Verschlüsselung, Session-basiertes Login ohne WordPress-User-Account.

Konfiguration in `src/Acf/Options.php` (ACF-Optionen: `member_area_active`, SFTP-Credentials).

## Multisite

Das Theme ist Multisite-safe. `ThemeContext` isoliert alle Option-Keys und Setup-Hooks pro Site. Mehrere Subsites können das Theme gleichzeitig aktiv haben, ohne sich gegenseitig zu beeinflussen.

## Plugins

Plugins werden per Composer verwaltet:

```bash
composer require wpackagist-plugin/contact-form-7
```

ACF Pro muss manuell installiert werden (Premium).

## Dokumentation

- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) — Service Provider Pattern, Bootstrap-Flow
- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) — Production Deployment
- [docs/SECURITY.md](docs/SECURITY.md) — CSP, Sicherheitspraktiken
- [docs/SEO.md](docs/SEO.md) — Schema.org, strukturierte Daten
- [docs/DESIGN-TOKENS.md](docs/DESIGN-TOKENS.md) — Token-System

## Author

Rafael Alex — Designer & Developer from Fürth, Germany.
Websites, apps and AI tools, built by one person.
[rafaelalex.de](https://rafaelalex.de) · [@raaaf](https://github.com/raaaf)
