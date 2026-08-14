<?php

declare(strict_types=1);

namespace WordpressStarter\Content;

/**
 * Beschriftungen fuer die Varianten-Schalter der Styleguide-Seite.
 *
 * Die Instanzen eines Layouts unterscheiden sich durch Auswahlfelder: der Hero
 * durch `variant`, `cards` durch Spaltenzahl und Flaeche, `posts` durch gleich
 * sechs Felder. Eine handgepflegte Liste von Beschriftungen waere eine zweite
 * Wahrheit neben `StyleguideLayoutData` und wuerde beim naechsten neuen Katalog-
 * Eintrag lautlos falsch.
 *
 * Deshalb wird die Beschriftung abgeleitet: es zaehlt nur, worin sich die
 * Instanzen eines Moduls tatsaechlich unterscheiden. Sind alle `cards`-Instanzen
 * dreispaltig und variieren nur in der Flaeche, steht auf den Schaltern die
 * Flaeche und nicht dreimal "3 Spalten".
 */
final class StyleguideVariantLabels
{
    /**
     * Feldtypen, deren Wert eine benennbare Variante ergibt.
     */
    private const CHOICE_TYPES = ['select', 'radio', 'button_group', 'true_false'];

    /**
     * Mehr als zwei Merkmale pro Schalter liest niemand mehr.
     */
    private const MAX_MERKMALE = 2;

    /**
     * Die Flaeche ist das, was zuerst auffaellt.
     *
     * Sie laeuft deshalb ausserhalb der Merkmalsauswahl: erst wurde sie nach
     * hinten sortiert und fiel raus, dann kam sie in Definitionsreihenfolge gar
     * nicht mehr dran. Auf dem Schalter stand "Spalten: 2", zu sehen waren
     * Karten auf dunkler Flaeche.
     */
    private const FLAECHE = 'background_color';

    /**
     * Auswahlfelder eines Layouts aus der registrierten Feldgruppe ziehen.
     *
     * @param array<int|string, mixed> $subFields Sub-Felder eines Layouts
     *
     * @return array<string, array{label: string, type: string, choices: array<int|string, string>, default: string}>
     */
    public static function choiceFields(array $subFields): array
    {
        $fields = [];

        foreach ($subFields as $field) {
            if (!is_array($field) || !isset($field['name'], $field['type'])) {
                continue;
            }

            if (!in_array($field['type'], self::CHOICE_TYPES, true)) {
                continue;
            }

            $fields[ (string) $field['name']] = [
                'label' => (string) ( $field['label'] ?? $field['name'] ),
                'type' => (string) $field['type'],
                'choices' => is_array($field['choices'] ?? null) ? $field['choices'] : [],
                'default' => self::normalisieren($field['default_value'] ?? null),
            ];
        }

        return $fields;
    }

    /**
     * Beschriftung je Instanz eines Moduls.
     *
     * @param array<int, array<string, mixed>> $instances Auswahlwerte je Instanz, gleiche Reihenfolge wie im DOM
     * @param array<string, array{label: string, type: string, choices: array<int|string, string>, default: string}> $fields
     *
     * @return array<int, string>
     */
    public static function forModule(array $instances, array $fields): array
    {
        return self::bauen($instances, $fields, false);
    }

    /**
     * Ausfuehrliche Fassung fuer das title-Attribut: auf dem Schalter steht
     * "Deutsch", im Tooltip "Sprache der Untertitel: Deutsch".
     *
     * @param array<int, array<string, mixed>> $instances
     * @param array<string, array{label: string, type: string, choices: array<int|string, string>, default: string}> $fields
     *
     * @return array<int, string>
     */
    public static function tooltipsForModule(array $instances, array $fields): array
    {
        return self::bauen($instances, $fields, true);
    }

    /**
     * @param array<int, array<string, mixed>> $instances
     * @param array<string, array{label: string, type: string, choices: array<int|string, string>, default: string}> $fields
     *
     * @return array<int, string>
     */
    private static function bauen(array $instances, array $fields, bool $mitFeldnamen): array
    {
        if ($instances === []) {
            return [];
        }

        if (count($instances) < 2) {
            return [__('Standard', 'wp-starter')];
        }

        $ohneFlaeche = $fields;
        unset($ohneFlaeche[self::FLAECHE]);

        $merkmale = self::unterscheidendeFelder($instances, $ohneFlaeche);

        // Weicht die Flaeche vom Feld-Standard ab, gehoert sie an den Schalter.
        // Maßstab ist der Standard und nicht die erste Instanz: sonst stuende
        // an jeder zweiten Variante "Standard (Weiß)", weil die Uebersicht
        // zufaellig auf Grau liegt. Genannt wird also nur, was auffaellt.
        $flaecheVariiert = isset($fields[self::FLAECHE])
            && count(array_unique(self::spalte($instances, self::FLAECHE))) > 1;
        $grundflaeche = $flaecheVariiert
            ? ( $fields[self::FLAECHE]['default'] !== '' ? $fields[self::FLAECHE]['default'] : self::spalte($instances, self::FLAECHE)[0] )
            : '';

        // Unterscheiden sich die Instanzen NUR in der Flaeche, ist sie das
        // Merkmal und kein Zusatz.
        if ($merkmale === [] && $flaecheVariiert) {
            $merkmale = [self::FLAECHE];
            $flaecheVariiert = false;
        }

        $labels = [];

        foreach ($instances as $index => $values) {
            $teile = [];

            foreach ($merkmale as $name) {
                $teil = self::wertLabel($fields[$name], $values[$name] ?? null, $mitFeldnamen);

                if ($teil !== '') {
                    $teile[] = $teil;
                }
            }

            if ($flaecheVariiert && self::normalisieren($values[self::FLAECHE] ?? null) !== $grundflaeche) {
                $teil = self::wertLabel($fields[self::FLAECHE], $values[self::FLAECHE] ?? null, $mitFeldnamen);

                if ($teil !== '') {
                    $teile[] = $teil;
                }
            }

            if ($teile !== []) {
                $labels[$index] = implode(' · ', $teile);
                continue;
            }

            if ($index === 0) {
                $labels[$index] = __('Standard', 'wp-starter');
                continue;
            }

            /* translators: %d: laufende Nummer der Variante innerhalb eines Moduls */
            $labels[$index] = sprintf(__('Variante %d', 'wp-starter'), $index + 1);
        }

        return self::vereindeutigen($labels);
    }

    /**
     * Welche Felder braucht es, damit sich die Instanzen unterscheiden lassen?
     *
     * Nicht einfach alle variierenden Felder: `two_columns` variiert in
     * Sektionskopf UND Ausrichtung, obwohl schon eines davon die beiden
     * Instanzen trennt. Und nicht die ersten beiden: bei `one_column` waeren das
     * Sektionskopf und Ausrichtung, waehrend drei der vier Instanzen sich allein
     * in der Flaeche unterscheiden und dieselbe Beschriftung bekaemen.
     *
     * Also gierig: immer das Feld dazunehmen, das die meisten Instanzen
     * voneinander trennt, und aufhoeren, sobald alle eindeutig sind.
     *
     * @param array<int, array<string, mixed>> $instances
     * @param array<string, array{label: string, type: string, choices: array<int|string, string>, default: string}> $fields
     *
     * @return array<int, string>
     */
    private static function unterscheidendeFelder(array $instances, array $fields): array
    {
        $offen = [];

        foreach (array_keys($fields) as $name) {
            $werte = self::spalte($instances, $name);

            if (count(array_unique($werte)) > 1) {
                $offen[] = $name;
            }
        }

        // In Definitionsreihenfolge, nicht nach groesster Trennschaerfe: die
        // Reihenfolge in FieldDefinitions bildet ab, was an einem Layout wichtig
        // ist. Nach Trennschaerfe gewann beim Video die Untertitelsprache, weil
        // sie zufaellig alle fuenf Instanzen trennt, und verschwieg die
        // Video-Quelle, um die es dort zuerst geht.
        $gewaehlt = [];
        $anzahlGewaehlt = 0;
        $trennschaerfe = count(self::signaturen($instances, []));
        $ziel = count($instances);

        foreach ($offen as $name) {
            if ($anzahlGewaehlt >= self::MAX_MERKMALE || $trennschaerfe === $ziel) {
                break;
            }

            $erweitert = count(self::signaturen($instances, [...$gewaehlt, $name]));

            if ($erweitert <= $trennschaerfe) {
                continue;
            }

            $gewaehlt[] = $name;
            ++$anzahlGewaehlt;
            $trennschaerfe = $erweitert;
        }

        return $gewaehlt;
    }

    /**
     * Wie viele Instanzen lassen sich mit diesen Feldern auseinanderhalten?
     *
     * @param array<int, array<string, mixed>> $instances
     * @param array<int, string> $names
     *
     * @return array<int, string>
     */
    private static function signaturen(array $instances, array $names): array
    {
        if ($names === []) {
            return $instances === [] ? [] : ['*'];
        }

        $signaturen = array_map(
            static function (array $values) use ($names): string {
                return implode(
                    '|',
                    array_map(static fn (string $name): string => self::normalisieren($values[$name] ?? null), $names),
                );
            },
            $instances,
        );

        return array_values(array_unique($signaturen));
    }

    /**
     * @param array<int, array<string, mixed>> $instances
     *
     * @return array<int, string>
     */
    private static function spalte(array $instances, string $name): array
    {
        return array_map(
            static fn (array $values): string => self::normalisieren($values[$name] ?? null),
            $instances,
        );
    }

    /**
     * @param array{label: string, type: string, choices: array<int|string, string>, default: string} $field
     */
    private static function wertLabel(array $field, mixed $value, bool $mitFeldname = false): string
    {
        if ($field['type'] === 'true_false') {
            // "Section Header anzeigen: an" ist doppelt gemoppelt, das Feld
            // heisst schon nach dem Anzeigen.
            $begriff = (string) preg_replace('/\\s+(anzeigen|aktivieren|einblenden)$/iu', '', $field['label']);

            return sprintf(
                '%s: %s',
                $begriff,
                self::normalisieren($value) === '1' ? __('an', 'wp-starter') : __('aus', 'wp-starter'),
            );
        }

        $key = self::normalisieren($value);

        if ($key === '') {
            return '';
        }

        $label = (string) ( $field['choices'][$key] ?? $key );

        // Reine Zahlen als Auswahl ("2", "3", "4") sagen ohne ihre Einheit
        // nichts: auf dem Schalter stuende sonst bloss "3". Vorangestellt statt
        // angehaengt, weil "1 Spalten" falsch waere und der Feldname im Plural
        // steht.
        if (is_numeric($label) || $mitFeldname) {
            return sprintf('%s: %s', $field['label'], $label);
        }

        return $label;
    }

    private static function normalisieren(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return implode(',', array_map(static fn (mixed $v): string => self::normalisieren($v), $value));
        }

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * Zwei Instanzen koennen sich in einem Feld unterscheiden, das nicht unter
     * den ersten beiden Merkmalen ist. Dann stuende zweimal dasselbe auf den
     * Schaltern und niemand wuesste, welcher welcher ist.
     *
     * @param array<int, string> $labels
     *
     * @return array<int, string>
     */
    private static function vereindeutigen(array $labels): array
    {
        $gesehen = [];

        foreach ($labels as $index => $label) {
            $gesehen[$label] = ( $gesehen[$label] ?? 0 ) + 1;

            if ($gesehen[$label] > 1) {
                $labels[$index] = sprintf('%s (%d)', $label, $gesehen[$label]);
            }
        }

        return $labels;
    }

    /**
     * Beschriftung, Tooltip und Zustands-Flag je Instanz eines Moduls, in einem
     * Aufruf.
     *
     * Kapselt, was sonst im Template stand: Zustaende bleiben aus der
     * Merkmalsableitung heraus (sonst hiesse jeder Schalter nach dem Feld, in
     * dem der Randfall zufaellig abweicht, statt nach dem, was das Modul
     * unterscheidet), die Beschriftung laeuft nur ueber die verbleibenden
     * Varianten, und beide Ergebnislisten werden per Handzaehler wieder in die
     * urspruengliche Instanz-Reihenfolge samt Zustaenden eingefuegt. Das ist
     * Ableitungslogik wie der Rest der Klasse, keine Darstellung, und gehoert
     * deshalb hierhin statt ins Blade-Template.
     *
     * @param array<int, array{anchor: string, html: string}> $instanzen Instanzen eines Moduls, gleiche Reihenfolge wie im DOM
     * @param array<int, array<string, mixed>> $werte Auswahlwerte je Instanz, deckungsgleich zu $instanzen
     * @param array<string, array{label: string, type: string, choices: array<int|string, string>, default: string}> $felder
     *
     * @return array{labels: array<int, string>, tooltips: array<int, string>, istZustand: array<int, bool>}
     */
    public static function forInstances(array $instanzen, array $werte, array $felder): array
    {
        $istZustand = array_map(
            static fn (array $instanz): bool => self::isState($instanz['anchor']),
            $instanzen,
        );

        $werteOhneZustand = array_values(array_filter(
            $werte,
            static fn (int $index): bool => !$istZustand[$index],
            ARRAY_FILTER_USE_KEY,
        ));

        $variantenLabels = self::forModule($werteOhneZustand, $felder);
        $variantenTooltips = self::tooltipsForModule($werteOhneZustand, $felder);

        $labels = [];
        $tooltips = [];
        $zaehler = 0;

        foreach ($instanzen as $index => $instanz) {
            if ($istZustand[$index]) {
                $labels[$index] = self::stateLabel($instanz['anchor']);
                $tooltips[$index] = '';
                continue;
            }

            $labels[$index] = $variantenLabels[$zaehler] ?? '';
            $tooltips[$index] = $variantenTooltips[$zaehler] ?? '';
            ++$zaehler;
        }

        return ['labels' => $labels, 'tooltips' => $tooltips, 'istZustand' => $istZustand];
    }

    /**
     * Anker-Praefix, an dem eine Zustands-Instanz erkennbar ist.
     *
     * Kein eigenes ACF-Feld: das waere im Editor jeder Kundenseite sichtbar,
     * ohne dort je einen Zweck zu haben. Der Anker existiert ohnehin an jedem
     * Layout und ergibt nebenbei sprechende Deep-Links.
     */
    private const ZUSTAND = '-zustand-';

    /**
     * Ist diese Instanz ein Randfall statt einer Variante?
     */
    public static function isState(string $anchor): bool
    {
        return str_contains($anchor, self::ZUSTAND);
    }

    /**
     * Beschriftung eines Randfalls, aus seinem Anker.
     *
     * `cards-zustand-ohne-bild` wird zu "Ohne Bild". Der Anker ist damit die
     * einzige Quelle: was im Deep-Link steht, steht auch auf dem Schalter.
     */
    public static function stateLabel(string $anchor): string
    {
        $position = strpos($anchor, self::ZUSTAND);

        if ($position === false) {
            return $anchor;
        }

        $rest = substr($anchor, $position + strlen(self::ZUSTAND));

        // Jedes Wort gross: "ohne-bild" ergibt sonst "Ohne bild".
        // Die Anker sind bewusst umlautfrei gewaehlt, damit diese Ableitung
        // ohne Uebersetzungstabelle auskommt und der Deep-Link lesbar bleibt.
        return ucwords(str_replace('-', ' ', $rest));
    }
}
