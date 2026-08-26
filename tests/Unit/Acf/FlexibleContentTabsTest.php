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
    private const MIN_CONTENT_FIELDS = 3;

    /**
     * Muss mit FlexibleContent::DISPLAY_FIELDS uebereinstimmen. Bewusst
     * doppelt: der Test soll den Vertrag festhalten, nicht die Konstante
     * zurueckspiegeln, die er prueft. Dasselbe gilt fuer MIN_CONTENT_FIELDS.
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
        $geprueft = 0;

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
                    ++$geprueft;
                    $this->assertGreaterThan(
                        $styleTab,
                        $index,
                        "Layout {$layout['name']}: Feld {$field['name']} steht vor dem Reiter Darstellung.",
                    );
                }
            }
        }

        $this->assertGreaterThan(0, $geprueft, 'Kein einziges Darstellungsfeld geprueft.');
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
        $geprueft = 0;

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

            ++$geprueft;

            foreach ($fields as $field) {
                $this->assertNotSame(
                    'tab',
                    $field['type'] ?? '',
                    "Layout {$layout['name']} gliedert per Akkordeon und hat zusaetzlich einen Reiter bekommen.",
                );
            }
        }

        $this->assertGreaterThan(0, $geprueft, 'Kein Layout mit eigener Gliederung geprueft.');
    }

    public function testLayoutsWithTooLittleContentKeepOneFieldList(): void
    {
        $geprueft = 0;

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

            if ($split !== null && $split >= self::MIN_CONTENT_FIELDS) {
                continue;
            }

            ++$geprueft;

            foreach ($layout['sub_fields'] ?? [] as $field) {
                $this->assertNotSame(
                    'tab',
                    $field['type'] ?? '',
                    "Layout {$layout['name']} hat zu wenig Inhalt fuer eine eigene Seite und trotzdem Reiter.",
                );
            }
        }

        $this->assertGreaterThan(0, $geprueft, 'Kein Layout unterhalb der Mindestgroesse geprueft.');
    }
    /**
     * Die Gegenrichtung zu {@see testDisplayFieldsSitBehindTheStyleTab()}.
     *
     * Jener Test belegt nur, dass Darstellungsfelder hinter ihrem Reiter
     * stehen. Rutschte durch eine Fehlzuordnung ein Inhaltsfeld dahinter,
     * bliebe er gruen. Genau das prueft dieser Test.
     *
     * Nur fuer die zentral eingesetzten Reiter: Layouts mit eigenen Reitern
     * gruppieren bewusst anders, das Kontaktformular etwa haelt seinen
     * Schalter "Kontaktdaten anzeigen" unter "Darstellung". Erkennbar sind
     * die eingesetzten am Schluessel, den withTabs() aus dem Layoutschluessel
     * bildet.
     */
    public function testNothingButDisplayFieldsSitsBehindAnInjectedStyleTab(): void
    {
        $geprueft = 0;

        foreach (FlexibleContent::layouts() as $layout) {
            $fields = $layout['sub_fields'] ?? [];
            $eigenerSchluessel = 'field_' . ($layout['key'] ?? '') . '_tab_style';
            $styleTab = null;

            foreach ($fields as $index => $field) {
                if (($field['type'] ?? '') === 'tab' && ($field['key'] ?? '') === $eigenerSchluessel) {
                    $styleTab = $index;
                    break;
                }
            }

            if ($styleTab === null) {
                continue;
            }

            foreach (array_slice($fields, $styleTab + 1) as $field) {
                ++$geprueft;
                $this->assertContains(
                    $field['name'] ?? '',
                    self::DISPLAY_FIELDS,
                    "Layout {$layout['name']}: Feld {$field['name']} steht hinter dem Reiter Darstellung, gehoert aber zum Inhalt.",
                );
            }
        }

        $this->assertGreaterThan(0, $geprueft, 'Kein eingesetzter Darstellungsreiter geprueft.');
    }
}
