<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

class IconShortcodeServiceProvider extends ServiceProvider
{
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

        $svgPath = get_template_directory() . '/resources/icons/' . $name . '.svg';

        if (!file_exists($svgPath)) {
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
        $extraClass = $atts['class'] !== '' ? ' ' . $atts['class'] : '';

        $svg = file_get_contents($svgPath);

        if ($svg === false) {
            return '';
        }

        $svg = trim($svg);
        $svg = preg_replace('/\s*(width|height)="[^"]*"/', '', $svg) ?? $svg;
        $svg = preg_replace(
            '/<svg/',
            '<svg class="icon ' . $sizeClass . $extraClass . ' inline-block align-middle shrink-0" aria-hidden="true"',
            $svg,
            1
        ) ?? $svg;

        return '<span class="inline-icon">' . $svg . '</span>';
    }
}
