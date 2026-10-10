# CLAUDE.md (resources/js)

Rules for the TypeScript and Alpine.js code.

## Alpine.js Components

Registered via `Alpine.data()` in `resources/js/app.ts`:

- `navigation` - Mobile menu with focus trap
- `statsCounter` - Animated number counters
- `beforeAfterSlider` - Image comparison slider
- `memberLogin` - Member area login form
- `downloadTable` - Member area download table
- `styleguideSprungnavigation` - Styleguide jump navigation (active-section highlighting)
- `jumpMenu` - Jump menu module: floating pill with section list and in-page search, one per page; reveals hits inside tabs and accordions (`resources/js/jump-menu.ts`)
- `styleguideModul` - Switches between the instances of a styleguide gallery module

`memberLogin` and `downloadTable` are registered in `resources/js/member-area.ts` and wired in via `registerMemberAreaComponents(Alpine)` in `resources/js/app.ts`.

Components using inline `x-data` (not registered via `Alpine.data`): `tabs`, `accordion`, `theme-switcher` (`templates/partials/theme-switcher.blade.php`), `footer-alert-bar` (`templates/partials/footer-alert-bar.blade.php`). The logo slider (`templates/flexible/logo-slider.blade.php`) uses inline `x-data` plus a CSS animation, pausing on hover and focus and honouring `prefers-reduced-motion`. The gallery uses medium-zoom directly, not Alpine.
