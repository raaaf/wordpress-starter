<?php

declare(strict_types=1);

namespace WordpressStarter\Helpers;

/**
 * Request-scoped state for the jump menu (Sprungmenü) module.
 *
 * Every flexible module can opt out of the menu via the ACF true_false field
 * `show_in_jump_menu` (layouts without that field are never hidden). The page loop reads that field per row and stores the
 * result here before including the template; section.blade.php then marks the
 * rendered <section> with data-jump-menu="hidden". A static flag on a class,
 * not a Blade variable, because the module template and the section component
 * render in separate view scopes.
 */
final class JumpMenu
{
    private static bool $currentHidden = false;

    /**
     * Only an explicit off hides a section. Stored values are strings: '' or '0'
     * for off, '1' for on, and unsaved fields get ACF's default_value 1. false
     * never means off: get_sub_field() returns false when the layout does not
     * register the field at all (client themes, filter-added layouts), and those
     * sections must stay in the menu. null does the same.
     */
    public static function isHidden(mixed $raw): bool
    {
        return $raw === '' || $raw === '0' || $raw === 0;
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
