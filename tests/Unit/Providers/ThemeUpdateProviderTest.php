<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\ThemeUpdateProvider;
use WP_Error;

/**
 * Tests for the release-checksum verification hooked into
 * ThemeUpdateProvider::verifyPackageChecksum() before a theme package is
 * installed (see docs/SECURITY.md "Update-Integritaet").
 */
final class ThemeUpdateProviderTest extends TestCase
{
    private ThemeUpdateProvider $provider;

    /** @var string[] */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new ThemeUpdateProvider();
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }
        $this->tempFiles = [];
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

    public function testIgnoresPackagesForOtherThemes(): void
    {
        $result = $this->provider->verifyPackageChecksum(
            false,
            'https://example.com/other-plugin.zip',
            ['plugin' => 'some-plugin/some-plugin.php']
        );

        $this->assertFalse($result);
    }

    public function testLeavesAnAlreadyShortCircuitedReplyAlone(): void
    {
        $result = $this->provider->verifyPackageChecksum(
            '/already/downloaded/file.zip',
            'https://example.com/wp-starter.zip',
            ['theme' => 'wp-starter']
        );

        $this->assertSame('/already/downloaded/file.zip', $result);
    }

    public function testMatchingChecksumReturnsDownloadedFile(): void
    {
        $file = $this->seedDownload('theme package contents');
        $hash = hash_file('sha256', $file);

        $GLOBALS['wp_mock_remote_responses']['https://example.com/wp-starter.zip.sha256'] = [
            'response' => ['code' => 200],
            'body' => "{$hash}  wp-starter.zip",
        ];

        $result = $this->provider->verifyPackageChecksum(
            false,
            'https://example.com/wp-starter.zip',
            ['theme' => 'wp-starter']
        );

        $this->assertSame($file, $result);
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
            ['theme' => 'wp-starter']
        );

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('theme_update_checksum_mismatch', $result->get_error_code());
    }

    public function testMissingChecksumAssetContinuesAndReturnsDownloadedFile(): void
    {
        // Older releases were published without a checksum asset: a 404 (or
        // any other non-200/error) must not abort the update, only warn.
        $file = $this->seedDownload('theme package contents');

        $GLOBALS['wp_mock_remote_responses']['https://example.com/wp-starter.zip.sha256'] = [
            'response' => ['code' => 404],
            'body' => '',
        ];

        $result = $this->provider->verifyPackageChecksum(
            false,
            'https://example.com/wp-starter.zip',
            ['theme' => 'wp-starter']
        );

        $this->assertSame($file, $result);
    }
}
