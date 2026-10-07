# CLAUDE.md (src/Providers)

Rules for the service providers: Blade directives and escaping, logging, ACF sanitizing.

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

**Groups:**

- `@group('name')...@endgroup` - Conditional block around an ACF group field
- `@kses(...)` - Sanitize HTML via `wp_kses_post()`

**Escaping (`{{ }}`):**

`{{ }}` matches WordPress `esc_html()` semantics: it escapes `<>"'&`, but does not double-encode a value that already contains valid entities (`BladeServiceProvider::boot()` calls `$compiler->withoutDoubleEncoding()`). Pass raw values into `{{ }}`, never pre-escaped ones. A value already run through `esc_html()`/`wp_kses_post()` before reaching the view keeps its entities as entities instead of being re-encoded, and a raw `<` or `&` in the value is still escaped exactly once, as expected. Covered by `tests/Unit/Providers/BladeServiceProviderTest.php`.

## Logging

Use `LogServiceProvider` for structured logging (writes to `wp-content/debug.log`):

```php
use WordpressStarter\Providers\LogServiceProvider;

LogServiceProvider::info('User logged in', ['user_id' => $userId]);
LogServiceProvider::error('Payment failed', ['order_id' => $orderId]);
LogServiceProvider::exception($e);
```

## Audit Context

**kses `<form action>` allowlist (`AcfServiceProvider::allowFormControlTags()`):** `action` bleibt bewusst im Allowlist für `<form>` in Post-Kontext, überlebt also `wp_kses_post()`. Grund: ohne `action` rendert ein Contact-Form-7-Shortcode in einem WYSIWYG-Feld als kaputtes halbes Formular (Labels und ein Textarea, aber keine Inputs, kein `<form>`-Element).

Akzeptiertes Risiko, unbeschönigt: jede Rolle, die Content bearbeiten darf, kann damit ein Formular veröffentlichen, das an eine beliebige externe URL sendet. Auf Multisite ist das weiter gefasst als auf einer Einzelseite, da dort selbst Administratoren kein `unfiltered_html` haben.

Das ist eine getroffene Entscheidung, kein Defekt. Ein künftiger Audit soll das nicht erneut als Sicherheitslücke melden und entfernen. Soll sich das ändern, ist das eine Produktentscheidung, kein Audit-Fix.

## ACF Textarea Sanitizing

- ACF textarea values are tag-stripped on save (`AcfServiceProvider::sanitizeTextarea`), except table cells (`*_cell_content`), which go through `wp_kses_post` minus form controls (form, input, select, option, optgroup, textarea, button). Pinned by `TextareaSanitizeTest`.
