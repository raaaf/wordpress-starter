<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

/**
 * Symmetric encryption for sensitive meta values (e.g. SFTP passwords).
 *
 * Uses libsodium (sodium_crypto_secretbox / XSalsa20-Poly1305), which is
 * bundled with PHP 7.2+ via the sodium extension.
 *
 * The encryption key is derived from WordPress AUTH_KEY. If AUTH_KEY changes
 * (e.g. after moving a site without copying wp-config.php), stored ciphertext
 * can no longer be decrypted — all affected entries must be re-entered.
 *
 * Stored format: base64( nonce . ciphertext )
 */
class Crypto
{
    private const PREFIX = 'enc:v1:';

    /**
     * Encrypt a plaintext string. Returns a prefixed, base64-encoded string.
     *
     * @throws \RuntimeException if sodium is unavailable or key derivation fails.
     */
    public static function encrypt(string $plaintext): string
    {
        $key   = self::deriveKey();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $box   = sodium_crypto_secretbox($plaintext, $nonce, $key);

        sodium_memzero($key);

        return self::PREFIX . base64_encode($nonce . $box);
    }

    /**
     * Decrypt a value produced by encrypt(). Returns the original plaintext,
     * or null if decryption fails (wrong key, corrupted data, or plain value).
     */
    public static function decrypt(string $value): ?string
    {
        if (!str_starts_with($value, self::PREFIX)) {
            // Value was stored before encryption was introduced — return as-is
            // so existing SFTP connections keep working after the migration.
            return $value ?: null;
        }

        $encoded = substr($value, strlen(self::PREFIX));
        $raw     = base64_decode($encoded, strict: true);

        if ($raw === false || strlen($raw) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
            return null;
        }

        $nonce      = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $key       = self::deriveKey();
        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
        sodium_memzero($key);

        return $plaintext === false ? null : $plaintext;
    }

    /**
     * Return true if the value is already encrypted by this class.
     */
    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    /**
     * Derive a 32-byte key from WordPress AUTH_KEY via BLAKE2b.
     *
     * @throws \RuntimeException
     */
    private static function deriveKey(): string
    {
        if (!defined('AUTH_KEY') || AUTH_KEY === 'put your unique phrase here') {
            throw new \RuntimeException('AUTH_KEY is not configured in wp-config.php.');
        }

        // sodium_crypto_generichash = BLAKE2b; produces exactly 32 bytes
        return sodium_crypto_generichash(AUTH_KEY, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}
