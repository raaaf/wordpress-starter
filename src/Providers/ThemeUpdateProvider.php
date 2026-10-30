<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WP_Error;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Handles automatic theme updates from GitHub releases.
 *
 * This provider enables WordPress dashboard updates for this theme
 * by checking GitHub releases for new versions.
 */
final class ThemeUpdateProvider extends ServiceProvider
{
    /**
     * GitHub repository URL for update checks.
     */
    private const GITHUB_REPO = 'https://github.com/raaaf/wordpress-starter/';

    /**
     * Theme slug used for identification.
     */
    private const THEME_SLUG = 'wp-starter';

    /**
     * Ed25519 public keys (base64) whose signatures an update must carry.
     * Generated offline with `php scripts/release-signing.php keygen`; the
     * private key stays on the maintainer's machine. While this list is
     * empty, no update installs.
     *
     * @var array<int, string>
     */
    private const RELEASE_PUBLIC_KEYS = [
        // Rafael Alex, generated 2026-10-30.
        '487alxgU1p6+PM9vVumTr6jJGSv/E497NFDb1qcBZzU=',
    ];

    /**
     * Test seam for RELEASE_PUBLIC_KEYS, set through reflection only
     * (ThemeUpdateProviderTest), which PHPStan cannot see.
     *
     * @var array<int, string>|null
     */
    // @phpstan-ignore property.unusedType
    private static ?array $publicKeysOverride = null;

    /**
     * Register services.
     */
    public function register(): void
    {
        // Nothing to register
    }

    /**
     * Bootstrap the update checker.
     */
    public function boot(): void
    {
        // Only run in admin context to avoid unnecessary overhead
        if (! is_admin()) {
            return;
        }

        // Ensure the update checker library is available
        if (! class_exists(PucFactory::class)) {
            return;
        }

        $this->initializeUpdateChecker();
        $this->registerChecksumVerification();
    }

    /**
     * Initialize the GitHub update checker.
     */
    private function initializeUpdateChecker(): void
    {
        $updateChecker = PucFactory::buildUpdateChecker(
            self::GITHUB_REPO,
            get_template_directory() . '/style.css',
            self::THEME_SLUG
        );

        $updateChecker->setBranch('master');

        $api = $updateChecker->getVcsApi();

        if (defined('GITHUB_ACCESS_TOKEN') && GITHUB_ACCESS_TOKEN) {
            $api->setAuthentication(GITHUB_ACCESS_TOKEN);
        }

        // enableReleaseAssets() liegt seit plugin-update-checker 5.7 in einem
        // Trait, das nur konkrete VCS-Klassen einbinden. getVcsApi() gibt aber
        // die abstrakte Basis zurueck, also kennt weder PHPStan noch die
        // Laufzeit die Methode bei einem anderen Backend als GitHub.
        if (method_exists($api, 'enableReleaseAssets')) {
            $api->enableReleaseAssets();
        }
    }

    /**
     * Verify the downloaded theme package before WordPress installs it: the
     * `.sha256` checksum and the `.sig` signature of the same GitHub release
     * are both mandatory.
     *
     * Hooked on core's `upgrader_pre_download` (WP_Upgrader::download_package())
     * rather than a plugin-update-checker filter: at this point we still have
     * the package URL (needed to derive the release-asset URLs) and full
     * control over the downloaded file before installation. A later hook such
     * as `upgrader_source_selection` only exposes an already unpacked
     * directory, too late to hash against a single zip checksum.
     */
    private function registerChecksumVerification(): void
    {
        add_filter('upgrader_pre_download', [$this, 'verifyPackageChecksum'], 10, 4);
    }

    /**
     * @param mixed $reply
     * @param \WP_Upgrader $upgrader
     * @param array<string, mixed> $hookExtra
     * @return mixed
     */
    public function verifyPackageChecksum(mixed $reply, string $package, \WP_Upgrader $upgrader, array $hookExtra): mixed
    {
        // Not our theme's package: leave core's default download flow alone.
        if (( $hookExtra['theme'] ?? null ) !== self::THEME_SLUG) {
            return $reply;
        }

        // Another filter already short-circuited the download; do not fetch
        // it ourselves on top of that.
        if (false !== $reply) {
            return $reply;
        }

        $file = download_url($package);

        if (is_wp_error($file)) {
            return $file;
        }

        $error = $this->checkChecksum($file, $package) ?? $this->checkSignature($file, $package);
        if ($error !== null) {
            @unlink($file);

            return $error;
        }

        return $file;
    }

    /**
     * The release's `.sha256` asset must exist and match. Catches a broken or
     * swapped download; it proves nothing about who built the zip, because
     * whoever can replace the zip can replace its checksum too (see
     * checkSignature()).
     */
    private function checkChecksum(string $file, string $package): ?WP_Error
    {
        $body = $this->fetchAsset($package . '.sha256');
        if ($body === null) {
            LogServiceProvider::warning('Theme-Update-Pruefsumme nicht verfuegbar', ['package' => $package]);

            return new WP_Error(
                'theme_update_checksum_missing',
                __('Zu diesem Theme-Update ist keine Prüfsumme veröffentlicht. Das Update wurde abgebrochen.', 'wp-starter')
            );
        }

        $expectedHash = $this->extractHash($body);
        $actualHash = hash_file('sha256', $file);

        if ($expectedHash === null || $actualHash === false || ! hash_equals(strtolower($expectedHash), $actualHash)) {
            return new WP_Error(
                'theme_update_checksum_mismatch',
                __('Die Pruefsumme des Theme-Updates stimmt nicht mit dem veroeffentlichten Release ueberein. Das Update wurde abgebrochen.', 'wp-starter')
            );
        }

        return null;
    }

    /**
     * The release's `.sig` asset must be an Ed25519 signature of the zip by
     * one of RELEASE_PUBLIC_KEYS. The private key never leaves the maintainer's
     * machine (scripts/release-signing.php), so neither a compromised GitHub
     * account nor a compromised CI run can ship an update that installs.
     */
    private function checkSignature(string $file, string $package): ?WP_Error
    {
        $keys = self::releasePublicKeys();
        if ($keys === []) {
            return new WP_Error(
                'theme_update_no_signing_key',
                __('In diesem Theme ist kein Schlüssel zum Prüfen der Update-Signatur hinterlegt. Das Update wurde abgebrochen.', 'wp-starter')
            );
        }

        $body = $this->fetchAsset($package . '.sig');
        $signature = $body !== null ? base64_decode(trim($body), true) : false;
        if ($signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            LogServiceProvider::warning('Theme-Update-Signatur nicht verfuegbar', ['package' => $package]);

            return new WP_Error(
                'theme_update_signature_missing',
                __('Dieses Theme-Update ist noch nicht signiert. Das Update wurde abgebrochen; bitte später erneut versuchen.', 'wp-starter')
            );
        }

        $contents = file_get_contents($file);
        if ($contents !== false) {
            foreach ($keys as $key) {
                $publicKey = base64_decode($key, true);
                if (
                    $publicKey !== false
                    && strlen($publicKey) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                    && sodium_crypto_sign_verify_detached($signature, $contents, $publicKey)
                ) {
                    return null;
                }
            }
        }

        return new WP_Error(
            'theme_update_signature_invalid',
            __('Die Signatur des Theme-Updates ist ungültig. Das Update wurde abgebrochen.', 'wp-starter')
        );
    }

    /**
     * Base64 Ed25519 public keys that may sign releases. Several entries allow
     * a key rotation: add the new key, release, then drop the old one.
     *
     * @return array<int, string>
     */
    private static function releasePublicKeys(): array
    {
        return self::$publicKeysOverride ?? self::RELEASE_PUBLIC_KEYS;
    }

    /** Body of a release asset, or null when it is missing or unreachable. */
    private function fetchAsset(string $url): ?string
    {
        $response = wp_remote_get($url, ['timeout' => 10]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        return wp_remote_retrieve_body($response);
    }

    /**
     * Extract the hex digest from a `sha256sum`-style checksum file body
     * (`<hex>  <filename>`).
     */
    private function extractHash(string $checksumFileContents): ?string
    {
        $hash = trim(strtok(trim($checksumFileContents), " \t"));

        return preg_match('/^[a-f0-9]{64}$/i', $hash) === 1 ? $hash : null;
    }
}
