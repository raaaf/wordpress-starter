<?php

declare(strict_types=1);

namespace Tests\Unit\MemberArea;

use RuntimeException;
use Tests\Support\TestCase;
use phpseclib3\Net\SFTP;
use WordpressStarter\MemberArea\FolderSync;
use WordpressStarter\MemberArea\SftpClient;
use WordpressStarter\ThemeContext;

/**
 * Tests for sync error collection, persistence and the listing failure.
 */
final class SyncErrorsTest extends TestCase
{
    public function testRecordedErrorsArePersistedAtFinish(): void
    {
        FolderSync::beginRun();
        FolderSync::recordError('sftp.example.com', '/docs', 'boom');
        FolderSync::finishRun();

        $stored = get_option(ThemeContext::optionKey('sftp_sync_errors'));
        $this->assertCount(1, $stored);
        $this->assertSame('sftp.example.com', $stored[0]['host']);
        $this->assertSame('/docs', $stored[0]['path']);
        $this->assertSame('boom', $stored[0]['message']);
        $this->assertEqualsWithDelta(time(), $stored[0]['time'], 5);
    }

    public function testRunWithoutErrorsStoresEmptyList(): void
    {
        FolderSync::beginRun();
        FolderSync::recordError('h', '', 'old');
        FolderSync::finishRun();

        FolderSync::beginRun();
        FolderSync::finishRun();

        $this->assertSame([], get_option(ThemeContext::optionKey('sftp_sync_errors')));
    }

    public function testLastErrorsReturnsStoredList(): void
    {
        $this->assertSame([], FolderSync::lastErrors());

        FolderSync::beginRun();
        FolderSync::recordError('h', 'p', 'm');
        FolderSync::finishRun();

        $this->assertSame('m', FolderSync::lastErrors()[0]['message']);
    }

    public function testListFilesThrowsWhenNlistFails(): void
    {
        // Anonymous subclass: a PHPUnit mock of SFTP fatals in SSH2::__destruct (constants defined lazily).
        $sftp = new class() extends SFTP {
            public function __construct()
            {
            }

            public function __destruct()
            {
            }

            public function nlist($dir = '.', $recursive = false)
            {
                return false;
            }
        };

        // listFiles() also logs the failed nlist as a warning.
        $this->expectErrorLog();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SFTP listing failed for /docs/');

        SftpClient::listFiles($sftp, '/docs', ['pdf']);
    }

    public function testScanReturnsNullAndRecordsErrorWhenListingFails(): void
    {
        // The failed listing and the failed scan are both logged as warnings.
        $this->expectErrorLog();
        $sftp = new class() extends SFTP {
            public function __construct()
            {
            }

            public function __destruct()
            {
            }

            public function nlist($dir = '.', $recursive = false)
            {
                return false;
            }
        };

        FolderSync::beginRun();
        $scan = new \ReflectionMethod(FolderSync::class, 'scanSftpDirectory');
        $result = $scan->invoke(null, $sftp, 'sftp.example.com', 22, '/docs');
        FolderSync::finishRun();

        $this->assertNull($result);
        $this->assertSame('/docs', FolderSync::lastErrors()[0]['path']);
    }
}
