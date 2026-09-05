<?php

declare(strict_types=1);

namespace WordpressStarter;

/**
 * Security class for Content Security Policy (CSP) and other security headers.
 *
 * NOTE: 'unsafe-inline' is currently required because:
 * - WordPress core adds inline styles (e.g., wp_add_inline_style)
 * - ACF Pro generates inline scripts for field initialization
 * - Block editor injects inline styles for block previews
 *
 * NOTE: 'unsafe-eval' is required because:
 * - Alpine.js evaluates x-data expressions as JavaScript
 * - Block previews are rendered via REST API (not caught by is_admin())
 *
 * To remove 'unsafe-inline' in the future:
 * 1. Add nonce attributes to all inline scripts/styles
 * 2. Use wp_script_add_data() with 'nonce' for registered scripts
 * 3. Filter script_loader_tag to add nonces to third-party scripts
 *
 * @see https://developer.wordpress.org/reference/hooks/script_loader_tag/
 */
class Security
{
    private static ?string $nonce = null;

    /**
     * Hosts fest verdrahtet in frame-src, unabhaengig von der Admin-Option
     * embed_allowed_hosts. Eine einzige Quelle fuer getCSPHeader() UND
     * isAllowedEmbedHost(), damit die beiden nicht auseinanderlaufen: sonst
     * erlaubt die CSP einen Host, den das Embed-Feld ablehnt (oder umgekehrt).
     *
     * @var list<string>
     */
    private const HARDCODED_FRAME_SRC_HOSTS = [
        'www.youtube-nocookie.com',
        'www.youtube.com',
        'player.vimeo.com',
        'www.google.com',
        'maps.google.com',
    ];

    /**
     * Get or generate the CSP nonce for this request.
     */
    public static function getNonce(): string
    {
        if (self::$nonce === null) {
            self::$nonce = base64_encode(random_bytes(16));  // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- CSP nonce encoding, not obfuscation
        }

        return self::$nonce;
    }

    /**
     * Patch a foreign admin CSP header (set by another plugin, e.g. Solid
     * Security) so the theme's own admin inline scripts/styles still work:
     *
     * - Adds 'unsafe-eval' to script-src, still required because ACF Pro's
     *   block-preview rendering (REST API, not caught by is_admin()) evaluates
     *   inline JavaScript.
     * - Adds the theme's current nonce ('nonce-{value}') to script-src and
     *   style-src, but ONLY when that directive already carries a
     *   'nonce-...'/'sha256-...'/'sha384-...'/'sha512-...' source. Per the
     *   CSP2+ spec, a nonce or hash source in a directive makes browsers
     *   ignore 'unsafe-inline' in that same directive, so nothing is lost
     *   by adding our nonce there. Injecting the nonce into a directive that
     *   still relies on 'unsafe-inline' alone (no nonce/hash present) would
     *   make CSP2+ browsers ignore that 'unsafe-inline' too and block every
     *   other plugin's and WordPress core's un-nonced inline script or
     *   style in wp-admin, so such directives are left untouched. The
     *   theme's own admin tags remain unblocked because the foreign
     *   'unsafe-inline' still applies to them.
     * - A foreign header with only default-src (no script-src/style-src) is
     *   a no-op for both patches: nothing to touch, nothing errors.
     *
     * Only script-src and style-src are touched. script-src-elem and
     * script-src-attr are separate directives under CSP3 and are left alone;
     * matching them here would also modify allowances the foreign header
     * intentionally set apart from script-src. Directive bodies are matched
     * up to the next ';' or ',' so a second, comma-separated policy in the
     * same header value is never touched.
     */
    public static function addUnsafeEvalToCSP(string $csp): string
    {
        if (preg_match('/(^|;\s*)(script-src)(\s[^;,]*)/', $csp, $scriptSrcMatch) === 1
            && !str_contains($scriptSrcMatch[3], "'unsafe-eval'")
        ) {
            $csp = preg_replace(
                '/(^|;\s*)(script-src)(\s[^;,]*)/',
                '$1$2$3 \'unsafe-eval\'',
                $csp,
                1,
            ) ?? $csp;
        }

        $nonceSource = "'nonce-" . self::getNonce() . "'";
        foreach (['script-src', 'style-src'] as $directive) {
            if (preg_match('/(^|;\s*)(' . $directive . ')(\s[^;,]*)/', $csp, $matches) === 1
                && preg_match('/\'(?:nonce|sha256|sha384|sha512)-/', $matches[3]) === 1
                && !str_contains($matches[3], $nonceSource)
            ) {
                $csp = preg_replace(
                    '/(^|;\s*)(' . $directive . ')(\s[^;,]*)/',
                    '$1$2$3 ' . $nonceSource,
                    $csp,
                    1,
                ) ?? $csp;
            }
        }

        return $csp;
    }

    /**
     * Recognize and patch a single raw "Header: value" line (as returned by
     * headers_list()) if it is the Content-Security-Policy header.
     *
     * Extracted out of the admin_init header_register_callback closure in
     * init() so the prefix-stripping and patching can be unit-tested without
     * relying on PHP actually flushing headers.
     *
     * Regex-strips the "Content-Security-Policy:" prefix instead of
     * substr(): a header value can arrive without a space after the colon
     * (e.g. no leading space from the sending plugin), and substr() with a
     * fixed 'Content-Security-Policy: ' length would then eat the CSP's
     * first character. "Content-Security-Policy-Report-Only:" is a
     * different, CSP3 header and is deliberately NOT matched here: the
     * literal prefix check requires the colon right after "Policy".
     *
     * @return string|null The replacement full header line ("Content-Security-Policy: ...")
     *                     or null when $rawHeaderValue is not a CSP header, left untouched.
     */
    public static function patchCspHeaderValue(string $rawHeaderValue): ?string
    {
        if (stripos($rawHeaderValue, 'Content-Security-Policy:') !== 0) {
            return null;
        }

        $csp = preg_replace('/^content-security-policy:\s*/i', '', $rawHeaderValue) ?? $rawHeaderValue;

        return 'Content-Security-Policy: ' . self::addUnsafeEvalToCSP($csp);
    }

    /**
     * Get the current Vite dev server port from .vite-port file.
     * Returns null if the file doesn't exist or is invalid.
     */
    private static function getVitePort(): ?int
    {
        $portFile = get_template_directory() . '/.vite-port';

        if (file_exists($portFile)) {
            $port = file_get_contents($portFile);
            if ($port !== false && is_numeric(trim($port))) {
                return (int) trim($port);
            }
        }

        return null;
    }

    /**
     * Get localhost sources for CSP in development environments.
     *
     * CSP doesn't support port wildcards, so we list common development ports.
     * Additionally reads the dynamic port from .vite-port if available.
     * Includes both localhost and 127.0.0.1 for maximum compatibility.
     *
     * @return string Space-prefixed localhost sources or empty string in production
     */
    private static function getLocalSources(): string
    {
        $isLocalEnv = defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'local';

        if (!$isLocalEnv) {
            return '';
        }

        // Common development server ports
        $ports = [
            3000,  // React, Next.js, generic dev servers
            3001,  // Alternative React port
            4173,  // Vite preview
            5173,  // Vite dev server (default)
            5180,  // Vite dev server (theme default)
            5181,  // Vite alternative port
            5182,  // Vite alternative port
            8000,  // Python, PHP built-in server
            8080,  // Common alternative port
            8888,  // Jupyter, some PHP setups
            9000,  // PHP-FPM, some dev servers
        ];

        // Add dynamic Vite port if available
        $vitePort = self::getVitePort();
        if ($vitePort !== null && !in_array($vitePort, $ports, true)) {
            $ports[] = $vitePort;
        }

        $hosts = ['localhost', '127.0.0.1'];
        $protocols = ['http', 'ws']; // HTTP and WebSocket

        $sources = [];
        foreach ($hosts as $host) {
            foreach ($ports as $port) {
                foreach ($protocols as $protocol) {
                    $sources[] = "{$protocol}://{$host}:{$port}";
                }
            }
        }

        return ' ' . implode(' ', $sources);
    }

    /**
     * Baue eine https-Origin aus einem geprueften Hostnamen.
     *
     * Gemeinsame Validierung und Aufbau fuer getAnalyticsOrigin() und
     * getEmbedOrigins(): nur Zeichen, die in einem Hostnamen vorkommen
     * duerfen, kein Schema, kein Pfad. Gibt null zurueck, wenn der Host
     * nicht passt, statt eine Direktive zu zerlegen.
     */
    private static function httpsOriginFromHost(string $host, mixed $port = null): ?string
    {
        if (preg_match('/^[A-Za-z0-9.-]+$/', $host) !== 1) {
            return null;
        }

        $origin = 'https://' . $host;

        if (is_int($port) && $port > 0 && $port <= 65535) {
            $origin .= ':' . $port;
        }

        return $origin;
    }

    /**
     * Herkunft des Analytics-Hosts fuer die CSP, aus der Plugin-Option.
     *
     * Rybbit laedt sein Skript per wp_enqueue_script von einem externen Host und
     * schickt die Ereignisse per fetch() dorthin zurueck. Beides braucht einen
     * Eintrag in der CSP: `connect-src` allein reicht nicht, dann wird schon das
     * Skript blockiert und es sendet nie jemand.
     *
     * Der Host wird nicht hartkodiert, weil jede Installation ihre eigene
     * Rybbit-Instanz haben kann. Quelle ist dieselbe Option, aus der das Plugin
     * sein Skript zieht, damit CSP und Skript-URL nicht auseinanderlaufen.
     *
     * Die Option ist von Administratoren setzbar und landet in einem Header,
     * deshalb streng gefiltert: nur https, nur Host und optionaler Port, und nur
     * Zeichen, die in einem Hostnamen vorkommen duerfen. Alles andere faellt
     * heraus, statt eine Direktive zu zerlegen oder den Header zu spalten.
     */
    private static function getAnalyticsOrigin(): string
    {
        $url = (string) get_option('rybbit_script_url', 'https://app.rybbit.io/api/script.js');

        if ($url === '') {
            return '';
        }

        $host = wp_parse_url($url, PHP_URL_HOST);
        $scheme = wp_parse_url($url, PHP_URL_SCHEME);

        if (!is_string($host) || $host === '' || $scheme !== 'https') {
            return '';
        }

        $port = wp_parse_url($url, PHP_URL_PORT);
        $origin = self::httpsOriginFromHost($host, $port);

        if ($origin === null) {
            return '';
        }

        return ' ' . $origin;
    }

    /**
     * Zulaessige Hosts aus der Admin-Option, normalisiert (Kleinschreibung,
     * ohne Schema/Pfad). Gemeinsame Quelle fuer getEmbedOrigins() (baut die
     * CSP-Origins) UND isAllowedEmbedHost() (prueft einen einzelnen Host),
     * damit beide dieselbe Zulassung sehen.
     *
     * @return list<string>
     */
    private static function getEmbedAllowedHosts(): array
    {
        $raw = Acf\Fields::option('embed_allowed_hosts', '');

        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $hosts = [];

        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $host = trim($line);

            if ($host === '') {
                continue;
            }

            // Ein eingetragenes "https://calendly.com/pfad" soll nicht scheitern,
            // sondern auf den Host eingedampft werden.
            if (str_contains($host, '://')) {
                $host = (string) wp_parse_url($host, PHP_URL_HOST);
            }

            $host = strtok($host, '/');

            if (!is_string($host) || preg_match('/^[A-Za-z0-9.-]+$/', $host) !== 1) {
                continue;
            }

            $hosts[] = strtolower($host);
        }

        return array_values(array_unique($hosts));
    }

    private static function getEmbedOrigins(): string
    {
        $origins = array_map(
            static fn (string $host): string => 'https://' . $host,
            self::getEmbedAllowedHosts(),
        );

        if ($origins === []) {
            return '';
        }

        return ' ' . implode(' ', $origins);
    }

    /**
     * Ist $url als Iframe-Quelle zulaessig? Dieselbe Zulassung, die
     * getCSPHeader() in frame-src schreibt: die fest verdrahteten Hosts
     * (HARDCODED_FRAME_SRC_HOSTS) plus die Admin-Option embed_allowed_hosts
     * (ueber getEmbedAllowedHosts() normalisiert). Templates, die vor dem
     * Rendern eines Iframes pruefen wollen, ob die CSP ihn ohnehin blockieren
     * wuerde, rufen diese Methode statt die private Zulassungslogik zu
     * duplizieren.
     *
     * Der eigene Host der Seite ist NIE zulaessig: ein Iframe mit
     * allow-same-origin auf die eigene Seite wuerde den CSP-Sandkasten
     * aushebeln, egal was in der Options-Liste steht.
     */
    public static function isAllowedEmbedHost(string $url): bool
    {
        $scheme = wp_parse_url($url, PHP_URL_SCHEME);

        if (!is_string($scheme) || strtolower($scheme) !== 'https') {
            return false;
        }

        // Ein expliziter Nicht-443-Port passiert diese Pruefung, waehrend
        // die CSP frame-src den portlosen Origin ausgibt: der Browser
        // blockiert den Iframe dann still. Nur der Standardport ist erlaubt.
        $port = wp_parse_url($url, PHP_URL_PORT);

        if (is_int($port) && $port !== 443) {
            return false;
        }

        $host = wp_parse_url($url, PHP_URL_HOST);

        if (!is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);

        $siteHost = wp_parse_url(home_url(), PHP_URL_HOST);

        if (is_string($siteHost) && $siteHost !== '') {
            $siteHost = strtolower($siteHost);
            // www./non-www-Alias des eigenen Hosts faellt unter dieselbe
            // Sperre, sonst reicht ein Alias in der home_url()-Option fuer
            // ein same-origin Iframe mit allow-same-origin + allow-scripts.
            $siteHostAlias = str_starts_with($siteHost, 'www.')
                ? substr($siteHost, 4)
                : 'www.' . $siteHost;

            if ($host === $siteHost || $host === $siteHostAlias) {
                return false;
            }
        }

        if (in_array($host, self::HARDCODED_FRAME_SRC_HOSTS, true)) {
            return true;
        }

        return in_array($host, self::getEmbedAllowedHosts(), true);
    }

    /**
     * Build the Content-Security-Policy header value.
     */
    public static function getCSPHeader(): string
    {
        $nonce = self::getNonce();
        $localSources = self::getLocalSources();
        $analyticsOrigin = self::getAnalyticsOrigin();

        // Base directives
        $directives = [
            "default-src 'self'" . $localSources,
            "font-src 'self' data:" . $localSources,
            "img-src 'self' data: https:" . $localSources,
            "frame-src 'self' " . implode(' ', array_map(
                static fn (string $host): string => 'https://' . $host,
                self::HARDCODED_FRAME_SRC_HOSTS,
            )) . self::getEmbedOrigins(),
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "media-src 'self' https:" . $localSources,
        ];

        // Script sources
        // Note: 'unsafe-eval' is required for Alpine.js to evaluate x-data expressions
        $scriptSrc = "'self' 'nonce-{$nonce}' 'unsafe-inline' 'unsafe-eval'" . $analyticsOrigin . $localSources;
        $directives[] = "script-src {$scriptSrc}";

        // Style sources (unsafe-inline needed for WordPress/ACF inline styles)
        $styleSrc = "'self' 'unsafe-inline'" . $localSources;
        $directives[] = "style-src {$styleSrc}";

        // Connect sources (API calls, WebSockets)
        $connectSrc = "'self'" . $analyticsOrigin . $localSources;
        $directives[] = "connect-src {$connectSrc}";

        // Worker sources (for WordPress emoji loader and other web workers)
        $directives[] = "worker-src 'self' blob:";

        return implode('; ', $directives);
    }

    /**
     * The hardening headers as a name => value map, so tests can pin the
     * exact set and values without triggering a real header() send.
     *
     * @return array<string, string>
     */
    public static function getHardeningHeaders(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), camera=(), microphone=(), payment=()',
        ];
    }

    /**
     * Send hardening headers that do not depend on the CSP.
     *
     * Registered before the CSP guard so disabling the CSP does not silently
     * drop clickjacking and MIME-sniffing protection as well.
     */
    private static function addHardeningHeaders(): void
    {
        add_action('send_headers', function (): void {
            if (is_admin() || wp_doing_ajax()) {
                return;
            }

            foreach (self::getHardeningHeaders() as $name => $value) {
                header("{$name}: {$value}");
            }
        });
    }

    /**
     * Initialize security features.
     */
    public static function init(): void
    {
        self::addHardeningHeaders();

        // Skip CSP if disabled in config
        if (!config('security.enable_csp', true)) {
            return;
        }

        // Add CSP header for frontend requests
        add_action('send_headers', function (): void {
            if (!is_admin() && !wp_doing_ajax()) {
                header('Content-Security-Policy: ' . self::getCSPHeader());
            }
        });

        // Add unsafe-eval to CSP for admin pages (needed for Alpine.js in block editor)
        // This modifies any CSP header set by other plugins (e.g., Solid Security)
        add_filter('wp_headers', function (array $headers): array {
            if (is_admin() && isset($headers['Content-Security-Policy'])) {
                $headers['Content-Security-Policy'] = self::addUnsafeEvalToCSP($headers['Content-Security-Policy']);
            }

            return $headers;
        });

        // Fallback: Also modify CSP headers set directly via header() function
        // This runs late to override plugin-set headers
        add_action('admin_init', function (): void {
            // Use output buffering to capture and modify headers
            if (!headers_sent()) {
                header_register_callback(function (): void {
                    $headers = headers_list();
                    foreach ($headers as $header) {
                        $patched = Security::patchCspHeaderValue($header);
                        if ($patched !== null) {
                            header_remove('Content-Security-Policy');
                            header($patched);
                            break;
                        }
                    }
                });
            }
        }, 1);

        // Make nonce available globally for templates
        $GLOBALS['csp_nonce'] = self::getNonce();

        // Add nonce to script tags (preparation for removing unsafe-inline)
        add_filter('script_loader_tag', function (string $tag, string $handle): string {
            // Core can concatenate several script tags (before/main/after
            // inline scripts) into one $tag string, so nonce each opening
            // <script ...> tag individually instead of only the first.
            $nonce = self::getNonce();

            return preg_replace_callback('/<script\b[^>]*>/i', function (array $matches) use ($nonce): string {
                $openingTag = $matches[0];
                // Skip if this tag already carries a nonce attribute, not
                // merely the substring "nonce=" inside a src URL query string
                if (preg_match('/\snonce=/i', $openingTag) === 1) {
                    return $openingTag;
                }

                return preg_replace('/<script\b/i', "<script nonce=\"{$nonce}\"", $openingTag, 1) ?? $openingTag;
            }, $tag) ?? $tag;
        }, 10, 2);

        // Add nonce to WordPress inline scripts (wp_add_inline_script)
        add_filter('wp_inline_script_attributes', function (array $attributes): array {
            if (!isset($attributes['nonce'])) {
                $attributes['nonce'] = self::getNonce();
            }

            return $attributes;
        });

        // Add nonce to wp_print_inline_script_tag calls
        add_filter('wp_script_attributes', function (array $attributes): array {
            if (!isset($attributes['nonce'])) {
                $attributes['nonce'] = self::getNonce();
            }

            return $attributes;
        });
    }
}
