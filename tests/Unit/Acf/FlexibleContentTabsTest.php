<?php

declare(strict_types=1);

namespace Tests\Unit\Acf;

use Tests\Support\TestCase;
use WordpressStarter\Acf\FlexibleContent;

/**
 * Die Module tragen zwischen 4 und 25 Felder, und der hintere Teil ist fast
 * ueberall derselbe: Hintergrundfarbe, Abstand, Breite, Anker. FlexibleContent
 * setzt deshalb zwei Reiter ein, "Inhalt" und "Darstellung", statt dass jeder
 * der rund 30 Feldbauer das fuer sich regelt.
 *
 * Diese Tests pinnen die Regel, nicht die Liste: sie zaehlen keine Layouts auf,
 * sondern pruefen an jedem einzelnen, dass die Trennung dort sitzt, wo sie
 * hingehoert, und dort ausbleibt, wo sie nichts brächte.
 */
final class FlexibleContentTabsTest extends TestCase
{
    /**
     * Muss mit FlexibleContent::DISPLAY_FIELDS uebereinstimmen. Bewusst
     * doppelt: der Test soll den Vertrag festhalten, nicht die Konstante
     * zurueckspiegeln, die er prueft.
     *
     * @var array<int, string>
     */
    private const DISPLAY_FIELDS = [
        'background_color',
        'section_spacing',
        'section_width',
        'section_anchor',
    ];

    public function testDisplayFieldsSitBehindTheStyleTab(): void
    {
        foreach (FlexibleContent::layouts() as $layout) {
            $fields = $layout['sub_fields'] ?? [];
            $styleTab = null;

            foreach ($fields as $index => $field) {
                if (($field['type'] ?? '') === 'tab' && ($field['label'] ?? '') === 'Darstellung') {
                    $styleTab = $index;
                    break;
                }
            }

            if ($styleTab === null) {
                continue;
            }

            foreach ($fields as $index => $field) {
                if (in_array($field['name'] ?? '', self::DISPLAY_FIELDS, true)) {
                    $this->assertGreaterThan(
                        $styleTab,
                        $index,
                        "Layout {$layout['name']}: Feld {$field['name']} steht vor dem Reiter Darstellung.",
                    );
                }
            }
        }
    }

    public function testTabKeysAreUniqueAcrossAllLayouts(): void
    {
        $keys = [];

        foreach (FlexibleContent::layouts() as $layout) {
            foreach ($layout['sub_fields'] ?? [] as $field) {
                if (($field['type'] ?? '') !== 'tab') {
                    continue;
                }

                $key = $field['key'] ?? '';
                $this->assertNotContains($key, $keys, "Reiter-Schluessel {$key} kommt doppelt vor.");
                $keys[] = $key;
            }
        }

        $this->assertNotEmpty($keys);
    }

    public function testLayoutsWithTheirOwnGroupingAreNotSplitAgain(): void
    {
        foreach (FlexibleContent::layouts() as $layout) {
            $fields = $layout['sub_fields'] ?? [];
            $hasAccordion = false;

            foreach ($fields as $field) {
                if (($field['type'] ?? '') === 'accordion') {
                    $hasAccordion = true;
                    break;
                }
            }

            if (!$hasAccordion) {
                continue;
            }

            foreach ($fields as $field) {
                $this->assertNotSame(
                    'tab',
                    $field['type'] ?? '',
                    "Layout {$layout['name']} gliedert per Akkordeon und hat zusaetzlich einen Reiter bekommen.",
                );
            }
        }
    }

    public function testLayoutsWithTooLittleContentKeepOneFieldList(): void
    {
        foreach (FlexibleContent::layouts() as $layout) {
            $fields = array_values(array_filter(
                $layout['sub_fields'] ?? [],
                static fn (array $field): bool => ($field['type'] ?? '') !== 'tab',
            ));

            $split = null;

            foreach ($fields as $index => $field) {
                if (in_array($field['name'] ?? '', self::DISPLAY_FIELDS, true)) {
                    $split = $index;
                    break;
                }
            }

            if ($split !== null && $split >= 3) {
                continue;
            }

            foreach ($layout['sub_fields'] ?? [] as $field) {
                $this->assertNotSame(
                    'tab',
                    $field['type'] ?? '',
                    "Layout {$layout['name']} hat zu wenig Inhalt fuer eine eigene Seite und trotzdem Reiter.",
                );
            }
        }
    }
}
