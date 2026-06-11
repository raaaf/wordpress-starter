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
        // Add SVG to allowed mime types
        add_filter('upload_mimes', function (array $mimes): array {
            $mimes['svg'] = 'image/svg+xml';
            $mimes['svgz'] = 'image/svg+xml';

            return $mimes;
        });

        // Fix SVG file type detection
        add_filter('wp_check_filetype_and_ext', function (array $data, string $file, string $filename, ?array $mimes): array {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            if ($ext === 'svg') {
                $data['ext'] = 'svg';
                $data['type'] = 'image/svg+xml';
            }

            return $data;
        }, 10, 4);

        // Basic SVG sanitization on upload
        add_filter('wp_handle_upload_prefilter', function (array $file): array {
            if ($file['type'] !== 'image/svg+xml') {
                return $file;
            }

            // Only allow admins to upload SVGs
            if (!current_user_can('manage_options')) {
                $file['error'] = __('SVG uploads are only allowed for administrators.', 'wp-starter');

                return $file;
            }

            // Read and sanitize SVG content
            $content = file_get_contents($file['tmp_name']);
            if ($content === false) {
                return $file;
            }

            // Remove potentially dangerous elements and attributes
            $content = $this->sanitizeSvg($content);

            // Write sanitized content back
            file_put_contents($file['tmp_name'], $content);

            return $file;
        });
    }

    /**
     * SVG sanitization using enshrined/svg-sanitize library
     *
     * Properly sanitizes SVG files by parsing the XML and removing
     * dangerous elements and attributes, rather than using regex.
     *
     * @see https://github.com/darylldoyle/svg-sanitizer
     */
    private function sanitizeSvg(string $content): string
    {
        // Use the proper SVG sanitizer library
        $sanitizer = new \enshrined\svgSanitize\Sanitizer();

        // Configure allowed tags and attributes for strict sanitization
        $sanitizer->removeRemoteReferences(true);
        $sanitizer->removeXMLTag(false); // Keep the XML declaration

        $sanitized = $sanitizer->sanitize($content);

        // Return original if sanitization failed (shouldn't happen with valid SVG)
        return $sanitized ?: $content;
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
