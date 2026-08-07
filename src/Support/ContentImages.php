<?php

declare(strict_types=1);

namespace WordpressStarter\Support;

/**
 * Restores the attachment reference on editor-inserted content images.
 *
 * Core builds srcset from the `wp-image-<id>` class that the media library adds
 * when an image is inserted. Images that reached the content another way (a
 * migration, a pasted URL, "insert from URL") carry no such class, so
 * wp_filter_content_tags() cannot resolve the attachment and silently ships the
 * full size file to phones.
 *
 * This is a safety net, not a substitute for inserting images through the media
 * library: it costs a lookup per unhandled image and only works for files that
 * actually live in this site's media library.
 */
class ContentImages
{
    /**
     * Adds a `wp-image-<id>` class to images that lack one but can be resolved.
     *
     * Runs before wp_filter_content_tags() so core can do the rest.
     */
    public static function addAttachmentIds(string $html): string
    {
        if (stripos($html, '<img') === false) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/<img\s[^>]*>/i',
            static fn (array $match): string => self::annotate($match[0]),
            $html,
        );
    }

    /**
     * Adds the attachment class to a single img tag, if one can be resolved.
     */
    private static function annotate(string $tag): string
    {
        if (preg_match('/wp-image-\d+/', $tag)) {
            return $tag;
        }

        if (!preg_match('/\ssrc=(["\'])(.*?)\1/i', $tag, $src)) {
            return $tag;
        }

        $id = self::resolveAttachmentId($src[2]);

        if ($id === 0) {
            return $tag;
        }

        // The leading whitespace is part of the match and has to be re-emitted,
        // otherwise the attribute collapses into the tag name.
        if (preg_match('/(\s)class=(["\'])(.*?)\2/i', $tag, $class)) {
            return str_replace(
                $class[0],
                $class[1] . 'class=' . $class[2] . trim($class[3] . ' wp-image-' . $id) . $class[2],
                $tag,
            );
        }

        return (string) preg_replace('/^<img\s/i', '<img class="wp-image-' . $id . '" ', $tag);
    }

    /**
     * Maps an image URL back to its attachment ID, cached per URL.
     *
     * Falls back to the unsized filename, because the editor usually inserts a
     * generated size such as photo-1792x1195.webp while only the original is
     * registered as an attachment.
     */
    private static function resolveAttachmentId(string $url): int
    {
        $cacheKey = 'content_image_id_' . md5($url);
        $cached = wp_cache_get($cacheKey, 'theme');

        if ($cached !== false) {
            return (int) $cached;
        }

        $id = attachment_url_to_postid($url);

        if ($id === 0) {
            $unsized = preg_replace('/-\d+x\d+(\.[a-z0-9]+)$/i', '$1', $url);

            if (is_string($unsized) && $unsized !== $url) {
                $id = attachment_url_to_postid($unsized);
            }
        }

        wp_cache_set($cacheKey, $id, 'theme', DAY_IN_SECONDS);

        return $id;
    }
}
