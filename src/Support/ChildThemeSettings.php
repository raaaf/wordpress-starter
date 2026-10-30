<?php

declare(strict_types=1);

namespace WordpressStarter\Support;

use WordpressStarter\ThemeContext;

/**
 * Reads settings.json of an active child theme (child contract).
 *
 * A generated child theme carries no logic of its own: its settings.json
 * names its token stylesheets, fonts and logos, and the parent applies them
 * (Providers\ChildThemeServiceProvider). The contract is additive and version
 * 1 is never dropped:
 *
 * {"contract": 1, "build_id": "<uuid v4>", "generator": "<version>",
 *  "tokens": {"css": "assets/tokens.css", "editor_css": "assets/tokens-editor.css"},
 *  "fonts_css": "assets/fonts/fonts.css" | null,
 *  "logo": {"light": "assets/logo-light.svg" | null, "dark": "assets/logo-dark.png" | null},
 *  "buttons": "neutral" | "accent"}
 *
 * Paths are read only relative to the child folder, must stay inside it after
 * realpath() and may only end in css, svg, png or woff2. A missing file drops
 * its key without an error.
 */
final class ChildThemeSettings
{
    public const SUPPORTED_CONTRACT = 1;

    public const FILE = 'settings.json';

    /** The settings.json exists but is no JSON object or has no valid contract. */
    public const PROBLEM_INVALID = 'invalid';

    /** The contract is newer than this parent knows; known keys still apply. */
    public const PROBLEM_NEWER = 'newer';

    /** @var array<string, array<int, string>> Allowed extensions per kind of file. */
    private const EXTENSIONS = [
        'css' => ['css'],
        'logo' => ['svg', 'png'],
    ];

    /** @var array<string, mixed>|null */
    private static ?array $data = null;

    private static ?string $problem = null;

    private static int $contract = 0;

    private static bool $loaded = false;

    private function __construct() {}

    /**
     * True when a child theme is active and its settings.json was usable
     * (also when the contract is newer than this parent knows).
     */
    public static function active(): bool
    {
        self::load();

        return self::$data !== null;
    }

    /** PROBLEM_INVALID, PROBLEM_NEWER or null. */
    public static function problem(): ?string
    {
        self::load();

        return self::$problem;
    }

    public static function contract(): int
    {
        self::load();

        return self::$contract;
    }

    /**
     * The build ID as a lowercase UUID, or null. It versions the child
     * stylesheets and keys the one-time logo import.
     */
    public static function buildId(): ?string
    {
        $value = self::value(['build_id']);
        if (!is_string($value) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            return null;
        }

        return strtolower($value);
    }

    /** @return array{path: string, url: string}|null */
    public static function tokensCss(): ?array
    {
        return self::file(['tokens', 'css'], 'css');
    }

    /** @return array{path: string, url: string}|null */
    public static function editorCss(): ?array
    {
        return self::file(['tokens', 'editor_css'], 'css');
    }

    /** @return array{path: string, url: string}|null */
    public static function fontsCss(): ?array
    {
        return self::file(['fonts_css'], 'css');
    }

    /**
     * @param 'light'|'dark' $mode
     *
     * @return array{path: string, url: string}|null
     */
    public static function logo(string $mode): ?array
    {
        return self::file(['logo', $mode], 'logo');
    }

    /**
     * Clears the cached settings. For tests, and for code that switches
     * themes within one request.
     *
     * @internal
     */
    public static function reset(): void
    {
        self::$data = null;
        self::$problem = null;
        self::$contract = 0;
        self::$loaded = false;
    }

    private static function load(): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        if (!ThemeContext::isChildActive()) {
            return;
        }

        $path = get_stylesheet_directory() . '/' . self::FILE;
        // INTENTIONAL: a child theme without settings.json is a hand-made
        // child, not a generated one. It is left alone, with no notice.
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $raw = file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        $contract = is_array($data) ? ( $data['contract'] ?? null ) : null;

        if (!is_array($data) || array_is_list($data) || !is_int($contract) || $contract < 1) {
            self::$problem = self::PROBLEM_INVALID;

            return;
        }

        self::$data = $data;
        self::$contract = $contract;
        if ($contract > self::SUPPORTED_CONTRACT) {
            self::$problem = self::PROBLEM_NEWER;
        }
    }

    /** @param array<int, string> $keys */
    private static function value(array $keys): mixed
    {
        self::load();
        $value = self::$data;
        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * A file named in settings.json, resolved inside the child folder.
     *
     * @param array<int, string> $keys
     *
     * @return array{path: string, url: string}|null
     */
    private static function file(array $keys, string $kind): ?array
    {
        $relative = self::value($keys);
        if (!is_string($relative) || $relative === '' || str_contains($relative, "\0")) {
            return null;
        }

        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        if (!in_array($extension, self::EXTENSIONS[$kind], true)) {
            return null;
        }

        $root = realpath(get_stylesheet_directory());
        $path = realpath(get_stylesheet_directory() . '/' . $relative);
        if ($root === false || $path === false || !is_file($path)) {
            return null;
        }

        // realpath() resolves "../" and symlinks; only what still lies in the
        // child folder is used.
        $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($path, $root)) {
            return null;
        }

        $inside = str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($root)));
        $url = rtrim(get_stylesheet_directory_uri(), '/') . '/'
            . implode('/', array_map('rawurlencode', explode('/', $inside)));

        return ['path' => $path, 'url' => $url];
    }
}
