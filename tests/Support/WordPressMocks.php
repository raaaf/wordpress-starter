<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Trait providing WordPress mock utilities for tests.
 *
 * Allows tests to easily set up mock values for WordPress functions
 * without needing the actual WordPress environment.
 */
trait WordPressMocks
{
    /**
     * Reset all mock values to their defaults.
     */
    protected function resetAllMocks(): void
    {
        $GLOBALS['wp_mock_fields'] = [];
        $GLOBALS['wp_mock_options'] = [];
        $GLOBALS['wp_mock_cache'] = [];
        $GLOBALS['wp_mock_hooks'] = ['actions' => [], 'filters' => []];
        $GLOBALS['wp_mock_enqueued'] = ['scripts' => [], 'styles' => []];
        $GLOBALS['wp_mock_attachments'] = [];
        $GLOBALS['wp_mock_post_meta'] = [];
        $GLOBALS['wp_mock_all_fields'] = [];
        $GLOBALS['wp_mock_sub_fields'] = [];
        $GLOBALS['wp_mock_repeater_rows'] = [];
        $GLOBALS['wp_mock_registered_blocks'] = [];
        $GLOBALS['wp_mock_theme_support'] = [];
        $GLOBALS['wp_mock_nav_menus'] = [];
        $GLOBALS['wp_mock_is_admin'] = false;
        $GLOBALS['wp_mock_template_directory'] = __DIR__ . '/../fixtures';
        $GLOBALS['wp_mock_template_directory_uri'] = 'https://example.com/wp-content/themes/wp-starter';
        $GLOBALS['blade'] = null;

        // Reset Config static state
        $this->resetConfigState();
    }

    /**
     * Reset Config class static properties.
     */
    protected function resetConfigState(): void
    {
        $reflection = new \ReflectionClass(\WordpressStarter\Config::class);

        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue(null, []);

        $loadedProperty = $reflection->getProperty('loaded');
        $loadedProperty->setAccessible(true);
        $loadedProperty->setValue(null, false);
    }

    /**
     * Set the template directory for tests.
     */
    protected function setTemplateDirectory(string $path): void
    {
        $GLOBALS['wp_mock_template_directory'] = $path;
    }

    /**
     * Set a mock ACF field value.
     */
    protected function setMockField(string $field, mixed $value, mixed $postId = null): void
    {
        $key = $postId !== null ? "{$field}:{$postId}" : $field;
        $GLOBALS['wp_mock_fields'][$key] = $value;
    }

    /**
     * Set all fields for a post.
     */
    protected function setMockAllFields(mixed $postId, array $fields): void
    {
        $GLOBALS['wp_mock_all_fields'][$postId] = $fields;
    }

    /**
     * Set a mock sub field value (for repeaters).
     */
    protected function setMockSubField(string $field, mixed $value): void
    {
        $GLOBALS['wp_mock_sub_fields'][$field] = $value;
    }

    /**
     * Set mock repeater rows.
     */
    protected function setMockRepeaterRows(string $field, array $rows): void
    {
        $GLOBALS['wp_mock_repeater_rows'][$field] = $rows;
    }

    /**
     * Set a mock cache value.
     */
    protected function setMockCache(string $key, mixed $value, string $group = 'default'): void
    {
        $cacheKey = "{$group}:{$key}";
        $GLOBALS['wp_mock_cache'][$cacheKey] = $value;
    }

    /**
     * Get a mock cache value.
     */
    protected function getMockCache(string $key, string $group = 'default'): mixed
    {
        $cacheKey = "{$group}:{$key}";
        return $GLOBALS['wp_mock_cache'][$cacheKey] ?? null;
    }

    /**
     * Clear a specific cache key.
     */
    protected function clearMockCache(string $key, string $group = 'default'): void
    {
        $cacheKey = "{$group}:{$key}";
        unset($GLOBALS['wp_mock_cache'][$cacheKey]);
    }

    /**
     * Set mock attachment image data.
     */
    protected function setMockAttachment(int $attachmentId, string $size, array $data): void
    {
        if (!isset($GLOBALS['wp_mock_attachments'][$attachmentId])) {
            $GLOBALS['wp_mock_attachments'][$attachmentId] = [];
        }
        $GLOBALS['wp_mock_attachments'][$attachmentId][$size] = $data;
    }

    /**
     * Set mock post meta.
     */
    protected function setMockPostMeta(int $postId, string $key, mixed $value): void
    {
        if (!isset($GLOBALS['wp_mock_post_meta'][$postId])) {
            $GLOBALS['wp_mock_post_meta'][$postId] = [];
        }
        $GLOBALS['wp_mock_post_meta'][$postId][$key] = $value;
    }

    /**
     * Check if an action was registered.
     */
    protected function assertActionAdded(string $hook): void
    {
        $this->assertArrayHasKey(
            $hook,
            $GLOBALS['wp_mock_hooks']['actions'],
            "Action '{$hook}' was not registered"
        );
    }

    /**
     * Check if a filter was registered.
     */
    protected function assertFilterAdded(string $hook): void
    {
        $this->assertArrayHasKey(
            $hook,
            $GLOBALS['wp_mock_hooks']['filters'],
            "Filter '{$hook}' was not registered"
        );
    }

    /**
     * Get enqueued scripts.
     */
    protected function getEnqueuedScripts(): array
    {
        return $GLOBALS['wp_mock_enqueued']['scripts'];
    }

    /**
     * Get enqueued styles.
     */
    protected function getEnqueuedStyles(): array
    {
        return $GLOBALS['wp_mock_enqueued']['styles'];
    }

    /**
     * Check if a script was enqueued.
     */
    protected function assertScriptEnqueued(string $handle): void
    {
        $this->assertArrayHasKey(
            $handle,
            $GLOBALS['wp_mock_enqueued']['scripts'],
            "Script '{$handle}' was not enqueued"
        );
    }

    /**
     * Check if a style was enqueued.
     */
    protected function assertStyleEnqueued(string $handle): void
    {
        $this->assertArrayHasKey(
            $handle,
            $GLOBALS['wp_mock_enqueued']['styles'],
            "Style '{$handle}' was not enqueued"
        );
    }

    /**
     * Get registered blocks.
     */
    protected function getRegisteredBlocks(): array
    {
        return $GLOBALS['wp_mock_registered_blocks'] ?? [];
    }

    /**
     * Set is_admin() return value.
     */
    protected function setIsAdmin(bool $isAdmin): void
    {
        $GLOBALS['wp_mock_is_admin'] = $isAdmin;
    }

    /**
     * Set the global Blade view factory.
     */
    protected function setBladeFactory(mixed $factory): void
    {
        $GLOBALS['blade'] = $factory;
    }

    /**
     * Create a temporary .env file for testing.
     */
    protected function createTempEnvFile(string $content): string
    {
        $tempDir = sys_get_temp_dir() . '/wp-starter-test-' . uniqid();
        mkdir($tempDir, 0777, true);
        file_put_contents($tempDir . '/.env', $content);
        $this->setTemplateDirectory($tempDir);
        return $tempDir;
    }

    /**
     * Create a temporary config file for testing.
     */
    protected function createTempConfigFile(string $content): string
    {
        $tempDir = $GLOBALS['wp_mock_template_directory'];
        $configDir = $tempDir . '/config';
        if (!is_dir($configDir)) {
            mkdir($configDir, 0777, true);
        }
        file_put_contents($configDir . '/app.php', $content);
        return $configDir . '/app.php';
    }

    /**
     * Clean up temporary test files.
     */
    protected function cleanupTempDir(string $dir): void
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                is_dir($path) ? $this->cleanupTempDir($path) : unlink($path);
            }
            rmdir($dir);
        }
    }
}
