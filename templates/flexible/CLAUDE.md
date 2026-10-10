# CLAUDE.md (templates/flexible)

Rules for the Flexible Content templates.

## Flexible Template Pattern

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

## Jump menu (Sprungmenü)

Every module has the display field `show_in_jump_menu` (default on). The page loops (`partials/page-sections-loop.blade.php`, `page-styleguide.blade.php`) store it per row via `JumpMenu::setCurrentHidden()`, and `components/section.blade.php` renders `data-jump-menu="hidden"` for an opted-out module.

The `jump_menu` layout (`jump-menu.blade.php`, `resources/js/jump-menu.ts`) renders no section and nothing in the page flow: only a floating pill button (teleported to `<body>`, z-40) that opens a panel with the section list and an optional search. Fields: `title`, `show_search`, `position` (`bottom_right` default, `bottom_left`, `top_right`, `top_left`; the top corners sit below the header, and `jump-menu.ts` publishes `--jump-menu-offset` so `.section[id]` scroll margins clear the pill). One per page: layout `max` is 1 and only the first in DOM order shows a button. The panel markup lives in `partials/jump-menu-panel.blade.php`.
