<?php

declare(strict_types=1);

namespace WordpressStarter\Helpers;

/**
 * Opt-out rule of the jump menu (Sprungmenü).
 *
 * Every flexible module can opt out of the menu via the ACF true_false field
 * `show_in_jump_menu`. section.blade.php reads the raw value of the current row
 * through isHidden() and marks the rendered <section> with
 * data-jump-menu="hidden"; layouts without the field are never hidden.
 */
final class JumpMenu
{
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
}
