<?php

declare(strict_types=1);

namespace WordpressStarter\Helpers;

/**
 * Font pairings for the design system playground, answered by a plugin through
 * the filter `wp_starter_styleguide_font_pairings` (default: none).
 *
 * The answer is plugin input and ends up in inline styles and in the CSS
 * custom properties the playground sets, so only well-formed entries pass:
 * ids and face names are lowercase slugs, the label is plain text.
 */
final class FontPairings
{
    public const FILTER = 'wp_starter_styleguide_font_pairings';

    private const SLUG = '/^[a-z0-9-]+$/';

    /** @return list<array{headline: string, body: string, label: string, headlineFace: string, bodyFace: string}> */
    public static function all(): array
    {
        return self::sanitize(apply_filters(self::FILTER, []));
    }

    /**
     * Drops everything that is not an array of arrays with the five non-empty
     * string fields, slugs for ids and faces.
     *
     * @return list<array{headline: string, body: string, label: string, headlineFace: string, bodyFace: string}>
     */
    public static function sanitize(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $pairings = [];

        foreach ($raw as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $slugs = [];

            foreach (['headline', 'body', 'headlineFace', 'bodyFace'] as $key) {
                $value = $entry[$key] ?? null;

                if (!is_string($value) || preg_match(self::SLUG, $value) !== 1) {
                    continue 2;
                }

                $slugs[$key] = $value;
            }

            $label = is_string($entry['label'] ?? null) ? trim(strip_tags($entry['label'])) : '';

            if ($label === '') {
                continue;
            }

            $pairings[] = [
                'headline' => $slugs['headline'],
                'body' => $slugs['body'],
                'label' => $label,
                'headlineFace' => $slugs['headlineFace'],
                'bodyFace' => $slugs['bodyFace'],
            ];
        }

        return $pairings;
    }
}
