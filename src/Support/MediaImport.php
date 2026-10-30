<?php

declare(strict_types=1);

namespace WordpressStarter\Support;

/**
 * Copies a theme file (SVG or PNG) into the media library.
 *
 * Used for the logos of a generated child theme. SVGs go through
 * SvgSanitizer before they are stored, like an upload would; a file that
 * cannot be sanitized is not imported.
 */
final class MediaImport
{
    private const MIME_TYPES = [
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
    ];

    private function __construct() {}

    /** Attachment ID, or null when the file is unsupported or the import failed. */
    public static function file(string $path, string $title): ?int
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeType = self::MIME_TYPES[$extension] ?? null;
        if ($mimeType === null || !is_file($path) || !is_readable($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if (!is_string($content) || $content === '') {
            return null;
        }

        if ($extension === 'svg') {
            $content = SvgSanitizer::sanitize($content);
            if ($content === false || trim($content) === '') {
                return null;
            }
        }

        $uploads = wp_upload_dir();
        if (!empty($uploads['error']) || empty($uploads['path'])) {
            return null;
        }

        $filename = wp_unique_filename($uploads['path'], sanitize_file_name(basename($path)));
        $target = $uploads['path'] . '/' . $filename;
        if (file_put_contents($target, $content) === false) {
            return null;
        }

        $attachmentId = wp_insert_attachment([
            'post_mime_type' => $mimeType,
            'post_title' => $title,
            'post_content' => '',
            'post_status' => 'inherit',
            'meta_input' => ['_wp_attachment_image_alt' => $title],
        ], $target);

        if (is_wp_error($attachmentId) || $attachmentId === 0) {
            wp_delete_file($target);

            return null;
        }

        // SVG dimensions come from MediaServiceProvider's metadata filter on read.
        if ($extension === 'png') {
            if (!function_exists('wp_generate_attachment_metadata')) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
            }
            wp_update_attachment_metadata($attachmentId, wp_generate_attachment_metadata($attachmentId, $target));
        }

        return $attachmentId;
    }
}
