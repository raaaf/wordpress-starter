<?php

declare(strict_types=1);

namespace WordpressStarter\Helpers;

/**
 * Request-scoped nesting depth for flexible content modules rendered inside
 * another module (e.g. inside a tab panel).
 *
 * Every flexible template wraps itself in <x-section>, which draws the section
 * chrome: background, vertical padding, container, anchor id, scroll animation.
 * Inside a tab panel the host section already provides all of that, so
 * section.blade.php asks active() and renders a bare wrapper instead. A static
 * counter on a class, not a Blade variable, because the nested template and the
 * section component render in separate view scopes.
 */
final class SectionNesting
{
    private static int $depth = 0;

    public static function enter(): void
    {
        ++self::$depth;
    }

    public static function leave(): void
    {
        self::$depth = max(0, self::$depth - 1);
    }

    public static function active(): bool
    {
        return self::$depth > 0;
    }
}
