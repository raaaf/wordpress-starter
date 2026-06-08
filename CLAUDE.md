# CLAUDE.md

Guidance for Claude Code when working with this WordPress starter theme.

## Quick Reference

- **Namespace:** `WordpressStarter\`
- **Text Domain:** `wp-starter`
- **PHP:** 8.4+ with strict types
- **Dev Server:** `npm run dev` (localhost:5180)
- **Editor:** Classic Editor + ACF Flexible Content (Gutenberg disabled)

## Essential Commands

```bash
npm run dev        # Development with HMR
npm run build      # Production build
npm run lint       # JS/TS linting
npm run test:e2e   # Playwright E2E tests
npm run test:a11y  # Accessibility tests
composer lint      # PHP linting (phpcs + phpstan)
composer test      # PHPUnit tests
```

## Architecture

### Directory Structure

```
src/                    # PHP source code
├── Acf/               # ACF: FlexibleContent, Fields, Options
├── PostTypes/         # Custom Post Types (AbstractPostType, Testimonial)
├── Taxonomies/        # Custom Taxonomies (AbstractTaxonomy)
├── Providers/         # Service providers
├── RateLimiter.php    # AJAX rate limiting
templates/             # Blade templates
├── layouts/          # Base layouts
├── partials/         # Reusable partials
├── components/       # Blade components
├── flexible/         # Flexible Content layouts (28 layouts)
resources/
├── css/              # TailwindCSS + tokens.css
├── js/               # TypeScript + Alpine.js
tests/
├── Unit/             # PHPUnit tests
├── js/               # Vitest tests
├── e2e/              # Playwright E2E tests
docs/                 # Documentation
├── ARCHITECTURE.md   # Service provider pattern
├── DEPLOYMENT.md     # Production deployment
├── SECURITY.md       # Security practices
├── SEO.md            # SEO implementation
```

### Key Technologies

- **Blade** (Laravel Illuminate v13) - Templates extend `layouts.app`
- **Alpine.js** (bundled, no CDN) - Interactive components
- **TailwindCSS v4.1** - Utility-first CSS
- **ACF Pro** - Flexible Content page builder
- **ACF Extended** (FREE) - Enhanced Flexible Content UX
- **Vite 7.3** - Asset compilation with HMR

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

## Design Tokens

Auto-generated from Figma in `resources/css/tokens.css`. See [docs/DESIGN-TOKENS.md](docs/DESIGN-TOKENS.md) for full documentation.

**Update tokens:**

```bash
# Export from Figma → config/design-tokens/*.tokens.json
npm run tokens        # Generate CSS
npm run tokens:watch  # Watch mode
```

**Semantic tokens:** `--bg-*`, `--text-*`, `--border-*`, `--icon-*`

Usage:

```css
background: var(--bg-primary);
color: var(--text-primary);
```

## ACF Flexible Content

All pages use Flexible Content as the primary content builder. 28 layouts in `templates/flexible/`.

### Layout Categories (ACF Extended)

| Category   | Layouts                                                                                                              |
| ---------- | -------------------------------------------------------------------------------------------------------------------- |
| Header     | hero                                                                                                                 |
| Layout     | one-column, two-columns, three-columns, four-columns, one-third-two-thirds, two-thirds-one-third, two-columns-images |
| Inhalte    | accordion, tabs, cta, button                                                                                         |
| Medien     | image, video, gallery, before-after                                                                                  |
| Interaktiv | testimonials, cards, stats, timeline, team, pricing-table                                                            |
| Formulare  | contact-form, map                                                                                                    |
| Beiträge   | posts, table                                                                                                         |
| Sonstiges  | divider, logo-slider                                                                                                 |

### Flexible Template Pattern

```blade
{{-- templates/flexible/example.blade.php --}}
@php
    $title = get_sub_field('title');
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

<x-section :background="$background">
    @if($title)
        <h2>{{ $title }}</h2>
    @endif
</x-section>
```

### Background Colors

All layouts support: `primary`, `secondary`, `tertiary`, `brand`, `brand-subtle`, `inverse`

## ACF Extended Features

ACF Extended (FREE) enhances the editing experience:

- **Modal Selection** - Choose layouts in visual grid modal
- **Modal Edit** - Edit layouts in large modal
- **Copy/Paste** - Copy layouts between pages
- **Layout Categories** - Organized layout picker
- **Layout Thumbnails** - Optional visual previews in `resources/images/layouts/`

Configuration in `src/Acf/AcfExtended.php`.

## Blade Directives

**ACF Fields:**

- `@field('name')` - Escaped field
- `@fieldRaw('name')` - HTML field (wp_kses_post)
- `@option('name')` - Theme option (cached)
- `@optionRaw('name')` - HTML option

**Conditionals:**

- `@hasfield('name')...@endhasfield`
- `@repeater('name')...@endrepeater`

**Flexible Content:**

- `@flexible('field_name')...@endflexible`
- `@layout('layout_name')...@endlayout`

## ACF Field Definitions

Single source of truth in `src/Acf/FieldDefinitions.php`:

```php
use WordpressStarter\Acf\FieldDefinitions;

FieldDefinitions::textField('key', 'Label', 'name', $required);
FieldDefinitions::wysiwygField('key', 'Label', 'name');
FieldDefinitions::imageField('key', 'Label', 'name');
FieldDefinitions::linkField('key', 'Label', 'name');
FieldDefinitions::backgroundColorField('prefix');
FieldDefinitions::repeaterField('key', 'Label', 'name', $subFields);
```

## Theme Options

Available under "Theme-Einstellungen" in admin:

- **Allgemein:** Logo, Favicon, Contact info
- **Header:** Sticky header, CTA button
- **Footer:** Footer text, copyright, alert bar (Hinweisleiste)
- **Social Media:** Social links repeater
- **Analytics:** Rybbit Analytics (DSGVO-konform, via Plugin)
- **Rechtliches:** Privacy, Imprint pages

## Alpine.js Components

Defined in `resources/js/app.ts`:

- `navigation` - Mobile menu with focus trap
- `statsCounter` - Animated number counters
- `tabs` - Tab navigation
- `accordion` - Expandable content
- `gallery` - Medium-zoom lightbox
- `logoSlider` - Partner logo carousel
- `beforeAfter` - Image comparison slider

## Adding New Layouts

1. Add layout method in `src/Acf/FlexibleContent.php`:

```php
private static function myNewLayout(): array
{
    return [
        'key' => 'layout_my_new',
        'name' => 'my_new',
        'label' => 'Mein neues Layout',
        'display' => 'block',
        'sub_fields' => FieldDefinitions::myNewFields('flex_my_new'),
        'acfe_flexible_category' => self::CATEGORIES['content'],
    ];
}
```

2. Add field definitions in `src/Acf/FieldDefinitions.php`

3. Create template `templates/flexible/my-new.blade.php`

4. Register layout in `getLayouts()` array

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

## Custom Post Types

Use the abstract base class for consistent CPT registration:

```php
<?php

namespace WordpressStarter\PostTypes;

class Service extends AbstractPostType
{
    protected static string $postType = 'service';
    protected static string $singular = 'Leistung';
    protected static string $plural = 'Leistungen';
    protected static string $menuIcon = 'dashicons-admin-generic';

    public static function registerFields(): void
    {
        // Register ACF fields for this CPT
    }
}
```

Register in `PostTypeServiceProvider::boot()`:

```php
Service::register();
```

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

## Logging

Use `LogServiceProvider` for structured logging (writes to `wp-content/debug.log`):

```php
use WordpressStarter\Providers\LogServiceProvider;

LogServiceProvider::info('User logged in', ['user_id' => $userId]);
LogServiceProvider::error('Payment failed', ['order_id' => $orderId]);
LogServiceProvider::exception($e);
```

## Important Notes

- All service providers in `src/Providers/` auto-registered
- ACF fields defined in PHP, not JSON (version control)
- Field labels/instructions in German
- Gutenberg is disabled - use Classic Editor
- Never edit `dist/` directly - always through Vite
- Clear `compiled/` if Blade cache issues
- Plugins managed via Composer (`wpackagist-plugin/*`)
- SVG uploads sanitized via `enshrined/svg-sanitize`
- AJAX handlers protected by rate limiting
- See `docs/` for detailed documentation
