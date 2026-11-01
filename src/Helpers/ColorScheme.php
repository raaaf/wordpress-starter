<?php

declare(strict_types=1);

namespace WordpressStarter\Helpers;

/**
 * Theme option "Farbschema" (color_scheme): system, light or dark.
 *
 * The value system follows the visitor's OS setting and shows the switcher; light and
 * dark fix the scheme via data-theme on <html>, so no prefers-color-scheme
 * rule applies (every dark rule excludes [data-theme='light']) and the
 * switcher is not rendered. The dark tokens stay in the CSS either way.
 */
final class ColorScheme
{
    /**
     * --bg-primary from resources/css/tokens.css (light: --rafael-color-gray-100,
     * dark: --rafael-color-black), the token bg-surface resolves to.
     */
    private const THEME_COLOR = ['light' => '#F5F5F5', 'dark' => '#000000'];

    /** Unknown or empty values fall back to system. */
    public static function normalize(mixed $value): string
    {
        return in_array($value, ['light', 'dark'], true) ? $value : 'system';
    }

    /**
     * <meta name="theme-color"> tags for the browser chrome: one per scheme
     * with a media query when following the system, otherwise only the fixed one.
     */
    public static function themeColorMeta(string $scheme): string
    {
        $scheme = self::normalize($scheme);
        if ($scheme !== 'system') {
            return '<meta name="theme-color" content="' . self::THEME_COLOR[$scheme] . '">';
        }

        $tags = [];
        foreach (self::THEME_COLOR as $name => $color) {
            $tags[] = '<meta name="theme-color" content="' . $color . '" media="(prefers-color-scheme: ' . $name . ')">';
        }

        return implode("\n    ", $tags);
    }
}
