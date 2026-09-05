<?php

declare(strict_types=1);

namespace Tests\Unit\MemberArea;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;
use WordpressStarter\MemberArea\Acf;

/**
 * Tests for the acf/validate_value guard on the member-area shared-password
 * field. Without it, users without unfiltered_html have their input run
 * through wp_kses_post_deep on the ACF save path (encoding `&`, stripping
 * `<`/`>`) before Acf::registerPasswordHashing() hashes it, while the login
 * form (MemberAreaServiceProvider::normaliseCredential()) sends the raw
 * value, so such passwords could never match.
 */
final class AcfTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function provideRejectedValues(): array
    {
        return [
            'contains angle bracket' => ['<b>secret</b>'],
            'contains ampersand' => ['pass&word'],
            'leading whitespace' => ['  password'],
            'trailing whitespace' => ['password  '],
        ];
    }

    #[DataProvider('provideRejectedValues')]
    public function testValidateValueRejectsUnsafeCharacters(string $value): void
    {
        $this->invokeStaticMethod(Acf::class, 'registerPasswordHashing');

        $callback = $this->lastRegisteredFilterCallback('acf/validate_value/key=field_member_shared_password');

        $result = $callback(true, $value, ['key' => 'field_member_shared_password']);

        $this->assertIsString($result);
        $this->assertSame('Das Passwort darf keine spitzen Klammern oder & enthalten.', $result);
    }

    public function testValidateValueAcceptsSafeValue(): void
    {
        $this->invokeStaticMethod(Acf::class, 'registerPasswordHashing');

        $callback = $this->lastRegisteredFilterCallback('acf/validate_value/key=field_member_shared_password');

        $result = $callback(true, 'a-safe-password', ['key' => 'field_member_shared_password']);

        $this->assertTrue($result);
    }

    public function testValidateValueSkipsEmptyValue(): void
    {
        $this->invokeStaticMethod(Acf::class, 'registerPasswordHashing');

        $callback = $this->lastRegisteredFilterCallback('acf/validate_value/key=field_member_shared_password');

        $result = $callback(true, '', ['key' => 'field_member_shared_password']);

        $this->assertTrue($result);
    }

    /**
     * Returns the callback most recently registered for $hook via add_filter().
     */
    private function lastRegisteredFilterCallback(string $hook): callable
    {
        $registrations = $GLOBALS['wp_mock_hooks']['filters'][$hook] ?? [];
        $this->assertNotEmpty($registrations, "No callback registered for '{$hook}'");

        return $registrations[array_key_last($registrations)]['callback'];
    }
}
