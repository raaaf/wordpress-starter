<?php

declare(strict_types=1);

namespace Tests\Unit\Content;

use ReflectionMethod;
use Tests\Support\TestCase;
use WordpressStarter\Acf\FlexibleContent;
use WordpressStarter\Content\StyleguideLayoutData;
use WordpressStarter\Content\StyleguideVariantLabels;

/**
 * Der Styleguide ist das Abnahmewerkzeug fuer alle Flexible-Content-Module.
 * Er taugt nur so weit, wie er zeigt: eine Auswahlmoeglichkeit, die dort nie
 * gerendert wird, ist in jeder visuellen Pruefung ein blinder Fleck.
 *
 * Genau so ist es passiert: die Hintergrundfarben "brand" und "inverse" kamen
 * in keiner der geseedeten Instanzen vor, und genau diese beiden waren kaputt
 * (Kontrast 2.76:1 bzw. 1.00:1). Layout "button" hat 5 Varianten, gezeigt wurde
 * eine.
 *
 * Dieser Test macht die Regel maschinell pruefbar:
 *
 * Stufe 1  Jeder Wert jedes Auswahlfeldes kommt im eigenen Layout mindestens
 *          einmal vor.
 * Stufe 2  Felder in UNION_FIELDS muessen ihre Werte nicht je Layout zeigen,
 *          sondern ueber alle Layouts hinweg. Sonst waeren allein fuer
 *          background_color 30 Layouts mal 6 Werte noetig.
 *
 * Ein Feld, das der Seeder nicht setzt, rendert seinen ACF-Default. Der zaehlt
 * deshalb als gezeigt, sonst wuerde der Test Werte einfordern, die auf der Seite
 * laengst sichtbar sind.
 *
 * Kombinationen prueft dieser Test bewusst nicht. Sie sind nicht darstellbar
 * (posts allein haette 288), die gezielt gewaehlten Wechselwirkungen stehen im
 * Auditplan.
 */
final class StyleguideVariantCoverageTest extends TestCase
{
    /**
     * Layouts, die bewusst keine Demo-Daten haben, mit Begruendung.
     *
     * @var array<string, string>
     */
    private const LAYOUTS_WITHOUT_DEMO_DATA = [
        'member_downloads' => 'Nur auf Seiten mit page_is_member_area verfuegbar; '
            . 'die Styleguide-Seite ist keine, und die Downloadtabelle braucht eine Anmeldung.',
    ];

    /**
     * Auswahlfelder, die an anderer Stelle vollstaendig gezeigt werden.
     *
     * @var array<string, string>
     */
    private const FIELDS_COVERED_ELSEWHERE = [
        'icon' => 'Alle Theme-Icons stehen einmal zentral in der Komponenten-Sektion '
            . 'der Styleguide-Seite, nicht ein weiteres Mal je Modul.',
    ];

    /**
     * Felder, deren Werte ueber alle Layouts hinweg abgedeckt sein muessen,
     * nicht innerhalb jedes einzelnen Layouts.
     *
     * @var array<int, string>
     */
    private const UNION_FIELDS = [
        'background_color',
        'section_spacing',
    ];

    public function testEveryRegisteredLayoutHasDemoData(): void
    {
        $registered = array_keys($this->registeredChoices());
        $seeded = array_keys($this->seededValues());

        $missing = array_diff($registered, $seeded, array_keys(self::LAYOUTS_WITHOUT_DEMO_DATA));

        $this->assertSame([], array_values($missing), sprintf(
            "Diese Layouts sind registriert, werden aber im Styleguide nie gerendert: %s.\n"
            . 'Entweder in StyleguideLayoutData::build() ergaenzen oder mit Begruendung in '
            . 'LAYOUTS_WITHOUT_DEMO_DATA eintragen.',
            implode(', ', $missing)
        ));
    }

    public function testDemoDataOnlyUsesRegisteredLayouts(): void
    {
        $registered = array_keys($this->registeredChoices());
        $seeded = array_keys($this->seededValues());

        $unknown = array_diff($seeded, $registered);

        $this->assertSame([], array_values($unknown), sprintf(
            'Der Seeder erzeugt Layouts, die es nicht mehr gibt: %s.',
            implode(', ', $unknown)
        ));
    }

    public function testEveryChoiceValueIsDemonstrated(): void
    {
        $seeded = $this->seededValues();
        $gaps = [];

        foreach ($this->registeredChoices() as $layout => $fields) {
            if (isset(self::LAYOUTS_WITHOUT_DEMO_DATA[$layout])) {
                continue;
            }

            foreach ($fields as $field => $spec) {
                if (isset(self::FIELDS_COVERED_ELSEWHERE[$field]) || in_array($field, self::UNION_FIELDS, true)) {
                    continue;
                }

                $shown = $seeded[$layout][$field] ?? [];
                $missing = array_values(array_diff($spec['choices'], $shown));

                if ($missing !== []) {
                    $gaps[] = sprintf('%s.%s fehlt: %s', $layout, $field, implode(', ', $missing));
                }
            }
        }

        $this->assertSame([], $gaps, sprintf(
            "Diese Auswahlwerte werden im Styleguide nie gerendert und sind damit in jeder\n"
            . "visuellen Abnahme unsichtbar:\n  %s\n"
            . 'Je Wert eine Instanz in StyleguideLayoutData::build() ergaenzen.',
            implode("\n  ", $gaps)
        ));
    }

    public function testUnionFieldsAreFullyDemonstratedAcrossLayouts(): void
    {
        $seeded = $this->seededValues();
        $gaps = [];

        foreach (self::UNION_FIELDS as $field) {
            $required = [];
            $shown = [];

            foreach ($this->registeredChoices() as $layout => $fields) {
                if (isset($fields[$field])) {
                    $required = array_merge($required, $fields[$field]['choices']);
                }

                $shown = array_merge($shown, $seeded[$layout][$field] ?? []);
            }

            $missing = array_values(array_unique(array_diff($required, $shown)));

            if ($missing !== []) {
                $gaps[] = sprintf('%s fehlt auf der gesamten Seite: %s', $field, implode(', ', $missing));
            }
        }

        $this->assertSame([], $gaps, sprintf(
            "%s\nGenau diese Luecke hat die Kontrastfehler auf brand und inverse verdeckt.",
            implode("\n", $gaps)
        ));
    }

    public function testExceptionListsStayInSync(): void
    {
        $registered = $this->registeredChoices();

        foreach (array_keys(self::LAYOUTS_WITHOUT_DEMO_DATA) as $layout) {
            $this->assertArrayHasKey($layout, $registered, sprintf(
                'LAYOUTS_WITHOUT_DEMO_DATA nennt "%s", dieses Layout ist nicht mehr registriert. '
                . 'Eintrag entfernen, damit die Ausnahmeliste nicht verrottet.',
                $layout
            ));
        }

        $knownFields = [];
        foreach ($registered as $fields) {
            $knownFields = array_merge($knownFields, array_keys($fields));
        }

        foreach (array_keys(self::FIELDS_COVERED_ELSEWHERE) as $field) {
            $this->assertContains($field, $knownFields, sprintf(
                'FIELDS_COVERED_ELSEWHERE nennt "%s", so ein Auswahlfeld gibt es nicht mehr.',
                $field
            ));
        }

        foreach (self::UNION_FIELDS as $field) {
            $this->assertContains($field, $knownFields, sprintf(
                'UNION_FIELDS nennt "%s", so ein Auswahlfeld gibt es nicht mehr.',
                $field
            ));
        }
    }

    /**
     * Alle registrierten Layouts mit ihren Auswahlfeldern und deren Werten.
     *
     * @return array<string, array<string, array{choices: array<int, string>, default: string|null}>>
     */
    private function registeredChoices(): array
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

            $result[$name] = $this->collectChoices($layout['sub_fields'] ?? []);
        }

        return $result;
    }

    /**
     * Auswahlfelder eines Feldbaums einsammeln, Repeater und Gruppen inklusive.
     *
     * @param array<int, array<string, mixed>> $fields
     *
     * @return array<string, array{choices: array<int, string>, default: string|null}>
     */
    private function collectChoices(array $fields): array
    {
        $found = [];

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $name = (string) ( $field['name'] ?? '' );
            $type = (string) ( $field['type'] ?? '' );

            if ($name !== '') {
                if (in_array($type, ['select', 'radio', 'button_group'], true) && !empty($field['choices'])) {
                    $choices = array_map('strval', array_keys( (array) $field['choices']));
                    $found[$name] = [
                        'choices' => array_values(array_filter($choices, static fn (string $c): bool => $c !== '')),
                        'default' => $this->normalize($field['default_value'] ?? null),
                    ];
                } elseif ($type === 'true_false') {
                    $found[$name] = [
                        'choices' => ['0', '1'],
                        'default' => $this->normalize($field['default_value'] ?? false) ?? '0',
                    ];
                }
            }

            if (!empty($field['sub_fields']) && is_array($field['sub_fields'])) {
                foreach ($this->collectChoices($field['sub_fields']) as $subName => $subChoices) {
                    $found[$subName] = $subChoices;
                }
            }
        }

        return $found;
    }

    /**
     * Alle im Styleguide geseedeten Werte, je Layout und Feldname.
     *
     * @return array<string, array<string, array<int, string>>>
     */
    private function seededValues(): array
    {
        $rows = ( new StyleguideLayoutData([]) )->build();
        $registered = $this->registeredChoices();

        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $name = (string) ( $row['acf_fc_layout'] ?? '' );
            if ($name === '') {
                continue;
            }

            $result[$name] ??= [];
            $this->collectValues($row, $result[$name]);

            // Ein Feld, das diese Instanz nicht setzt, rendert seinen Default.
            // Der ist auf der Seite sichtbar und zaehlt deshalb als gezeigt.
            foreach ($registered[$name] ?? [] as $field => $spec) {
                if (!array_key_exists($field, $row) && $spec['default'] !== null) {
                    $result[$name][$field] ??= [];
                    $result[$name][$field][] = $spec['default'];
                    $result[$name][$field] = array_values(array_unique($result[$name][$field]));
                }
            }
        }

        return $result;
    }

    /**
     * ACF-Werte auf die Schreibweise der Choice-Schluessel bringen.
     */
    private function normalize(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * Skalare Werte eines Datensatzes einsammeln, Repeaterzeilen inklusive.
     *
     * @param array<string, mixed>                    $data
     * @param array<string, array<int, string>>       $into
     */
    private function collectValues(array $data, array &$into): void
    {
        foreach ($data as $key => $value) {
            if ($key === 'acf_fc_layout') {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $this->collectValues($item, $into);
                    }
                }
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            if (!is_scalar($value)) {
                continue;
            }

            $key = (string) $key;
            $into[$key] ??= [];
            $into[$key][] = (string) $value;
            $into[$key] = array_values(array_unique($into[$key]));
        }
    }
    /**
     * Jeder geseedete Zustand muss ueber genau einen Schalter erreichbar sein.
     *
     * Die Zustaende (fehlendes Bild, ueberlanger Text, Repeater mit einem
     * Eintrag) sind der Teil des Styleguides, der die haesslichen Faelle zeigt.
     * Sie haengen an einer Namenskonvention im Anker statt an einem eigenen
     * ACF-Feld, und eine Konvention ohne Test zerfaellt beim ersten Tippfehler:
     * ein Anker ohne `-zustand-` landet stumm in der Variantenleiste und die
     * Merkmalsableitung benennt ihn nach dem Feld, in dem er zufaellig abweicht.
     */
    public function testJederZustandIstAlsSolcherErkennbar(): void
    {
        $zustaende = [];

        foreach (( new StyleguideLayoutData([]) )->build() as $zeile) {
            $anker = (string) ( $zeile['section_anchor'] ?? '' );

            if ($anker === '' || !StyleguideVariantLabels::isState($anker)) {
                continue;
            }

            $layout = (string) ( $zeile['acf_fc_layout'] ?? '' );
            $zustaende[] = ['layout' => $layout, 'anker' => $anker];

            $this->assertStringStartsWith(
                str_replace('_', '-', $layout) . '-zustand-',
                $anker,
                "Der Anker {$anker} nennt ein anderes Layout als die Zeile selbst ({$layout}). "
                . 'Der Umschalter gruppiert dann nach dem falschen Modul.'
            );

            $this->assertNotSame(
                '',
                StyleguideVariantLabels::stateLabel($anker),
                "Aus dem Anker {$anker} laesst sich keine Beschriftung ableiten."
            );
        }

        $this->assertGreaterThanOrEqual(
            6,
            count($zustaende),
            'Es sind kaum noch Zustaende geseedet. Wurden sie beim Umbau entfernt?'
        );

        $anker = array_column($zustaende, 'anker');
        $this->assertSame(
            $anker,
            array_values(array_unique($anker)),
            'Zwei Zustaende teilen sich einen Anker, einer davon ist per Deep-Link nicht erreichbar.'
        );
    }

    /**
     * Zustandsanker duerfen keine Umlaute enthalten.
     *
     * Die Beschriftung entsteht aus dem Anker, und der Anker ist eine URL. Ein
     * Umlaut wuerde dort prozentkodiert und im Schalter als "Ueberlanger Text"
     * oder Schlimmerem landen. Deshalb umlautfreie Slugs und diese Pruefung.
     */
    public function testZustandsankerSindUrlTauglich(): void
    {
        foreach (( new StyleguideLayoutData([]) )->build() as $zeile) {
            $anker = (string) ( $zeile['section_anchor'] ?? '' );

            if ($anker === '' || !StyleguideVariantLabels::isState($anker)) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/^[a-z0-9-]+$/',
                $anker,
                "Der Anker {$anker} enthaelt Zeichen, die in einer URL kodiert werden muessten."
            );
        }
    }
}
