# CLAUDE.md

Guidance for Claude Code when working with this WordPress starter theme.

## Quick Reference

- **Namespace:** `WordpressStarter\`
- **Text Domain:** `wp-starter`
- **PHP:** 8.4+ with strict types
- **Dev Server:** `npm run dev` (localhost:5173)
- **Editor:** Classic Editor + ACF Flexible Content (Gutenberg disabled)

## Essential Commands

```bash
npm run dev       # Development with HMR
npm run build     # Production build
npm run lint      # JS/TS linting
composer lint     # PHP linting (phpcs + phpstan)
```

## Architecture

### Directory Structure
```
src/                    # PHP source code
├── Acf/               # ACF: FlexibleContent, Fields, Options
├── Providers/         # Service providers
templates/             # Blade templates
├── layouts/          # Base layouts
├── partials/         # Reusable partials
├── components/       # Blade components
├── flexible/         # Flexible Content layouts (28 layouts)
resources/
├── css/              # TailwindCSS + tokens.css
├── js/               # TypeScript + Alpine.js
```

### Key Technologies
- **Blade** (Laravel Illuminate v12) - Templates extend `layouts.app`
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
background: var(--bg-surface);
color: var(--text-content);
```

## ACF Flexible Content

All pages use Flexible Content as the primary content builder. 28 layouts in `templates/flexible/`.

### Layout Categories (ACF Extended)
| Category | Layouts |
|----------|---------|
| Header | hero |
| Layout | one-column, two-columns, three-columns, four-columns, one-third-two-thirds, two-thirds-one-third, two-columns-images |
| Inhalte | accordion, tabs, cta, button |
| Medien | image, video, gallery, before-after |
| Interaktiv | testimonials, cards, stats, timeline, team, pricing-table |
| Formulare | contact-form, map |
| Beiträge | posts, table |
| Sonstiges | divider, logo-slider |

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
- **Footer:** Footer text, copyright
- **Social Media:** Social links repeater
- **Analytics:** Pirsch Analytics (DSGVO-konform)
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

## Important Notes

- All service providers in `src/Providers/` auto-registered
- ACF fields defined in PHP, not JSON (version control)
- Field labels/instructions in German
- Gutenberg is disabled - use Classic Editor
- Never edit `dist/` directly - always through Vite
- Clear `compiled/` if Blade cache issues
