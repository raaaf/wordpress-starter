<?php

declare(strict_types=1);

namespace Tests\Unit\Content;

use Tests\Support\TestCase;
use WordpressStarter\Acf\FlexibleContent;
use WordpressStarter\Content\StyleguideFieldReference;

/**
 * Die Feldreferenz auf der Styleguide-Seite entsteht aus den echten
 * ACF-Felddefinitionen, nie aus einer von Hand gepflegten Liste. Dieser Test
 * ist die Anti-Drift-Absicherung dafuer: ein neues Layout ohne Felder faellt
 * hier auf, statt still eine leere Referenz zu zeigen.
 */
final class StyleguideFieldReferenceTest extends TestCase
{
    public function testEveryRegisteredLayoutHasANonEmptyReference(): void
    {
        foreach (FlexibleContent::layouts() as $definition) {
            $layout = $definition['name'];

            $this->assertNotSame([], StyleguideFieldReference::fuer($layout), sprintf(
                'Layout "%s" liefert keine Feldreferenz. Entweder fehlen sub_fields in '
                . 'FlexibleContent.php, oder StyleguideFieldReference::fuer() ist kaputt.',
                $layout
            ));
        }
    }

    public function testRepeaterLayoutYieldsChildrenUnderItsNode(): void
    {
        $felder = StyleguideFieldReference::fuer('cards');

        $cards = null;
        foreach ($felder as $feld) {
            if ($feld['name'] === 'cards') {
                $cards = $feld;
                break;
            }
        }

        $this->assertNotNull($cards, 'Layout "cards" sollte ein Feld "cards" (Repeater) haben.');
        $this->assertSame('repeater', $cards['type']);
        $this->assertNotSame([], $cards['children'], 'Der Repeater "cards" sollte Kind-Felder tragen.');
    }

    public function testRequiredIsARealBoolAndTrueForAtLeastOneKnownField(): void
    {
        $felder = StyleguideFieldReference::fuer('cards');

        $cards = null;
        foreach ($felder as $feld) {
            if ($feld['name'] === 'cards') {
                $cards = $feld;
                break;
            }
        }

        $this->assertNotNull($cards);

        $titel = null;
        foreach ($cards['children'] as $kind) {
            $this->assertIsBool($kind['required']);

            if ($kind['name'] === 'title') {
                $titel = $kind;
            }
        }

        $this->assertNotNull($titel, 'Kartentitel "title" sollte in den Kind-Feldern von "cards" stehen.');
        $this->assertTrue($titel['required'], 'Kartentitel ist im Feldschema als required markiert.');
    }

    public function testChoicesHoldKeysAndBackgroundColorContainsPrimaryAndInverse(): void
    {
        $felder = StyleguideFieldReference::fuer('cards');

        $backgroundColor = null;
        foreach ($felder as $feld) {
            if ($feld['name'] === 'background_color') {
                $backgroundColor = $feld;
                break;
            }
        }

        $this->assertNotNull($backgroundColor, 'Layout "cards" sollte ein Feld "background_color" haben.');
        $this->assertContains('primary', $backgroundColor['choices']);
        $this->assertContains('inverse', $backgroundColor['choices']);

        // Choices sind die Schluessel, nicht die deutschen Labels.
        foreach ($backgroundColor['choices'] as $choice) {
            $this->assertDoesNotMatchRegularExpression('/[A-ZÄÖÜ]/', $choice, sprintf(
                'Choice "%s" sieht nach einem Label aus, nicht nach einem Schluessel.',
                $choice
            ));
        }
    }

    public function testUnknownLayoutReturnsEmptyArray(): void
    {
        $this->assertSame([], StyleguideFieldReference::fuer('does_not_exist'));
    }

    public function testOutputNeverCarriesAcfEditorPlumbingKeys(): void
    {
        $verbotenerSchluessel = ['key', 'instructions', 'conditional_logic', 'wrapper'];

        foreach (FlexibleContent::layouts() as $definition) {
            $this->assertKeinerDerSchluesselTief(
                StyleguideFieldReference::fuer($definition['name']),
                $verbotenerSchluessel
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $felder
     * @param array<int, string> $verboten
     */
    private function assertKeinerDerSchluesselTief(array $felder, array $verboten): void
    {
        foreach ($felder as $feld) {
            foreach ($verboten as $schluessel) {
                $this->assertArrayNotHasKey($schluessel, $feld, sprintf(
                    'Feld "%s" traegt den ACF-Editor-Schluessel "%s", der nicht in die Referenz gehoert.',
                    $feld['name'] ?? '?',
                    $schluessel
                ));
            }

            $this->assertKeinerDerSchluesselTief($feld['children'], $verboten);
        }
    }

    public function testFlachOrdersDepthFirstWithCorrectTiefe(): void
    {
        $zeilen = StyleguideFieldReference::flach('cards');

        $cardsIndex = null;
        foreach ($zeilen as $index => $zeile) {
            if ($zeile['name'] === 'cards' && $zeile['tiefe'] === 0) {
                $cardsIndex = $index;
                break;
            }
        }

        $this->assertNotNull($cardsIndex, 'Der Top-Level-Repeater "cards" sollte in flach() bei Tiefe 0 stehen.');

        $kinder = StyleguideFieldReference::fuer('cards');
        $cardsKnoten = null;
        foreach ($kinder as $feld) {
            if ($feld['name'] === 'cards') {
                $cardsKnoten = $feld;
                break;
            }
        }
        $this->assertNotNull($cardsKnoten);
        $anzahlKinder = count($cardsKnoten['children']);
        $this->assertSame(4, $anzahlKinder, 'Der Repeater "cards" sollte 4 Kind-Felder haben.');

        for ($i = 1; $i <= $anzahlKinder; $i++) {
            $this->assertSame(
                1,
                $zeilen[$cardsIndex + $i]['tiefe'],
                sprintf('Zeile %d nach "cards" sollte Tiefe 1 haben (Kind-Feld direkt nach seinem Elternknoten).', $i)
            );
        }

        $nachDenKindern = $zeilen[$cardsIndex + $anzahlKinder + 1];
        $this->assertSame('columns', $nachDenKindern['name'], 'Nach den Kind-Feldern von "cards" sollte "columns" wieder bei Tiefe 0 folgen.');
        $this->assertSame(0, $nachDenKindern['tiefe']);
    }

    public function testFlachEntriesCarryNoChildrenKey(): void
    {
        $zeilen = StyleguideFieldReference::flach('cards');

        $this->assertNotEmpty($zeilen);

        foreach ($zeilen as $zeile) {
            $this->assertArrayNotHasKey('children', $zeile);
            $this->assertArrayHasKey('tiefe', $zeile);
        }
    }

    public function testAnzahlCountsChildrenNotJustTopLevelNodes(): void
    {
        $felder = StyleguideFieldReference::fuer('cards');
        $topLevel = count($felder);

        $mitKindern = 0;
        $rekursivZaehlen = function (array $felder) use (&$mitKindern, &$rekursivZaehlen): void {
            foreach ($felder as $feld) {
                $mitKindern++;
                $rekursivZaehlen($feld['children']);
            }
        };
        $rekursivZaehlen($felder);

        $this->assertGreaterThan($topLevel, $mitKindern, 'Der Repeater "cards" sollte Kind-Felder beitragen.');
        $this->assertSame($mitKindern, StyleguideFieldReference::anzahl('cards'));
    }
}
