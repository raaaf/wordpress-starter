# CLAUDE.md (resources/js)

Rules for the TypeScript and Alpine.js code.

## Alpine.js Components

Registered via `Alpine.data()` in `resources/js/app.ts`:

- `navigation` - Mobile menu with focus trap
- `statsCounter` - Animated number counters
- `beforeAfterSlider` - Image comparison slider, with an optional one-time intro sweep (`resources/js/motion.ts`)
- `themeSwitcher` - Light/dark/system switcher (`templates/partials/theme-switcher.blade.php`); reveals the new scheme as a circle via the View Transitions API (`resources/js/motion.ts`), instant with reduced motion
- `tokenPlayground` - The "Dein Look" control panel of the Design System page template (off-canvas drawer from the right on `lg`+, closed by default, no backdrop or scroll lock; bottom sheet below `lg`; the page's Tokens and Components sections are the live preview) (`resources/js/token-playground.ts`); writes CSS variables inline on `<html>`, stores nothing. The font row needs a plugin answering the filter `wp_starter_styleguide_font_pairings` (validated by `FontPairings`); the generator button in the panel carries the full look once the visitor changed anything (`generatorUrl`); `panelOpen` is the drawer/sheet state (`open(trigger)` remembers the opener, `close()` returns focus to it); `changed` (`isLookChanged`) drives the dot on the floating button
- `memberLogin` - Member area login form
- `downloadTable` - Member area download table
- `styleguideSprungnavigation` - Styleguide jump navigation (active-section highlighting)
- `jumpMenu` - Jump menu module: floating pill with section list and in-page search, one per page; reveals hits inside tabs and accordions (`resources/js/jump-menu.ts`)
- `styleguideModul` - Switches between the instances of a styleguide gallery module

`memberLogin` and `downloadTable` are registered in `resources/js/member-area.ts` and wired in via `registerMemberAreaComponents(Alpine)` in `resources/js/app.ts`.

Components using inline `x-data` (not registered via `Alpine.data`): `tabs`, `accordion`, `footer-alert-bar` (`templates/partials/footer-alert-bar.blade.php`). The logo slider (`templates/flexible/logo-slider.blade.php`) uses inline `x-data` plus a CSS animation, pausing on hover and focus and honouring `prefers-reduced-motion`. The gallery uses medium-zoom directly, not Alpine.
