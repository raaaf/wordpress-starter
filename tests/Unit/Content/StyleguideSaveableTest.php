<?php

declare(strict_types=1);

namespace Tests\Unit\Content;

use ReflectionMethod;
use Tests\Support\TestCase;
use WordpressStarter\Acf\FlexibleContent;
use WordpressStarter\Content\StyleguideLayoutData;

/**
 * Die geseedete Styleguide-Seite muss sich im Backend speichern lassen.
 *
 * So ist es gebrochen: der Varianten-Seeder schrieb die Akkordeon-Eintraege
 * unter dem Schluessel `items`, das ACF-Feld heisst aber `accordion`. ACF
 * verwirft einen unbekannten Schluessel wortlos, der Repeater blieb leer, und
 * weil er `min => 1` mit Pflicht-Unterfeldern hat, rendert die Maske eine leere
 * Pflichtzeile. Ergebnis: "Titel Wert ist erforderlich" bei jedem Speichern,
 * die Seite laesst sich nicht mehr aktualisieren.
 *
 * Sichtbar war davon nichts: im Frontend rendert ein leerer Repeater einfach
 * nichts, und kein Test las die Seite je durch die ACF-Validierung.
 *
 * Die Regel, die das pinnt, ist allgemeiner als der eine Tippfehler: jeder
 * Repeater mit `min >= 1` und mindestens einem Pflicht-Unterfeld braucht vom
 * Seeder auch Zeilen. Von Conditional Logic ausgeblendete Felder sind
 * ausgenommen, ACF validiert sie nicht (so bleiben die `source => cpt`-Varianten
 * von testimonials und team zu Recht leer).
 */
final class StyleguideSaveableTest extends TestCase
{
    public function testSeededRowsFillEveryRequiredRepeater(): void
    {
        $blockierend = $this->blockierendeRepeater();
        $luecken = [];

        foreach (( new StyleguideLayoutData(self::bildIds()) )->build() as $index => $zeile) {
            $layout = (string) ( $zeile['acf_fc_layout'] ?? '' );

            foreach ($blockierend[$layout] ?? [] as $feld => $pflicht) {
                $wert = $zeile[$feld] ?? null;

                if (is_array($wert) && $wert !== []) {
                    continue;
                }

                $luecken[] = sprintf(
                    'Zeile %d (%s): Repeater "%s" bekommt keine Daten, '
                    . 'Pflicht-Unterfelder: %s',
                    $index + 1,
                    $layout,
                    $feld,
                    implode(', ', $pflicht)
                );
            }
        }

        $this->assertSame([], $luecken, sprintf(
            "Diese Styleguide-Zeilen blockieren das Speichern der Seite:\n%s\n"
            . 'ACF rendert fuer jeden leeren min-1-Repeater eine leere Pflichtzeile. '
            . 'Meist ist der Schluessel im Seeder falsch geschrieben, dann verwirft ACF ihn stumm.',
            implode("\n", $luecken)
        ));
    }

    /**
     * Dieselben Schluessel, die WelcomeServiceProvider::importPlaceholderImages()
     * nach dem Medienimport liefert. Ohne sie filtert der Seeder bildbasierte
     * Repeater leer und der Test meldete einen Mangel, den es nur im Fixture gibt.
     *
     * @return array<string, int>
     */
    private static function bildIds(): array
    {
        $ids = ['portrait' => 300];

        for ($i = 1; $i <= 6; $i++) {
            $ids["placeholder_{$i}"] = 100 + $i;
            $ids["logo_{$i}"] = 200 + $i;
        }

        return $ids;
    }

    /**
     * Repeater, die leer das Speichern blockieren, je Layout.
     *
     * @return array<string, array<string, array<int, string>>>
     */
    private function blockierendeRepeater(): array
    {
        $method = new ReflectionMethod(FlexibleContent::class, 'getLayouts');
        $method->setAccessible(true);

        /** @var array<int, array<string, mixed>> $layouts */
        $layouts = $method->invoke(null);

        $result = [];
        foreach ($layouts as $layout) {
            $name = (string) ( $layout['name'] ?? '' );
            if ($name === '') {
                continue;
            }

            foreach (( $layout['sub_fields'] ?? [] ) as $feld) {
                if (( $feld['type'] ?? '' ) !== 'repeater') {
                    continue;
                }

                if ((int) ( $feld['min'] ?? 0 ) < 1) {
                    continue;
                }

                // Ausgeblendete Felder validiert ACF nicht.
                if (!empty($feld['conditional_logic'])) {
                    continue;
                }

                $pflicht = [];
                foreach (( $feld['sub_fields'] ?? [] ) as $unterfeld) {
                    if (!empty($unterfeld['required'])) {
                        $pflicht[] = (string) ( $unterfeld['name'] ?? '?' );
                    }
                }

                if ($pflicht !== []) {
                    $result[$name][(string) ( $feld['name'] ?? '' )] = $pflicht;
                }
            }
        }

        return $result;
    }
}
