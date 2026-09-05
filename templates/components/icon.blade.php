{{--
    Icon Component - Based on Figma Design System

    Renders inline SVG icons from resources/icons/ directory.
    Icons inherit text color via currentColor for easy styling.

    @param string $name - Icon name (without .svg extension)
    @param string $size - xs, sm, md, lg, xl or custom Tailwind class (default: md)
    @param string $class - Additional CSS classes

    Available icons: einzige Quelle der Wahrheit ist config/icons.json, nicht
    diese Liste. Sie driftete zuvor bereits ab, siehe scripts/sync-icons.js.

    Usage:
    <x-icon name="search" />
    <x-icon name="check" size="lg" class="text-icon-success" />
    <x-icon name="linkedin" size="lg" />
    <x-icon name="close" class="w-8 h-8 text-icon-error" />
--}}

@props([
    'name',
    'size' => 'md',
    'class' => '',
])

@php
    $sizes = [
        'xs' => 'w-3 h-3',
        'sm' => 'w-3.5 h-3.5',
        'md' => 'w-4 h-4',
        'lg' => 'w-5 h-5',
        'xl' => 'w-6 h-6',
    ];

    // Wenn der Aufrufer die Groesse ueber class mitgibt, faellt die Standardgroesse weg.
    // Sonst standen beide im Markup ("w-4 h-4 w-5 h-5") und welche gewann, entschied
    // die Reihenfolge im generierten CSS, nicht der Aufrufer.
    $sizeClass = preg_match('/(^|\s)[wh]-/', $class) ? '' : ($sizes[$size] ?? $sizes['md']);
    $safeName = basename($name);
    $iconPath = get_template_directory() . '/resources/icons/' . $safeName . '.svg';

    static $iconCache = [];

    $svgContent = '';
    if (file_exists($iconPath)) {
        if (!isset($iconCache[$safeName])) {
            $raw = file_get_contents($iconPath);
            $raw = trim($raw);
            // Anchored to the root <svg ...> opening tag only: an
            // unanchored replace also stripped width/height from nested
            // elements (e.g. a <rect width="..."> inside a multi-shape
            // icon), corrupting the icon's own artwork.
            $raw = preg_replace_callback(
                '/<svg\b[^>]*>/',
                static fn (array $matches): string => preg_replace('/\s*(width|height)="[^"]*"/', '', $matches[0]),
                $raw,
                1
            );
            $iconCache[$safeName] = $raw;
        }
        // Built via preg_replace_callback, not preg_replace: a plain preg_replace
        // treats $0/\1 sequences inside the replacement string as backreferences,
        // so a $class value containing one would corrupt the injected attributes.
        // The callback's return value is used verbatim, no backreference expansion.
        $svgAttrs = 'class="icon ' . esc_attr($sizeClass) . ' ' . esc_attr($class) . ' inline-block align-middle shrink-0" aria-hidden="true"';
        $svgContent = preg_replace_callback(
            '/<svg/',
            static fn (array $matches): string => $matches[0] . ' ' . $svgAttrs,
            $iconCache[$safeName],
            1
        );
    }
@endphp

@if($svgContent)
    {!! $svgContent !!}
@endif
