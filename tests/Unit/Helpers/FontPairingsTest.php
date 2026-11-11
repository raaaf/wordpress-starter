<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;
use WordpressStarter\Helpers\FontPairings;

/** The filter answer is plugin input: only well-formed pairings reach the playground. */
final class FontPairingsTest extends TestCase
{
    /** @return array<string, string> */
    private static function valid(): array
    {
        return [
            'headline' => 'lora', 'body' => 'inter', 'label' => 'Lora + Inter',
            'headlineFace' => 'wpsg-lora', 'bodyFace' => 'wpsg-inter',
        ];
    }

    public function testAWellFormedPairingPasses(): void
    {
        $this->assertSame([self::valid()], FontPairings::sanitize([self::valid()]));
    }

    public function testNonArraysAndNonArrayEntriesAreDropped(): void
    {
        $this->assertSame([], FontPairings::sanitize('lora'));
        $this->assertSame([self::valid()], FontPairings::sanitize(['lora', null, self::valid()]));
    }

    /** @param array<string, mixed> $override */
    #[DataProvider('badEntries')]
    public function testAMalformedPairingIsDropped(array $override): void
    {
        $this->assertSame([], FontPairings::sanitize([array_merge(self::valid(), $override)]));
    }

    /** @return array<string, array{0: array<string, mixed>}> */
    public static function badEntries(): array
    {
        return [
            'uppercase id' => [['headline' => 'Lora']],
            'face with a quote' => [['headlineFace' => "x'; color: red"]],
            'face with a space' => [['bodyFace' => 'wpsg inter']],
            'trailing newline' => [['headline' => "lora\n"]],
            'empty body id' => [['body' => '']],
            'array id' => [['body' => ['inter']]],
            'empty label' => [['label' => '']],
            'markup-only label' => [['label' => '<b></b>']],
            'array label' => [['label' => ['Lora']]],
        ];
    }

    public function testTheLabelIsPlainText(): void
    {
        $pairings = FontPairings::sanitize([array_merge(self::valid(), ['label' => '<b>Lora</b> + Inter'])]);

        $this->assertSame('Lora + Inter', $pairings[0]['label']);
    }

    public function testAMissingFieldDropsThePairing(): void
    {
        $entry = self::valid();
        unset($entry['bodyFace']);

        $this->assertSame([], FontPairings::sanitize([$entry]));
    }
}
