<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;
use WordpressStarter\RateLimiter;

/**
 * Tests for RateLimiter, covering both the transient-based path (default
 * test environment) and the persistent-object-cache path
 * ($GLOBALS['wp_mock_using_ext_object_cache'] = true).
 */
final class RateLimiterTest extends TestCase
{
    public function testAttemptAllowsRequestsUpToTheLimit(): void
    {
        $limiter = new RateLimiter('test_action', 3, 60);

        $this->assertTrue($limiter->attempt());
        $this->assertTrue($limiter->attempt());
        $this->assertTrue($limiter->attempt());
    }

    public function testAttemptBlocksAfterMaxAttemptsReached(): void
    {
        $limiter = new RateLimiter('test_action', 2, 60);

        $this->assertTrue($limiter->attempt());
        $this->assertTrue($limiter->attempt());
        $this->assertFalse($limiter->attempt());
    }

    public function testTooManyAttemptsDoesNotRecordAnAttempt(): void
    {
        $limiter = new RateLimiter('test_action', 1, 60);

        $this->assertFalse($limiter->tooManyAttempts());

        $limiter->attempt();

        $this->assertTrue($limiter->tooManyAttempts());
        // Checking again must not itself count as a further attempt.
        $this->assertTrue($limiter->tooManyAttempts());
    }

    public function testRemainingAttemptsCountsDown(): void
    {
        $limiter = new RateLimiter('test_action', 3, 60);

        $this->assertSame(3, $limiter->remainingAttempts());
        $limiter->attempt();
        $this->assertSame(2, $limiter->remainingAttempts());
        $limiter->attempt();
        $this->assertSame(1, $limiter->remainingAttempts());
        $limiter->attempt();
        $this->assertSame(0, $limiter->remainingAttempts());
    }

    public function testRemainingAttemptsNeverGoesBelowZero(): void
    {
        $limiter = new RateLimiter('test_action', 1, 60);

        $limiter->attempt();
        $limiter->attempt();
        $limiter->attempt();

        $this->assertSame(0, $limiter->remainingAttempts());
    }

    public function testRetryAfterIsZeroBeforeAnyAttempt(): void
    {
        $limiter = new RateLimiter('test_action', 1, 60);

        $this->assertSame(0, $limiter->retryAfter());
    }

    public function testRetryAfterReflectsTheDecayWindow(): void
    {
        $limiter = new RateLimiter('test_action', 1, 60);

        $limiter->attempt();

        $retryAfter = $limiter->retryAfter();

        $this->assertGreaterThan(0, $retryAfter);
        $this->assertLessThanOrEqual(60, $retryAfter);
    }

    public function testWindowExpiresAndResetsTheCount(): void
    {
        // decaySeconds = -1 puts 'expires' in the past immediately, simulating
        // an already-expired window on the very next read.
        $limiter = new RateLimiter('test_action', 1, -1);

        $this->assertTrue($limiter->attempt());
        // The window from the first attempt has already expired, so this is
        // attempt 1 of a fresh window, not attempt 2 of the old one.
        $this->assertTrue($limiter->attempt());
    }

    public function testClearRemovesTheRecordedAttempts(): void
    {
        $limiter = new RateLimiter('test_action', 1, 60);

        $limiter->attempt();
        $this->assertTrue($limiter->tooManyAttempts());

        $limiter->clear();

        $this->assertFalse($limiter->tooManyAttempts());
        $this->assertSame(0, $limiter->retryAfter());
    }

    public function testDifferentActionsAreTrackedIndependently(): void
    {
        $first = new RateLimiter('action_one', 1, 60);
        $second = new RateLimiter('action_two', 1, 60);

        $this->assertTrue($first->attempt());
        $this->assertTrue($first->tooManyAttempts());
        $this->assertFalse($second->tooManyAttempts());
    }

    public function testDifferentUsersAreTrackedIndependently(): void
    {
        $GLOBALS['wp_mock_current_user_id'] = 1;
        $first = new RateLimiter('shared_action', 1, 60);
        $first->attempt();

        $GLOBALS['wp_mock_current_user_id'] = 2;
        $second = new RateLimiter('shared_action', 1, 60);

        $this->assertFalse($second->tooManyAttempts());
    }

    // -- Object cache branch ------------------------------------------------

    public function testObjectCachePathAllowsRequestsUpToTheLimit(): void
    {
        $GLOBALS['wp_mock_using_ext_object_cache'] = true;

        $limiter = new RateLimiter('cache_action', 3, 60);

        $this->assertTrue($limiter->attempt());
        $this->assertTrue($limiter->attempt());
        $this->assertTrue($limiter->attempt());
    }

    public function testObjectCachePathBlocksAfterMaxAttemptsReached(): void
    {
        $GLOBALS['wp_mock_using_ext_object_cache'] = true;

        $limiter = new RateLimiter('cache_action', 2, 60);

        $this->assertTrue($limiter->attempt());
        $this->assertTrue($limiter->attempt());
        $this->assertFalse($limiter->attempt());
    }

    public function testObjectCachePathRetryAfterReflectsDecayWindow(): void
    {
        $GLOBALS['wp_mock_using_ext_object_cache'] = true;

        $limiter = new RateLimiter('cache_action', 1, 60);
        $limiter->attempt();

        $retryAfter = $limiter->retryAfter();

        $this->assertGreaterThan(0, $retryAfter);
        $this->assertLessThanOrEqual(60, $retryAfter);
    }

    public function testObjectCachePathClearRemovesTheRecordedAttempts(): void
    {
        $GLOBALS['wp_mock_using_ext_object_cache'] = true;

        $limiter = new RateLimiter('cache_action', 1, 60);
        $limiter->attempt();
        $this->assertTrue($limiter->tooManyAttempts());

        $limiter->clear();

        $this->assertFalse($limiter->tooManyAttempts());
        $this->assertSame(0, $limiter->retryAfter());
    }

    /**
     * Core wp_using_ext_object_cache() returns null (not false) until
     * wp_start_object_cache() has run; RateLimiter must treat that as "no
     * cache" and fall back to the transient path rather than crash on the
     * bool cast.
     */
    public function testNullObjectCacheStateFallsBackToTransientPath(): void
    {
        $GLOBALS['wp_mock_using_ext_object_cache'] = null;

        $limiter = new RateLimiter('null_cache_action', 1, 60);

        $this->assertTrue($limiter->attempt());
        $this->assertFalse($limiter->attempt());
    }

    public function testStaticCheckHelperReturnsAttemptResult(): void
    {
        $this->assertTrue(RateLimiter::check('static_action', 1, 60));
        $this->assertFalse(RateLimiter::check('static_action', 1, 60));
    }
}
