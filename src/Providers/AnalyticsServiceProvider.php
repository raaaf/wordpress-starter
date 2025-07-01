<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\Application;
use WordpressStarter\Config;
use WordpressStarter\Providers\ServiceProvider;

class AnalyticsServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Add Pirsch Analytics script to footer
        add_action('wp_footer', [$this, 'renderPirschScript'], 100);
    }

    /**
     * Render Pirsch Analytics script
     */
    public function renderPirschScript(): void
    {
        $pirschId = Config::get('analytics.pirsch_id');
        
        if (!$pirschId) {
            return;
        }

        ?>
        <!-- Pirsch Analytics -->
        <script defer data-domain="<?php echo esc_attr(get_site_url()); ?>" data-code="<?php echo esc_attr($pirschId); ?>" src="https://api.pirsch.io/pirsch-extended.js"></script>
        <?php
    }
}