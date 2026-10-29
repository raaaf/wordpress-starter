<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The en_US translation (languages/) must keep every placeholder of its German
 * source string, otherwise sprintf() throws or drops a value at runtime.
 */
class TranslationFileTest extends TestCase
{
    private const PLACEHOLDER = '/%(?:\d\$)?[sd]|\{year\}|\[_\w+\]/';

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        $data = require dirname(__DIR__, 2) . '/languages/en_US.l10n.php';

        $this->assertSame('wp-starter', $data['domain']);

        return $data['messages'];
    }

    public function testEveryTranslationKeepsThePlaceholdersOfItsSource(): void
    {
        foreach ($this->messages() as $source => $translation) {
            $sources = explode("\0", (string) $source);
            // A plural entry may be keyed by its singular alone, with both forms as value.
            foreach (explode("\0", $translation) as $i => $to) {
                $from = $sources[$i] ?? $sources[0];
                preg_match_all(self::PLACEHOLDER, (string) $from, $expected);
                preg_match_all(self::PLACEHOLDER, (string) $to, $actual);
                sort($expected[0]);
                sort($actual[0]);

                $this->assertSame($expected[0], $actual[0], "Placeholders differ for: {$from}");
            }
        }
    }

    public function testFrontendStringsAreTranslated(): void
    {
        $messages = $this->messages();

        foreach (['Nächster Termin', 'Karte laden', 'Zurück', 'Seite nicht gefunden', 'Folge uns', 'Rechtliche Links', 'Fußnavigation', 'Veranstaltungen', 'Kundenstimmen', 'Dokumente', 'Teammitglieder', 'Icon einfügen'] as $source) {
            $this->assertArrayHasKey($source, $messages);
            $this->assertNotSame($source, $messages[$source]);
        }
    }
}
