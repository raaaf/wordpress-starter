<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;
use WordpressStarter\Config;

/**
 * Tests for helper functions in src/helpers.php.
 */
final class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear any cached env values
        foreach ($_ENV as $key => $value) {
            if (str_starts_with($key, 'TEST_')) {
                unset($_ENV[$key]);
                putenv($key);
            }
        }
    }

    public function testEnvReturnsDefaultWhenNotSet(): void
    {
        $result = env('NON_EXISTENT_KEY', 'default_value');

        $this->assertSame('default_value', $result);
    }

    public function testEnvReturnsValueFromEnv(): void
    {
        $_ENV['TEST_KEY'] = 'test_value';

        $result = env('TEST_KEY', 'default');

        $this->assertSame('test_value', $result);
    }

    public function testEnvCastsTrueStrings(): void
    {
        $_ENV['TEST_TRUE_LOWER'] = 'true';
        $_ENV['TEST_TRUE_PARENS'] = '(true)';

        $this->assertTrue(env('TEST_TRUE_LOWER'));
        $this->assertTrue(env('TEST_TRUE_PARENS'));
    }

    public function testEnvCastsFalseStrings(): void
    {
        $_ENV['TEST_FALSE_LOWER'] = 'false';
        $_ENV['TEST_FALSE_PARENS'] = '(false)';

        $this->assertFalse(env('TEST_FALSE_LOWER'));
        $this->assertFalse(env('TEST_FALSE_PARENS'));
    }

    public function testEnvCastsEmptyStrings(): void
    {
        $_ENV['TEST_EMPTY_LOWER'] = 'empty';
        $_ENV['TEST_EMPTY_PARENS'] = '(empty)';

        $this->assertSame('', env('TEST_EMPTY_LOWER'));
        $this->assertSame('', env('TEST_EMPTY_PARENS'));
    }

    public function testEnvCastsNullStrings(): void
    {
        $_ENV['TEST_NULL_LOWER'] = 'null';
        $_ENV['TEST_NULL_PARENS'] = '(null)';

        $this->assertNull(env('TEST_NULL_LOWER'));
        $this->assertNull(env('TEST_NULL_PARENS'));
    }

    public function testEnvIsCaseInsensitive(): void
    {
        $_ENV['TEST_TRUE_UPPER'] = 'TRUE';
        $_ENV['TEST_FALSE_MIXED'] = 'False';
        $_ENV['TEST_NULL_UPPER'] = 'NULL';

        $this->assertTrue(env('TEST_TRUE_UPPER'));
        $this->assertFalse(env('TEST_FALSE_MIXED'));
        $this->assertNull(env('TEST_NULL_UPPER'));
    }

    public function testEnvReturnsDefaultWhenGetenvReturnsFalse(): void
    {
        // Ensure key is not in $_ENV and getenv returns false
        unset($_ENV['TOTALLY_MISSING_KEY']);
        putenv('TOTALLY_MISSING_KEY');

        $result = env('TOTALLY_MISSING_KEY', 'fallback');

        $this->assertSame('fallback', $result);
    }

    public function testConfigDelegatesToConfigClass(): void
    {
        // Set a value directly in Config
        Config::set('test_key', 'test_value');

        $result = config('test_key', 'default');

        $this->assertSame('test_value', $result);
    }

    public function testGetBladeViewFactoryReturnsGlobal(): void
    {
        // Create a mock that satisfies the ?Factory return type
        $mockFactory = $this->createMock(\Illuminate\View\Factory::class);
        $GLOBALS['blade'] = $mockFactory;

        $result = getBladeViewFactory();

        $this->assertSame($mockFactory, $result);
    }

    public function testGetBladeViewFactoryReturnsNullWhenNotSet(): void
    {
        $GLOBALS['blade'] = null;

        $result = getBladeViewFactory();

        $this->assertNull($result);
    }
}
