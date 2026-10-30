<?php

declare(strict_types=1);

namespace Tests\Unit\MemberArea;

use RuntimeException;
use Tests\Support\TestCase;
use WordpressStarter\MemberArea\Crypto;

/**
 * Tests for Crypto v2 format, v1 compatibility and key rotation.
 */
final class CryptoTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['wp_mock_hooks']['filters']['wp_starter_crypto_secrets']);

        parent::tearDown();
    }

    /**
     * @param list<string> $secrets
     */
    private function useSecrets(array $secrets): void
    {
        unset($GLOBALS['wp_mock_hooks']['filters']['wp_starter_crypto_secrets']);
        add_filter('wp_starter_crypto_secrets', static fn (): array => $secrets);
    }

    private function legacyV1(string $plaintext, string $secret): string
    {
        $key   = sodium_crypto_generichash($secret, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return 'enc:v1:' . base64_encode($nonce . sodium_crypto_secretbox($plaintext, $nonce, $key));
    }

    public function testRoundtrip(): void
    {
        $this->useSecrets(['secret-a']);

        $this->assertSame('hunter2', Crypto::decrypt(Crypto::encrypt('hunter2')));
    }

    public function testEncryptWritesV2WithCurrentKid(): void
    {
        $this->useSecrets(['secret-a']);

        $value = Crypto::encrypt('hunter2');

        $this->assertMatchesRegularExpression('/^enc:v2:[0-9a-f]{8}:[A-Za-z0-9+\/=]+$/', $value);
        $this->assertStringStartsWith('enc:v2:' . Crypto::currentKeyId() . ':', $value);
        $this->assertTrue(Crypto::isEncrypted($value));
    }

    public function testEncryptThrowsWithoutUsableSecret(): void
    {
        $this->useSecrets(['put your unique phrase here']);

        $this->expectException(RuntimeException::class);
        Crypto::encrypt('hunter2');
    }

    public function testLegacyV1ValueDecryptsAndNeedsReencrypt(): void
    {
        $this->useSecrets(['secret-a']);
        $v1 = $this->legacyV1('hunter2', 'secret-a');

        $this->assertTrue(Crypto::isEncrypted($v1));
        $this->assertSame('hunter2', Crypto::decrypt($v1));
        $this->assertTrue(Crypto::needsReencrypt($v1));
    }

    public function testLegacyV1DecryptsWithPreviousKey(): void
    {
        $v1 = $this->legacyV1('hunter2', 'secret-old');
        $this->useSecrets(['secret-new', 'secret-old']);

        $this->assertSame('hunter2', Crypto::decrypt($v1));
    }

    public function testRotationKeepsOldValuesReadableAndReencryptsToNewKid(): void
    {
        $this->useSecrets(['secret-old']);
        $old    = Crypto::encrypt('hunter2');
        $oldKid = Crypto::currentKeyId();
        $this->assertFalse(Crypto::needsReencrypt($old));

        $this->useSecrets(['secret-new', 'secret-old']);
        $this->assertSame('hunter2', Crypto::decrypt($old));
        $this->assertTrue(Crypto::needsReencrypt($old));

        $fresh = Crypto::encrypt('hunter2');
        $this->assertNotSame($oldKid, Crypto::currentKeyId());
        $this->assertStringStartsWith('enc:v2:' . Crypto::currentKeyId() . ':', $fresh);
        $this->assertFalse(Crypto::needsReencrypt($fresh));
        $this->assertSame('hunter2', Crypto::decrypt($fresh));
    }

    public function testValueUnderRemovedKeyDecryptsToNull(): void
    {
        $this->useSecrets(['secret-old']);
        $old = Crypto::encrypt('hunter2');

        $this->useSecrets(['secret-new']);
        $this->assertNull(Crypto::decrypt($old));
    }

    public function testTamperedCiphertextDecryptsToNull(): void
    {
        $this->useSecrets(['secret-a']);
        $value = Crypto::encrypt('hunter2');
        $last  = substr($value, -3, 1) === 'A' ? 'B' : 'A';
        $tampered = substr($value, 0, -3) . $last . substr($value, -2);

        $this->assertNull(Crypto::decrypt($tampered));
    }

    public function testMalformedV2ValueDecryptsToNull(): void
    {
        $this->useSecrets(['secret-a']);

        $this->assertNull(Crypto::decrypt('enc:v2:nocolonhere'));
    }

    public function testPlaintextPassesThroughAndNeedsReencrypt(): void
    {
        $this->useSecrets(['secret-a']);

        $this->assertSame('plain', Crypto::decrypt('plain'));
        $this->assertNull(Crypto::decrypt(''));
        $this->assertFalse(Crypto::isEncrypted('plain'));
        $this->assertTrue(Crypto::needsReencrypt('plain'));
        $this->assertFalse(Crypto::needsReencrypt(''));
    }
}
