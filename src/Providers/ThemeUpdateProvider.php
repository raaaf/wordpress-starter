<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Handles automatic theme updates from GitHub releases.
 *
 * This provider enables WordPress dashboard updates for this theme
 * by checking GitHub releases for new versions.
 */
final class ThemeUpdateProvider extends ServiceProvider
{
    /**
     * GitHub repository URL for update checks.
     */
    private const GITHUB_REPO = 'https://github.com/raaaf/starter/';

    /**
     * Theme slug used for identification.
     */
    private const THEME_SLUG = 'wp-starter';

    /**
     * Register services.
     */
    public function register(): void
    {
        // Nothing to register
    }

    /**
     * Bootstrap the update checker.
     */
    public function boot(): void
    {
        // Only run in admin context to avoid unnecessary overhead
        if (! is_admin()) {
            return;
        }

        // Ensure the update checker library is available
        if (! class_exists(PucFactory::class)) {
            return;
        }

        $this->initializeUpdateChecker();
    }

    /**
     * Initialize the GitHub update checker.
     */
    private function initializeUpdateChecker(): void
    {
        $updateChecker = PucFactory::buildUpdateChecker(
            self::GITHUB_REPO,
            get_template_directory() . '/style.css',
            self::THEME_SLUG
        );

        // Set the branch to check for updates (uses releases by default)
        $updateChecker->setBranch('master');

        // Optional: Enable release assets (if you attach zip files to releases)
        // $updateChecker->getVcsApi()->enableReleaseAssets();
    }
}
