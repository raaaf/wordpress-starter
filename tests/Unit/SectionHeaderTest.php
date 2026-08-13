<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;
use WordpressStarter\Helpers\SectionHeader;

/**
 * Ten layouts read the section-header fields with the identical five lines.
 * They share this helper now, so the behaviour those five lines had is pinned
 * here instead of being repeated ten times and verified nowhere.
 *
 * The toggle is the point: with the header switched off a layout must get nulls
 * and the neutral alignment, never leftover field values the editor believes
 * are hidden.
 */
final class SectionHeaderTest extends TestCase
{
    /**
     * @param array<string, mixed> $felder
     *
     * @return array{chip: ?string, headline: ?string, description: ?string, alignment: string}
     */
    private function mitFeldern(array $felder): array
    {
        $GLOBALS['wp_mock_sub_fields'] = $felder;

        return SectionHeader::fields();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['wp_mock_sub_fields']);
        parent::tearDown();
    }

    public function testLiefertDieFelderWennDerKopfAngeschaltetIst(): void
    {
        $ergebnis = $this->mitFeldern([
            'show_section_header' => true,
            'section_chip' => 'Neu',
            'section_headline' => 'Titel',
            'section_description' => 'Beschreibung',
            'section_alignment' => 'left',
        ]);

        $this->assertSame('Neu', $ergebnis['chip']);
        $this->assertSame('Titel', $ergebnis['headline']);
        $this->assertSame('Beschreibung', $ergebnis['description']);
        $this->assertSame('left', $ergebnis['alignment']);
    }

    public function testLiefertNichtsWennDerKopfAusgeschaltetIst(): void
    {
        // Die Felder sind absichtlich gefuellt: ein ausgeschalteter Kopf darf
        // sie nicht durchreichen, sonst taucht Text auf, den der Redakteur
        // ausgeblendet glaubt.
        $ergebnis = $this->mitFeldern([
            'show_section_header' => false,
            'section_chip' => 'Neu',
            'section_headline' => 'Titel',
            'section_description' => 'Beschreibung',
            'section_alignment' => 'left',
        ]);

        $this->assertNull($ergebnis['chip']);
        $this->assertNull($ergebnis['headline']);
        $this->assertNull($ergebnis['description']);
        $this->assertSame('center', $ergebnis['alignment']);
    }

    public function testFaelltAufZentriertZurueckOhneAusrichtung(): void
    {
        $ergebnis = $this->mitFeldern([
            'show_section_header' => true,
            'section_alignment' => '',
        ]);

        $this->assertSame('center', $ergebnis['alignment']);
    }

    public function testLiefertImmerAlleVierSchluessel(): void
    {
        // Die Layouts destrukturieren das Ergebnis; ein fehlender Schluessel
        // waere dort ein undefined index statt eines leeren Kopfes.
        $this->assertSame(
            ['chip', 'headline', 'description', 'alignment'],
            array_keys($this->mitFeldern([]))
        );
    }
}
