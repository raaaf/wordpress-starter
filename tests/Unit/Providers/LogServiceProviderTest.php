<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\LogServiceProvider;

/**
 * Tests for log level gating: warnings always reach the PHP error log, info only with WP_DEBUG_LOG.
 */
final class LogServiceProviderTest extends TestCase
{
    private string $logFile;
    private string|false|null $previousLog = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logFile = (string) tempnam(sys_get_temp_dir(), 'logtest');
    }

    protected function tearDown(): void
    {
        if ($this->previousLog !== null) {
            ini_set('error_log', $this->previousLog === false ? '' : $this->previousLog);
        }
        unlink($this->logFile);
        parent::tearDown();
    }

    /**
     * Point error_log() at the temp file. Called inside each test, not in setUp():
     * PHPUnit redirects error_log itself after setUp() to capture unexpected output.
     */
    private function captureErrorLog(): void
    {
        $this->previousLog = ini_get('error_log');
        ini_set('error_log', $this->logFile);
    }

    public function testWarningIsWrittenWithoutWpDebugLog(): void
    {
        $this->captureErrorLog();
        LogServiceProvider::warning('warn-message');

        $this->assertStringContainsString('warn-message', (string) file_get_contents($this->logFile));
    }

    public function testExceptionTraceOmitsCallArguments(): void
    {
        $this->captureErrorLog();
        $thrower = static function (string $secret): void {
            throw new \RuntimeException('boom');
        };

        try {
            $thrower('s3cret-password');
        } catch (\RuntimeException $e) {
            LogServiceProvider::exception($e);
        }

        $log = (string) file_get_contents($this->logFile);
        $this->assertStringNotContainsString('s3cret-password', $log);
        $this->assertStringContainsString('{closure', $log);
    }

    public function testInfoIsNotWrittenWithoutWpDebugLog(): void
    {
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $this->markTestSkipped('WP_DEBUG_LOG is enabled in this environment.');
        }

        $this->captureErrorLog();
        LogServiceProvider::info('info-message');

        $this->assertStringNotContainsString('info-message', (string) file_get_contents($this->logFile));
    }
}
