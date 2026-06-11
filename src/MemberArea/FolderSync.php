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
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip'];

    public static function run(): void
    {
        // Pre-load all existing SFTP identifiers in a single DB query to avoid N+1
        $existingIdentifiers = self::loadExistingIdentifiers();

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
            static function (SFTP $sftp, array $creds, int $postId, string $path) use (&$existingIdentifiers): void {
                $files = self::scanSftpDirectory($sftp, $creds['host'], $creds['port'], $path);

                foreach ($files as $file) {
                    $identifier = $creds['host'] . ':' . $creds['port'] . $file['remotePath'];
                    if (!isset($existingIdentifiers[$identifier])) {
                        self::createSftpFileCpt($file, $creds['host'], $creds['port'], $creds['username'], $creds['password'], $path, $postId);
                        // Track newly created identifiers so duplicate files in the same run are not re-created
                        $existingIdentifiers[$identifier] = true;
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

        foreach ($externalPosts->posts as $postId) {
            $url = get_field('download_external_url', $postId) ?: '';
            if (!empty($url)) {
                self::updateExternalAvailability($postId, $url);
            }
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
            $files = SftpClient::listFiles($sftp, $remotePath, self::ALLOWED_EXTENSIONS);

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

        $current = (bool) get_field('download_available', $postId);
        if ($current !== $available) {
            update_field('download_available', $available, $postId);
        }
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

        $current = (bool) get_field('download_available', $postId);
        if ($current !== $available) {
            update_field('download_available', $available, $postId);
        }

        if (isset($mtime)) {
            $iso = gmdate('c', $mtime);
            $stored = get_field('download_last_modified', $postId) ?: '';
            if ($stored !== $iso) {
                update_field('download_last_modified', $iso, $postId);
            }
        }
    }

    /**
     * Update availability for an external URL entry via HEAD request.
     */
    private static function updateExternalAvailability(int $postId, string $url): void
    {
        try {
            SsrfGuard::assertSafeUrl($url);
        } catch (Throwable $e) {
            error_log(ThemeContext::logPrefix() . ': Skipping external availability check for post ' . $postId . ' — ' . $e->getMessage()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

            return;
        }

        $response = wp_remote_head($url, ['timeout' => 10]);
        $available = !is_wp_error($response) && (int) wp_remote_retrieve_response_code($response) >= 200
            && (int) wp_remote_retrieve_response_code($response) < 300;

        $current = (bool) get_field('download_available', $postId);
        if ($current !== $available) {
            update_field('download_available', $available, $postId);
        }

        if (!is_wp_error($response)) {
            $lastModified = wp_remote_retrieve_header($response, 'last-modified');
            if (!empty($lastModified)) {
                $timestamp = strtotime($lastModified);
                if ($timestamp !== false) {
                    $iso = gmdate('c', $timestamp);
                    $stored = get_field('download_last_modified', $postId) ?: '';
                    if ($stored !== $iso) {
                        update_field('download_last_modified', $iso, $postId);
                    }
                }
            }
        }
    }
}
