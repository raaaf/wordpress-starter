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
     * Add 'unsafe-eval' to a CSP header's script-src directive.
     * Required for Alpine.js to work in the block editor.
     */
    public static function addUnsafeEvalToCSP(string $csp): string
    {
        if (!str_contains($csp, "'unsafe-eval'")) {
            $csp = preg_replace(
                '/(script-src[^;]*)/',
                "$1 'unsafe-eval'",
                $csp
            ) ?? $csp;
        }
        return $csp;
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
            "font-src 'self' data: https://fonts.gstatic.com",
            "img-src 'self' data: https:",
            "frame-src 'self' https://www.youtube-nocookie.com https://www.youtube.com https://player.vimeo.com https://www.google.com https://maps.google.com",
        ];

        // Script sources
        // Note: 'unsafe-eval' is required for Alpine.js to evaluate x-data expressions
        $scriptSrc = "'self' 'nonce-{$nonce}' 'unsafe-inline' 'unsafe-eval'";
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

        // Worker sources (for WordPress emoji loader and other web workers)
        $directives[] = "worker-src 'self' blob:";

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
                        if (stripos($header, 'Content-Security-Policy:') === 0) {
                            // Remove the old header and set a new one with unsafe-eval
                            $csp = substr($header, strlen('Content-Security-Policy: '));
                            header_remove('Content-Security-Policy');
                            header('Content-Security-Policy: ' . Security::addUnsafeEvalToCSP($csp));
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
            // Skip if already has nonce
            if (str_contains($tag, 'nonce=')) {
                return $tag;
            }
            $nonce = self::getNonce();
            return str_replace('<script ', "<script nonce=\"{$nonce}\" ", $tag);
        }, 10, 2);

        // Add nonce to WordPress inline scripts (wp_add_inline_script)
        add_filter('wp_inline_script_attributes', function (array $attributes): array {
            $attributes['nonce'] = self::getNonce();
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
