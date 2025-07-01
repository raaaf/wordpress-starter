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
    }
}