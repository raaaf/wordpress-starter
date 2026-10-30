# Child themes

The token generator on the design system page builds a child theme for this parent. The child carries no logic: its
`functions.php` only has the `strict_types` declaration and the `ABSPATH` guard. Everything it changes is listed in
`settings.json`, and the parent applies it (`Providers\ChildThemeServiceProvider`, reader `Support\ChildThemeSettings`).

A hand-made child theme without `settings.json` is left alone: no notice, no changes.

## Folder layout

```
wp-starter-child/
├── style.css            # Template: wp-starter, Update URI: false
├── functions.php        # empty apart from strict_types and the ABSPATH guard
├── settings.json
├── screenshot.png
└── assets/
    ├── tokens.css
    ├── tokens-editor.css
    ├── logo-light.svg|png
    ├── logo-dark.svg|png
    └── fonts/
        ├── *.woff2
        └── fonts.css
```

`Update URI: false` keeps WordPress from offering a wordpress.org theme with the same slug as an update.

## Contract v1 (`settings.json`)

```json
{
  "contract": 1,
  "build_id": "<uuid v4>",
  "generator": "<generator version>",
  "tokens": { "css": "assets/tokens.css", "editor_css": "assets/tokens-editor.css" },
  "fonts_css": "assets/fonts/fonts.css",
  "logo": { "light": "assets/logo-light.svg", "dark": "assets/logo-dark.png" },
  "buttons": "neutral"
}
```

- `fonts_css`, `logo.light` and `logo.dark` may be `null`. `buttons` is information only.
- The file is public like every theme file: no personal data in it.
- Paths are relative to the child folder, must stay inside it after `realpath()` and may only end in `css`, `svg`,
  `png` or `woff2` (stylesheets `css`, logos `svg` or `png`). A missing file drops its key without an error.

### Versions

- The contract is additive: new keys may appear, existing keys keep their meaning. Version 1 is never dropped.
- Missing or non-integer `contract`, or invalid JSON: the settings are ignored and administrators see a notice.
- A contract newer than `ChildThemeSettings::SUPPORTED_CONTRACT`: known keys apply, unknown keys are ignored, and
  administrators see a notice to update the parent.

## What the parent does in child mode

| Where      | What                                                                                                           |
| ---------- | -------------------------------------------------------------------------------------------------------------- |
| Frontend   | Enqueues `tokens.css` after `app-css` (its custom properties win) and `fonts.css`, versioned by `build_id`     |
| TinyMCE    | Appends `fonts.css` and `tokens-editor.css` to `mce_css` after the theme's editor stylesheet                   |
| `acf/init` | Imports the logos into the media library and the logo options, once per `build_id` and only into empty options |
| Backend    | The Design tokens page is read-only (no upload, restore or colour pickers) and links to the token generator    |

The logo import claims the option `{prefix}_child_import_<build_id>` before it runs, so parallel requests cannot import
twice and a failed import is not retried on every page view; a new build retries. A logo chosen in the backend is never
replaced. SVG logos go through `Support\SvgSanitizer` like uploads.

The token generator link points to `home_url('/design-system/#token-generator')`; the filter
`{prefix}_token_generator_url` changes it, for example to a central generator site.

Parent paths and versions always come from the parent (`get_template_directory()`, `wp_get_theme(get_template())`),
so assets, config and the update check never look into the child folder.

## Without ACF

Every Blade template reads ACF fields. While Advanced Custom Fields PRO is inactive the frontend answers with a plain
503 page (`config/acf-missing.php`) instead of failing, and the backend shows the missing plugin as a notice
(`PluginServiceProvider`). The filter `{prefix}_acf_available` overrides the check.
