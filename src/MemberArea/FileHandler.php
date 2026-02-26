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

        $nonce  = sanitize_text_field(wp_unslash($_GET['nonce'] ?? ''));
        $postId = isset($_GET['download_id']) ? absint($_GET['download_id']) : 0;

        if ($postId <= 0) {
            wp_send_json_error(['message' => __('Ungültige Download-ID.', 'wp-starter')], 400);
        }

        if (!wp_verify_nonce($nonce, 'member_download_' . $postId)) {
            wp_send_json_error(['message' => __('Ungültige Anfrage.', 'wp-starter')], 403);
        }

        $post = get_post($postId);
        if (!$post || $post->post_type !== 'member_download' || $post->post_status !== 'publish') {
            wp_send_json_error(['message' => __('Dokument nicht gefunden.', 'wp-starter')], 404);
        }

        $entry = [
            'download_source_type'       => get_field('download_source_type',       $postId),
            'download_file'              => get_field('download_file',               $postId),
            'download_external_url'      => get_field('download_external_url',       $postId),
            'download_sftp_host'         => get_field('download_sftp_host',          $postId),
            'download_sftp_port'         => get_field('download_sftp_port',          $postId),
            'download_sftp_username'     => get_field('download_sftp_username',      $postId),
            'download_sftp_password'     => get_field('download_sftp_password',      $postId),
            'download_sftp_remote_file'  => get_field('download_sftp_remote_file',   $postId),
        ];

        $available = (bool) get_field('download_available', $postId);
        if (!$available) {
            wp_send_json_error(['message' => __('Datei nicht verfügbar.', 'wp-starter')], 503);
        }

        $sourceType = $entry['download_source_type'] ?? 'upload';

        match ($sourceType) {
            'upload'   => self::streamUpload($entry),
            'external' => self::redirectExternal($entry),
            'sftp'     => self::streamSftp($entry),
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
    private static function streamSftp(array $entry): never
    {
        $host       = $entry['download_sftp_host']        ?? '';
        $port       = (int) ( $entry['download_sftp_port'] ?? 22 ) ?: 22;
        $username   = $entry['download_sftp_username']    ?? '';
        $password   = Crypto::decrypt($entry['download_sftp_password'] ?? '') ?? '';
        $remotePath = $entry['download_sftp_remote_file'] ?? '';

        if (empty($host) || empty($username) || empty($password) || empty($remotePath)) {
            wp_send_json_error(['message' => __('SFTP-Konfiguration unvollständig.', 'wp-starter')], 404);
        }

        try {
            SftpClient::assertSafeHost($host);
        } catch (\RuntimeException $e) {
            wp_send_json_error(['message' => __('Diese URL ist nicht erlaubt.', 'wp-starter')], 403);
        }

        try {
            $sftp     = SftpClient::connect($host, $port, $username, $password);
            $contents = SftpClient::readFile($sftp, $remotePath);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => __('Datei konnte nicht abgerufen werden.', 'wp-starter')], 502);
        }

        if ($contents === null) {
            wp_send_json_error(['message' => __('Datei nicht verfügbar.', 'wp-starter')], 404);
        }

        $fileName = basename($remotePath);
        $mimeType = self::guessMimeType($fileName);

        nocache_headers();
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . esc_attr($fileName) . '"');
        header('Content-Length: ' . strlen($contents));
        header('X-Content-Type-Options: nosniff');

        echo $contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    private static function guessMimeType(string $fileName): string
    {
        return match (strtolower(pathinfo($fileName, PATHINFO_EXTENSION))) {
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip'  => 'application/zip',
            default => 'application/octet-stream',
        };
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
