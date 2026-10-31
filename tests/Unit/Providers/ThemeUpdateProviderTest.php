<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\ThemeUpdateProvider;
use WP_Error;

/**
 * Tests for the release checksum and signature verification hooked into
 * ThemeUpdateProvider::verifyPackageChecksum() before a theme package is
 * installed (see docs/SECURITY.md "Update Integrity"). Both assets are
 * mandatory.
 */
final class ThemeUpdateProviderTest extends TestCase
{
    private ThemeUpdateProvider $provider;

    private const PACKAGE = 'https://example.com/wp-starter.zip';

    private string $secretKey = '';

    /** @var string[] */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new ThemeUpdateProvider();

        // A throwaway signing key per test; the real one stays offline.
        $pair = sodium_crypto_sign_keypair();
        $this->secretKey = sodium_crypto_sign_secretkey($pair);
        $this->resetStaticProperties(ThemeUpdateProvider::class, [
            'publicKeysOverride' => [base64_encode(sodium_crypto_sign_publickey($pair))],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }
        $this->tempFiles = [];
        $this->resetStaticProperties(ThemeUpdateProvider::class, ['publicKeysOverride' => null]);
        parent::tearDown();
    }

    private function writeTempFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'theme-update-test-');
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return $path;
    }

    private function seedDownload(string $content): string
    {
        $path = $this->writeTempFile($content);
        $GLOBALS['wp_mock_download_url_result'] = $path;

        return $path;
    }

    /** Seeds a download plus a matching checksum and, optionally, a signature. */
    private function seedRelease(string $content, ?string $signature = null): string
    {
        $file = $this->seedDownload($content);
        $GLOBALS['wp_mock_remote_responses'][self::PACKAGE . '.sha256'] = [
            'response' => ['code' => 200],
            'body' => hash_file('sha256', $file) . '  wp-starter.zip',
        ];
        if ($signature !== null) {
            $GLOBALS['wp_mock_remote_responses'][self::PACKAGE . '.sig'] = [
                'response' => ['code' => 200],
                'body' => $signature . "\n",
            ];
        }

        return $file;
    }

    /** Bytes of a theme zip like the release build: wp-starter/style.css plus a file. */
    private function themeZip(?string $version): string
    {
        $path = $this->writeTempFile('');
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::OVERWRITE);
        $header = "/*\nTheme Name: WP Starter\n" . ( $version !== null ? "Version: {$version}\n" : '' ) . "*/\n";
        $zip->addFromString('wp-starter/style.css', $header);
        $zip->addFromString('wp-starter/assets/style.css', "/*\nVersion: 99.0.0\n*/\n");
        $zip->close();

        return (string) file_get_contents($path);
    }

    private function sign(string $content): string
    {
        return base64_encode(sodium_crypto_sign_detached($content, $this->secretKey));
    }

    private function verify(): mixed
    {
        return $this->provider->verifyPackageChecksum(false, self::PACKAGE, new \WP_Upgrader(), ['theme' => 'wp-starter']);
    }

    public function testIgnoresPackagesForOtherThemes(): void
    {
        $result = $this->provider->verifyPackageChecksum(
            false,
            'https://example.com/other-plugin.zip',
            new \WP_Upgrader(),
            ['plugin' => 'some-plugin/some-plugin.php']
        );

        $this->assertFalse($result);
    }

    public function testLeavesAnAlreadyShortCircuitedReplyAlone(): void
    {
        $result = $this->provider->verifyPackageChecksum(
            '/already/downloaded/file.zip',
            'https://example.com/wp-starter.zip',
            new \WP_Upgrader(),
            ['theme' => 'wp-starter']
        );

        $this->assertSame('/already/downloaded/file.zip', $result);
    }

    public function testMatchingChecksumAndSignatureReturnDownloadedFile(): void
    {
        $zip = $this->themeZip('1.1.0');
        $file = $this->seedRelease($zip, $this->sign($zip));

        $this->assertSame($file, $this->verify());
        $this->assertFileExists($file);
    }

    public function testMismatchedChecksumReturnsWpError(): void
    {
        $file = $this->seedDownload('theme package contents');

        $GLOBALS['wp_mock_remote_responses']['https://example.com/wp-starter.zip.sha256'] = [
            'response' => ['code' => 200],
            'body' => str_repeat('a', 64) . '  wp-starter.zip',
        ];

        $result = $this->provider->verifyPackageChecksum(
            false,
            'https://example.com/wp-starter.zip',
            new \WP_Upgrader(),
            ['theme' => 'wp-starter']
        );

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('theme_update_checksum_mismatch', $result->get_error_code());
    }

    public function testMissingChecksumAssetAbortsTheUpdate(): void
    {
        $this->expectErrorLog();
        $file = $this->seedDownload('theme package contents');

        $GLOBALS['wp_mock_remote_responses'][self::PACKAGE . '.sha256'] = [
            'response' => ['code' => 404],
            'body' => '',
        ];

        $result = $this->verify();

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('theme_update_checksum_missing', $result->get_error_code());
        $this->assertFileDoesNotExist($file, 'the unverified download is deleted');
    }

    public function testMissingSignatureAbortsTheUpdate(): void
    {
        $this->expectErrorLog();
        $file = $this->seedRelease('theme package contents');

        $result = $this->verify();

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('theme_update_signature_missing', $result->get_error_code());
        $this->assertFileDoesNotExist($file);
    }

    public function testSignatureOfOtherContentIsRejected(): void
    {
        $this->seedRelease('theme package contents', $this->sign('other contents'));

        $result = $this->verify();

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('theme_update_signature_invalid', $result->get_error_code());
    }

    public function testSignatureByAnUnknownKeyIsRejected(): void
    {
        $foreign = sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair());
        $this->seedRelease('theme package contents', base64_encode(sodium_crypto_sign_detached('theme package contents', $foreign)));

        $result = $this->verify();

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('theme_update_signature_invalid', $result->get_error_code());
    }

    public function testSecondKeyAllowsRotation(): void
    {
        $old = sodium_crypto_sign_keypair();
        $this->resetStaticProperties(ThemeUpdateProvider::class, [
            'publicKeysOverride' => [
                base64_encode(sodium_crypto_sign_publickey($old)),
                base64_encode(sodium_crypto_sign_publickey_from_secretkey($this->secretKey)),
            ],
        ]);
        $zip = $this->themeZip('1.1.0');
        $file = $this->seedRelease($zip, $this->sign($zip));

        $this->assertSame($file, $this->verify());
    }

    public function testReplayedOlderSignedPackageIsRejected(): void
    {
        // A validly signed zip of an older release, attached to a new tag.
        $GLOBALS['wp_mock_theme_version'] = '2.43.0';
        $zip = $this->themeZip('2.42.4');
        $file = $this->seedRelease($zip, $this->sign($zip));

        $result = $this->verify();

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('theme_update_version_mismatch', $result->get_error_code());
        $this->assertFileDoesNotExist($file);
    }

    public function testSameVersionAsInstalledIsRejected(): void
    {
        $zip = $this->themeZip('1.0.0');
        $this->seedRelease($zip, $this->sign($zip));

        $this->assertSame('theme_update_version_mismatch', $this->verify()->get_error_code());
    }

    public function testPackagedVersionMustMatchTheAnnouncedOne(): void
    {
        $GLOBALS['wp_mock_site_transients']['update_themes'] = (object) [
            'response' => ['wp-starter' => ['new_version' => '2.0.0']],
        ];
        $older = $this->themeZip('1.5.0');
        $this->seedRelease($older, $this->sign($older));
        $this->assertSame('theme_update_version_mismatch', $this->verify()->get_error_code());

        $announced = $this->themeZip('2.0.0');
        $file = $this->seedRelease($announced, $this->sign($announced));
        $this->assertSame($file, $this->verify());
    }

    public function testPackageWithoutReadableVersionIsRejected(): void
    {
        foreach ([$this->themeZip(null), $this->themeZip('next'), 'not a zip'] as $content) {
            $this->seedRelease($content, $this->sign($content));
            $this->assertSame('theme_update_version_mismatch', $this->verify()->get_error_code());
        }
    }

    public function testShippedKeysAreEd25519PublicKeys(): void
    {
        $keys = ( new \ReflectionClassConstant(ThemeUpdateProvider::class, 'RELEASE_PUBLIC_KEYS') )->getValue();

        $this->assertNotEmpty($keys, 'without a key no update installs');
        foreach ($keys as $key) {
            $this->assertSame(SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES, strlen( (string) base64_decode($key, true)));
        }
    }

    public function testWithoutConfiguredKeyNoUpdateInstalls(): void
    {
        $this->resetStaticProperties(ThemeUpdateProvider::class, ['publicKeysOverride' => []]);
        $this->seedRelease('theme package contents', $this->sign('theme package contents'));

        $result = $this->verify();

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('theme_update_no_signing_key', $result->get_error_code());
    }

    public function testFilterAcceptsCoreArgumentOrder(): void
    {
        $method = new \ReflectionMethod($this->provider, 'registerChecksumVerification');
        $method->invoke($this->provider);

        $this->assertSame(4, $GLOBALS['wp_mock_hooks']['filters']['upgrader_pre_download'][0]['args']);

        $result = apply_filters(
            'upgrader_pre_download',
            false,
            'https://example.com/other-plugin.zip',
            new \WP_Upgrader(),
            ['plugin' => 'some-plugin/some-plugin.php']
        );

        $this->assertFalse($result);
    }
}
