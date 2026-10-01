# Security

This document describes the security measures implemented in the WordPress Starter Theme.

## Content Security Policy (CSP)

The theme sends a deliberately small CSP. The full header value is exactly:

```
frame-ancestors 'self'; base-uri 'self'; object-src 'none'
```

Built in `Security::getCSPHeader()` in `src/Security.php`, pinned by `SecurityTest::testGetCSPHeaderIsExactlyTheThreeNonBreakingDirectives`.

- `frame-ancestors 'self'`: no foreign site can frame the pages (clickjacking).
- `base-uri 'self'`: an injected `<base>` tag cannot rebase relative URLs.
- `object-src 'none'`: no plugins or `<object>`/`<embed>` content.

Nothing in the CSP restricts scripts, styles, images, fonts, connections, frames or form targets. A stricter policy (`script-src`, `frame-src`, `form-action` with a provider list) broke plugins, embeds and newsletter forms too often, so it was dropped. The protection against injected content comes from other layers: kses and the `unfiltered_html` save-context gate in `AcfServiceProvider::allowFormControlTags` (a user without that capability cannot store form controls), SVG sanitizing (below) and the hardening headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`).

The CSP is only sent for frontend requests and only when `config('security.enable_csp')` is true.

### Embeds and Newsletter

`Security::isAllowedEmbedHost()` still decides which iframes the embed and map layouts render (`HARDCODED_FRAME_SRC_HOSTS` plus the admin option `embed_allowed_hosts`). The newsletter layout and its ACF validator (`validateNewsletterActionUrl`) accept any `https://` form action.

### Nonce

`Security::getNonce()` creates a per-request nonce, exposed as `$GLOBALS['csp_nonce']`. It is added to registered, inline and printed scripts and can be used in templates:

```blade
<script nonce="{{ $GLOBALS['csp_nonce'] }}">
    // Your code here
</script>
```

The CSP no longer references it, so it has no security effect today.

### Customizing CSP

Edit `Security::getCSPHeader()` in `src/Security.php`. Keep every addition non-breaking: a new restricting directive needs a check against all active plugins and embeds first.

## SVG Sanitization

SVG uploads are sanitized using the `enshrined/svg-sanitize` library.

### What's Removed

- `<script>` tags
- Event handlers (`onclick`, `onload`, etc.)
- `javascript:` URLs
- External entity references
- Foreign objects
- External references (optional)

### Upload Restrictions

- Only administrators (`manage_options`) can upload SVGs
- The `svg` mime type is only registered for administrators; server-initiated imports without a logged-in user (WP-CLI, cron) skip the capability check but are still sanitized
- An upload counts as SVG by its file extension (`.svg`, case-insensitive), never by the client-supplied type, which an uploader controls
- `.svgz` is not allowed: it is not in `upload_mimes`, and gzipped SVGs cannot be sanitized
- Sanitization runs on regular uploads and sideloads (`wp_handle_upload_prefilter`, `wp_handle_sideload_prefilter`) before the file is saved; a file that cannot be read, sanitized or written back is rejected (fails closed)

### Implementation

```php
// In MediaServiceProvider.php
private function sanitizeSvg(string $content): string|false
{
    $sanitizer = new \enshrined\svgSanitize\Sanitizer();
    $sanitizer->removeRemoteReferences(true);
    $sanitizer->removeXMLTag(false); // Keep the XML declaration
    // false propagates to the upload prefilter, which rejects the file
    return $sanitizer->sanitize($content);
}
```

## AJAX Rate Limiting

AJAX handlers are protected against abuse with transient-based rate limiting.

### Usage

```php
// In your AJAX handler
\WordpressStarter\RateLimiter::enforce('my_action', 10, 60);
// Allows 10 requests per 60 seconds
```

### How It Works

1. Tracks requests per user (by ID) or IP (hashed for privacy)
2. Uses WordPress transients for storage
3. Automatically expires old rate limit windows
4. Returns 429 Too Many Requests when exceeded

### Default Limits

| Endpoint            | Limit  | Window |
| ------------------- | ------ | ------ |
| Plugin install      | 20/min | 60s    |
| Bulk plugin install | 5/min  | 60s    |

## Input Validation

### ACF Field Sanitization

ACF fields are sanitized on save (`acf/update_value`) and validated before save (`acf/validate_value`):

```php
// In AcfServiceProvider.php
add_filter('acf/update_value/type=text', function ($value) {
    return sanitize_text_field($value);
}, 10, 1);

add_filter('acf/update_value/type=textarea', [self::class, 'sanitizeTextarea'], 10, 3);

add_filter('acf/validate_value/type=url', [self::class, 'validateUrl'], 10, 2);
add_filter('acf/validate_value/type=email', [self::class, 'validateEmail'], 10, 2);
```

### Sanitization Functions Used

| Field Type     | Function                                                                                                                                                               |
| -------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Text           | `sanitize_text_field()`                                                                                                                                                |
| Textarea       | `sanitize_textarea_field()`; table cells (`*_cell_content`) use `wp_kses_post()` minus form controls (form, input, select, option, optgroup, textarea, button) instead |
| HTML (WYSIWYG) | `wp_kses_post()`                                                                                                                                                       |

### Validators

URL and email fields are validators, not sanitizers: an invalid value blocks the save with an error message and nothing is rewritten or blanked.

| Field Type | Hook                                                                                   |
| ---------- | -------------------------------------------------------------------------------------- |
| URL        | `acf/validate_value/type=url` (`validateUrl`)                                          |
| Email      | `acf/validate_value/type=email` (`validateEmail`)                                      |
| Newsletter | `acf/validate_value/key=field_flex_newsletter_action_url` (empty or an `https://` URL) |

### Member Area Shared Password

`Acf::registerPasswordHashing()` (`src/MemberArea/Acf.php`) validates the shared-password field on save via `acf/validate_value/key=field_member_shared_password`, rejecting `<`, `>`, `&` and leading/trailing whitespace before the value is hashed with `wp_hash_password()`. This keeps the hashed value consistent with what `Auth::getSharedPassword()` receives at login: without the check, users without `unfiltered_html` have their input run through `wp_kses_post_deep` on the ACF save path (encoding `&` to `&amp;` and stripping `<`/`>`) before hashing, while the login form submits the raw value, so the hash would never match.

### Seitenpasswort und Mitgliederbereich

Ein WordPress-Seitenpasswort auf einer Mitgliederbereich-Seite ist nur eine
Anzeigesperre für diese eine Seite. Die Download-Endpunkte prüfen dagegen
gegen den Mitgliederbereich-Login (`Auth`/`Access`), nicht gegen das
Seitenpasswort. Empfohlenes Setup ist ausschließlich der Mitgliederbereich-Login,
ohne zusätzliches Seitenpasswort (Entscheidung 2026-09-05).

## Nonce Verification

All state-changing actions verify WordPress nonces:

```php
public function ajaxHandler(): void
{
    check_ajax_referer('my_action_nonce', 'nonce');
    // ... handle request
}
```

### Blade Form Example

```blade
<form method="post">
    @php wp_nonce_field('my_action', 'my_nonce'); @endphp
    <!-- form fields -->
</form>
```

## Capability Checks

Actions are restricted to appropriate user roles:

```php
if (!current_user_can('manage_options')) {
    wp_die(__('No permission.', 'wp-starter'));
}
```

### Capability Usage

| Action         | Required Capability |
| -------------- | ------------------- |
| Theme options  | `manage_options`    |
| Plugin install | `install_plugins`   |
| SVG upload     | `manage_options`    |
| Content edit   | `edit_posts`        |

Exception: the `member_download` post type stores SFTP credentials, so it sets
`AbstractPostType::$requiredCapability = 'manage_options'`. Every capability of that
post type (edit, delete, publish, read) then maps to `manage_options` instead of
`edit_posts`. Set the same property on any new post type that stores sensitive data.

## REST API Security

### Endpoint Protection

```php
register_rest_route('theme/v1', '/options', [
    'permission_callback' => function () {
        return current_user_can('manage_options');
    },
]);
```

### Sensitive Data Filtering

```php
// Filter out sensitive fields from REST responses
$filtered = array_filter($options, function ($key) {
    return !str_starts_with($key, 'analytics_') &&
           !str_starts_with($key, 'api_');
}, ARRAY_FILTER_USE_KEY);
```

## Security Headers

Additional security headers sent with responses, defined in `Security::getHardeningHeaders()` and sent on `send_headers`:

```php
'X-Content-Type-Options' => 'nosniff',
'X-Frame-Options' => 'SAMEORIGIN',
'Referrer-Policy' => 'strict-origin-when-cross-origin',
'Permissions-Policy' => 'geolocation=(), camera=(), microphone=(), payment=()',
```

They are skipped for admin and AJAX requests.

`Strict-Transport-Security` is set separately, in `SecurityServiceProvider::boot()`:

```php
// In SecurityServiceProvider.php
header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
```

Only sent on HTTPS frontend requests (skipped for admin and AJAX requests, and
whenever the request is not HTTPS), so it never interferes with a non-HTTPS
staging login.

## Database Security

### Prepared Statements

Always use prepared statements for custom queries:

```php
global $wpdb;
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_type = %s",
        $post_type
    )
);
```

### Escaping Output

```php
// In PHP
echo esc_html($user_input);
echo esc_attr($attribute);
echo esc_url($url);

// In Blade
{{ $variable }}      // Auto-escaped
{!! $trusted_html !!}  // Raw output (use carefully)
```

`phpcs.xml` excludes `*.blade.php` because Blade syntax cannot be parsed by phpcs, so the
WordPress.Security escaping sniffs never run against `templates/`. Escaping in Blade
templates is instead verified by `tests/Unit/TemplateRenderTest.php` (a hostile-payload
render pass across all templates) and by the `@kses` Blade directive, not by phpcs.

## File Security

### Sensitive Files

These files should not be web-accessible:

- `.env`
- `composer.json` / `composer.lock`
- `package.json` / `package-lock.json`
- `phpunit.xml`
- `phpstan.neon`

### .htaccess Protection

```apache
<FilesMatch "^(\.env|composer\.(json|lock)|package(-lock)?\.json|phpunit\.xml|phpstan\.neon)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

## Update Integrity

Theme updates come through `ThemeUpdateProvider` (`src/Providers/ThemeUpdateProvider.php`)
via plugin-update-checker from GitHub releases. The release pipeline
(`.github/workflows/release.yml`) stores a `.sha256` file next to the release zip
as a second release asset (`shasum -a 256` output).

Before installation, `ThemeUpdateProvider::verifyPackageChecksum()` hooks into the
WordPress core filter `upgrader_pre_download`, downloads the zip itself and
compares its hash against the `.sha256` file of the same release. If the checksum
does not match, the installation is aborted with a `WP_Error`. If the `.sha256`
file is missing (older releases from before this change), the update is not
blocked but logged as a warning via `LogServiceProvider::warning()`.

## Contact Form Spam Protection

Contact Form 7 submissions pass through server-side heuristics registered in
`src/PluginConfigurators/ContactForm7Configurator.php`. No third-party service,
no admin configuration, GDPR-clean.

### Layers

1. **Honeypot** -- a hidden field (`your-website`) is injected into every form.
   Real users never see it; bots that fill every field are flagged.
2. **JS token** -- a hidden field (`_wpcf7_js_token`) starts empty and is only
   filled client-side on the first interaction with the form. A submission
   without JavaScript or without any interaction never fills it and is
   flagged. Replaces an earlier signed-timestamp time-trap, which was inert
   in production: the timestamp got baked into the cached HTML under
   full-page caching, so every visitor's form looked "old".
3. **Link limit** -- submissions with more than `MAX_URLS` (2) URLs across all
   fields are flagged.
4. **Keyword filter** -- a conservative, high-confidence list (pharma, gambling,
   adult, replica). Extend per site:

```php
add_filter("theme_cf7_spam_keywords", function (array $keywords): array {
    $keywords[] = "another-spam-term";
    return $keywords;
});
```

Flagged submissions are recorded via `WPCF7_Submission::add_spam_log()` and are
visible in Contact Form 7 / Flamingo. CF7 treats them as spam (no mail sent).

### Not enabled (optional second layer)

Cloudflare Turnstile and Akismet require external keys and are configured in the
Contact Form 7 admin (Integration tab), not in theme code.

## Security Audit Checklist

### Regular Checks

- [ ] Update WordPress core
- [ ] Update plugins
- [ ] Update theme dependencies (`composer update`, `npm update`)
- [ ] Review user accounts and permissions
- [ ] Check debug log for errors
- [ ] Verify backup schedule

### Code Review

- [ ] No hardcoded credentials
- [ ] All user input sanitized
- [ ] All output escaped
- [ ] Nonces verified on forms
- [ ] Capabilities checked on actions
- [ ] SQL queries use prepared statements

## Reporting Vulnerabilities

If you discover a security vulnerability:

1. **Do not** open a public GitHub issue
2. Email security concerns to: security@example.com
3. Include detailed reproduction steps
4. Allow reasonable time for fix before disclosure

## Resources

- [WordPress Security Best Practices](https://developer.wordpress.org/plugins/security/)
- [OWASP WordPress Security](https://owasp.org/www-project-web-security-testing-guide/)
- [Content Security Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)

## Externe Einbettungen

Das Modul "Einbettung" rendert ab Werk nur YouTube, Vimeo und Google Maps. Jeder
weitere Anbieter muss unter **Theme-Einstellungen → Analytics → Externe
Einbettungen** eingetragen werden, ein Host je Zeile. `Security::isAllowedEmbedHost()`
prueft die Adresse: nur https, nur Hostnamen, nie der eigene Host. Die CSP
beschraenkt Frames nicht.

Das Modul „Einbettung" nimmt deshalb nur die Adresse aus dem `src`-Attribut und
baut den iframe selbst. Den Einbettungscode des Anbieters entgegenzunehmen waere
zwecklos: ACF entfernt `<iframe>` beim Speichern fuer jede Rolle ohne
`unfiltered_html`, im Multisite also auch fuer Administratoren.
