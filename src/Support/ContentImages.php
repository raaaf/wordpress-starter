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
            '/<img\s(?:[^>\"\']|\"[^\"]*\"|\'[^\']*\')*>/i',
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

        $attributes = self::tokenizeAttributes($tag);
        $src = self::firstAttribute($attributes, 'src');

        if ($src === null) {
            return $tag;
        }

        $id = self::resolveAttachmentId($src['value']);

        if ($id === 0) {
            return $tag;
        }

        $class = self::firstAttribute($attributes, 'class');

        if ($class !== null) {
            $replacement = 'class=' . $class['quote'] . trim($class['value'] . ' wp-image-' . $id) . $class['quote'];

            return substr_replace($tag, $replacement, $class['offset'], $class['length']);
        }

        return (string) preg_replace('/^<img\s/i', '<img class="wp-image-' . $id . '" ', $tag);
    }

    /**
     * Splits a tag's attributes into name/value/quote/offset tokens.
     *
     * Quote-aware and offset-ordered, so a `src=` or `class=` occurring inside
     * another attribute's quoted value (e.g. data-caption="<img src='x'>")
     * is never mistaken for the tag's own attribute: each match consumes its
     * whole quoted value, so the scan never re-enters it.
     *
     * @return list<array{name: string, value: string, quote: string, offset: int, length: int}>
     */
    private static function tokenizeAttributes(string $tag): array
    {
        preg_match_all(
            '/([a-zA-Z_:][-\w:.]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))/',
            $tag,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
        );

        $attributes = [];

        foreach ($matches as $match) {
            $quote = '"';
            $value = $match[2][0];

            if ($match[2][1] === -1) {
                if ($match[3][1] !== -1) {
                    $quote = "'";
                    $value = $match[3][0];
                } else {
                    $quote = '"';
                    $value = $match[4][0];
                }
            }

            $attributes[] = [
                'name' => $match[1][0],
                'value' => $value,
                'quote' => $quote,
                'offset' => $match[0][1],
                'length' => strlen($match[0][0]),
            ];
        }

        return $attributes;
    }

    /**
     * Returns the first tokenized attribute matching a name, if any.
     *
     * @param list<array{name: string, value: string, quote: string, offset: int, length: int}> $attributes
     * @return array{name: string, value: string, quote: string, offset: int, length: int}|null
     */
    private static function firstAttribute(array $attributes, string $name): ?array
    {
        foreach ($attributes as $attribute) {
            if (strcasecmp($attribute['name'], $name) === 0) {
                return $attribute;
            }
        }

        return null;
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
            foreach (self::candidateUrls($url) as $candidate) {
                $id = attachment_url_to_postid($candidate);

                if ($id !== 0) {
                    break;
                }
            }
        }

        wp_cache_set($cacheKey, $id, 'theme', DAY_IN_SECONDS);

        return $id;
    }

    /**
     * Alternative spellings of an image URL that may be the registered file.
     *
     * The editor inserts a generated size such as photo-1792x1195.webp. For a
     * normal upload the attachment is photo.webp, for an image above the big
     * image threshold WordPress registers photo-scaled.webp instead and the bare
     * name does not exist at all.
     *
     * @return list<string>
     */
    private static function candidateUrls(string $url): array
    {
        $unsized = preg_replace('/-\d+x\d+(\.[a-z0-9]+)$/i', '$1', $url);

        if (!is_string($unsized) || $unsized === $url) {
            return [];
        }

        $scaled = preg_replace('/(\.[a-z0-9]+)$/i', '-scaled$1', $unsized);

        return is_string($scaled) ? [$unsized, $scaled] : [$unsized];
    }
}
