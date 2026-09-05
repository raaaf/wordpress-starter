<?php

declare(strict_types=1);

namespace Tests\Support;

use ReflectionClass;

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
        // Every wp_mock_* global defined in tests/bootstrap.php, mapped to the
        // default it has there (either an explicit initial assignment or the
        // `??` fallback a mock function reads when the key is absent). Keeps
        // one test's state from leaking into the next via any of them.
        $defaults = [
            'wp_mock_fields' => [],
            'wp_mock_options' => [],
            'wp_mock_cache' => [],
            'wp_mock_hooks' => ['actions' => [], 'filters' => []],
            'wp_mock_enqueued' => ['scripts' => [], 'styles' => []],
            'wp_mock_attachments' => [],
            'wp_mock_post_meta' => [],
            'wp_mock_post_fields' => [],
            'wp_mock_all_fields' => [],
            'wp_mock_sub_fields' => [],
            'wp_mock_repeater_rows' => [],
            'wp_mock_registered_blocks' => [],
            'wp_mock_theme_support' => [],
            'wp_mock_nav_menus' => [],
            'wp_mock_is_admin' => false,
            'wp_mock_queried_object_id' => 0,
            'wp_mock_post_types' => [],
            'wp_mock_taxonomies' => [],
            'wp_mock_template_directory' => __DIR__ . '/../fixtures',
            'wp_mock_template_directory_uri' => 'https://example.com/wp-content/themes/wp-starter',
            'wp_mock_template' => null,
            'wp_mock_stylesheet' => null,
            'wp_mock_theme_mods' => [],
            'wp_mock_current_blog_id' => 1,
            'wp_mock_current_row' => null,
            'wp_mock_current_user_can' => [],
            'wp_mock_current_user_id' => 0,
            'wp_mock_doing_ajax' => false,
            'wp_mock_inline_scripts' => [],
            'wp_mock_is_404' => false,
            'wp_mock_is_front_page' => false,
            'wp_mock_is_multisite' => false,
            'wp_mock_is_page' => false,
            'wp_mock_is_search' => false,
            'wp_mock_is_singular' => false,
            'wp_mock_localized' => [],
            'wp_mock_nocache_called' => false,
            'wp_mock_password_required' => false,
            'wp_mock_permalinks' => [],
            'wp_mock_post_id' => false,
            'wp_mock_posts' => [],
            'wp_mock_posts_by_id' => [],
            'wp_mock_registered_field_groups' => [],
            'wp_mock_script_data' => [],
            'wp_mock_settings_errors' => [],
            'wp_mock_shortcodes' => [],
            'wp_mock_titles' => [],
            'wp_mock_transients' => [],
            'wp_mock_excerpt' => '',
            'wp_mock_archive_description' => '',
            'wp_mock_bloginfo' => [],
            'wp_mock_have_rows_cursor' => [],
        ];

        foreach ($defaults as $key => $default) {
            $GLOBALS[$key] = $default;
        }

        $GLOBALS['blade'] = null;

        // Reset Config static state
        $this->resetConfigState();

        // Reset StyleguidePage::find() memoization cache (see $cachedResults doc
        // there): otherwise a cached page ID from one test class can silently
        // survive into the next without any test noticing.
        $this->resetStyleguidePageCache();

        // Reset FieldDefinitions::getThemeIcons() memoization: otherwise a
        // fallback icon list read against a fixture template dir (no
        // config/icons.json) freezes before a later test sets the real
        // theme directory.
        \WordpressStarter\Acf\FieldDefinitions::resetIconCache();
    }

    /**
     * Reset Config class static properties.
     */
    protected function resetConfigState(): void
    {
        $reflection = new ReflectionClass(\WordpressStarter\Config::class);

        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue(null, []);

        $loadedProperty = $reflection->getProperty('loaded');
        $loadedProperty->setAccessible(true);
        $loadedProperty->setValue(null, false);
    }

    /**
     * Reset StyleguidePage's per-blog find() memoization cache.
     */
    protected function resetStyleguidePageCache(): void
    {
        $reflection = new ReflectionClass(\WordpressStarter\Services\StyleguidePage::class);

        $cachedResultsProperty = $reflection->getProperty('cachedResults');
        $cachedResultsProperty->setAccessible(true);
        $cachedResultsProperty->setValue(null, []);
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
            "Action '{$hook}' was not registered",
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
            "Filter '{$hook}' was not registered",
        );
    }

    /**
     * Check if an action was registered by a specific class (and, optionally,
     * a specific method). Unlike assertActionAdded(), this fails if the hook
     * name matches but the registering code was swapped for something else.
     */
    protected function assertActionAddedWith(string $hook, string $class, ?string $method = null): void
    {
        $this->assertArrayHasKey(
            $hook,
            $GLOBALS['wp_mock_hooks']['actions'],
            "Action '{$hook}' was not registered",
        );

        $this->assertTrue(
            $this->hookCallbackMatches($GLOBALS['wp_mock_hooks']['actions'][$hook], $class, $method),
            "Action '{$hook}' was registered, but not by {$class}" . ( $method !== null ? "::{$method}" : '' ),
        );
    }

    /**
     * Check if a filter was registered by a specific class (and, optionally,
     * a specific method). Unlike assertFilterAdded(), this fails if the hook
     * name matches but the registering code was swapped for something else.
     */
    protected function assertFilterAddedWith(string $hook, string $class, ?string $method = null): void
    {
        $this->assertArrayHasKey(
            $hook,
            $GLOBALS['wp_mock_hooks']['filters'],
            "Filter '{$hook}' was not registered",
        );

        $this->assertTrue(
            $this->hookCallbackMatches($GLOBALS['wp_mock_hooks']['filters'][$hook], $class, $method),
            "Filter '{$hook}' was registered, but not by {$class}" . ( $method !== null ? "::{$method}" : '' ),
        );
    }

    /**
     * Check whether any recorded registration for a hook was made by the
     * given class/method. Handles the three shapes add_action()/add_filter()
     * accept: `[$target, 'method']`, `'Class::method'`, and a closure
     * (matched via its defining scope, since an anonymous function has no
     * method name of its own).
     *
     * @param array<int, array{callback: mixed, priority: int, args: int}> $registrations
     */
    private function hookCallbackMatches(array $registrations, string $class, ?string $method): bool
    {
        foreach ($registrations as $registration) {
            $callback = $registration['callback'];

            if (is_array($callback) && count($callback) === 2) {
                [$target, $targetMethod] = $callback;
                $targetClass = is_object($target) ? get_class($target) : (string) $target;

                if ($targetClass === $class && ( $method === null || $targetMethod === $method )) {
                    return true;
                }

                continue;
            }

            if (is_string($callback) && str_contains($callback, '::')) {
                [$targetClass, $targetMethod] = explode('::', $callback, 2);

                if ($targetClass === $class && ( $method === null || $targetMethod === $method )) {
                    return true;
                }

                continue;
            }

            if ($callback instanceof \Closure) {
                $scope = ( new \ReflectionFunction($callback) )->getClosureScopeClass();

                if ($scope !== null && $scope->getName() === $class) {
                    return true;
                }
            }
        }

        return false;
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
            "Script '{$handle}' was not enqueued",
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
            "Style '{$handle}' was not enqueued",
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
        $tempDir = sys_get_temp_dir() . '/wp-starter-test-' . bin2hex(random_bytes(4));
        mkdir($tempDir, 0o700, true);
        file_put_contents($tempDir . '/.env', $content);
        $this->setTemplateDirectory($tempDir);

        return $tempDir;
    }

    /**
     * Create a temporary config file for testing.
     *
     * @param string $tempDir Directory to write the config file under (e.g. one
     *                        returned by createTempEnvFile()). Never defaults to
     *                        the committed fixtures directory.
     */
    protected function createTempConfigFile(string $content, string $tempDir): string
    {
        $configDir = $tempDir . '/config';
        if (!is_dir($configDir)) {
            mkdir($configDir, 0o700, true);
        }
        file_put_contents($configDir . '/app.php', $content);

        return $configDir . '/app.php';
    }

    /**
     * Clean up temporary test files.
     *
     * Refuses to touch anything outside the temp directories this class
     * itself creates (see createTempEnvFile()), and never follows a
     * directory symlink into unrelated parts of the filesystem.
     *
     * @throws \RuntimeException If $dir is outside the test temp prefix.
     */
    protected function cleanupTempDir(string $dir): void
    {
        $allowedPrefix = realpath(sys_get_temp_dir()) . '/wp-starter-test-';
        $realDir = realpath($dir);

        if ($realDir === false || !str_starts_with($realDir, $allowedPrefix)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new \RuntimeException("Refusing to clean up directory outside the test temp prefix: {$dir}");
        }

        if (is_dir($realDir)) {
            $files = array_diff(scandir($realDir), ['.', '..']);
            foreach ($files as $file) {
                $path = $realDir . '/' . $file;
                if (is_link($path)) {
                    unlink($path);
                    continue;
                }
                is_dir($path) ? $this->cleanupTempDir($path) : unlink($path);
            }
            rmdir($realDir);
        }
    }
}
