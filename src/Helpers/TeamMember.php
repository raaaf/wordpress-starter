<?php

declare(strict_types=1);

namespace WordpressStarter\Helpers;

class TeamMember
{
    /**
     * Whitespace-only text counts as empty (editors use blanks as spacers).
     */
    public static function text(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    /**
     * A member without name, position and image is a spacer entry and renders nothing.
     *
     * @param array<string, mixed> $member
     */
    public static function isEmpty(array $member): bool
    {
        return self::text($member['name'] ?? '') === ''
            && self::text($member['position'] ?? '') === ''
            && empty($member['image']);
    }
}
