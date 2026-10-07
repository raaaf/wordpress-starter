<?php

declare(strict_types=1);

namespace WordpressStarter\Services;

use WordpressStarter\Acf\FlexibleContent;
use WordpressStarter\Providers\LogServiceProvider;
use WordpressStarter\ThemeContext;

/**
 * One-time move of the removed per-tab text (`tabs_{j}_content`) into a nested
 * `one_column` module at position 0 of the same tab.
 *
 * The tab text field is gone, a tab now holds modules only. Pages saved before
 * that still carry the text as post meta; without this move it would stay in
 * the database and never render again. plan() is the pure part (meta in,
 * operations out), the rest is WordPress glue around it.
 */
final class TabsContentMigration
{
    private const LOCK_TTL = 5 * MINUTE_IN_SECONDS;

    /**
     * Handler for init (late, after ACF registered the local fields). Runs on
     * every request, not only in wp-admin: the live pages must not lose their
     * tab text while nobody happens to open the admin. After the first run the
     * cost is one autoloaded option read. A failure is logged and never breaks
     * the request; the flag stays unset, so the next request tries again.
     */
    public static function maybeRun(): void
    {
        if (self::isDone()) {
            return;
        }

        // Two requests must not shift the same modules twice.
        $lockKey = ThemeContext::optionKey('tabs_content_migration_lock');
        $held = self::acquireLock($lockKey);

        if ($held === null) {
            return;
        }

        try {
            // The winner of an earlier race may have finished while we waited.
            wp_load_alloptions(true);

            if (self::isDone()) {
                return;
            }

            // Only after every post is done: an interrupted run resumes next time.
            // null means this theme has no one_column layout the text could move
            // into; flag it anyway, or every request would take the lock again.
            if (self::migrate() === null) {
                LogServiceProvider::warning('Tabs content migration skipped: no one_column layout to move the text into');
            }

            update_option(ThemeContext::optionKey('tabs_content_migrated'), '1', autoload: true);
        } catch (\Throwable $e) {
            LogServiceProvider::error('Tabs content migration failed', ['error' => $e->getMessage()]);
        } finally {
            self::releaseLock($lockKey, $held);
        }
    }

    /**
     * Whether the migration finished on an earlier run.
     *
     * @phpstan-impure
     */
    private static function isDone(): bool
    {
        return get_option(ThemeContext::optionKey('tabs_content_migrated')) === '1';
    }

    /**
     * Take the lock; returns the value that proves ownership, null if someone
     * else holds it. The value is "token|time": add_option() inserts only when
     * the row does not exist yet, and a stale lock is taken over by a
     * compare-and-swap on the old value, so two requests never both win.
     *
     * @phpstan-impure
     */
    private static function acquireLock(string $lockKey): ?string
    {
        global $wpdb;

        $value = wp_generate_uuid4() . '|' . time();

        if (add_option($lockKey, $value, '', false)) {
            return $value;
        }

        wp_cache_delete($lockKey, 'options');
        $current = get_option($lockKey);

        $since = is_string($current) ? (int) substr(strrchr($current, '|') ?: '|0', 1) : time();

        if (!is_string($current) || time() - $since < self::LOCK_TTL) {
            return null;
        }

        // A crashed run left the lock behind.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $swapped = $wpdb->update($wpdb->options, ['option_value' => $value], ['option_name' => $lockKey, 'option_value' => $current]);
        wp_cache_delete($lockKey, 'options');

        return $swapped === 1 ? $value : null;
    }

    /**
     * Release the lock, but only while it still holds our value.
     */
    private static function releaseLock(string $lockKey, string $held): void
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete($wpdb->options, ['option_name' => $lockKey, 'option_value' => $held]);
        wp_cache_delete($lockKey, 'options');
        wp_cache_delete('notoptions', 'options');
    }

    /**
     * Move the tab text of every affected post.
     *
     * @return int|null Number of tabs whose text became a module, null if it could not run
     *
     * @throws \RuntimeException When a post does not hold the planned meta after writing
     * @throws \Throwable        When a post's changes fail; that post is rolled back
     */
    public static function migrate(): ?int
    {
        global $wpdb;

        $contentKey = self::oneColumnContentKey();

        if ($contentKey === null) {
            return null;
        }

        // One-time migration, a REGEXP over meta keys has no WP API equivalent.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $postIds = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key REGEXP %s",
            '^page_sections_[0-9]+_tabs_[0-9]+_content$',
        ));

        $migrated = 0;

        foreach ($postIds as $postId) {
            $postId = (int) $postId;
            $meta = [];

            foreach (get_post_meta($postId) as $key => $values) {
                if (preg_match('/^_?page_sections(_|$)/', (string) $key)) {
                    $meta[$key] = maybe_unserialize($values[0] ?? '');
                }
            }

            $plan = self::plan($meta, FlexibleContent::NESTED_MODULES_KEY, $contentKey);

            // One transaction per post: an interruption between the shift and the
            // delete would leave a half-shifted modules list the next run shifts again.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query('START TRANSACTION');

            try {
                foreach ($plan['set'] as $key => $value) {
                    update_post_meta($postId, $key, wp_slash($value));
                }

                foreach ($plan['delete'] as $key) {
                    delete_post_meta($postId, $key);
                }

                // update_post_meta()/delete_post_meta() return false instead of throwing
                // (also for an unchanged value), so read the result back before COMMIT.
                wp_cache_delete($postId, 'post_meta');
                $actual = [];

                foreach (get_post_meta($postId) as $key => $values) {
                    $actual[$key] = maybe_unserialize($values[0] ?? '');
                }

                $failed = self::verifyApplied($plan, $actual);

                if ($failed !== null) {
                    throw new \RuntimeException("Tabs content migration: post {$postId} does not hold the planned meta at {$failed}");
                }

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->query('COMMIT');
            } catch (\Throwable $e) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->query('ROLLBACK');

                throw $e;
            } finally {
                clean_post_cache($postId);
            }

            $migrated += $plan['migrated'];
        }

        return $migrated;
    }

    /**
     * Operations that move the tab text of one post into modules.
     *
     * @param array<string, mixed> $meta        Meta of the post (key => value), incl. page_sections
     * @param string               $modulesKey  ACF key of the nested modules field
     * @param string               $contentKey  ACF key of the one_column content field
     *
     * @return array{set: array<string, mixed>, delete: array<int, string>, migrated: int}
     */
    public static function plan(array $meta, string $modulesKey, string $contentKey): array
    {
        $set = [];
        $delete = [];
        $migrated = 0;

        foreach ($meta as $key => $content) {
            if (!preg_match('/^(page_sections_(\d+)_tabs_\d+)_content$/', (string) $key, $match)) {
                continue;
            }

            // The key shape alone is not proof: another layout may carry a "tabs" field.
            $sections = $meta['page_sections'] ?? [];
            $section = (int) $match[2];

            if (!is_array($sections) || ( $sections[$section] ?? null ) !== 'tabs') {
                continue;
            }

            $prefix = $match[1];
            $delete[] = $prefix . '_content';
            $delete[] = '_' . $prefix . '_content';

            if (!is_string($content) || trim($content) === '') {
                continue;
            }

            // Already moved by an earlier (interrupted or concurrent) run: the module
            // is there, only the legacy keys are left. Shifting again would duplicate it.
            $modules = $meta[$prefix . '_modules'] ?? null;

            if (is_array($modules) && ( $modules[0] ?? null ) === 'one_column'
                && ( $meta[$prefix . '_modules_0_content'] ?? null ) === $content) {
                continue;
            }

            ++$migrated;

            // Existing modules move one slot down, sub keys and "_" reference keys included.
            $pattern = '/^(_?)' . preg_quote($prefix, '/') . '_modules_(\d+)_(.+)$/';

            foreach ($meta as $other => $value) {
                if (preg_match($pattern, (string) $other, $slot)) {
                    $delete[] = $other;
                    $set[$slot[1] . $prefix . '_modules_' . ( (int) $slot[2] + 1 ) . '_' . $slot[3]] = $value;
                }
            }

            $existing = $meta[$prefix . '_modules'] ?? [];

            $set[$prefix . '_modules'] = ['one_column', ...( is_array($existing) ? array_values($existing) : [] )];
            $set['_' . $prefix . '_modules'] = $modulesKey;
            $set[$prefix . '_modules_0_content'] = $content;
            $set['_' . $prefix . '_modules_0_content'] = $contentKey;
        }

        // Highest module index first: a shifted slot never overwrites one not yet written.
        $slot = static fn (string $key): int => preg_match('/_modules_(\d+)_/', $key, $m) ? (int) $m[1] : -1;
        uksort($set, static fn (string $a, string $b): int => $slot($b) <=> $slot($a));

        return [
            'set' => $set,
            'delete' => array_values(array_diff(array_unique($delete), array_keys($set))),
            'migrated' => $migrated,
        ];
    }

    /**
     * First key where the stored meta differs from the plan, null if all good.
     *
     * @param array{set: array<string, mixed>, delete: array<int, string>} $plan
     * @param array<string, mixed>                                         $actual Meta as stored (key => unserialized value)
     */
    public static function verifyApplied(array $plan, array $actual): ?string
    {
        foreach ($plan['set'] as $key => $value) {
            // Loose on purpose: ids and counts come back as strings.
            // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
            if (!array_key_exists($key, $actual) || $actual[$key] != $value) {
                return (string) $key;
            }
        }

        foreach ($plan['delete'] as $key) {
            if (array_key_exists($key, $actual)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * The rewritten ACF key of the content field of the nested one_column
     * layout, read from the real field tree instead of guessed.
     */
    private static function oneColumnContentKey(): ?string
    {
        foreach (FlexibleContent::layouts() as $layout) {
            if (( $layout['name'] ?? '' ) !== 'tabs') {
                continue;
            }

            foreach ($layout['sub_fields'] as $field) {
                if (( $field['name'] ?? '' ) !== 'tabs') {
                    continue;
                }

                foreach ($field['sub_fields'] as $sub) {
                    if (( $sub['key'] ?? '' ) !== FlexibleContent::NESTED_MODULES_KEY) {
                        continue;
                    }

                    foreach ($sub['layouts'] as $nested) {
                        if (( $nested['name'] ?? '' ) !== 'one_column') {
                            continue;
                        }

                        foreach ($nested['sub_fields'] as $nestedField) {
                            if (( $nestedField['name'] ?? '' ) === 'content') {
                                return $nestedField['key'];
                            }
                        }
                    }
                }
            }
        }

        return null;
    }
}
