<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;
use WordpressStarter\ColorPaletteGenerator;

/**
 * The public token generator (resources/js/token-generator.ts) ports the
 * palette maths to TypeScript. Both sides read the same fixture, so a palette
 * made on the website and one made in wp-admin stay identical.
 */
final class ColorPaletteGeneratorParityTest extends TestCase
{
    public function testGenerateMatchesTheSharedFixture(): void
    {
        $fixture = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/fixtures/color-palette-parity.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertNotEmpty($fixture['cases']);
        foreach ($fixture['cases'] as $case) {
            $expected = [];
            foreach ($case['palette'] as $shade => $hex) {
                $expected[ (int) $shade] = $hex;
            }
            $this->assertSame($expected, ColorPaletteGenerator::generate($case['input']), $case['input']);
        }
    }
}
