# Security

This document describes the security measures implemented in the WordPress Starter Theme.

## Content Security Policy (CSP)

The theme implements a strict Content Security Policy to prevent XSS attacks.

### Implementation

Built by `Security::getCSPHeader()` in `src/Security.php`:

```php
$directives = [
    "default-src 'self'" . $localSources,
    "font-src 'self' data:" . $localSources,
    "img-src 'self' data: https:" . $localSources,
    "frame-src 'self' https://www.youtube-nocookie.com https://www.youtube.com https://player.vimeo.com https://www.google.com https://maps.google.com" . self::getEmbedOrigins(),
    "form-action 'self' https://<FORM_ACTION_PROVIDER_HOSTS>" . self::getSiteUrlFormOrigin(),
    "frame-ancestors 'self'",
    "base-uri 'self'",
    "media-src 'self' https:" . $localSources,
    "script-src 'self' 'nonce-{$nonce}' 'unsafe-inline' 'unsafe-eval'" . $analyticsOrigin . $localSources,
    "style-src 'self' 'unsafe-inline'" . $localSources,
    "connect-src 'self'" . $analyticsOrigin . $localSources,
    "worker-src 'self' blob:",
];
```

`$localSources` (from `Security::getLocalSources()`) appends a space-separated list of `http://` and `ws://` origins for `localhost` and `127.0.0.1` across common dev ports (3000, 3001, 4173, 5173, 5180-5182, 8000, 8080, 8888, 9000, plus the dynamic Vite port read from `.vite-port`). It only applies when `WP_ENVIRONMENT_TYPE` is `local`; in production it resolves to an empty string. `default-src`, `font-src`, `img-src`, `media-src`, `script-src`, `style-src` and `connect-src` all carry this suffix, `frame-src`, `frame-ancestors`, `base-uri` and `worker-src` do not.

`$analyticsOrigin` (from `Security::getAnalyticsOrigin()`) resolves the `rybbit_script_url` option to its `https://` origin and appends it to `script-src` and `connect-src`, so the Rybbit Analytics tracking script (if the plugin is active) can both load and send events. Falls back to the plugin's own default origin when the option is unset, and to an empty string when the value cannot be parsed as a safe `https://` host.

`Security::getEmbedOrigins()` reads the admin-configured `embed_allowed_hosts` option, one host per line, and appends the resulting `https://` origins to `frame-src`. It strips any scheme or path from each entry and drops anything it cannot parse as a plain hostname, so `frame-src` never widens beyond a host list an administrator explicitly entered under Theme-Einstellungen → Analytics → Externe Einbettungen. The hostname pattern is ASCII-only, so an internationalised host must be entered in its punycode form (`xn--...`), not as Unicode.

`Security::isAllowedEmbedHost()` is the shared gate the same layouts use before rendering an iframe: it accepts exactly what `getCSPHeader()` writes into `frame-src`. On top of the host list it rejects a non-default port (only an implicit or explicit `443` passes) and rejects the site's own host together with its `www.`/non-www counterpart, so a same-origin alias in `home_url()` cannot combine with `allow-same-origin` to break the sandbox.

`form-action` limits where forms may submit: form tags survive kses in post content (`AcfServiceProvider::allowFormControlTags`), so without it a contributor could post to a foreign host. The allowed hosts (`FORM_ACTION_PROVIDER_HOSTS`: Mailchimp, Brevo, CleverReach, rapidmail, KlickTipp, MailerLite, Kit, ActiveCampaign, GetResponse, Klaviyo, AWeber, EmailOctopus, Constant Contact, PayPal buttons) belong to the newsletter layout and PayPal embeds; `Security::isAllowedFormActionUrl()` gates both its template and its ACF validation. When the host of `site_url()` differs from `home_url()`, `Security::getSiteUrlFormOrigin()` adds the `site_url()` origin to `form-action` so the password form (which posts to `wp-login.php`) keeps working.

**Limit of `form-action`:** the allowed providers are multi-tenant, so the CSP cannot stop a post to an attacker-owned tenant at an allowed provider. The actual guard is the save-context rule in `AcfServiceProvider::allowFormControlTags`: a user without the `unfiltered_html` capability cannot save form controls at all (rendering keeps them for forms an administrator saved). The CSP is also only sent when `config('security.enable_csp')` is true and never for admin or AJAX requests.

### Nonce-Based Script Loading

Open point: whether the per-request nonce survives a full-page cache is not settled. A cached page carries the nonce of the request that built it, while the header is generated per request. Do not treat this as solved.

All inline scripts require a nonce for execution:

```blade
<script nonce="{{ $GLOBALS['csp_nonce'] }}">
    // Your code here
</script>
```

The nonce is automatically generated per-request using cryptographically secure random bytes.

### Known Limitations

| Directive                 | Reason                                |
| ------------------------- | ------------------------------------- |
| `'unsafe-inline'` (style) | WordPress/ACF generates inline styles |
| `'unsafe-eval'` (script)  | Alpine.js x-data requires eval        |

### Customizing CSP

Edit the `$directives` array in `Security::getCSPHeader()` (`src/Security.php`) to adjust policies for your needs. Edit the existing directive line directly rather than appending a new one; a repeated directive of the same type is ignored by the browser, not merged:

```php
// Add a domain to the existing img-src line
"img-src 'self' data: https: https://cdn.example.com" . $localSources,
```

Script, style and connect sources are built in `$scriptSrc`, `$styleSrc` and `$connectSrc`; extend those variables. Embed hosts need no code change: add them to the `embed_allowed_hosts` option.

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

| Field Type | Hook                                                                                             |
| ---------- | ------------------------------------------------------------------------------------------------ |
| URL        | `acf/validate_value/type=url` (`validateUrl`)                                                    |
| Email      | `acf/validate_value/type=email` (`validateEmail`)                                                |
| Newsletter | `acf/validate_value/key=field_flex_newsletter_action_url` (only hosts that `form-action` allows) |

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

`frame-src` erlaubt ab Werk YouTube, Vimeo und Google Maps. Jeder weitere
Anbieter muss unter **Theme-Einstellungen → Analytics → Externe Einbettungen**
eingetragen werden, ein Host je Zeile. `Security::getEmbedOrigins()` filtert die
Liste streng: nur Hostnamen, kein Schema, kein Pfad, nichts, was die Direktive
zerlegen koennte.

Das Modul „Einbettung" nimmt deshalb nur die Adresse aus dem `src`-Attribut und
baut den iframe selbst. Den Einbettungscode des Anbieters entgegenzunehmen waere
zwecklos: ACF entfernt `<iframe>` beim Speichern fuer jede Rolle ohne
`unfiltered_html`, im Multisite also auch fuer Administratoren.
