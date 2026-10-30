<?php

declare(strict_types=1);

namespace WordpressStarter\Helpers;

/**
 * Read access to the site's design token files (config/design-tokens/*.tokens.json)
 * for the public token generator: the REST route that hands them to the
 * browser and the generator template, which needs the start colours.
 *
 * The files are read from disk on every call on purpose: the admin's
 * Design tokens page rewrites them, and the generator must start from what
 * the site uses now.
 */
final class DesignTokenFiles
{
    /** The only files the generator may read. */
    public const FILES = ['primitives', 'light', 'dark'];

    /** Palette and status colours the generator offers, as in DesignTokenServiceProvider. */
    public const COLORS = ['accent', 'primary', 'secondary', 'gray', 'success', 'warning', 'error'];

    public static function path(string $name): ?string
    {
        if (!in_array($name, self::FILES, true)) {
            return null;
        }

        return get_template_directory() . "/config/design-tokens/{$name}.tokens.json";
    }

    /** Raw file contents, or null for an unknown name or an unreadable file. */
    public static function raw(string $name): ?string
    {
        $path = self::path($name);
        if ($path === null || !is_readable($path)) {
            return null;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file
        $content = file_get_contents($path);

        return $content === false ? null : $content;
    }

    /**
     * Base (500) colour of every generator colour, uppercase hex. Colours missing
     * from the file are left out; the generator then waits for the files.
     *
     * @return array<string, string>
     */
    public static function baseColors(): array
    {
        $raw = self::raw('primitives');
        $primitives = $raw === null ? null : json_decode($raw, true);
        if (!is_array($primitives) || !is_array($primitives['color'] ?? null)) {
            return [];
        }

        $colors = [];
        foreach (self::COLORS as $name) {
            $color = $primitives['color'][$name] ?? null;
            if (!is_array($color)) {
                continue;
            }
            $token = isset($color['$value']) ? $color : ( $color['500'] ?? null );
            $hex = is_array($token) ? ( $token['$value']['hex'] ?? null ) : null;
            if (is_string($hex) && preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
                $colors[$name] = strtoupper($hex);
            }
        }

        return $colors;
    }
}
