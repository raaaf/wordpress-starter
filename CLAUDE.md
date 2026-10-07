# CLAUDE.md

Guidance for Claude Code when working with this WordPress starter theme.

## Produkt

My own WordPress theme framework as the basis for every client project. No page builder, no Gutenberg: Classic Editor, ACF Flexible Content and a service-provider stack. Client themes are forked from this starter (see README.MD, "Create a new client project").

## Quick Reference

- **Namespace:** `WordpressStarter\`
- **Text Domain:** `wp-starter`
- **PHP:** 8.3+ with strict types
- **Dev Server:** `npm run dev` (localhost:5180)
- **Editor:** Classic Editor + ACF Flexible Content (Gutenberg disabled)

## Essential Commands

```bash
composer install    # PHP dependencies
npm install          # JS dependencies
npm run dev        # Development with HMR
npm run build      # Production build
npm run lint       # JS/TS linting
npm run icons      # Sync resources/icons/ from config/icons.json
npm test            # Vitest (JS unit tests)
npx vitest run <file>  # Vitest, nur eine Datei
npx vitest run -t <name>  # Vitest, nur Tests, deren Name matcht
npm run test:watch  # Vitest in watch mode
npm run test:coverage # Vitest with coverage report
npm run test:e2e   # Playwright E2E tests
npm run test:a11y  # Accessibility tests
npm run test:styleguide  # Styleguide-Seite (braucht WP_USER + WP_PASSWORD, siehe unten)
composer lint      # PHP linting (phpcs + phpstan)
composer test      # PHPUnit tests
```

Während der Arbeit nur betroffene Tests laufen lassen; die volle Suite erst vor dem Push.

Der Vite-Dev-Server bindet standardmäßig nur an `localhost`. `VITE_HOST=true` bindet
zusätzlich an alle Interfaces (z. B. zum Testen von einem anderen Gerät im LAN).

### E2E gegen die Styleguide-Seite

Die Styleguide-Seite ist `private` und damit nur eingeloggt erreichbar. Der Spec
`tests/e2e/styleguide.spec.ts` prueft dort Sprungnavigation, Anker-Eindeutigkeit,
Farbschema-Umschalter und axe-Verstoesse. Ohne Zugangsdaten ueberspringt er sich
selbst, statt rot zu werden.

```bash
PLAYWRIGHT_BASE_URL=https://wordpress.local \
WP_USER=<login> WP_PASSWORD=<passwort> \
npm run test:styleguide
```

Optional: `WP_STYLEGUIDE_PATH` (Standard `/styleguide/`).

## Architecture

### Directory Structure

```
src/                    # PHP source code
├── Acf/               # ACF: AcfExtended, FieldDefinitions, FlexibleContent, Fields, Options, PageSettings
├── PostTypes/         # Custom Post Types (AbstractPostType, Event, MemberDownload, Team, Testimonial)
├── Taxonomies/        # Custom Taxonomies (AbstractTaxonomy, DownloadCategory)
├── Providers/         # Service providers
├── Services/          # StyleguidePage.php, TabsContentMigration.php (one-time tab text move)
├── Content/           # Styleguide reference/data classes
├── Helpers/           # Text.php, SectionHeader.php, ComponentId.php (request-scoped ids + anchor slugs), FormAttributes.php (shared form-attribute allowlist), SectionNesting.php (nesting depth, no section chrome inside tab panels)
├── RateLimiter.php    # AJAX rate limiting
templates/             # Blade templates
├── layouts/          # Base layouts
├── partials/         # Reusable partials
├── components/       # Blade components
├── flexible/         # Flexible Content layouts (37 layouts)
├── styleguide/        # Styleguide page sections (components, tokens)
├── page-styleguide.blade.php # Styleguide page template
resources/
├── css/              # TailwindCSS + tokens.css
├── js/               # TypeScript + Alpine.js
config/
├── icons.json         # Single source of truth for theme icons
scripts/
├── sync-icons.js      # Generates resources/icons/ from config/icons.json
tests/
├── Unit/             # PHPUnit tests
├── e2e/              # Playwright E2E tests
# Vitest tests live next to the code: resources/js/*.test.ts, resources/js/admin/*.test.ts, scripts/transform-tokens.test.js
docs/                 # Documentation
├── ARCHITECTURE.md        # Service provider pattern
├── COMPONENT-DEVELOPMENT.md # Adding new Blade components
├── DEPLOYMENT.md          # Production deployment
├── SECURITY.md            # Security practices
├── SEO.md                 # SEO implementation
├── DESIGN-TOKENS.md       # Token system
├── DESIGN-TOKEN-GAPS.md   # Known token coverage gaps
```

### Key Technologies

- **Blade** (Laravel Illuminate v13) - Templates extend `layouts.app`. symfony/translation, clock and finder are pinned to ^7.4 because symfony 8 needs PHP >= 8.4.1 and production runs 8.3; lift the pin when the server moves to 8.4.
- **Alpine.js** (bundled, no CDN) - Interactive components
- **TailwindCSS v4.1** - Utility-first CSS
- **ACF Pro** - Flexible Content page builder
- **ACF Extended** (FREE) - Enhanced Flexible Content UX
- **Vite 8** - Asset compilation with HMR

## Wegweiser

Path-bound rules live in nested `CLAUDE.md` files next to the code (loaded when a file in that directory is read):

- `src/Acf/CLAUDE.md`: Flexible Content layouts and categories, field tabs, nested modules in tabs, ACF Extended, field definitions, theme options, adding a layout
- `templates/flexible/CLAUDE.md`: Flexible template pattern
- `src/Providers/CLAUDE.md`: Blade directives and escaping, logging, ACF textarea sanitizing, accepted kses `<form action>` risk (Audit Context)
- `src/PostTypes/CLAUDE.md`: custom post types
- `resources/js/CLAUDE.md`: Alpine.js components
- `resources/css/CLAUDE.md`: design tokens (`npm run tokens`)
- `DESIGN.md`: origin of the token values, the command chain, the mapping table
- `README.MD`, `docs/`: architecture, deployment, security, SEO, token system

## Plugin Management

Plugins are managed via **Composer** using [wpackagist.org](https://wpackagist.org).

**Install configured plugins:**

```bash
composer install
```

**Add a new plugin:**

```bash
composer require wpackagist-plugin/plugin-slug
```

Plugins are installed to `wp-content/plugins/` via `composer/installers`.

**Note:** ACF PRO is a premium plugin and must be installed manually.

## Git Commit Conventions

This project uses **Conventional Commits** with **Semantic Release** for automated versioning.

### Commit Message Format

```
<type>(<scope>): <description>

[optional body]

[optional footer(s)]
```

### Commit Types and Version Bumps

| Type       | Description                           | Version Bump  |
| ---------- | ------------------------------------- | ------------- |
| `feat`     | New feature                           | Minor (1.x.0) |
| `fix`      | Bug fix                               | Patch (1.0.x) |
| `perf`     | Performance improvement               | Patch         |
| `refactor` | Code refactoring (no feature change)  | Patch         |
| `style`    | Code style changes (formatting, etc.) | Patch         |
| `docs`     | Documentation only                    | No release    |
| `chore`    | Maintenance tasks                     | No release    |
| `ci`       | CI/CD changes                         | No release    |
| `test`     | Adding/updating tests                 | No release    |

### Breaking Changes → Major Version (x.0.0)

Add `!` after type or include `BREAKING CHANGE:` in footer:

```bash
feat!: redesign theme options API
# or
feat: redesign theme options

BREAKING CHANGE: Theme options structure changed
```

### Examples

```bash
# New feature → 1.1.0
git commit -m "feat: add pricing table layout"

# Bug fix → 1.0.1
git commit -m "fix: hero image not displaying on mobile"

# New feature with scope → 1.1.0
git commit -m "feat(acf): add video background option to hero"

# Breaking change → 2.0.0
git commit -m "feat!: change flexible content field structure"

# No release (docs only)
git commit -m "docs: update installation instructions"
```

### Automated Releases

On push to `master`:

1. CI runs all tests
2. Semantic Release analyzes commit messages
3. Version bumped in `package.json` and `style.css`
4. `CHANGELOG.md` updated automatically
5. GitHub Release created with tag

### Theme Updates

Users receive updates via WordPress Dashboard → Updates (powered by `ThemeUpdateProvider`).

## Rate Limiting

Protect AJAX handlers with transient-based rate limiting:

```php
use WordpressStarter\RateLimiter;

// Quick check (returns bool)
if (!RateLimiter::check('my_action', 10, 60)) {
    wp_send_json_error('Rate limit exceeded', 429);
}

// Or auto-send 429 response
RateLimiter::enforce('my_action', 10, 60);
```

## Important Notes

- Service providers are listed in `Application::registerProviders()` (`PluginServiceProvider` and `DesignTokenServiceProvider` only in wp-admin)
- ACF fields defined in PHP, not JSON (version control)
- Field labels/instructions in German
- Gutenberg is disabled - use Classic Editor
- Never edit `dist/` directly - always through Vite
- Clear `compiled/` if Blade cache issues
- Plugins managed via Composer (`wpackagist-plugin/*`)
- SVG uploads sanitized via `enshrined/svg-sanitize`
- AJAX handlers protected by rate limiting
- See `docs/` for detailed documentation
