<?php

declare(strict_types=1);

namespace Tests\Unit\MemberArea;

use Tests\Support\TestCase;
use WordpressStarter\MemberArea\FolderSync;
use WordpressStarter\ThemeContext;

/**
 * Tests for the sync lock (acquire, stale takeover, release).
 */
final class FolderSyncLockTest extends TestCase
{
    private function lockKey(): string
    {
        return ThemeContext::optionKey('sftp_sync_lock');
    }

    public function testAcquireOnEmptySucceeds(): void
    {
        $this->assertTrue(FolderSync::acquireLock());
    }

    public function testSecondAcquireFails(): void
    {
        FolderSync::acquireLock();

        $this->assertFalse(FolderSync::acquireLock());
    }

    public function testStaleLockIsTakenOver(): void
    {
        $GLOBALS['wp_mock_options'][$this->lockKey()] = (string) ( time() - 16 * 60 );

        $this->assertTrue(FolderSync::acquireLock());
        $this->assertGreaterThan(time() - 5, (int) get_option($this->lockKey()));
    }

    public function testFreshLockIsRespected(): void
    {
        $stamp = (string) ( time() - 60 );
        $GLOBALS['wp_mock_options'][$this->lockKey()] = $stamp;

        $this->assertFalse(FolderSync::acquireLock());
        $this->assertSame($stamp, get_option($this->lockKey()));
    }

    public function testReleaseAllowsAcquire(): void
    {
        FolderSync::acquireLock();
        FolderSync::releaseLock();

        $this->assertTrue(FolderSync::acquireLock());
    }

    public function testReleaseKeepsLockOfAnotherOwner(): void
    {
        FolderSync::acquireLock();
        $other = time() . ':other';
        $GLOBALS['wp_mock_options'][$this->lockKey()] = $other;

        FolderSync::releaseLock();

        $this->assertSame($other, get_option($this->lockKey()));
    }
}
