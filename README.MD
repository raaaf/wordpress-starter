# WordPress Starter Theme

My own WordPress theme framework as the basis for every client project. No page builder, no Gutenberg — Classic Editor, ACF Flexible Content and a clean service-provider stack instead.

## Stack

| Technology                    | Version        | Purpose                            |
| ----------------------------- | -------------- | ---------------------------------- |
| PHP                           | 8.4+           | Strict types, modern features      |
| Laravel Blade                 | Illuminate v13 | Templating                         |
| TailwindCSS                   | v4.1           | Utility CSS, design tokens         |
| Alpine.js                     | v3.15          | Interactive components, bundled    |
| Vite                          | 7.3            | Build tool with HMR                |
| ACF Pro + ACF Extended (FREE) | —              | Flexible Content page builder      |
| TypeScript                    | —              | Type-safe JS                       |

Gutenberg is disabled. Content model: Classic Editor + ACF Flexible Content.

## Requirements

- PHP 8.4+
- Composer
- Node.js 20.19+ / 22.12+
- WordPress 6.8+
- ACF Pro (install manually — premium plugin)
- Local by Flywheel

## Create a new client project

```bash
git clone https://github.com/raaaf/starter.git theme-name
cd theme-name
php bin/setup.php
```

The setup wizard:

1. Sets theme name, author, text domain
2. Replaces the `WordpressStarter\` namespace across all files
3. Lets you select plugins (managed via Composer)
4. Configures initial pages and permalinks
5. Runs `composer install`, `npm install`, `npm run build`
6. Creates `.env` from `.env.example`

Then activate the theme in WordPress and run:

```bash
npm run dev
```

## Directory structure

```
├── acf-json/              # ACF field group JSON (versioned)
├── bin/
│   └── setup.php          # Setup wizard for new projects
├── compiled/              # Blade cache (gitignored)
├── config/
│   ├── app.php            # Theme configuration
│   └── functions.php      # Bootstrap
├── dist/                  # Compiled assets (gitignored)
├── docs/                  # Extended documentation
├── resources/
│   ├── css/
│   │   ├── app.css        # Main stylesheet
│   │   ├── tokens.css     # Design tokens (generated from Figma)
│   │   └── editor-style.css
│   ├── js/
│   │   └── app.ts         # TypeScript entry point
│   └── fonts/
├── src/
│   ├── Acf/
│   │   ├── AcfExtended.php      # ACF Extended configuration
│   │   ├── FieldDefinitions.php # Central field definitions
│   │   ├── Fields.php           # Field helpers
│   │   ├── FlexibleContent.php  # 28 Flexible Content layouts
│   │   └── Options.php          # Theme options pages
│   ├── MemberArea/              # Member area (optional)
│   ├── PostTypes/               # Custom post types
│   ├── Providers/               # Service providers
│   ├── Taxonomies/              # Custom taxonomies
│   ├── Application.php
│   ├── Config.php
│   ├── EditorConfig.php
│   ├── RateLimiter.php
│   ├── Security.php
│   ├── ThemeContext.php         # Multisite isolation
│   └── Vite.php
└── templates/
    ├── components/        # Blade components (<x-button>, <x-section> etc.)
    ├── flexible/          # Flexible Content templates (28 layouts)
    ├── layouts/           # Base layouts
    └── partials/          # Reusable partials
```

## Development

```bash
npm run dev          # Vite dev server with HMR (localhost:5180)
npm run build        # Production build
npm run lint         # ESLint
composer lint        # PHPCS + PHPStan
composer test        # PHPUnit
npm run test:e2e     # Playwright E2E tests
npm run test:a11y    # Accessibility tests
```

## Service providers

All providers in `src/Providers/` are registered automatically via `Application::boot()`.

| Provider                            | Responsibility                                     |
| ----------------------------------- | -------------------------------------------------- |
| `ThemeServiceProvider`              | Theme support, favicon, login logo, script loading |
| `BladeServiceProvider`              | Blade engine, cache directory                      |
| `AcfServiceProvider`                | ACF fields, Blade directives, REST API             |
| `MenuServiceProvider`               | Register nav menus                                 |
| `SecurityServiceProvider`           | CSP headers, nonces                                |
| `ImageServiceProvider`              | Custom image sizes                                 |
| `PostTypeServiceProvider`           | CPTs and taxonomies                                |
| `MemberAreaServiceProvider`         | Member area (login, downloads, SFTP sync)          |
| `PluginServiceProvider`             | Plugin setup on theme activation                   |
| `PluginConfiguratorServiceProvider` | Auto-configuration of plugins                      |
| `WelcomeServiceProvider`            | Welcome notice, ACF options prefill                |
| `SeoServiceProvider`                | Schema.org, OG tags, canonical                     |
| `DesignTokenServiceProvider`        | Token upload from Figma                            |
| `CronServiceProvider`               | Transient and revision cleanup                     |
| `LogServiceProvider`                | Structured logging to debug.log                    |
| `ThemeUpdateProvider`               | GitHub-based theme updates                         |

## Blade directives

```blade
@field('name')          {{-- escaped --}}
@fieldRaw('name')       {{-- wp_kses_post --}}
@option('name')         {{-- theme option, escaped --}}
@optionRaw('name')      {{-- theme option, HTML --}}

@hasfield('name') ... @endhasfield
@repeater('name') ... @endrepeater
@flexible('content') ... @endflexible
@layout('hero') ... @endlayout
```

## Flexible Content layouts

28 layouts in `src/Acf/FlexibleContent.php`, templates in `templates/flexible/`.

**Structure:** one-column, two-columns, three-columns, four-columns, one-third-two-thirds, two-thirds-one-third, two-columns-images

**Content:** hero, accordion, cta, button, image, video, divider

**Interactive:** testimonials, cards, gallery, logo-slider, tabs, stats, timeline, team, before-after, pricing-table

**Forms:** contact-form, map

**Posts:** posts, table

### Add a new layout

1. Method in `src/Acf/FlexibleContent.php`:

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

2. Field definitions in `src/Acf/FieldDefinitions.php`
3. Template `templates/flexible/my.blade.php`
4. Add it to `getLayouts()`

## Design tokens

Tokens are exported from Figma and generated into `resources/css/tokens.css` via `npm run tokens`. Semantic variables:

```css
background: var(--bg-primary);
color: var(--text-primary);
border-color: var(--border-default);
```

Dark mode via `<html data-theme="dark">` or automatically through `prefers-color-scheme`.

## Custom post types

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

Registration in `PostTypeServiceProvider::boot()`:

```php
Service::register();
```

## Member area

Optional. Features: ACF-driven download management, SFTP sync, password encryption, session-based login without a WordPress user account.

Configuration in `src/Acf/Options.php` (ACF options: `member_area_active`, SFTP credentials).

## Multisite

The theme is multisite-safe. `ThemeContext` isolates all option keys and setup hooks per site. Multiple subsites can run the theme at the same time without interfering with each other.

## Plugins

Plugins are managed via Composer:

```bash
composer require wpackagist-plugin/contact-form-7
```

ACF Pro must be installed manually (premium).

## Documentation

- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) — Service provider pattern, bootstrap flow
- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) — Production deployment
- [docs/SECURITY.md](docs/SECURITY.md) — CSP, security practices
- [docs/SEO.md](docs/SEO.md) — Schema.org, structured data
- [docs/DESIGN-TOKENS.md](docs/DESIGN-TOKENS.md) — Token system

## Author

Rafael Alex — Designer & Developer from Fürth, Germany.
Websites, apps and AI tools, built by one person.
[rafaelalex.de](https://rafaelalex.de) · [@raaaf](https://github.com/raaaf)
