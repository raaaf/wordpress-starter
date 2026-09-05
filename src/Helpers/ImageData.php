<?php

declare(strict_types=1);

namespace WordpressStarter\Helpers;

class ImageData
{
    /**
     * Normalize the three shapes ACF hands back for an image field (numeric
     * attachment ID, ACF array with an 'ID', or a plain URL string) into one
     * consistent structure.
     *
     * Callers are responsible for esc_url() on the returned 'url' before
     * printing it, same as the call sites this replaces already did.
     *
     * Returns null when $value carries no usable image at all.
     *
     * @param array<string, mixed>|int|string|null $value
     *
     * @return array{id: ?int, url: string, alt: string, width: int|string, height: int|string}|null
     */
    public static function resolve(int|string|array|null $value, string $size): ?array
    {
        if (is_array($value)) {
            $id = (int) ( $value['ID'] ?? $value['id'] ?? 0 );
            if ($id > 0) {
                return self::fromAttachmentId($id, $size);
            }

            $url = (string) ( $value['url'] ?? '' );
            $normalizedUrl = self::normalizeUrl($url);
            if ($normalizedUrl === '' || !self::isSafeUrl($normalizedUrl)) {
                return null;
            }

            return [
                'id' => null,
                'url' => $normalizedUrl,
                'alt' => (string) ( $value['alt'] ?? '' ),
                'width' => $value['width'] ?? '',
                'height' => $value['height'] ?? '',
            ];
        }

        if (is_numeric($value)) {
            return self::fromAttachmentId( (int) $value, $size);
        }

        if (is_string($value) && $value !== '') {
            $normalizedUrl = self::normalizeUrl($value);
            if (!self::isSafeUrl($normalizedUrl)) {
                return null;
            }

            return [
                'id' => null,
                'url' => $normalizedUrl,
                'alt' => '',
                'width' => '',
                'height' => '',
            ];
        }

        return null;
    }

    /**
     * @return array{id: ?int, url: string, alt: string, width: int|string, height: int|string}|null
     */
    private static function fromAttachmentId(int $id, string $size): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $src = wp_get_attachment_image_src($id, $size);
        $url = $src ? $src[0] : wp_get_attachment_url($id);
        if (!$url) {
            return null;
        }

        return [
            'id' => $id,
            'url' => $url,
            'alt' => trim( (string) get_post_meta($id, '_wp_attachment_image_alt', true)),
            'width' => $src ? $src[1] : '',
            'height' => $src ? $src[2] : '',
        ];
    }

    /**
     * Browsers (WHATWG URL spec) strip ASCII tab/newline wherever they
     * appear in the URL, then trim leading/trailing C0 control chars and
     * space, before parsing it. "/\tevil.test" survives our own
     * str_starts_with() checks below as a root-relative path, but a
     * browser sees "//evil.test" - a protocol-relative link to a foreign
     * host. Normalize the same way before checking, and return the
     * normalized value so resolve() cannot hand the caller a URL that
     * differs from the one the safety check actually ran against
     * (esc_url() at the call sites strips the same characters, so this
     * changes nothing for a URL that was already valid).
     */
    private static function normalizeUrl(string $url): string
    {
        $url = str_replace(["\t", "\n", "\r"], '', $url);

        return ltrim($url, "\x00..\x20");
    }

    /**
     * Manual URL fields (ACF array without an ID, or a plain string) can carry
     * any scheme an editor types in. Only http(s) and root-relative paths are
     * a real image src; everything else (javascript:, data:, vbscript:) is
     * rejected here rather than passed through to esc_url() at the call site.
     * Expects an already-normalized $url (see normalizeUrl()).
     */
    private static function isSafeUrl(string $url): bool
    {
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return true;
        }

        $scheme = wp_parse_url($url, PHP_URL_SCHEME);

        return in_array(strtolower( (string) $scheme), ['http', 'https'], true);
    }
}
