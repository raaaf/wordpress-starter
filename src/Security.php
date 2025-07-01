<?php

declare(strict_types=1);

namespace WordpressStarter;

class Security
{
    private static ?string $nonce = null;

    public static function getNonce(): string
    {
        if (self::$nonce === null) {
            self::$nonce = wp_create_nonce('wp-starter-nonce');
        }
        return self::$nonce;
    }

    public static function getCSPHeader(): string
    {
        $nonce = self::getNonce();
        
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https:",
            "connect-src 'self'",
        ];

        // Add development server sources if in dev mode
        if (defined('WP_DEBUG') && WP_DEBUG && Vite::isDevServerRunning()) {
            $directives[] = "connect-src 'self' http://localhost:5173 ws://localhost:5173";
            $directives[] = "script-src 'self' 'nonce-{$nonce}' 'unsafe-inline' https://unpkg.com http://localhost:5173";
        }

        return implode('; ', $directives);
    }

    public static function init(): void
    {
        // Add CSP header
        add_action('send_headers', function (): void {
            if (!is_admin()) {
                header('Content-Security-Policy: ' . self::getCSPHeader());
            }
        });

        // Make nonce available to templates
        add_filter('timber/context', function (array $context): array {
            $context['csp_nonce'] = self::getNonce();
            return $context;
        });
    }
}