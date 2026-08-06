<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

use InvalidArgumentException;
use RuntimeException;
use Throwable;
use WordpressStarter\RateLimiter;

class FileHandler
{
    private const POST_TYPE = 'member_download';
    private const NONCE_PREFIX = 'member_download_';

    public static function handleDownload(): void
    {
        if (!Auth::isAuthenticated()) {
            wp_send_json_error(['message' => __('Nicht authentifiziert.', 'wp-starter')], 401);
        }

        $nonce = sanitize_text_field(wp_unslash($_GET['nonce'] ?? ''));
        $postId = isset($_GET['download_id']) ? absint($_GET['download_id']) : 0;

        if ($postId <= 0) {
            wp_send_json_error(['message' => __('Ungültige Download-ID.', 'wp-starter')], 400);
        }

        if (!wp_verify_nonce($nonce, self::NONCE_PREFIX . $postId)) {
            wp_send_json_error(['message' => __('Ungültige Anfrage.', 'wp-starter')], 403);
        }

        // Downloads trigger a backend file fetch (local read or SFTP round-trip) per
        // request, so the budget is far tighter than DownloadQuery's 60/60 listing
        // limit: 20 downloads per 60 seconds still covers a burst of manual clicks
        // through a result table without allowing scripted mass-fetching.
        RateLimiter::enforce('member_download', 20, 60);

        $post = get_post($postId);
        if (!$post || $post->post_type !== self::POST_TYPE || $post->post_status !== 'publish') {
            wp_send_json_error(['message' => __('Dokument nicht gefunden.', 'wp-starter')], 404);
        }

        $fields = get_fields($postId) ?: [];
        $entry = [
            'download_source_type' => $fields['download_source_type'] ?? null,
            'download_file' => $fields['download_file'] ?? null,
            'download_external_url' => $fields['download_external_url'] ?? null,
            'download_sftp_host' => $fields['download_sftp_host'] ?? null,
            'download_sftp_port' => $fields['download_sftp_port'] ?? null,
            'download_sftp_username' => $fields['download_sftp_username'] ?? null,
            'download_sftp_password' => $fields['download_sftp_password'] ?? null,
            'download_sftp_remote_file' => $fields['download_sftp_remote_file'] ?? null,
        ];

        $available = (bool) ( $fields['download_available'] ?? false );
        if (!$available) {
            wp_send_json_error(['message' => __('Datei nicht verfügbar.', 'wp-starter')], 503);
        }

        $sourceType = $entry['download_source_type'] ?? 'upload';

        match ($sourceType) {
            'upload' => self::streamUpload($entry),
            'external' => self::redirectExternal($entry),
            'sftp' => self::streamSftp($entry),
            default => wp_send_json_error(['message' => __('Unbekannter Quelltyp.', 'wp-starter')], 400),
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

        $fileId = is_array($file) ? ( $file['ID'] ?? 0 ) : (int) $file;
        $filePath = get_attached_file($fileId);

        if (!$filePath || !file_exists($filePath)) {
            wp_send_json_error(['message' => __('Datei nicht verfügbar.', 'wp-starter')], 404);
        }

        $fileName = is_array($file) ? ( $file['filename'] ?? '' ) : '';
        if (empty($fileName)) {
            $fileUrl = wp_get_attachment_url($fileId);
            $fileName = $fileUrl ? basename($fileUrl) : 'download';
        }

        $mimeType = is_array($file) ? ( $file['mime_type'] ?? '' ) : '';
        if (empty($mimeType)) {
            $mimeType = get_post_mime_type($fileId) ?: 'application/octet-stream';
        }

        self::sendFileHeaders($fileName, $mimeType, (int) filesize($filePath));

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
        $host = $entry['download_sftp_host'] ?? '';
        $port = (int) ( $entry['download_sftp_port'] ?? 22 ) ?: 22;
        $username = $entry['download_sftp_username'] ?? '';
        $password = Crypto::decrypt($entry['download_sftp_password'] ?? '') ?? '';
        $remotePath = $entry['download_sftp_remote_file'] ?? '';

        if (empty($host) || empty($username) || empty($password) || empty($remotePath)) {
            wp_send_json_error(['message' => __('SFTP-Konfiguration unvollständig.', 'wp-starter')], 404);
        }

        try {
            SftpClient::assertSafeHost($host);
        } catch (RuntimeException) {
            wp_send_json_error(['message' => __('Diese URL ist nicht erlaubt.', 'wp-starter')], 403);
        }

        try {
            $sftp = SftpClient::connect($host, $port, $username, $password);
            $stat = $sftp->stat($remotePath);
        } catch (Throwable) {
            wp_send_json_error(['message' => __('Datei konnte nicht abgerufen werden.', 'wp-starter')], 502);
        }

        if (!is_array($stat) || !isset($stat['size'])) {
            wp_send_json_error(['message' => __('Datei nicht verfügbar.', 'wp-starter')], 404);
        }

        $fileName = basename($remotePath);
        $mimeType = self::guessMimeType($fileName);

        self::sendFileHeaders($fileName, $mimeType, (int) $stat['size']);

        // Stream chunk-by-chunk via phpseclib's callback overload of get() instead of
        // buffering the whole remote file into a PHP string first, which would hold
        // the entire file in memory for the duration of the request.
        $sftp->get($remotePath, static function (string $chunk): void {
            echo $chunk; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            flush();
        });

        exit;
    }

    private static function guessMimeType(string $fileName): string
    {
        return match (strtolower(pathinfo($fileName, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip' => 'application/zip',
            default => 'application/octet-stream',
        };
    }

    /**
     * Strip characters that could break out of the Content-Disposition header value.
     */
    private static function sanitizeFilename(string $fileName): string
    {
        return preg_replace('/["\r\n]/', '', $fileName) ?? $fileName;
    }

    /**
     * Send the shared nocache/Content-Type/Content-Disposition/Content-Length/
     * X-Content-Type-Options header sequence used by every download source.
     */
    private static function sendFileHeaders(string $fileName, string $mimeType, int $contentLength): void
    {
        $mimeType = sanitize_mime_type($mimeType);
        if (empty($mimeType)) {
            $mimeType = 'application/octet-stream';
        }

        nocache_headers();
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . self::sanitizeFilename($fileName) . '"');
        header('Content-Length: ' . $contentLength);
        header('X-Content-Type-Options: nosniff');
    }

    private static function assertSafeUrl(string $url): void
    {
        try {
            SsrfGuard::assertSafeUrl($url);
        } catch (InvalidArgumentException) {
            wp_send_json_error(['message' => __('Ungültiges URL-Schema.', 'wp-starter')], 400);
        } catch (RuntimeException) {
            wp_send_json_error(['message' => __('Diese URL ist nicht erlaubt.', 'wp-starter')], 403);
        }
    }
}
