# CLAUDE.md (src/Acf)

Rules for the ACF layer: Flexible Content layouts, field definitions, theme options.

## ACF Flexible Content

All pages use Flexible Content as the primary content builder. 37 layouts in `templates/flexible/`.

### Layout Categories (ACF Extended)

| Category         | Layouts                                                                                                                                                                           |
| ---------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Header           | hero                                                                                                                                                                              |
| Layout           | one-column, one-column-image, two-columns, three-columns, four-columns, one-third-two-thirds, two-thirds-one-third, two-columns-images, three-columns-images, four-columns-images |
| Inhalte          | accordion, tabs, cta, button, alert, quote                                                                                                                                        |
| Medien           | image, video, gallery, before-after                                                                                                                                               |
| Interaktiv       | testimonials, cards, stats, timeline, team, pricing-table, events                                                                                                                 |
| Formulare        | contact-form, map, newsletter                                                                                                                                                     |
| Beiträge         | posts, table                                                                                                                                                                      |
| Interner Bereich | member-downloads                                                                                                                                                                  |
| Sonstiges        | divider, logo-slider, embed                                                                                                                                                       |

### Field Tabs

`FlexibleContent::withTabs()` splits every layout's fields into two tabs,
**Inhalt** and **Darstellung**, at registration time. The split point is the
first field named `background_color`, `section_spacing`, `section_width` or
`section_anchor` (`FlexibleContent::DISPLAY_FIELDS`); everything from there on
is display.

The rule lives in one place instead of in ~30 field builders. Field order and
field names are untouched, only two markers are inserted.

Skipped, on purpose:

- Layouts that already group themselves with their own tabs or accordions
  (hero, posts, map, contact-form, the three `*-columns-images`). Hero,
  contact-form, map and posts name their display tab "Darstellung" too and
  carry their own Inhalt tab; contact-form and map add one extra leading
  tab ("Formular", "Karte"), posts has a middle "Anzeige" tab between Inhalt
  and Darstellung, hero has none. The three
  `*-columns-images` layouts use accordions and have no Darstellung tab.
- Layouts with fewer than three fields on the content side, where a tab would
  sit above a single control (divider), or none at all because the layout _is_
  the display tail (member-downloads).
- Layouts without a display tail at all, so there is nothing to separate (in
  some themes cta and button).

### Nested modules in tabs

- Each tab row holds a `modules` flexible field (`FlexibleContent::NESTED_MODULES_KEY`), built from the page layouts minus `NESTED_EXCLUDED_LAYOUTS` (hero, tabs, divider, map, contact_form, newsletter, logo_slider). A denylist, so layouts added via the `_flexible_content_layouts` filter are offered too.
- The nested copies get rewritten ACF keys (`tabsnested_` inserted after the first `_`), no display fields (background, spacing, width, anchor), a `left` default for `section_alignment`, and no empty tabs.
- `tabs.blade.php` renders them through the normal `flexible.*` templates inside `SectionNesting::enter()/leave()`; `x-section` then outputs a plain `section-nested` div instead of the section chrome.
- The per-tab text field is gone. `Services/TabsContentMigration` moves legacy tab text once into a leading `one_column` module (on `init`, behind an option lock, flag option `*_tabs_content_migrated`).
- Both flexible fields use ACFE async layouts (`acfe_flexible_async`), and the member_downloads visibility filter covers both.

### Background Colors

All layouts support: `primary`, `secondary`, `tertiary`, `brand`, `brand-subtle`, `inverse`

## ACF Extended Features

ACF Extended (FREE) enhances the editing experience:

- **Modal Selection** - Choose layouts in visual grid modal
- **Modal Edit** - Edit layouts in large modal
- **Copy/Paste** - Copy layouts between pages
- **Layout Categories** - Organized layout picker
- **Layout Thumbnails** - Visual previews in `resources/images/layouts/` (all 37 layouts), wired via `acfe_flexible_thumbnail`, generated by `scripts/generate-layout-thumbnails.php`

Configuration in `src/Acf/AcfExtended.php`.

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

Available under "Theme-Einstellungen" in admin (`src/Acf/Options.php`):

| Sub page         | Content                                                                                               | Capability           |
| ---------------- | ----------------------------------------------------------------------------------------------------- | -------------------- |
| Allgemein        | Logo, Favicon, contact info                                                                           | `edit_theme_options` |
| Blog             | Blog listing settings                                                                                 | `edit_theme_options` |
| Header           | Sticky header, CTA button                                                                             | `edit_theme_options` |
| Footer           | Footer text, copyright, alert bar (Hinweisleiste)                                                     | `edit_theme_options` |
| Social Media     | Social links repeater                                                                                 | `edit_theme_options` |
| Interner Bereich | Member-area auth mode, shared password for protected downloads (conditional on `member_area.enabled`) | `manage_options`     |
| Analytics        | Rybbit Analytics (DSGVO-konform, via Plugin)                                                          | `manage_options`     |
| Werkzeuge        | Maintenance tools                                                                                     | `manage_options`     |
| Design Tokens    | Design token overrides                                                                                | `manage_options`     |

Administrators can reach all nine sub pages. Editors reach none of them by default: WordPress grants `edit_theme_options` and `manage_options` to Administrators only. If Editors should manage the five content pages (Allgemein, Blog, Header, Footer, Social Media), switching those to `edit_pages` is a product decision, not a default.

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
        'acfe_flexible_category' => self::getCategories()['content'],
    ];
}
```

2. Add field definitions in `src/Acf/FieldDefinitions.php`

3. Create template `templates/flexible/{name-with-hyphens}.blade.php` (underscores in the layout name become hyphens, so `my_new` is `my-new.blade.php`; a mismatched filename is skipped silently by `@includeIf`)

4. Register layout in `getLayouts()` array
