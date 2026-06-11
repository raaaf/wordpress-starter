<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\Services\TransientCleanupService;
use WordpressStarter\ThemeContext;

/**
 * Handles scheduled tasks (WP-Cron) for the theme
 *
 * Registers custom cron schedules and hooks for maintenance tasks.
 */
class CronServiceProvider extends ServiceProvider
{
    public static function scheduleName(): string
    {
        return ThemeContext::prefix() . '_twice_daily';
    }

    public static function hookCleanupTransients(): string
    {
        return ThemeContext::prefix() . '_cleanup_transients';
    }

    public static function hookCleanupRevisions(): string
    {
        return ThemeContext::prefix() . '_cleanup_revisions';
    }

    public function register(): void
    {
        // Add custom cron schedules
        add_filter('cron_schedules', [$this, 'addCronSchedules']);
    }

    public function boot(): void
    {
        // Register cron hooks
        add_action(self::hookCleanupTransients(), [$this, 'cleanupExpiredTransients']);
        add_action(self::hookCleanupRevisions(), [$this, 'cleanupOldRevisions']);

        // Schedule events if not already scheduled
        add_action('init', [$this, 'scheduleEvents']);

        // Clear scheduled events when the theme is switched
        add_action('switch_theme', [static::class, 'deactivate']);
    }

    /**
     * Add custom cron schedules
     *
     * @param array<string, array{interval: int, display: string}> $schedules
     *
     * @return array<string, array{interval: int, display: string}>
     */
    public function addCronSchedules(array $schedules): array
    {
        $schedules[self::scheduleName()] = [
            'interval' => 12 * HOUR_IN_SECONDS,
            'display' => __('Zweimal täglich', 'wp-starter'),
        ];

        return $schedules;
    }

    /**
     * Schedule cron events
     */
    public function scheduleEvents(): void
    {
        // Schedule transient cleanup (daily)
        if (!wp_next_scheduled(self::hookCleanupTransients())) {
            wp_schedule_event(time(), 'daily', self::hookCleanupTransients());
        }

        // Schedule revision cleanup (weekly)
        if (!wp_next_scheduled(self::hookCleanupRevisions())) {
            wp_schedule_event(time(), 'weekly', self::hookCleanupRevisions());
        }
    }

    /**
     * Remove all scheduled events on theme deactivation
     */
    public static function deactivate(): void
    {
        wp_clear_scheduled_hook(self::hookCleanupTransients());
        wp_clear_scheduled_hook(self::hookCleanupRevisions());
    }

    /**
     * Cleanup expired transients from the database.
     *
     * WordPress should handle this automatically, but this ensures
     * cleanup happens regularly for better database performance.
     */
    public function cleanupExpiredTransients(): void
    {
        ( new TransientCleanupService() )->deleteExpiredTransients();
        LogServiceProvider::info('Transient cleanup completed');
    }

    /**
     * Cleanup old post revisions.
     *
     * Keeps the last 5 revisions per post, removes older ones.
     */
    public function cleanupOldRevisions(): void
    {
        $deleted = ( new TransientCleanupService() )->deleteOldRevisions(5);

        if ($deleted > 0) {
            LogServiceProvider::info('Revision cleanup completed', ['deleted' => $deleted]);
        }
    }

    /**
     * Manually trigger a cleanup task.
     *
     * @param string $task Task name: 'transients' or 'revisions'
     */
    public static function runCleanup(string $task): void
    {
        $instance = new self();

        switch ($task) {
            case 'transients':
                $instance->cleanupExpiredTransients();
                break;
            case 'revisions':
                $instance->cleanupOldRevisions();
                break;
        }
    }
}
