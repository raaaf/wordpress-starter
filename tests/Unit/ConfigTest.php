<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;
use WordpressStarter\Config;

/**
 * Tests for Config class.
 */
final class ConfigTest extends TestCase
{
    private string $tempDir = '';

    protected function tearDown(): void
    {
        if ($this->tempDir !== '' && is_dir($this->tempDir)) {
            $this->cleanupTempDir($this->tempDir);
        }
        parent::tearDown();
    }

    public function testGetReturnsDefaultWhenKeyNotFound(): void
    {
        $result = Config::get('non_existent_key', 'default_value');

        $this->assertSame('default_value', $result);
    }

    public function testGetReturnsValueWhenKeyExists(): void
    {
        Config::set('existing_key', 'stored_value');

        $result = Config::get('existing_key', 'default');

        $this->assertSame('stored_value', $result);
    }

    public function testDotNotationAccessesNestedValues(): void
    {
        // Use fixtures which have nested config
        $this->setTemplateDirectory(__DIR__ . '/../fixtures');

        $result = Config::get('nested.level1.level2.value');

        $this->assertSame('deep-nested-value', $result);
    }

    public function testDotNotationReturnsDefaultForMissingNested(): void
    {
        $this->setTemplateDirectory(__DIR__ . '/../fixtures');

        $result = Config::get('nested.missing.path', 'fallback');

        $this->assertSame('fallback', $result);
    }

    public function testSetStoresValue(): void
    {
        Config::set('new_key', 'new_value');

        $result = Config::get('new_key');

        $this->assertSame('new_value', $result);
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        Config::set('check_key', 'value');

        $this->assertTrue(Config::has('check_key'));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        $this->assertFalse(Config::has('definitely_not_set_key'));
    }

    public function testLoadEnvironmentVariablesParsesEnvFile(): void
    {
        $this->setTemplateDirectory(__DIR__ . '/../fixtures');

        // Force a fresh load
        $this->resetConfigState();

        $result = Config::get('APP_ENV');

        $this->assertSame('testing', $result);
    }

    public function testEnvFileCommentsAreIgnored(): void
    {
        $this->tempDir = $this->createTempEnvFile(
            "# This is a comment\nVALID_KEY=valid_value\n# Another comment"
        );

        $result = Config::get('VALID_KEY');

        $this->assertSame('valid_value', $result);
        $this->assertNull(Config::get('# This is a comment'));
    }

    public function testEnvFileQuotedValuesAreTrimmed(): void
    {
        $this->setTemplateDirectory(__DIR__ . '/../fixtures');
        $this->resetConfigState();

        $doubleQuoted = Config::get('QUOTED_DOUBLE');
        $singleQuoted = Config::get('QUOTED_SINGLE');

        $this->assertSame('value with spaces', $doubleQuoted);
        $this->assertSame('another value', $singleQuoted);
    }

    public function testLoadOnlyExecutesOnce(): void
    {
        $this->tempDir = $this->createTempEnvFile('COUNTER_KEY=first_value');

        // First load
        Config::get('COUNTER_KEY');

        // Modify the env file
        file_put_contents($this->tempDir . '/.env', 'COUNTER_KEY=second_value');

        // Second access should still return first value (cached)
        $result = Config::get('COUNTER_KEY');

        $this->assertSame('first_value', $result);
    }

    public function testGetWithNonArrayValueReturnDefault(): void
    {
        Config::set('string_value', 'just a string');

        // Try to access nested path on a string value
        $result = Config::get('string_value.nested', 'default');

        $this->assertSame('default', $result);
    }
}
