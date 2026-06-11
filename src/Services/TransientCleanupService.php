<?php

declare(strict_types=1);

namespace WordpressStarter\Services;

/**
 * Handles database cleanup for expired transients and old post revisions.
 */
class TransientCleanupService
{
    /**
     * Delete all expired transients from the options table.
     *
     * Removes both the transient value row and the orphaned timeout row.
     */
    public function deleteExpiredTransients(): void
    {
        global $wpdb;

        // Delete expired transients (value + timeout rows joined)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            $wpdb->prepare(
                "DELETE a, b FROM {$wpdb->options} a
                INNER JOIN {$wpdb->options} b ON b.option_name = CONCAT('_transient_timeout_', SUBSTRING(a.option_name, 12))
                WHERE a.option_name LIKE %s
                AND b.option_value < %d",
                $wpdb->esc_like('_transient_') . '%',
                time(),
            ),
        );

        // Delete orphaned transient timeout rows (no matching value row)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options}
                WHERE option_name LIKE %s
                AND option_value < %d",
                $wpdb->esc_like('_transient_timeout_') . '%',
                time(),
            ),
        );
    }

    /**
     * Delete old post revisions, keeping the newest $keep per post.
     *
     * @return int Number of revisions deleted
     */
    public function deleteOldRevisions(int $keep = 5): int
    {
        global $wpdb;

        // Find posts that have more revisions than the allowed limit
        $postsWithRevisions = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT post_parent, COUNT(*) as revision_count
                FROM {$wpdb->posts}
                WHERE post_type = %s
                GROUP BY post_parent
                HAVING revision_count > %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                'revision',
                $keep,
            ),
        );

        $deleted = 0;

        foreach ($postsWithRevisions as $post) {
            // Retrieve IDs of revisions to delete (skip the newest $keep)
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $revisions = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts}
                    WHERE post_parent = %d
                    AND post_type = 'revision'
                    ORDER BY post_date DESC
                    LIMIT %d, 999999",
                    $post->post_parent,
                    $keep,
                ),
            );

            foreach ($revisions as $revisionId) {
                wp_delete_post_revision( (int) $revisionId);
                ++$deleted;
            }
        }

        return $deleted;
    }
}
