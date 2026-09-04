<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

class IconShortcodeServiceProvider extends ServiceProvider
{
    /** @var array<string, string|null> Per-request cache of width/height-stripped SVG markup, keyed by icon name. */
    private static array $iconCache = [];

    public function register(): void
    {
        // Nothing to bind in the container
    }

    public function boot(): void
    {
        add_shortcode('icon', [$this, 'renderIcon']);
    }

    /**
     * Render an inline SVG icon from the [icon name="..."] shortcode.
     *
     * @param array<string, string>|string $atts Shortcode attributes
     *
     * @return string Rendered HTML or empty string
     */
    public function renderIcon(array|string $atts): string
    {
        $atts = shortcode_atts([
            'name' => '',
            'size' => 'md',
            'class' => '',
        ], $atts, 'icon');

        $name = sanitize_file_name($atts['name']);

        if ($name === '') {
            return '';
        }

        $svg = $this->getIconSvg($name);

        if ($svg === null) {
            return '';
        }

        $sizes = [
            'xs' => 'w-3 h-3',
            'sm' => 'w-3.5 h-3.5',
            'md' => 'w-4 h-4',
            'lg' => 'w-5 h-5',
            'xl' => 'w-6 h-6',
        ];

        $sizeClass = $sizes[$atts['size']] ?? $sizes['md'];
        $classTokens = preg_split('/\s+/', trim($atts['class']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $extraClasses = array_filter($classTokens, static fn (string $c): bool => preg_match('/^[A-Za-z0-9_\-:.\/\[\]%()!,]+$/', $c) === 1);
        $classParts = array_merge(['icon'], explode(' ', $sizeClass), $extraClasses, ['inline-block', 'align-middle', 'shrink-0']);
        $classAttr = esc_attr(implode(' ', $classParts));

        $svgTagPos = strpos($svg, '<svg');

        if ($svgTagPos !== false) {
            $svg = substr_replace($svg, '<svg class="' . $classAttr . '" aria-hidden="true"', $svgTagPos, 4);
        }

        return '<span class="inline-icon">' . $svg . '</span>';
    }

    /**
     * Read and cache the width/height-stripped SVG markup for an icon name.
     * Caches per request so repeated [icon] shortcodes for the same icon
     * don't re-read and re-process the file from disk.
     */
    private function getIconSvg(string $name): ?string
    {
        if (array_key_exists($name, self::$iconCache)) {
            return self::$iconCache[$name];
        }

        $svgPath = get_template_directory() . '/resources/icons/' . $name . '.svg';

        if (!file_exists($svgPath)) {
            self::$iconCache[$name] = null;

            return self::$iconCache[$name];
        }

        $svg = file_get_contents($svgPath);

        if ($svg === false) {
            self::$iconCache[$name] = null;

            return self::$iconCache[$name];
        }

        $svg = trim($svg);
        $svg = preg_replace('/\s*(width|height)="[^"]*"/', '', $svg) ?? $svg;

        self::$iconCache[$name] = $svg;

        return self::$iconCache[$name];
    }
}
