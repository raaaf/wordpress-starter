<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\Security;

class SecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Initialize security features
        Security::init();
    }

    public function boot(): void
    {
        // Make nonce available globally for Blade templates
        add_action('init', function (): void {
            $GLOBALS['csp_nonce'] = Security::getNonce();
        });

        // HSTS: tell browsers to always use HTTPS for two years, including subdomains.
        // Only emitted on HTTPS frontend requests. Skip admin/ajax to avoid interfering
        // with non-HTTPS staging logins. `preload` is included so the domain is eligible
        // for the browser HSTS preload list (https://hstspreload.org) once confirmed.
        add_action('send_headers', function (): void {
            if (is_admin() || wp_doing_ajax()) {
                return;
            }
            $forwardedProto = isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                ? strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_PROTO'])))
                : '';
            $isHttps = is_ssl() || $forwardedProto === 'https';
            if (!$isHttps || headers_sent()) {
                return;
            }
            header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
        });

        // Suppress the "password changed" confirmation email when an admin
        // edits another user. Users still get the email when changing their
        // own password, which preserves the security signal.
        add_filter('send_password_change_email', function (bool $send, array $user): bool {
            $currentUserId = get_current_user_id();
            if ($currentUserId > 0 && $currentUserId !== (int) $user['ID']) {
                return false;
            }
            return $send;
        }, 10, 2);
    }
}
