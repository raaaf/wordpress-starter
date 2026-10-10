<?php

declare(strict_types=1);

namespace WordpressStarter\Helpers;

/**
 * Request-scoped state for the jump menu (Sprungmenü) module.
 *
 * Every flexible module can opt out of the menu via the ACF true_false field
 * `show_in_jump_menu`. The page loop reads that field per row and stores the
 * result here before including the template; section.blade.php then marks the
 * rendered <section> with data-jump-menu="hidden". A static flag on a class,
 * not a Blade variable, because the module template and the section component
 * render in separate view scopes.
 */
final class JumpMenu
{
    private static bool $currentHidden = false;

    /**
     * Only an explicit off hides a section. A saved off is '' (update_field,
     * seeder) or '0' (admin form). null means the field is not registered on
     * that layout; unsaved fields get ACF's default_value 1. Both stay in the menu.
     */
    public static function isHidden(mixed $raw): bool
    {
        return $raw === 0 || $raw === '0' || $raw === false || $raw === '';
    }

    public static function setCurrentHidden(bool $hidden): void
    {
        self::$currentHidden = $hidden;
    }

    public static function currentHidden(): bool
    {
        return self::$currentHidden;
    }
}
