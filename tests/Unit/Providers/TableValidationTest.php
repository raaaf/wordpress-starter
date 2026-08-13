<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use ReflectionMethod;
use Tests\Support\TestCase;
use WordpressStarter\Providers\AcfServiceProvider;

/**
 * Tabellen-Layout: Spaltenueberschriften und Zeilen sind zwei unabhaengige
 * Repeater. Wer eine Spalte ergaenzt und die Zeilen vergisst, bekam bisher
 * keinerlei Rueckmeldung. Das Template gleicht die Zahl beim Rendern an, aber
 * ueberzaehlige Zellen fallen dabei weg: stiller Datenverlust.
 *
 * Geprueft wird hier die Suche im geposteten Baum, nicht der ACF-Hook selbst.
 * Sie traegt die ganze Logik: sie muss Tabellen in beliebiger
 * Verschachtelungstiefe finden und darf nichts als Tabelle ansehen, das keine
 * ist.
 */
final class TableValidationTest extends TestCase
{
    /**
     * @param array<mixed> $baum
     *
     * @return array<int, array{feld: string, headers: array<mixed>, rows: array<mixed>}>
     */
    private function finde(array $baum): array
    {
        $methode = new ReflectionMethod(AcfServiceProvider::class, 'findTableGroups');
        $methode->setAccessible(true);

        /** @var array<int, array{feld: string, headers: array<mixed>, rows: array<mixed>}> $ergebnis */
        $ergebnis = $methode->invoke(null, $baum);

        return $ergebnis;
    }

    public function testFindetTabelleInEinerFlexibleContentZeile(): void
    {
        $treffer = $this->finde([
            'field_page_sections' => [
                'row-0' => [
                    'acf_fc_layout' => 'table',
                    'field_flex_table_headers' => [
                        'row-0' => ['field_flex_table_header_label' => 'Produkt'],
                        'row-1' => ['field_flex_table_header_label' => 'Preis'],
                    ],
                    'field_flex_table_rows' => [
                        'row-0' => [
                            'field_flex_table_row_cells' => [
                                'row-0' => ['field_flex_table_cell_content' => 'Beratung'],
                                'row-1' => ['field_flex_table_cell_content' => '90 Euro'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertCount(1, $treffer);
        $this->assertSame('field_flex_table_rows', $treffer[0]['feld']);
        $this->assertCount(2, $treffer[0]['headers']);
        $this->assertCount(1, $treffer[0]['rows']);
    }

    public function testFindetZweiTabellenAufDerselbenSeite(): void
    {
        $zeile = [
            'field_flex_table_headers' => ['row-0' => ['label' => 'A']],
            'field_flex_table_rows' => ['row-0' => ['cells' => ['row-0' => []]]],
        ];

        $treffer = $this->finde([
            'field_page_sections' => ['row-0' => $zeile, 'row-1' => $zeile],
        ]);

        $this->assertCount(2, $treffer);
    }

    public function testHaeltEinenKnotenOhneZeilenNichtFuerEineTabelle(): void
    {
        // Nur Kopfzeilen, keine Zeilen: kein Treffer, sonst meldete die Pruefung
        // jede halbe Konfiguration als Fehler.
        $this->assertSame([], $this->finde([
            'field_page_sections' => [
                'row-0' => ['field_flex_table_headers' => ['row-0' => ['label' => 'A']]],
            ],
        ]));
    }

    public function testIgnoriertEinenBaumOhneTabelle(): void
    {
        $this->assertSame([], $this->finde([
            'field_page_sections' => [
                'row-0' => [
                    'acf_fc_layout' => 'cards',
                    'field_flex_cards_items' => ['row-0' => ['title' => 'Beratung']],
                ],
            ],
        ]));
    }

    /**
     * Der Zellenvergleich selbst: eine Zeile zaehlt so viele Zellen, wie ihr
     * erster Array-Wert Eintraege hat. Das ist die Annahme, auf der die Meldung
     * beruht, und sie muss auch dann halten, wenn ACF den Zellen-Repeater unter
     * einem Schluessel mit anderem Praefix ablegt.
     */
    public function testZaehltZellenUnabhaengigVomSchluesselnamen(): void
    {
        $treffer = $this->finde([
            'field_flex_table_headers' => ['a' => [], 'b' => [], 'c' => []],
            'field_flex_table_rows' => [
                'row-0' => ['irgendein_key' => ['z1' => [], 'z2' => []]],
            ],
        ]);

        $this->assertCount(1, $treffer);

        $zeile = array_values($treffer[0]['rows'])[0];
        $zellen = 0;

        foreach ((array) $zeile as $wert) {
            if (is_array($wert)) {
                $zellen = count($wert);
                break;
            }
        }

        $this->assertSame(2, $zellen, 'Zwei Zellen bei drei Spalten muss auffallen.');
        $this->assertCount(3, $treffer[0]['headers']);
    }
}
