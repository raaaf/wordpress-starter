# CLAUDE.md (resources/js)

Rules for the TypeScript and Alpine.js code.

## Alpine.js Components

Registered via `Alpine.data()` in `resources/js/app.ts`:

- `navigation` - Mobile menu with focus trap
- `statsCounter` - Animated number counters
- `beforeAfterSlider` - Image comparison slider, with an optional one-time intro sweep (`resources/js/motion.ts`)
- `themeSwitcher` - Light/dark/system switcher (`templates/partials/theme-switcher.blade.php`); reveals the new scheme as a circle via the View Transitions API (`resources/js/motion.ts`), instant with reduced motion
- `tokenPlayground` - Accent, radius and type-scale playground on the Design System page template (`resources/js/token-playground.ts`); writes CSS variables inline on `<html>`, stores nothing
- `tokenGenerator` - Public token generator on the Design System page template (`templates/styleguide/generator.blade.php`, anchor `#token-generator`, logic in `resources/js/token-generator.ts`, component in `resources/js/token-generator-component.ts`). Loads the site's own token files through the REST route of `DesignTokenRestProvider` (hosts block `config/` for the web), only on the first pointer or focus interaction, applies colours with the admin's logic (palettes 50 to 950, status colours with -light/-dark, alias updates), writes the changed primitives inline on `<html>` and offers the three JSON files, `tokens.css` and a ZIP (`resources/js/zip.ts`) as downloads. `tokens.css` comes from `scripts/tokens-core.js`, the same pure transform `npm run tokens` uses, loaded on the first CSS/ZIP download. Palette maths is pinned against `ColorPaletteGenerator` by `tests/fixtures/color-palette-parity.json` (Vitest + PHPUnit); regenerate the fixture from PHP when the PHP side changes. Beyond the admin it also moves the `color/ds/accent/*` roles with the accent (see `syncAccentRoles`)
- `memberLogin` - Member area login form
- `downloadTable` - Member area download table
- `styleguideSprungnavigation` - Styleguide jump navigation (active-section highlighting)
- `jumpMenu` - Jump menu module: floating pill with section list and in-page search, one per page; reveals hits inside tabs and accordions (`resources/js/jump-menu.ts`)
- `styleguideModul` - Switches between the instances of a styleguide gallery module

`memberLogin` and `downloadTable` are registered in `resources/js/member-area.ts` and wired in via `registerMemberAreaComponents(Alpine)` in `resources/js/app.ts`.

Components using inline `x-data` (not registered via `Alpine.data`): `tabs`, `accordion`, `footer-alert-bar` (`templates/partials/footer-alert-bar.blade.php`). The logo slider (`templates/flexible/logo-slider.blade.php`) uses inline `x-data` plus a CSS animation, pausing on hover and focus and honouring `prefers-reduced-motion`. The gallery uses medium-zoom directly, not Alpine.
