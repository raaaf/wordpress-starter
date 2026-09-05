<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;
use WordpressStarter\MemberArea\Auth;
use WordpressStarter\Providers\MemberAreaServiceProvider;

/**
 * Tests for MemberAreaServiceProvider::normaliseCredential(), which must
 * treat the login credential differently depending on the auth mode:
 * in shared-password mode the credential IS the password and must survive
 * unsanitized (only wp_unslash applied), in WordPress mode it is a
 * username/e-mail and gets sanitize_text_field() treatment.
 */
final class MemberAreaServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Auth::getAuthMode() memoizes its result in a static property that
        // resetAllMocks() does not touch, so a mode set by one test would
        // otherwise leak into the next.
        $this->resetStaticProperties(Auth::class, ['cachedAuthMode' => false]);
    }

    /**
     * @return array<string, array{0: string, 1: ?string, 2: string}>
     */
    public static function provideCredentials(): array
    {
        return [
            'shared-password mode preserves HTML tags' => ['<b>secret</b>', null, '<b>secret</b>'],
            'shared-password mode preserves ampersand' => ['pass&word', null, 'pass&word'],
            'shared-password mode preserves double spaces' => ['pass  word', null, 'pass  word'],
            'shared-password mode preserves leading/trailing spaces' => ['  password  ', null, '  password  '],
            'shared-password mode strips slashes via wp_unslash' => ["pass\\'word", null, "pass'word"],
            'WordPress mode strips tags and trims' => ['  <b>user</b>  ', 'wordpress', 'user'],
        ];
    }

    #[DataProvider('provideCredentials')]
    public function testNormaliseCredential(string $raw, ?string $authMode, string $expected): void
    {
        if ($authMode !== null) {
            $this->setMockField('member_auth_mode', $authMode, 'option');
        }

        $result = $this->invokeStaticMethod(MemberAreaServiceProvider::class, 'normaliseCredential', [$raw]);

        $this->assertSame($expected, $result);
    }
}
