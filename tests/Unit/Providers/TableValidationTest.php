<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Acf\FieldDefinitions;
use WordpressStarter\Providers\AcfServiceProvider;

/**
 * Tabellen-Layout: Spaltenueberschriften und Zeilen sind zwei unabhaengige
 * Repeater. Wer eine Spalte ergaenzt und die Zeilen vergisst, bekam bisher
 * keinerlei Rueckmeldung. Das Template gleicht die Zahl beim Rendern an, aber
 * ueberzaehlige Zellen fallen dabei weg: stiller Datenverlust.
 *
 * Geprueft wird das oeffentliche Verhalten: findTableRowMismatches (welche
 * Zeilen weichen ab, mit Zeilennummer, Zellen- und Spaltenzahl und dem vollen
 * Eingabenamen) und validateTableRows (der Hook, der daraus ACF-Fehler macht).
 * Die Suche im Baum und die Zellenzaehlung laufen dabei ueber diesen Weg mit.
 */
final class TableValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        unset($_POST['acf']);
    }

    protected function tearDown(): void
    {
        unset($_POST['acf']);

        parent::tearDown();
    }

    /**
     * @param array<mixed> $baum
     *
     * @return array<int, array{eingabe: string, zeile: int, zellen: int, spalten: int}>
     */
    private function abweichungen(array $baum): array
    {
        return AcfServiceProvider::findTableRowMismatches($baum);
    }

    /**
     * @return array<mixed>
     */
    private function tabelleInFlexibleContent(): array
    {
        return [
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
                        'row-1' => [
                            'field_flex_table_row_cells' => [
                                'row-0' => ['field_flex_table_cell_content' => 'Nur eine Zelle'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testMeldetZeileMitZuWenigZellenMitVollemEingabenamen(): void
    {
        $treffer = $this->abweichungen($this->tabelleInFlexibleContent());

        // Zeile 1 passt, nur die zweite weicht ab: Nummer ist 1-basiert.
        $this->assertSame([
            [
                'input' => 'acf[field_page_sections][row-0][field_flex_table_rows]',
                'row' => 2,
                'cells' => 1,
                'columns' => 2,
            ],
        ], $treffer);
    }

    public function testMeldetNichtsWennZellenzahlUndSpaltenzahlPassen(): void
    {
        $baum = $this->tabelleInFlexibleContent();
        unset($baum['field_page_sections']['row-0']['field_flex_table_rows']['row-1']);

        $this->assertSame([], $this->abweichungen($baum));
    }

    public function testTabelleOhneKopfzeilenWirdUebersprungen(): void
    {
        $baum = $this->tabelleInFlexibleContent();
        $baum['field_page_sections']['row-0']['field_flex_table_headers'] = [];

        $this->assertSame([], $this->abweichungen($baum));
    }

    public function testSkalarFeldVorDenZellenStoertDieZaehlungNicht(): void
    {
        // Der Schalter "Dicke Linie darunter" wird als "0"/"1" gepostet und kann
        // vor dem Zellen-Repeater stehen: er ist kein Array und zaehlt nicht.
        $treffer = $this->abweichungen([
            'field_flex_table_headers' => ['a' => [], 'b' => [], 'c' => []],
            'field_flex_table_rows' => [
                'row-0' => [
                    'field_flex_table_row_thick_border' => '1',
                    'field_flex_table_row_cells' => ['row-0' => [], 'row-1' => []],
                ],
            ],
        ]);

        $this->assertCount(1, $treffer);
        $this->assertSame(2, $treffer[0]['cells']);
        $this->assertSame(3, $treffer[0]['columns']);
    }

    public function testFindetZweiTabellenAufDerselbenSeite(): void
    {
        $tabelle = [
            'field_flex_table_headers' => ['row-0' => ['label' => 'A']],
            'field_flex_table_rows' => ['row-0' => ['cells' => ['row-0' => [], 'row-1' => []]]],
        ];

        $treffer = $this->abweichungen([
            'field_page_sections' => ['row-0' => $tabelle, 'row-1' => $tabelle],
        ]);

        $this->assertSame(
            [
                'acf[field_page_sections][row-0][field_flex_table_rows]',
                'acf[field_page_sections][row-1][field_flex_table_rows]',
            ],
            array_column($treffer, 'input')
        );
    }

    public function testHaeltEinenKnotenOhneZeilenNichtFuerEineTabelle(): void
    {
        // Nur Kopfzeilen, keine Zeilen: kein Treffer, sonst meldete die Pruefung
        // jede halbe Konfiguration als Fehler.
        $this->assertSame([], $this->abweichungen([
            'field_page_sections' => [
                'row-0' => ['field_flex_table_headers' => ['row-0' => ['label' => 'A']]],
            ],
        ]));
    }

    public function testIgnoriertEinenBaumOhneTabelle(): void
    {
        $this->assertSame([], $this->abweichungen([
            'field_page_sections' => [
                'row-0' => [
                    'acf_fc_layout' => 'cards',
                    'field_flex_cards_items' => ['row-0' => ['title' => 'Beratung']],
                ],
            ],
        ]));
    }

    /**
     * Eine Zeile zaehlt so viele Zellen, wie ihr erster Array-Wert Eintraege
     * hat. Das muss auch dann halten, wenn ACF den Zellen-Repeater unter einem
     * Schluessel mit anderem Praefix ablegt.
     */
    public function testZaehltZellenUnabhaengigVomSchluesselnamen(): void
    {
        $treffer = $this->abweichungen([
            'field_flex_table_headers' => ['a' => [], 'b' => [], 'c' => []],
            'field_flex_table_rows' => [
                'row-0' => ['irgendein_key' => ['z1' => [], 'z2' => []]],
            ],
        ]);

        $this->assertCount(1, $treffer);
        $this->assertSame(2, $treffer[0]['cells'], 'Zwei Zellen bei drei Spalten muss auffallen.');
        $this->assertSame(3, $treffer[0]['columns']);
    }

    public function testZeileOhneArrayWertHatNullZellen(): void
    {
        // Kein Wert der Zeile ist ein Array, z. B. nur ein Layout-Schluessel.
        $treffer = $this->abweichungen([
            'field_flex_table_headers' => ['a' => []],
            'field_flex_table_rows' => ['row-0' => ['acf_fc_layout' => 'row']],
        ]);

        $this->assertCount(1, $treffer);
        $this->assertSame(0, $treffer[0]['cells']);
    }

    public function testZaehltNurDenErstenArrayWertBeiMehreren(): void
    {
        // Zwei Array-Werte in derselben Zeile: nur der erste zaehlt.
        $treffer = $this->abweichungen([
            'field_flex_table_headers' => ['a' => [], 'b' => [], 'c' => []],
            'field_flex_table_rows' => [
                'row-0' => [
                    'field_flex_table_row_cells' => ['row-0' => [], 'row-1' => []],
                    'field_flex_table_row_meta' => ['row-0' => [], 'row-1' => [], 'row-2' => []],
                ],
            ],
        ]);

        $this->assertCount(1, $treffer);
        $this->assertSame(2, $treffer[0]['cells']);
    }

    /**
     * Die Erkennung laeuft ueber Schluessel-Suffixe (_headers, _rows). Hier
     * kommen die Schluessel aus der echten Felddefinition: wird dort ein
     * Repeater umbenannt, faellt die Validierung sonst unbemerkt aus.
     */
    public function testErkenntDieSchluesselDerEchtenTabellenDefinition(): void
    {
        $schluessel = [];
        foreach (FieldDefinitions::tableFields('flex_table') as $feld) {
            $schluessel[$feld['name']] = $feld['key'];
        }

        $treffer = $this->abweichungen([
            $schluessel['headers'] => ['row-0' => [], 'row-1' => []],
            $schluessel['rows'] => ['row-0' => ['cells' => ['row-0' => []]]],
        ]);

        $this->assertSame(
            [['input' => 'acf[' . $schluessel['rows'] . ']', 'row' => 1, 'cells' => 1, 'columns' => 2]],
            $treffer
        );
    }

    public function testValidateTableRowsMeldetEinenFehlerAmZeilenRepeater(): void
    {
        $_POST['acf'] = $this->tabelleInFlexibleContent();

        AcfServiceProvider::validateTableRows();

        $fehler = $GLOBALS['wp_mock_acf_validation_errors'];

        $this->assertCount(1, $fehler);
        $this->assertSame('acf[field_page_sections][row-0][field_flex_table_rows]', $fehler[0]['input']);
    }

    public function testValidateTableRowsOhnePostDatenMeldetNichts(): void
    {
        AcfServiceProvider::validateTableRows();

        $this->assertSame([], $GLOBALS['wp_mock_acf_validation_errors']);
    }
}
