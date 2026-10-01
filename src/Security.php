<?php

declare(strict_types=1);

namespace WordpressStarter;

/**
 * Security class for Content Security Policy (CSP) and other security headers.
 *
 * The CSP is reduced to frame-ancestors, base-uri and object-src: a stricter
 * policy broke plugins, embeds and newsletter forms. Nonces are still added to
 * registered, inline and printed scripts (harmless, templates read csp_nonce).
 *
 * @see https://developer.wordpress.org/reference/hooks/script_loader_tag/
 */
class Security
{
    private static ?string $nonce = null;

    /**
     * Test seam for the hardening headers: when set, addHardeningHeaders()
     * calls this instead of the real header() function, so tests can assert
     * on the emitted header lines without triggering "headers already sent"
     * warnings or needing a real HTTP response.
     *
     * FOR TESTS ONLY: production code must never call setHeaderEmitter().
     *
     * @var (callable(string): void)|null
     */
    private static $headerEmitter = null;

    /**
     * FOR TESTS ONLY. Overrides the header() call used by
     * addHardeningHeaders() so tests can capture emitted header lines.
     * Pass null to restore the real header() call.
     *
     * @param (callable(string): void)|null $emitter
     */
    public static function setHeaderEmitter(?callable $emitter): void
    {
        self::$headerEmitter = $emitter;
    }

    /**
     * Hosts fest verdrahtet fuer Embeds, unabhaengig von der Admin-Option
     * embed_allowed_hosts. Quelle fuer isAllowedEmbedHost().
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
     * Zulaessige Hosts aus der Admin-Option, normalisiert (Kleinschreibung,
     * ohne Schema/Pfad). Quelle fuer isAllowedEmbedHost().
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

    /**
     * Ist $url als Iframe-Quelle zulaessig? Zulassung: die fest verdrahteten
     * Hosts (HARDCODED_FRAME_SRC_HOSTS) plus die Admin-Option
     * embed_allowed_hosts (ueber getEmbedAllowedHosts() normalisiert).
     * Templates pruefen damit vor dem Rendern eines Iframes den Anbieter.
     *
     * Der eigene Host der Seite ist NIE zulaessig: ein Iframe mit
     * allow-same-origin auf die eigene Seite wuerde den Sandkasten
     * aushebeln, egal was in der Options-Liste steht.
     */
    public static function isAllowedEmbedHost(string $url): bool
    {
        $scheme = wp_parse_url($url, PHP_URL_SCHEME);

        if (!is_string($scheme) || strtolower($scheme) !== 'https') {
            return false;
        }

        // Nur der https-Standardport ist erlaubt.
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
     *
     * Bewusst nur drei Direktiven, die nichts Bestehendes brechen: Clickjacking,
     * Base-Tag-Injection und Plugins/Objekte. Skripte, Styles, Bilder, Fonts,
     * Verbindungen, Frames und Formularziele sind nicht eingeschraenkt, weil
     * eine strengere Richtlinie Plugins, Embeds und Newsletter-Formulare brach.
     */
    public static function getCSPHeader(): string
    {
        return "frame-ancestors 'self'; base-uri 'self'; object-src 'none'";
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

            $emitter = self::$headerEmitter ?? static function (string $headerLine): void {
                header($headerLine);
            };

            foreach (self::getHardeningHeaders() as $name => $value) {
                $emitter("{$name}: {$value}");
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
