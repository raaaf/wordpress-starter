<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;
use WordpressStarter\Providers\AcfServiceProvider;

/**
 * Tests for the date_picker and time_picker validators.
 *
 * The values are user-writable. ACF's format_value runs
 * date_i18n() on them, which throws on e.g. "300000000000" and turns every
 * page that reads the field into an HTTP 500 (stored DoS). The validators
 * reject anything but canonical stored values at save time.
 */
final class AcfDateTimeValidationTest extends TestCase
{
    /**
     * @return array<string, array{0: mixed}>
     */
    public static function provideValidDates(): array
    {
        return [
            'empty' => [''],
            'ymd' => ['20261008'],
            'leap day' => ['20280229'],
        ];
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function provideInvalidDates(): array
    {
        return [
            'huge number' => ['300000000000'],
            'iso format' => ['2026-10-08'],
            'month 13' => ['20261332'],
            'not a leap year' => ['20270229'],
            'array' => [['20261008']],
            'int' => [20261008],
        ];
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function provideValidTimes(): array
    {
        return [
            'empty' => [''],
            'his' => ['18:30:00'],
            'hi' => ['18:30'],
        ];
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function provideInvalidTimes(): array
    {
        return [
            'out of range' => ['99:99:99'],
            'huge number' => ['300000000000'],
            'array' => [['18:30']],
            'int' => [1830],
        ];
    }

    #[DataProvider('provideValidDates')]
    public function testDateValidatorAcceptsValidValues(mixed $value): void
    {
        $this->assertTrue(AcfServiceProvider::validateDatePicker(true, $value));
    }

    #[DataProvider('provideInvalidDates')]
    public function testDateValidatorRejectsInvalidValues(mixed $value): void
    {
        $this->assertSame('Bitte wähle ein gültiges Datum.', AcfServiceProvider::validateDatePicker(true, $value));
    }

    #[DataProvider('provideValidTimes')]
    public function testTimeValidatorAcceptsValidValues(mixed $value): void
    {
        $this->assertTrue(AcfServiceProvider::validateTimePicker(true, $value));
    }

    #[DataProvider('provideInvalidTimes')]
    public function testTimeValidatorRejectsInvalidValues(mixed $value): void
    {
        $this->assertSame('Bitte wähle eine gültige Uhrzeit.', AcfServiceProvider::validateTimePicker(true, $value));
    }

    public function testValidatorsKeepAnEarlierFailureMessage(): void
    {
        $this->assertSame('Pflichtfeld', AcfServiceProvider::validateDatePicker('Pflichtfeld', '300000000000'));
        $this->assertSame('Pflichtfeld', AcfServiceProvider::validateTimePicker('Pflichtfeld', '99:99:99'));
    }
}
