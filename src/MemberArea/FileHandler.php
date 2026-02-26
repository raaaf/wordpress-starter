<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

class FileHandler
{
    private const ALLOWED_PROTOCOLS = ['https', 'http'];
    private const SSRF_BLOCKED_RANGES = [
        '/^127\./',
        '/^10\./',
        '/^192\.168\./',
        '/^172\.(1[6-9]|2[0-9]|3[01])\./',
        '/^::1$/',
        '/^localhost$/i',
    ];

    public static function handleDownload(): void
    {
        if (!Auth::isAuthenticated()) {
            wp_send_json_error(['message' => __('Nicht authentifiziert.', 'wp-starter')], 401);
        }

        $nonce = sanitize_text_field(wp_unslash($_GET['nonce'] ?? ''));
        $index = isset($_GET['download_index']) ? absint($_GET['download_index']) : -1;

        if ($index < 0) {
            wp_send_json_error(['message' => __('Ungültiger Download-Index.', 'wp-starter')], 400);
        }

        if (!wp_verify_nonce($nonce, 'member_download_' . $index)) {
            wp_send_json_error(['message' => __('Ungültige Anfrage.', 'wp-starter')], 403);
        }

        $downloads = get_field('member_downloads', 'option') ?: [];
        $entry = $downloads[$index] ?? null;

        if (!$entry) {
            wp_send_json_error(['message' => __('Dokument nicht gefunden.', 'wp-starter')], 404);
        }

        $available = $entry['download_available'] ?? true;
        if (!$available) {
            wp_send_json_error(['message' => __('Datei nicht verfügbar.', 'wp-starter')], 503);
        }

        $sourceType = $entry['download_source_type'] ?? 'upload';

        match ($sourceType) {
            'upload'   => self::streamUpload($entry),
            'external' => self::redirectExternal($entry),
            'folder'   => self::streamFolder($entry),
            default    => wp_send_json_error(['message' => __('Unbekannter Quelltyp.', 'wp-starter')], 400),
        };
    }

    /**
     * @param array<string, mixed> $entry
     */
    private static function streamUpload(array $entry): never
    {
        $file = $entry['download_file'] ?? null;
        if (!$file) {
            wp_send_json_error(['message' => __('Keine Datei hinterlegt.', 'wp-starter')], 404);
        }

        $fileId   = is_array($file) ? ( $file['ID'] ?? 0 ) : (int) $file;
        $filePath = get_attached_file($fileId);

        if (!$filePath || !file_exists($filePath)) {
            wp_send_json_error(['message' => __('Datei nicht verfügbar.', 'wp-starter')], 404);
        }

        $fileName = is_array($file) ? ( $file['filename'] ?? '' ) : '';
        if (empty($fileName)) {
            $fileUrl  = wp_get_attachment_url($fileId);
            $fileName = $fileUrl ? basename($fileUrl) : 'download';
        }

        $mimeType = is_array($file) ? ( $file['mime_type'] ?? '' ) : '';
        if (empty($mimeType)) {
            $mimeType = get_post_mime_type($fileId) ?: 'application/octet-stream';
        }

        nocache_headers();
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . esc_attr($fileName) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('X-Content-Type-Options: nosniff');

        readfile($filePath);
        exit;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private static function redirectExternal(array $entry): never
    {
        $url = $entry['download_external_url'] ?? '';

        if (empty($url)) {
            wp_send_json_error(['message' => __('Keine URL hinterlegt.', 'wp-starter')], 404);
        }

        self::assertSafeUrl($url);

        // URL is already validated against SSRF and protocol allowlist above.
        wp_redirect($url, 302); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
        exit;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private static function streamFolder(array $entry): never
    {
        $fileUrl  = $entry['download_external_url'] ?? '';
        $username = $entry['download_folder_username'] ?? '';
        $password = $entry['download_folder_password'] ?? '';

        // For folder-type entries that represent individual files, the URL
        // is stored in download_external_url (set by FolderSync on import).
        if (empty($fileUrl)) {
            wp_send_json_error(['message' => __('Keine Datei-URL hinterlegt.', 'wp-starter')], 404);
        }

        self::assertSafeUrl($fileUrl);

        $args = ['timeout' => 30, 'stream' => false];

        if (!empty($username) && !empty($password)) {
            $args['headers'] = [
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
            ];
        }

        $response = wp_remote_get($fileUrl, $args);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => __('Datei konnte nicht abgerufen werden.', 'wp-starter')], 502);
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        if ($statusCode !== 200) {
            wp_send_json_error(['message' => __('Datei nicht verfügbar.', 'wp-starter')], (int) $statusCode);
        }

        $body     = wp_remote_retrieve_body($response);
        $mimeType = wp_remote_retrieve_header($response, 'content-type') ?: 'application/octet-stream';
        // Strip charset suffix if present
        $mimeType = explode(';', $mimeType)[0];
        $fileName = basename( (string) wp_parse_url($fileUrl, PHP_URL_PATH));

        nocache_headers();
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . esc_attr($fileName) . '"');
        header('Content-Length: ' . strlen($body));
        header('X-Content-Type-Options: nosniff');

        echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    private static function assertSafeUrl(string $url): void
    {
        $parsed = wp_parse_url($url);

        if (!$parsed || empty($parsed['scheme']) || !in_array($parsed['scheme'], self::ALLOWED_PROTOCOLS, true)) {
            wp_send_json_error(['message' => __('Ungültiges URL-Schema.', 'wp-starter')], 400);
        }

        $host = $parsed['host'] ?? '';

        foreach (self::SSRF_BLOCKED_RANGES as $pattern) {
            if (preg_match($pattern, $host)) {
                wp_send_json_error(['message' => __('Diese URL ist nicht erlaubt.', 'wp-starter')], 403);
            }
        }
    }
}
