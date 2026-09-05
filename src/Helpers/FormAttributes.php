<?php

declare(strict_types=1);

namespace WordpressStarter\Helpers;

/**
 * Shared attribute allowlists for the form components
 * (templates/components/{input,checkbox,radio,textarea,toggle}.blade.php).
 * input, checkbox and radio exposed the exact same 11-item exact-name
 * allowlist and the same prefix list before this was extracted; textarea and
 * toggle pass their own extras (cols/wrap/spellcheck, value/required);
 * checkbox and radio add 'required' since they have no $required prop.
 * select keeps a deliberately smaller inline exact-name list.
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
