<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

class FolderSync
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip'];

    public static function run(): void
    {
        $downloads = get_field('member_downloads', 'option') ?: [];

        // Collect folder entries and check availability for all external/folder entries
        $updated = false;

        foreach ($downloads as $index => $entry) {
            $sourceType = $entry['download_source_type'] ?? 'upload';

            if ($sourceType === 'folder') {
                $folderUrl = $entry['download_folder_url'] ?? '';
                $username  = $entry['download_folder_username'] ?? '';
                $password  = $entry['download_folder_password'] ?? '';

                if (empty($folderUrl)) {
                    continue;
                }

                // Scan folder for new files
                $files = self::scanFolder($folderUrl, $username, $password);

                foreach ($files as $file) {
                    if (!self::entryExists($downloads, $file['url'])) {
                        $downloads[]   = self::buildFolderFileEntry($file, $folderUrl, $username, $password);
                        $updated       = true;
                    }
                }
            }

            if (in_array($sourceType, ['external', 'folder'], true)) {
                $urlToCheck = $sourceType === 'folder'
                    ? ( $entry['download_folder_url'] ?? '' )
                    : ( $entry['download_external_url'] ?? '' );

                if (!empty($urlToCheck)) {
                    $available = self::isAvailable($urlToCheck, $entry['download_folder_username'] ?? '', $entry['download_folder_password'] ?? '');
                    if ( (bool) ( $entry['download_available'] ?? true ) !== $available) {
                        $downloads[$index]['download_available'] = $available ? 1 : 0;
                        $updated = true;
                    }

                    // Fetch last-modified for non-upload entries
                    $lastModified = self::fetchLastModified($urlToCheck, $entry['download_folder_username'] ?? '', $entry['download_folder_password'] ?? '');
                    if ($lastModified && ( $entry['download_last_modified'] ?? '' ) !== $lastModified) {
                        $downloads[$index]['download_last_modified'] = $lastModified;
                        $updated = true;
                    }
                }
            }
        }

        // Also check availability for individually imported folder-file entries
        foreach ($downloads as $index => $entry) {
            $sourceType = $entry['download_source_type'] ?? 'upload';
            if ($sourceType !== 'folder' || empty($entry['download_folder_source'] ?? '')) {
                continue;
            }

            $fileUrl  = $entry['download_external_url'] ?? '';
            $username = $entry['download_folder_username'] ?? '';
            $password = $entry['download_folder_password'] ?? '';

            if (!empty($fileUrl)) {
                $available = self::isAvailable($fileUrl, $username, $password);
                if ( (bool) ( $entry['download_available'] ?? true ) !== $available) {
                    $downloads[$index]['download_available'] = $available ? 1 : 0;
                    $updated = true;
                }

                $lastModified = self::fetchLastModified($fileUrl, $username, $password);
                if ($lastModified && ( $entry['download_last_modified'] ?? '' ) !== $lastModified) {
                    $downloads[$index]['download_last_modified'] = $lastModified;
                    $updated = true;
                }
            }
        }

        if ($updated) {
            update_field('member_downloads', $downloads, 'option');
        }
    }

    /**
     * Scan an HTTP directory listing and return a list of files.
     *
     * @return array<int, array{url: string, filename: string, ext: string}>
     */
    public static function scanFolder(string $url, string $username, string $password): array
    {
        $args = ['timeout' => 15];

        if (!empty($username) && !empty($password)) {
            $args['headers'] = [
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
            ];
        }

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return [];
        }

        $html = wp_remote_retrieve_body($response);

        return self::parseDirectoryListing($html, $url);
    }

    /**
     * Parse Apache/Nginx directory listing HTML and extract downloadable file links.
     *
     * @return array<int, array{url: string, filename: string, ext: string}>
     */
    public static function parseDirectoryListing(string $html, string $baseUrl): array
    {
        $files = [];

        $dom = new \DOMDocument();
        @$dom->loadHTML($html); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        $links = $dom->getElementsByTagName('a');

        foreach ($links as $link) {
            /** @var \DOMElement $link */
            $href = $link->getAttribute('href');

            // Skip parent dir, query-string sort links, absolute paths, and fragments
            if (
                $href === '../' ||
                str_starts_with($href, '?') ||
                str_starts_with($href, '/') ||
                str_starts_with($href, '#') ||
                str_starts_with($href, 'http')
            ) {
                continue;
            }

            $ext = strtolower(pathinfo($href, PATHINFO_EXTENSION));

            if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                continue;
            }

            $files[] = [
                'url'      => rtrim($baseUrl, '/') . '/' . ltrim($href, '/'),
                'filename' => rawurldecode($href),
                'ext'      => $ext,
            ];
        }

        return $files;
    }

    /**
     * Perform a HEAD request and return the Last-Modified value as ISO 8601 string.
     */
    public static function fetchLastModified(string $url, string $username, string $password): ?string
    {
        $args = ['timeout' => 10, 'method' => 'HEAD'];

        if (!empty($username) && !empty($password)) {
            $args['headers'] = [
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
            ];
        }

        $response = wp_remote_head($url, $args);

        if (is_wp_error($response)) {
            return null;
        }

        $lastModified = wp_remote_retrieve_header($response, 'last-modified');

        if (empty($lastModified)) {
            return null;
        }

        $timestamp = strtotime($lastModified);

        if ($timestamp === false) {
            return null;
        }

        return gmdate('c', $timestamp);
    }

    /**
     * Check whether a URL is reachable (HTTP 2xx response).
     */
    public static function isAvailable(string $url, string $username, string $password): bool
    {
        $args = ['timeout' => 10, 'method' => 'HEAD'];

        if (!empty($username) && !empty($password)) {
            $args['headers'] = [
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
            ];
        }

        $response = wp_remote_head($url, $args);

        if (is_wp_error($response)) {
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        return $code >= 200 && $code < 300;
    }

    /**
     * Check whether a file URL already exists as an entry in the downloads repeater.
     *
     * @param array<int, array<string, mixed>> $downloads
     */
    private static function entryExists(array $downloads, string $fileUrl): bool
    {
        foreach ($downloads as $entry) {
            if (( $entry['download_external_url'] ?? '' ) === $fileUrl) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a new repeater entry for a file imported from a folder listing.
     *
     * @param array{url: string, filename: string, ext: string} $file
     * @return array<string, mixed>
     */
    private static function buildFolderFileEntry(array $file, string $folderUrl, string $username, string $password): array
    {
        $lastModified = self::fetchLastModified($file['url'], $username, $password);

        return [
            'download_title'           => pathinfo($file['filename'], PATHINFO_FILENAME),
            'download_description'     => '',
            'download_source_type'     => 'folder',
            'download_file'            => null,
            'download_external_url'    => $file['url'],
            'download_folder_url'      => '',
            'download_folder_username' => $username,
            'download_folder_password' => $password,
            'download_last_modified'   => $lastModified ?? '',
            'download_available'       => 1,
            'download_folder_source'   => $folderUrl,
            'download_category'        => 'general',
            'download_sort'            => 0,
        ];
    }
}
