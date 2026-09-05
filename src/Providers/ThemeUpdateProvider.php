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
     * Verify the downloaded theme package against its GitHub release
     * checksum before WordPress installs it.
     *
     * Hooked on core's `upgrader_pre_download` (WP_Upgrader::download_package())
     * rather than a plugin-update-checker filter: at this point we still have
     * the package URL (needed to derive the `.sha256` release-asset URL) and
     * full control over the downloaded file before installation. A later
     * hook such as `upgrader_source_selection` only exposes an already
     * unpacked directory, too late to hash against a single zip checksum.
     */
    private function registerChecksumVerification(): void
    {
        add_filter('upgrader_pre_download', [$this, 'verifyPackageChecksum'], 10, 3);
    }

    /**
     * @param mixed $reply
     * @param array<string, mixed> $hookExtra
     * @return mixed
     */
    public function verifyPackageChecksum(mixed $reply, string $package, array $hookExtra): mixed
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

        $checksumUrl = $package . '.sha256';
        $response = wp_remote_get($checksumUrl, ['timeout' => 10]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            // Older releases were published without a checksum asset; do not
            // block the update, just record that it could not be verified.
            LogServiceProvider::warning('Theme-Update-Pruefsumme nicht verfuegbar', [
                'package' => $package,
            ]);

            return $file;
        }

        $expectedHash = $this->extractHash(wp_remote_retrieve_body($response));
        $actualHash = hash_file('sha256', $file);

        if ($expectedHash === null || $actualHash === false || ! hash_equals($expectedHash, $actualHash)) {
            @unlink($file);

            return new WP_Error(
                'theme_update_checksum_mismatch',
                __('Die Pruefsumme des Theme-Updates stimmt nicht mit dem veroeffentlichten Release ueberein. Das Update wurde abgebrochen.', 'wp-starter')
            );
        }

        return $file;
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
