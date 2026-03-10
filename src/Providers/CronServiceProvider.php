<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

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
     * Cleanup expired transients from the database
     *
     * WordPress should handle this automatically, but this ensures
     * cleanup happens regularly for better database performance.
     */
    public function cleanupExpiredTransients(): void
    {
        global $wpdb;

        // Delete expired transients
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            $wpdb->prepare(
                "DELETE a, b FROM {$wpdb->options} a
                INNER JOIN {$wpdb->options} b ON b.option_name = CONCAT('_transient_timeout_', SUBSTRING(a.option_name, 12))
                WHERE a.option_name LIKE %s
                AND b.option_value < %d",
                $wpdb->esc_like('_transient_') . '%',
                time()
            )
        );

        // Delete orphaned transient timeouts
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options}
                WHERE option_name LIKE %s
                AND option_value < %d",
                $wpdb->esc_like('_transient_timeout_') . '%',
                time()
            )
        );

        // Log the cleanup
        LogServiceProvider::info('Transient cleanup completed');
    }

    /**
     * Cleanup old post revisions
     *
     * Keeps the last 5 revisions per post, removes older ones.
     */
    public function cleanupOldRevisions(): void
    {
        global $wpdb;

        // Get posts with more than 5 revisions
        $posts_with_revisions = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT post_parent, COUNT(*) as revision_count
                FROM {$wpdb->posts}
                WHERE post_type = %s
                GROUP BY post_parent
                HAVING revision_count > 5", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                'revision'
            )
        );

        $deleted = 0;

        foreach ($posts_with_revisions as $post) {
            // Get revisions to delete (keep newest 5)
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $revisions = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts}
                    WHERE post_parent = %d
                    AND post_type = 'revision'
                    ORDER BY post_date DESC
                    LIMIT %d, 999999",
                    $post->post_parent,
                    5
                )
            );

            foreach ($revisions as $revision_id) {
                wp_delete_post_revision( (int) $revision_id);
                ++$deleted;
            }
        }

        if ($deleted > 0) {
            LogServiceProvider::info('Revision cleanup completed', ['deleted' => $deleted]);
        }
    }

    /**
     * Manually trigger a cleanup task
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
