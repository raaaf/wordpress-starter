<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

/**
 * Media Service Provider
 *
 * Handles SVG upload permissions, sanitization, and dimension resolution
 * so WordPress correctly renders SVG attachments (logos, icons) at their
 * intrinsic aspect ratio.
 */
class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No bindings required
    }

    public function boot(): void
    {
        $this->allowSvgUploads();
        $this->resolveSvgDimensions();
    }

    /**
     * Allow SVG uploads for admin users
     *
     * SVGs are used for logo placeholders in the styleguide and can be
     * uploaded by administrators. Basic sanitization is applied.
     */
    private function allowSvgUploads(): void
    {
        // Add SVG to allowed mime types. svgz (gzipped SVG) is deliberately
        // NOT allowed: the sanitizer below only understands plain XML, so a
        // gzipped payload would bypass sanitization.
        add_filter('upload_mimes', function (array $mimes): array {
            // Only admins get SVG allowed at all: XML-RPC's
            // mw_newMediaObject calls wp_upload_bits() directly, which
            // never runs the wp_handle_upload_prefilter/sideload_prefilter
            // sanitizer below, so an admin uploading via XML-RPC stores an
            // unsanitised SVG; this capability gate is the only checkpoint
            // on that path.
            if (current_user_can('manage_options')) {
                $mimes['svg'] = 'image/svg+xml';
            }

            return $mimes;
        });

        // Fix SVG file type detection. Relabel only when the extension is
        // svg AND the actual file content looks like SVG, so this filter
        // can't be tricked into declaring an unrelated file type=svg by
        // filename alone.
        add_filter('wp_check_filetype_and_ext', function (array $data, string $file, string $filename, ?array $mimes): array {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if ($ext === 'svg' && $this->looksLikeSvgContent($file)) {
                $data['ext'] = 'svg';
                $data['type'] = 'image/svg+xml';
            }

            return $data;
        }, 10, 4);

        // Basic SVG sanitization on upload. Whether a file is treated as SVG
        // is decided from the file extension alone (see isSvgUpload()), never
        // from $file['type'] (the browser-supplied Content-Type), which an
        // uploader fully controls. Hooked on both the regular upload path and
        // the sideload path (importer, media_sideload_image()), which would
        // otherwise skip the admin gate and the sanitizer entirely.
        $sanitizeUpload = function (array $file): array {
            return $this->sanitizeSvgUpload($file);
        };
        add_filter('wp_handle_upload_prefilter', $sanitizeUpload);
        add_filter('wp_handle_sideload_prefilter', $sanitizeUpload);
    }

    /**
     * Gate and sanitize an uploaded or sideloaded SVG file. Non-SVG files
     * pass through untouched. Fails closed: any read, sanitize, or write
     * failure rejects the upload instead of storing unsanitized content.
     * Sanitization runs on both the wp_handle_upload_prefilter and
     * wp_handle_sideload_prefilter paths (see allowSvgUploads()); the
     * XML-RPC path (wp_upload_bits()) is NOT covered here, see the
     * upload_mimes filter above.
     *
     * The admin capability gate below only applies when a user is logged
     * in: server-initiated imports with no current user (WP-CLI media
     * import, cron) have no capability to check, so they skip the gate
     * but never skip the sanitizer.
     *
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     */
    private function sanitizeSvgUpload(array $file): array
    {
        $name = isset($file['name']) && is_string($file['name']) ? $file['name'] : '';
        if (!$this->isSvgUpload($name)) {
            return $file;
        }

        // Only allow admins to upload SVGs. Server-initiated imports with
        // no logged-in user (WP-CLI media import, cron) have no capability
        // to check and are not rejected here, but still go through the
        // sanitizer below.
        if (is_user_logged_in() && !current_user_can('manage_options')) {
            $file['error'] = __('SVG uploads are only allowed for administrators.', 'wp-starter');

            return $file;
        }

        $tmpName = isset($file['tmp_name']) && is_string($file['tmp_name']) ? $file['tmp_name'] : '';

        // Read and sanitize SVG content. Fail closed: if the content
        // can't be read or the sanitizer rejects it, reject the upload
        // instead of storing the unsanitized original.
        $content = $tmpName === '' ? false : file_get_contents($tmpName);
        if ($content === false) {
            $file['error'] = __('SVG konnte nicht gelesen werden.', 'wp-starter');

            return $file;
        }

        $sanitized = $this->sanitizeSvg($content);
        if ($sanitized === false) {
            $file['error'] = __('SVG konnte nicht bereinigt werden.', 'wp-starter');

            return $file;
        }

        // Write sanitized content back. Fail closed: if the write
        // fails or is truncated, the tmp file may still hold the
        // unsanitized original, so reject the upload instead of letting
        // it proceed.
        $written = file_put_contents($tmpName, $sanitized);
        if ($written === false || $written < strlen($sanitized)) {
            $file['error'] = __('SVG konnte nicht gespeichert werden.', 'wp-starter');

            return $file;
        }

        return $file;
    }

    /**
     * Determine whether an uploaded file is an SVG, independent of the
     * browser-supplied Content-Type. Keys off the file extension alone:
     * upload_mimes() only permits the "svg" extension in the first place, so
     * extension is the only signal that gates this path. Content sniffing
     * (looksLikeSvgContent()) is used separately, in the
     * wp_check_filetype_and_ext filter, to confirm a .svg file's content
     * before relabelling it, never to widen what counts as an SVG upload.
     */
    private function isSvgUpload(string $filename): bool
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'svg';
    }

    /**
     * Sniff whether a file's content is SVG by looking for an <svg> root
     * tag in the first bytes of the file, with or without a leading XML
     * declaration.
     */
    private function looksLikeSvgContent(string $path): bool
    {
        if ($path === '' || !is_readable($path)) {
            return false;
        }

        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            return false;
        }
        $head = (string) fread($fh, 2048);
        fclose($fh);

        return (bool) preg_match('/<svg\b/i', $head);
    }

    /**
     * SVG sanitization using enshrined/svg-sanitize library
     *
     * Properly sanitizes SVG files by parsing the XML and removing
     * dangerous elements and attributes, rather than using regex.
     *
     * @see https://github.com/darylldoyle/svg-sanitizer
     */
    private function sanitizeSvg(string $content): string|false
    {
        // Use the proper SVG sanitizer library
        $sanitizer = new \enshrined\svgSanitize\Sanitizer();

        // Configure allowed tags and attributes for strict sanitization
        $sanitizer->removeRemoteReferences(true);
        $sanitizer->removeXMLTag(false); // Keep the XML declaration

        // Fail closed: false propagates to the prefilter, which rejects
        // the upload instead of storing unsanitized content.
        return $sanitizer->sanitize($content);
    }

    /**
     * Resolve SVG dimensions from viewBox / width / height attributes.
     *
     * WordPress relies on getimagesize() for attachment dimensions, which
     * returns false for SVG files. Without dimensions, wp_get_attachment_image()
     * outputs width="1" height="1" on the <img> tag, forcing a 1:1 aspect ratio
     * that squashes logos down to tiny squares.
     *
     * Two hook points cover both new uploads and existing attachments:
     * - wp_generate_attachment_metadata: populate dimensions when SVG is uploaded
     * - wp_get_attachment_metadata: fill in missing dimensions on demand for
     *   SVGs that were uploaded before this fix landed.
     *
     * Dimensions are parsed from (in order): viewBox, width+height attributes.
     */
    private function resolveSvgDimensions(): void
    {
        add_filter('wp_generate_attachment_metadata', function (array $metadata, int $attachmentId): array {
            if (get_post_mime_type($attachmentId) !== 'image/svg+xml') {
                return $metadata;
            }

            $dimensions = $this->extractSvgDimensions(get_attached_file($attachmentId) ?: '');
            if ($dimensions) {
                $metadata['width'] = $dimensions['width'];
                $metadata['height'] = $dimensions['height'];
                if (empty($metadata['file'])) {
                    $attachedFile = get_post_meta($attachmentId, '_wp_attached_file', true);
                    if (is_string($attachedFile)) {
                        $metadata['file'] = $attachedFile;
                    }
                }
            }

            return $metadata;
        }, 10, 2);

        add_filter('wp_get_attachment_metadata', function ($metadata, int $attachmentId) {
            if (!empty($metadata['width']) && !empty($metadata['height'])) {
                return $metadata;
            }
            if (get_post_mime_type($attachmentId) !== 'image/svg+xml') {
                return $metadata;
            }

            $dimensions = $this->extractSvgDimensions(get_attached_file($attachmentId) ?: '');
            if (!$dimensions) {
                return $metadata;
            }

            $metadata = is_array($metadata) ? $metadata : [];
            $metadata['width'] = $dimensions['width'];
            $metadata['height'] = $dimensions['height'];

            return $metadata;
        }, 10, 2);

        // Also cover direct wp_get_attachment_image_src() calls for SVGs without
        // persisted metadata (e.g. attachments uploaded before this fix).
        add_filter('wp_get_attachment_image_src', function ($image, $attachmentId) {
            if (!is_array($image) || ( !empty($image[1]) && !empty($image[2]) )) {
                return $image;
            }
            if (get_post_mime_type( (int) $attachmentId) !== 'image/svg+xml') {
                return $image;
            }

            $dimensions = $this->extractSvgDimensions(get_attached_file( (int) $attachmentId) ?: '');
            if (!$dimensions) {
                return $image;
            }

            $image[1] = $dimensions['width'];
            $image[2] = $dimensions['height'];

            return $image;
        }, 10, 2);
    }

    /**
     * Extract pixel dimensions from an SVG file.
     *
     * Preference order:
     * 1. `viewBox="minX minY width height"` attribute (most reliable)
     * 2. `width` + `height` attributes on the root <svg>
     *
     * Returns null if the file is unreadable or both approaches fail.
     *
     * @return array{width: int, height: int}|null
     */
    private function extractSvgDimensions(string $filePath): ?array
    {
        if ($filePath === '' || !is_readable($filePath)) {
            return null;
        }

        // Read only the first 2KB — the <svg> root tag with its attributes
        // always lives at the start of the file.
        $fh = @fopen($filePath, 'rb');
        if ($fh === false) {
            return null;
        }
        $head = (string) fread($fh, 2048);
        fclose($fh);

        if (!preg_match('/<svg\b[^>]*>/is', $head, $svgTag)) {
            return null;
        }
        $tag = $svgTag[0];

        if (preg_match('/\bviewBox\s*=\s*["\']([^"\']+)["\']/i', $tag, $vb)) {
            $parts = preg_split('/[\s,]+/', trim($vb[1])) ?: [];
            if (count($parts) === 4) {
                $width = (int) round( (float) $parts[2]);
                $height = (int) round( (float) $parts[3]);
                if ($width > 0 && $height > 0) {
                    return ['width' => $width, 'height' => $height];
                }
            }
        }

        $width = preg_match('/\bwidth\s*=\s*["\']([\d.]+)/i', $tag, $w) ? (int) round( (float) $w[1]) : 0;
        $height = preg_match('/\bheight\s*=\s*["\']([\d.]+)/i', $tag, $h) ? (int) round( (float) $h[1]) : 0;

        return ( $width > 0 && $height > 0 ) ? ['width' => $width, 'height' => $height] : null;
    }
}
