<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

use phpseclib3\Net\SFTP;
use Throwable;
use WordpressStarter\Providers\LogServiceProvider;
use WordpressStarter\ThemeContext;
use WP_Query;

class FolderSync
{
    /**
     * Default cap on how many new member_download entries a single run() call may
     * create from remote SFTP directory listings, overridable via the
     * '{prefix}_member_area_sync_max_entries' filter.
     */
    private const DEFAULT_MAX_NEW_ENTRIES_PER_RUN = 500;

    /**
     * Scan SFTP folder connections and imported/external entries, creating CPT
     * entries for newly discovered remote files and refreshing availability.
     *
     * New entries created from remote directory listings are capped per run at
     * '{prefix}_member_area_sync_max_entries' filter (default:
     * self::DEFAULT_MAX_NEW_ENTRIES_PER_RUN) to bound wp_insert_post() calls
     * driven by an untrusted remote listing.
     */
    public static function run(): void
    {
        // Pre-load all existing SFTP identifiers in a single DB query to avoid N+1
        $existingIdentifiers = self::loadExistingIdentifiers();

        $maxNewEntries = (int) apply_filters(
            ThemeContext::prefix() . '_member_area_sync_max_entries',
            self::DEFAULT_MAX_NEW_ENTRIES_PER_RUN,
        );
        $newEntriesCreated = 0;
        $capLogged = false;

        // Scan SFTP folder parent entries (those without a download_sftp_source = created by admin)
        $folderPosts = new WP_Query([
            'post_type' => 'member_download',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'no_found_rows' => true,
            'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                ['key' => 'download_source_type', 'value' => 'sftp'],
                [
                    'relation' => 'OR',
                    ['key' => 'download_sftp_source', 'value' => '', 'compare' => '='],
                    ['key' => 'download_sftp_source', 'compare' => 'NOT EXISTS'],
                ],
            ],
            'fields' => 'ids',
        ]);

        self::syncGroupedByConnection(
            $folderPosts->posts,
            'download_sftp_path',
            '/',
            static fn (array $creds, string $path): bool => empty($creds['host']) || empty($creds['username']) || empty($creds['password']),
            'folder group',
            static function (SFTP $sftp, array $creds, int $postId, string $path) use (&$existingIdentifiers, &$newEntriesCreated, &$capLogged, $maxNewEntries): void {
                $files = self::scanSftpDirectory($sftp, $creds['host'], $creds['port'], $path);

                foreach ($files as $file) {
                    $identifier = $creds['host'] . ':' . $creds['port'] . $file['remotePath'];
                    if (!isset($existingIdentifiers[$identifier])) {
                        if ($newEntriesCreated >= $maxNewEntries) {
                            if (!$capLogged) {
                                LogServiceProvider::warning('SFTP sync reached per-run new-entry cap', [
                                    'max_new_entries' => $maxNewEntries,
                                ]);
                                $capLogged = true;
                            }

                            continue;
                        }

                        self::createSftpFileCpt($file, $creds['host'], $creds['port'], $creds['username'], $creds['password'], $path, $postId);
                        // Track newly created identifiers so duplicate files in the same run are not re-created
                        $existingIdentifiers[$identifier] = true;
                        $newEntriesCreated++;
                    }
                }

                self::updateSftpFolderAvailability($sftp, $postId, $path);

                // Mark as synced so the admin list can reflect the status
                update_post_meta($postId, '_sftp_synced', '1');
            },
        );

        // Check availability for imported SFTP file entries
        $importedPosts = new WP_Query([
            'post_type' => 'member_download',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'no_found_rows' => true,
            'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                ['key' => 'download_source_type', 'value' => 'sftp'],
                ['key' => 'download_sftp_source', 'value' => '', 'compare' => '!='],
            ],
            'fields' => 'ids',
        ]);

        self::syncGroupedByConnection(
            $importedPosts->posts,
            'download_sftp_remote_file',
            '',
            static fn (array $creds, string $remotePath): bool => empty($creds['host']) || empty($remotePath),
            'imported file group',
            static function (SFTP $sftp, array $creds, int $postId, string $remotePath): void {
                self::updateSftpFileAvailability($sftp, $postId, $remotePath);
            },
        );

        // Check availability for external-type entries
        $externalPosts = new WP_Query([
            'post_type' => 'member_download',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'no_found_rows' => true,
            'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                ['key' => 'download_source_type', 'value' => 'external'],
            ],
            'fields' => 'ids',
        ]);

        $externalUrlsByPostId = [];
        foreach ($externalPosts->posts as $postId) {
            $url = get_field('download_external_url', $postId) ?: '';
            if (empty($url)) {
                continue;
            }

            try {
                SsrfGuard::assertSafeUrl($url);
            } catch (Throwable $e) {
                LogServiceProvider::warning('Skipping external availability check', [
                    'post_id' => $postId,
                    'message' => $e->getMessage(),
                ]);

                continue;
            }

            $externalUrlsByPostId[$postId] = $url;
        }

        if (!empty($externalUrlsByPostId)) {
            self::updateExternalAvailabilityBatch($externalUrlsByPostId);
        }
    }

    /**
     * Load all existing download_sftp_identifier meta values in one DB query.
     *
     * @return array<string, true>
     */
    private static function loadExistingIdentifiers(): array
    {
        global $wpdb;

        $identifiers = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                'download_sftp_identifier',
            ),
        );

        return array_fill_keys($identifiers, true);
    }

    /**
     * Shared sync sequence: prime the postmeta cache, load credentials, group
     * posts by connection key to reuse SFTP connections, connect once per
     * connection, and invoke $process for each post entry.
     *
     * @param list<int> $postIds
     * @param string $pathMetaKey Meta key holding the per-post path value
     * @param string $pathDefault Fallback when the meta value is empty
     * @param callable(array{host: string, port: int, username: string, password: string}, string): bool $isIncomplete Returns true when an entry lacks required values and must be skipped
     * @param string $logContext Context suffix for the connection-failure log message
     * @param callable(SFTP, array{host: string, port: int, username: string, password: string}, int, string): void $process Invoked per post: ($sftp, $creds, $postId, $path)
     */
    private static function syncGroupedByConnection(
        array $postIds,
        string $pathMetaKey,
        string $pathDefault,
        callable $isIncomplete,
        string $logContext,
        callable $process,
    ): void {
        // Prime the postmeta cache for all post IDs to avoid N+1 DB queries
        update_meta_cache('post', $postIds);

        /** @var array<string, list<array{postId: int, path: string}>> $groups */
        $groups = [];
        /** @var array<string, array{host: string, port: int, username: string, password: string}> $credentials */
        $credentials = [];

        foreach ($postIds as $postId) {
            $host = get_post_meta($postId, 'download_sftp_host', true) ?: '';
            $port = (int) ( get_post_meta($postId, 'download_sftp_port', true) ?: 22 ) ?: 22;
            $username = get_post_meta($postId, 'download_sftp_username', true) ?: '';
            $password = Crypto::decrypt(get_post_meta($postId, 'download_sftp_password', true) ?: '') ?? '';
            $path = get_post_meta($postId, $pathMetaKey, true) ?: $pathDefault;

            $creds = compact('host', 'port', 'username', 'password');

            if ($isIncomplete($creds, $path)) {
                continue;
            }

            $connKey = md5($host . ':' . $port . ':' . $username . ':' . $password);

            if (!isset($credentials[$connKey])) {
                $credentials[$connKey] = $creds;
            }

            $groups[$connKey][] = ['postId' => $postId, 'path' => $path];
        }

        foreach ($groups as $connKey => $entries) {
            $creds = $credentials[$connKey];

            try {
                $sftp = SftpClient::connect($creds['host'], $creds['port'], $creds['username'], $creds['password']);
            } catch (Throwable $e) {
                LogServiceProvider::warning('SFTP connection failed for ' . $logContext, [
                    'host' => $creds['host'],
                    'message' => $e->getMessage(),
                ]);

                continue;
            }

            foreach ($entries as ['postId' => $postId, 'path' => $path]) {
                $process($sftp, $creds, $postId, $path);
            }
        }
    }

    /**
     * Scan an SFTP directory and return a list of files with their remote paths.
     *
     * @return array<int, array{filename: string, ext: string, mtime: int|null, size: int, remotePath: string}>
     */
    private static function scanSftpDirectory(
        SFTP $sftp,
        string $host,
        int $port,
        string $remotePath,
    ): array {
        try {
            $files = SftpClient::listFiles($sftp, $remotePath, DownloadFileTypes::allowed());

            $normalizedPath = rtrim($remotePath, '/') . '/';

            return array_map(static function (array $file) use ($normalizedPath): array {
                $file['remotePath'] = $normalizedPath . $file['filename'];

                return $file;
            }, $files);
        } catch (Throwable $e) {
            LogServiceProvider::warning('SFTP scan failed', [
                'host' => $host,
                'port' => $port,
                'path' => $remotePath,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Create a new CPT post for a file imported from an SFTP folder listing.
     *
     * @param array{filename: string, ext: string, mtime: int|null, size: int, remotePath: string} $file
     */
    private static function createSftpFileCpt(
        array $file,
        string $host,
        int $port,
        string $username,
        string $password,
        string $folderPath,
        int $parentPostId,
    ): void {
        $title = pathinfo($file['filename'], PATHINFO_FILENAME);
        $identifier = $host . ':' . $port . $file['remotePath'];

        $postId = wp_insert_post([
            'post_type' => 'member_download',
            'post_status' => 'publish',
            'post_title' => $title,
        ]);

        if (is_wp_error($postId) || $postId === 0) {
            LogServiceProvider::warning('Failed to create CPT entry for SFTP file', [
                'filename' => $file['filename'],
            ]);

            return;
        }

        $lastModified = $file['mtime'] !== null ? gmdate('c', $file['mtime']) : '';

        update_field('download_source_type', 'sftp', $postId);
        update_field('download_sftp_host', $host, $postId);
        update_field('download_sftp_port', $port, $postId);
        update_field('download_sftp_username', $username, $postId);
        update_field('download_sftp_password', Crypto::encrypt($password), $postId);
        update_field('download_sftp_remote_file', $file['remotePath'], $postId);
        update_field('download_sftp_identifier', $identifier, $postId);
        update_field('download_sftp_source', $folderPath, $postId);
        update_field('download_available', true, $postId);
        update_field('download_last_modified', $lastModified, $postId);

        // Copy taxonomy terms from the parent folder entry
        $terms = wp_get_post_terms($parentPostId, 'download_category', ['fields' => 'ids']);
        if (!is_wp_error($terms) && !empty($terms)) {
            wp_set_post_terms($postId, $terms, 'download_category');
        }
    }

    /**
     * Update availability for a parent SFTP folder entry.
     */
    private static function updateSftpFolderAvailability(
        SFTP $sftp,
        int $postId,
        string $remotePath,
    ): void {
        try {
            $available = $sftp->is_dir($remotePath);
        } catch (Throwable $e) {
            $available = false;
            LogServiceProvider::warning('SFTP folder availability check failed', [
                'post_id' => $postId,
                'message' => $e->getMessage(),
            ]);
        }

        self::updateFieldIfChanged($postId, 'download_available', $available, false, castBool: true);
    }

    /**
     * Update availability and last-modified for an individual imported SFTP file entry.
     */
    private static function updateSftpFileAvailability(
        SFTP $sftp,
        int $postId,
        string $remotePath,
    ): void {
        try {
            $available = SftpClient::fileExists($sftp, $remotePath);
            $stat = $sftp->stat($remotePath);
            $mtime = isset($stat['mtime']) && is_int($stat['mtime']) ? $stat['mtime'] : null;
        } catch (Throwable $e) {
            $available = false;
            $mtime = null;
            LogServiceProvider::warning('SFTP file availability check failed', [
                'post_id' => $postId,
                'message' => $e->getMessage(),
            ]);
        }

        self::updateFieldIfChanged($postId, 'download_available', $available, false, castBool: true);

        if (isset($mtime)) {
            self::updateFieldIfChanged($postId, 'download_last_modified', gmdate('c', $mtime), '');
        }
    }

    /**
     * Check availability for a batch of external URLs in a single round of parallel
     * HEAD requests, then persist the results per post. Batching avoids one sequential
     * wp_remote_head() call per post (both from the daily cron and the synchronous
     * admin manual-sync AJAX handler).
     *
     * This intentionally bypasses the WordPress HTTP API (WP_HTTP_BLOCK_EXTERNAL,
     * WP_PROXY_*, the pre_http_request/http_request_args filters) because that API
     * has no batched/parallel request call: one round-trip per sync run via
     * Requests::request_multiple() instead of N sequential wp_remote_head() calls
     * and their timeouts. WP_HTTP_BLOCK_EXTERNAL is checked explicitly below so a
     * site that blocks external requests is still honoured; the other WP HTTP API
     * facilities (proxy settings, request filters) remain bypassed.
     *
     * @param array<int, string> $urlsByPostId Post ID => external URL.
     */
    private static function updateExternalAvailabilityBatch(array $urlsByPostId): void
    {
        if (defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL) {
            LogServiceProvider::warning('Skipping batched external availability check: WP_HTTP_BLOCK_EXTERNAL is set', []);

            return;
        }

        $requests = [];
        foreach ($urlsByPostId as $postId => $url) {
            $requests[$postId] = [
                'url' => $url,
                'type' => \WpOrg\Requests\Requests::HEAD,
            ];
        }

        try {
            $responses = \WpOrg\Requests\Requests::request_multiple($requests, [
                'timeout' => 5,
                // Mirrors wp_remote_head()'s sslcertificates default.
                // WPINC is 'wp-includes'; hardcoded because phpstan's WP stubs don't declare the constant.
                'verify' => ABSPATH . 'wp-includes/certificates/ca-bundle.crt',
                // Mirrors wp_remote_head()'s redirection => 0 default (HEAD requests don't follow redirects).
                'follow_redirects' => false,
            ]);
        } catch (Throwable $e) {
            LogServiceProvider::warning('Batched external availability check failed', [
                'message' => $e->getMessage(),
            ]);
            $responses = [];
        }

        foreach ($urlsByPostId as $postId => $url) {
            $response = $responses[$postId] ?? null;

            if (!$response instanceof \WpOrg\Requests\Response) {
                self::updateFieldIfChanged($postId, 'download_available', false, false, castBool: true);

                continue;
            }

            $available = $response->status_code >= 200 && $response->status_code < 300;
            self::updateFieldIfChanged($postId, 'download_available', $available, false, castBool: true);

            $lastModified = $response->headers['last-modified'] ?? null;
            if (!empty($lastModified)) {
                $timestamp = strtotime($lastModified);
                if ($timestamp !== false) {
                    self::updateFieldIfChanged($postId, 'download_last_modified', gmdate('c', $timestamp), '');
                }
            }
        }
    }

    /**
     * Fetch a field's current value and write the new one only if it changed.
     *
     * @return bool Whether the field was written.
     */
    private static function updateFieldIfChanged(int $postId, string $field, mixed $value, mixed $default, bool $castBool = false): bool
    {
        $current = get_field($field, $postId) ?: $default;
        if ($castBool) {
            $current = (bool) $current;
        }

        if ($current !== $value) {
            update_field($field, $value, $postId);

            return true;
        }

        return false;
    }
}
