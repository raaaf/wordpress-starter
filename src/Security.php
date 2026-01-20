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
     * Get or generate the CSP nonce for this request.
     */
    public static function getNonce(): string
    {
        if (self::$nonce === null) {
            self::$nonce = base64_encode(random_bytes(16));
        }
        return self::$nonce;
    }

    /**
     * Build the Content-Security-Policy header value.
     */
    public static function getCSPHeader(): string
    {
        $nonce = self::getNonce();
        $isDevMode = defined('WP_DEBUG') && WP_DEBUG && Vite::isDevServerRunning();

        // Base directives
        $directives = [
            "default-src 'self'",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https:",
            "frame-src 'self' https://www.youtube-nocookie.com https://www.youtube.com https://player.vimeo.com",
        ];

        // Script sources
        $scriptSrc = "'self' 'nonce-{$nonce}' 'unsafe-inline'";
        if ($isDevMode) {
            $scriptSrc .= ' http://localhost:5173';
        }
        $directives[] = "script-src {$scriptSrc}";

        // Style sources (unsafe-inline needed for WordPress/ACF inline styles)
        $styleSrc = "'self' 'unsafe-inline' https://fonts.googleapis.com";
        $directives[] = "style-src {$styleSrc}";

        // Connect sources (API calls, WebSockets)
        $connectSrc = "'self'";
        if ($isDevMode) {
            $connectSrc .= ' http://localhost:5173 ws://localhost:5173';
        }
        $directives[] = "connect-src {$connectSrc}";

        return implode('; ', $directives);
    }

    /**
     * Initialize security features.
     */
    public static function init(): void
    {
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
            // Skip if already has nonce
            if (str_contains($tag, 'nonce=')) {
                return $tag;
            }
            $nonce = self::getNonce();
            return str_replace('<script ', "<script nonce=\"{$nonce}\" ", $tag);
        }, 10, 2);
    }
}
