<?php

declare(strict_types=1);

namespace WordpressStarter\Content;

use WordpressStarter\Acf\FlexibleContent;

/**
 * Field reference for the styleguide page: which ACF fields a layout has.
 *
 * Derived from FlexibleContent::layouts() at runtime, never hand-maintained.
 * A hand-written list would drift the moment a field is renamed, added or
 * removed in FieldDefinitions.php, and the styleguide would keep showing
 * fields that no longer exist.
 */
final class StyleguideFieldReference
{
    private function __construct()
    {
    }

    /**
     * Normalised field tree for one layout, by layout name.
     *
     * Unknown layout names return an empty array rather than throwing: a
     * styleguide must not fatal just because a name was mistyped.
     *
     * @return array<int, array{
     *     name: string,
     *     type: string,
     *     label: string,
     *     required: bool,
     *     choices: array<int, string>,
     *     children: array<int, mixed>,
     * }>
     */
    public static function fuer(string $layout): array
    {
        foreach (FlexibleContent::layouts() as $definition) {
            if (( $definition['name'] ?? null ) === $layout) {
                return self::normalise($definition['sub_fields'] ?? []);
            }
        }

        return [];
    }

    /**
     * Total node count for a layout, including children at every depth.
     */
    public static function anzahl(string $layout): int
    {
        return count(self::flach($layout));
    }

    /**
     * Field tree for one layout, flattened depth-first into a flat list.
     *
     * Each entry carries its nesting depth ('tiefe') instead of a
     * 'children' array, so a template can iterate a ready list rather than
     * flatten the tree itself. Order matches a depth-first walk: a parent is
     * immediately followed by its own children, then the next sibling.
     *
     * @return array<int, array{
     *     name: string,
     *     type: string,
     *     label: string,
     *     required: bool,
     *     choices: array<int, string>,
     *     tiefe: int,
     * }>
     */
    public static function flach(string $layout): array
    {
        return self::flachen(self::fuer($layout), 0);
    }

    /**
     * Recursively flatten an already-normalised field tree depth-first.
     *
     * @param array<int, array{
     *     name: string,
     *     type: string,
     *     label: string,
     *     required: bool,
     *     choices: array<int, string>,
     *     children: array<int, mixed>,
     * }> $felder
     *
     * @return array<int, array{
     *     name: string,
     *     type: string,
     *     label: string,
     *     required: bool,
     *     choices: array<int, string>,
     *     tiefe: int,
     * }>
     */
    private static function flachen(array $felder, int $tiefe): array
    {
        $zeilen = [];

        foreach ($felder as $feld) {
            $zeilen[] = [
                'name' => $feld['name'],
                'type' => $feld['type'],
                'label' => $feld['label'],
                'required' => $feld['required'],
                'choices' => $feld['choices'],
                'tiefe' => $tiefe,
            ];

            foreach (self::flachen($feld['children'], $tiefe + 1) as $kind) {
                $zeilen[] = $kind;
            }
        }

        return $zeilen;
    }

    /**
     * Normalise a raw ACF sub_fields array into the reference shape.
     *
     * Copies only name, type, label, required and choices. Everything else
     * (key, instructions, conditional_logic, wrapper, acfe_* and so on) is
     * ACF/editor plumbing, not part of the field reference a developer needs.
     *
     * @param array<int, array<string, mixed>> $subFields
     *
     * @return array<int, array{
     *     name: string,
     *     type: string,
     *     label: string,
     *     required: bool,
     *     choices: array<int, string>,
     *     children: array<int, mixed>,
     * }>
     */
    private static function normalise(array $subFields): array
    {
        $felder = [];

        foreach ($subFields as $feld) {
            if (empty($feld['name'])) {
                continue;
            }

            $felder[] = [
                'name' => (string) $feld['name'],
                'type' => (string) ( $feld['type'] ?? '' ),
                'label' => (string) ( $feld['label'] ?? '' ),
                'required' => !empty($feld['required']),
                'choices' => isset($feld['choices']) && is_array($feld['choices'])
                    ? array_map('strval', array_keys($feld['choices']))
                    : [],
                'children' => self::normalise($feld['sub_fields'] ?? []),
            ];
        }

        return $felder;
    }
}
