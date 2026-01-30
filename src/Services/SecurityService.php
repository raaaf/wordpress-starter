<?php

declare(strict_types=1);

namespace WordpressStarter\Services;

/**
 * Service for security-related functionality.
 *
 * Handles CSP nonce generation and security headers.
 */
class SecurityService
{
    private ?string $nonce = null;

    /**
     * Get or generate a CSP nonce for inline scripts/styles.
     *
     * The nonce is generated once per request and cached.
     */
    public function getNonce(): string
    {
        if ($this->nonce === null) {
            $this->nonce = base64_encode(random_bytes(16));
        }
        return $this->nonce;
    }

    /**
     * Get the Content-Security-Policy header value.
     *
     * @return string The CSP header value
     */
    public function getCSPHeader(): string
    {
        $nonce = $this->getNonce();
        $isDev = defined('WP_DEBUG') && WP_DEBUG;

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: https:",
            "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com https://www.google.com https://maps.google.com",
            "connect-src 'self' https://tracking.maki-it.de",
        ];

        // Allow Vite dev server in development
        if ($isDev) {
            $directives[1] = "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' http://localhost:5173";
            $directives[2] = "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com http://localhost:5173";
            $directives[6] = "connect-src 'self' https://tracking.maki-it.de ws://localhost:5173";
        }

        return implode('; ', $directives);
    }

    /**
     * Output the CSP header.
     */
    public function sendCSPHeader(): void
    {
        if (!headers_sent()) {
            header('Content-Security-Policy: ' . $this->getCSPHeader());
        }
    }

    /**
     * Get HTML attribute string for nonce.
     *
     * @return string The nonce attribute (e.g., 'nonce="abc123"')
     */
    public function getNonceAttribute(): string
    {
        return 'nonce="' . esc_attr($this->getNonce()) . '"';
    }
}
