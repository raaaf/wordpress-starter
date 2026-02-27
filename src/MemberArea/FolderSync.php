<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

use WordpressStarter\Providers\LogServiceProvider;
use WordpressStarter\MemberArea\Crypto;

class FolderSync
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip'];

    public static function run(): void
    {
        // Scan SFTP folder parent entries (those without a download_sftp_source = created by admin)
        $folderPosts = new \WP_Query([
            'post_type'      => 'member_download',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                ['key' => 'download_source_type', 'value' => 'sftp'],
                [
                    'relation' => 'OR',
                    ['key' => 'download_sftp_source', 'value' => '', 'compare' => '='],
                    ['key' => 'download_sftp_source', 'compare' => 'NOT EXISTS'],
                ],
            ],
            'fields' => 'ids',
        ]);

        foreach ($folderPosts->posts as $postId) {
            $host     = get_field('download_sftp_host',     $postId) ?: '';
            $port     = (int) ( get_field('download_sftp_port', $postId) ?: 22 ) ?: 22;
            $username = get_field('download_sftp_username', $postId) ?: '';
            $password = Crypto::decrypt(get_field('download_sftp_password', $postId) ?: '') ?? '';
            $path     = get_field('download_sftp_path',     $postId) ?: '/';

            if (empty($host) || empty($username) || empty($password)) {
                continue;
            }

            $files = self::scanSftpDirectory($host, $port, $username, $password, $path);

            foreach ($files as $file) {
                $identifier = $host . ':' . $port . $file['remotePath'];
                if (!self::entryExistsByIdentifier($identifier)) {
                    self::createSftpFileCpt($file, $host, $port, $username, $password, $path, $postId);
                }
            }

            self::updateSftpFolderAvailability($postId, $host, $port, $username, $password, $path);

            // Mark as synced so the admin list can reflect the status
            update_post_meta($postId, '_sftp_synced', '1');
        }

        // Check availability for imported SFTP file entries
        $importedPosts = new \WP_Query([
            'post_type'      => 'member_download',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                ['key' => 'download_source_type', 'value' => 'sftp'],
                ['key' => 'download_sftp_source', 'value' => '', 'compare' => '!='],
            ],
            'fields' => 'ids',
        ]);

        foreach ($importedPosts->posts as $postId) {
            $host       = get_field('download_sftp_host',        $postId) ?: '';
            $port       = (int) ( get_field('download_sftp_port', $postId) ?: 22 ) ?: 22;
            $username   = get_field('download_sftp_username',    $postId) ?: '';
            $password   = Crypto::decrypt(get_field('download_sftp_password', $postId) ?: '') ?? '';
            $remotePath = get_field('download_sftp_remote_file', $postId) ?: '';

            if (!empty($host) && !empty($remotePath)) {
                self::updateSftpFileAvailability($postId, $host, $port, $username, $password, $remotePath);
            }
        }

        // Check availability for external-type entries
        $externalPosts = new \WP_Query([
            'post_type'      => 'member_download',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
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
     * Scan an SFTP directory and return a list of files with their remote paths.
     *
     * @return array<int, array{filename: string, ext: string, mtime: int|null, size: int, remotePath: string}>
     */
    private static function scanSftpDirectory(
        string $host,
        int $port,
        string $username,
        string $password,
        string $remotePath
    ): array {
        try {
            $sftp  = SftpClient::connect($host, $port, $username, $password);
            $files = SftpClient::listFiles($sftp, $remotePath, self::ALLOWED_EXTENSIONS);

            $normalizedPath = rtrim($remotePath, '/') . '/';

            return array_map(static function (array $file) use ($normalizedPath): array {
                $file['remotePath'] = $normalizedPath . $file['filename'];
                return $file;
            }, $files);
        } catch (\Throwable $e) {
            LogServiceProvider::warning('SFTP scan failed', [
                'host'    => $host,
                'path'    => $remotePath,
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Check whether a CPT entry already exists for a given SFTP identifier.
     */
    private static function entryExistsByIdentifier(string $identifier): bool
    {
        $query = new \WP_Query([
            'post_type'      => 'member_download',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                ['key' => 'download_sftp_identifier', 'value' => $identifier, 'compare' => '='],
            ],
            'fields' => 'ids',
        ]);

        return $query->found_posts > 0;
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
        int $parentPostId
    ): void {
        $title      = pathinfo($file['filename'], PATHINFO_FILENAME);
        $identifier = $host . ':' . $port . $file['remotePath'];

        $postId = wp_insert_post([
            'post_type'   => 'member_download',
            'post_status' => 'publish',
            'post_title'  => $title,
        ]);

        if (is_wp_error($postId) || $postId === 0) {
            LogServiceProvider::warning('Failed to create CPT entry for SFTP file', [
                'filename' => $file['filename'],
            ]);
            return;
        }

        $lastModified = $file['mtime'] !== null ? gmdate('c', $file['mtime']) : '';

        update_field('download_source_type',      'sftp',              $postId);
        update_field('download_sftp_host',         $host,               $postId);
        update_field('download_sftp_port',         $port,               $postId);
        update_field('download_sftp_username',     $username,           $postId);
        update_field('download_sftp_password',     $password,           $postId);
        update_field('download_sftp_remote_file',  $file['remotePath'], $postId);
        update_field('download_sftp_identifier',   $identifier,         $postId);
        update_field('download_sftp_source',       $folderPath,         $postId);
        update_field('download_available',         true,                $postId);
        update_field('download_last_modified',     $lastModified,       $postId);

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
        int $postId,
        string $host,
        int $port,
        string $username,
        string $password,
        string $remotePath
    ): void {
        try {
            $sftp      = SftpClient::connect($host, $port, $username, $password);
            $available = $sftp->is_dir($remotePath);
        } catch (\Throwable $e) {
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
        int $postId,
        string $host,
        int $port,
        string $username,
        string $password,
        string $remotePath
    ): void {
        try {
            $sftp      = SftpClient::connect($host, $port, $username, $password);
            $available = SftpClient::fileExists($sftp, $remotePath);
            $stat      = $sftp->stat($remotePath);
            $mtime     = isset($stat['mtime']) && is_int($stat['mtime']) ? $stat['mtime'] : null;
        } catch (\Throwable $e) {
            $available = false;
            $mtime     = null;
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
            $iso    = gmdate('c', $mtime);
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
        $response  = wp_remote_head($url, ['timeout' => 10]);
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
                    $iso    = gmdate('c', $timestamp);
                    $stored = get_field('download_last_modified', $postId) ?: '';
                    if ($stored !== $iso) {
                        update_field('download_last_modified', $iso, $postId);
                    }
                }
            }
        }
    }
}
