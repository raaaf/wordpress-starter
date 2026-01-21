# CLAUDE.md

Guidance for Claude Code when working with this WordPress starter theme.

## Quick Reference

- **Namespace:** `WordpressStarter\`
- **Text Domain:** `wp-starter`
- **PHP:** 8.4+ with strict types
- **Dev Server:** `npm run dev` (localhost:5173)

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
├── Acf/               # ACF: Blocks, Fields, Options
├── Providers/         # Service providers
blocks/                # ACF Gutenberg blocks (28 blocks)
templates/             # Blade templates
├── layouts/          # Base layouts
├── partials/         # Reusable partials
├── components/       # Blade components
resources/
├── css/              # TailwindCSS + tokens.css
├── js/               # TypeScript + Alpine.js
```

### Key Technologies
- **Blade** (Laravel Illuminate v12) - Templates extend `layouts.app`
- **Alpine.js** (bundled, no CDN) - Interactive components
- **TailwindCSS v4.1** - Utility-first CSS
- **ACF Pro** - Custom blocks and fields
- **Vite 7.3** - Asset compilation with HMR

## Design Tokens

Auto-generated from Figma in `resources/css/tokens.css`:
- **Light/Dark Mode** via `data-theme="dark"` or `prefers-color-scheme`
- **Semantic tokens:** `--bg-*`, `--text-*`, `--border-*`, `--icon-*`
- **Component tokens:** buttons, inputs, badges, cards, typography

Usage:
```css
background: var(--bg-primary);
color: var(--text-secondary);
```

## ACF Blocks

28 blocks in `blocks/` directory. Each block has:
- `block.json` - Configuration
- `template.blade.php` - Blade template

### Block Template Pattern
```blade
@php
    $title = $fields['title'] ?? '';
    $bgColor = $fields['background_color'] ?? 'primary';
@endphp

<div {!! $wrapper_attributes !!}>
    <h2>{{ $title }}</h2>
</div>
```

### Background Colors
All blocks support: `primary`, `secondary`, `tertiary`, `brand`, `brand-subtle`, `inverse`

### InnerBlocks
```blade
@innerblocks(['allowedBlocks' => ['core/paragraph', 'core/heading']])
```

## Blade Directives

**ACF Fields:**
- `@field('name')` - Escaped field
- `@fieldRaw('name')` - HTML field (wp_kses_post)
- `@option('name')` - Theme option (cached)
- `@optionRaw('name')` - HTML option

**Conditionals:**
- `@hasfield('name')...@endhasfield`
- `@repeater('name')...@endrepeater`

**Blocks:**
- `@innerblocks` / `@innerblocks($options)`
- `@blockwrapper($block)`

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

## Adding New Blocks

1. Create `blocks/block-name/block.json`:
```json
{
    "name": "acf/block-name",
    "title": "Block Title",
    "description": "Description",
    "category": "theme-blocks",
    "icon": "dashicons-icon",
    "supports": {
        "align": ["wide", "full"],
        "anchor": true
    },
    "acf": {
        "mode": "preview",
        "renderTemplate": "blocks/block-name/template.blade.php"
    }
}
```

2. Create `blocks/block-name/template.blade.php`
3. Add fields in `src/Acf/BlockFields.php`

## Plugin Dependencies

Blocks can require plugins via `block.json`:
```json
{
    "requires": ["contact-form-7"]
}
```

## Important Notes

- All service providers in `src/Providers/` auto-registered
- ACF fields defined in PHP, not JSON (version control)
- Block labels/instructions in German
- Use `$wrapper_attributes` in block templates for Gutenberg integration
- Never edit `dist/` directly - always through Vite
- Clear `compiled/` if Blade cache issues
