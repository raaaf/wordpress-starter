<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

/**
 * Symmetric encryption for sensitive meta values (e.g. SFTP passwords).
 *
 * Uses libsodium (sodium_crypto_secretbox / XSalsa20-Poly1305), which is
 * bundled with PHP 7.2+ via the sodium extension.
 *
 * The encryption key is derived from WordPress AUTH_KEY. After a rotation, list
 * the old keys in the wp-config.php constant WP_STARTER_PREVIOUS_AUTH_KEYS (array
 * of strings) so existing ciphertext stays readable until it is re-encrypted.
 * Without the old key, affected entries can no longer be decrypted and must be
 * re-entered.
 *
 * Stored format: enc:v2:<kid>:base64( nonce . ciphertext ), kid = first 8 hex
 * chars of sha256 of the derived key. Legacy format enc:v1:base64(...) has no kid.
 */
class Crypto
{
    private const PREFIX_V1 = 'enc:v1:';
    private const PREFIX_V2 = 'enc:v2:';
    private const PLACEHOLDER = 'put your unique phrase here';

    /**
     * Encrypt a plaintext string. Returns a prefixed, base64-encoded string.
     *
     * @throws \RuntimeException if sodium is unavailable or key derivation fails.
     */
    public static function encrypt(string $plaintext): string
    {
        $key   = self::deriveKey(self::currentSecret());
        $kid   = self::keyId($key);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $box   = sodium_crypto_secretbox($plaintext, $nonce, $key);

        sodium_memzero($key);

        return self::PREFIX_V2 . $kid . ':' . base64_encode($nonce . $box);  // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- symmetric-crypto payload encoding, not obfuscation
    }

    /**
     * Decrypt a value produced by encrypt(). Returns the original plaintext,
     * or null if decryption fails (wrong key, corrupted data, or plain value).
     */
    public static function decrypt(string $value): ?string
    {
        if (str_starts_with($value, self::PREFIX_V2)) {
            $parts = explode(':', substr($value, strlen(self::PREFIX_V2)), 2);
            if (count($parts) !== 2) {
                return null;
            }

            [$kid, $encoded] = $parts;

            foreach (self::secrets() as $secret) {
                $key = self::deriveKey($secret);
                if (hash_equals(self::keyId($key), $kid)) {
                    return self::open($encoded, $key);
                }
                sodium_memzero($key);
            }

            return null;
        }

        if (str_starts_with($value, self::PREFIX_V1)) {
            $encoded = substr($value, strlen(self::PREFIX_V1));

            foreach (self::secrets() as $secret) {
                $plaintext = self::open($encoded, self::deriveKey($secret));
                if ($plaintext !== null) {
                    return $plaintext;
                }
            }

            return null;
        }

        // Value was stored before encryption was introduced — return as-is
        // so existing SFTP connections keep working after the migration.
        return $value ?: null;
    }

    /**
     * Return true if the value is already encrypted by this class (any version).
     */
    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX_V2) || str_starts_with($value, self::PREFIX_V1);
    }

    /**
     * Return true if the value should be re-encrypted with the current key:
     * legacy v1, non-empty plaintext, or v2 under a different key id.
     */
    public static function needsReencrypt(string $value): bool
    {
        if (str_starts_with($value, self::PREFIX_V2)) {
            $parts = explode(':', substr($value, strlen(self::PREFIX_V2)), 2);

            return $parts[0] !== self::currentKeyId();
        }

        return $value !== '';
    }

    /**
     * Key id of the current (first) secret.
     *
     * @throws \RuntimeException
     */
    public static function currentKeyId(): string
    {
        $key = self::deriveKey(self::currentSecret());
        $kid = self::keyId($key);
        sodium_memzero($key);

        return $kid;
    }

    private static function keyId(string $key): string
    {
        return substr(hash('sha256', $key), 0, 8);
    }

    /**
     * Decrypt base64( nonce . ciphertext ) with the given key and wipe the key.
     */
    private static function open(string $encoded, string $key): ?string
    {
        $raw = base64_decode($encoded, strict: true);  // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- symmetric-crypto payload decoding, not obfuscation

        if ($raw === false || strlen($raw) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
            sodium_memzero($key);

            return null;
        }

        $nonce      = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
        sodium_memzero($key);

        return $plaintext === false ? null : $plaintext;
    }

    /**
     * Secrets after the filter, current first, then previous ones. Unvalidated.
     *
     * @return list<mixed>
     */
    private static function rawSecrets(): array
    {
        $defaults = [];
        if (defined('AUTH_KEY')) {
            $defaults[] = AUTH_KEY;
        }
        if (defined('WP_STARTER_PREVIOUS_AUTH_KEYS') && is_array(WP_STARTER_PREVIOUS_AUTH_KEYS)) {
            foreach (WP_STARTER_PREVIOUS_AUTH_KEYS as $previous) {
                $defaults[] = $previous;
            }
        }

        $secrets = apply_filters('wp_starter_crypto_secrets', $defaults);

        return is_array($secrets) ? array_values($secrets) : [];
    }

    private static function isUsable(mixed $secret): bool
    {
        return is_string($secret) && $secret !== '' && $secret !== self::PLACEHOLDER;
    }

    /**
     * All usable secrets: current first, then previous ones.
     *
     * @return list<string>
     */
    private static function secrets(): array
    {
        return array_values(array_filter(self::rawSecrets(), self::isUsable(...)));
    }

    /**
     * @throws \RuntimeException
     */
    private static function currentSecret(): string
    {
        $current = self::rawSecrets()[0] ?? null;

        if (!is_string($current) || !self::isUsable($current)) {
            throw new \RuntimeException('AUTH_KEY is not configured in wp-config.php.');
        }

        return $current;
    }

    /**
     * Derive a 32-byte key from a secret via BLAKE2b.
     */
    private static function deriveKey(string $secret): string
    {
        // sodium_crypto_generichash = BLAKE2b; produces exactly 32 bytes
        return sodium_crypto_generichash($secret, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}
