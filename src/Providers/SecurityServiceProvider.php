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
