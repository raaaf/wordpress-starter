# Security

This document describes the security measures implemented in the WordPress Starter Theme.

## Content Security Policy (CSP)

The theme implements a strict Content Security Policy to prevent XSS attacks.

### Implementation

Located in `src/Security.php`:

```php
$csp = [
    "default-src 'self'",
    "script-src 'self' 'nonce-{$nonce}' 'unsafe-inline' 'unsafe-eval'",
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
    "img-src 'self' data: https: blob:",
    "font-src 'self' https://fonts.gstatic.com",
    "frame-src 'self' https://www.youtube.com https://player.vimeo.com https://www.google.com",
    "connect-src 'self' http://localhost:* https://tracking.maki-it.de",
];
```

### Nonce-Based Script Loading

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

Edit `src/Security.php` to adjust policies for your needs:

```php
// Add a new domain to img-src
$csp[] = "img-src 'self' https://cdn.example.com";
```

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

- Only administrators can upload SVGs
- Sanitization runs before file is saved
- MIME type verification enforced

### Implementation

```php
// In ThemeServiceProvider.php
private function sanitizeSvg(string $content): string
{
    $sanitizer = new \enshrined\svgSanitize\Sanitizer();
    $sanitizer->removeRemoteReferences(true);
    return $sanitizer->sanitize($content) ?: $content;
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

ACF fields are sanitized on save:

```php
// In AcfServiceProvider.php
add_filter('acf/update_value/type=text', function ($value) {
    return sanitize_text_field($value);
});

add_filter('acf/update_value/type=url', function ($value) {
    return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
});
```

### Sanitization Functions Used

| Field Type     | Function                                  |
| -------------- | ----------------------------------------- |
| Text           | `sanitize_text_field()`                   |
| Textarea       | `sanitize_textarea_field()`               |
| URL            | `filter_var($value, FILTER_VALIDATE_URL)` |
| Email          | `is_email()`                              |
| HTML (WYSIWYG) | `wp_kses_post()`                          |

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

Additional security headers sent with responses:

```php
// In Security.php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
```

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

## Contact Form Spam Protection

Contact Form 7 submissions pass through server-side heuristics registered in
`src/PluginConfigurators/ContactForm7Configurator.php`. No third-party service,
no admin configuration, GDPR-clean.

### Layers

1. **Honeypot** -- a hidden field (`your-website`) is injected into every form.
   Real users never see it; bots that fill every field are flagged.
2. **Time-trap** -- a signed render timestamp (HMAC, `wp_salt`) is injected.
   Submissions arriving in under `MIN_SUBMIT_SECONDS` (3s) are flagged. Fails
   open when the timestamp is missing or its signature mismatches (page cache),
   so legitimate users are never blocked.
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
