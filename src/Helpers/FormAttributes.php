<?php

declare(strict_types=1);

namespace WordpressStarter\Helpers;

/**
 * Shared attribute allowlists for x-input, x-checkbox and x-radio
 * (templates/components/{input,checkbox,radio}.blade.php). All three exposed
 * the exact same 11-item exact-name allowlist and the exact same
 * prefix-matched list before this was extracted; checkbox and radio each add
 * 'required' as their own extra since, unlike x-input, they have no
 * dedicated $required prop.
 */
class FormAttributes
{
    /** @var array<int, string> */
    private const BASE = [
        'autocomplete',
        'maxlength',
        'minlength',
        'pattern',
        'min',
        'max',
        'step',
        'inputmode',
        'readonly',
        'autofocus',
        'form',
    ];

    /** @var array<int, string> */
    private const PREFIXES = ['x-', '@', ':', 'aria-', 'data-'];

    /**
     * @param array<int, string> $extra Per-component additions (e.g. 'required').
     *
     * @return array<int, string>
     */
    public static function passthrough(array $extra = []): array
    {
        return array_merge(self::BASE, $extra);
    }

    /** @return array<int, string> */
    public static function prefixes(): array
    {
        return self::PREFIXES;
    }
}
