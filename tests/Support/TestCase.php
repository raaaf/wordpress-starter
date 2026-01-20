<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base test case class for all tests.
 *
 * Provides common setup and teardown functionality,
 * and includes the WordPressMocks trait for easy mocking.
 */
abstract class TestCase extends BaseTestCase
{
    use WordPressMocks;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetAllMocks();
    }

    protected function tearDown(): void
    {
        $this->resetAllMocks();
        parent::tearDown();
    }
}
