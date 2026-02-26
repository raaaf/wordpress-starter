<?php

declare(strict_types=1);

namespace WordpressStarter;

use Illuminate\Container\Container;

/**
 * Minimal application stub that satisfies Blade's component resolution requirements.
 * Blade's ComponentTagCompiler calls getNamespace() to resolve anonymous components
 * and class-based components — this stub provides the minimum needed interface.
 */
class BladeApplication extends Container
{
    private string $namespace = 'WordpressStarter\\';

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function basePath(string $path = ''): string
    {
        $base = get_template_directory();
        return $path ? $base . DIRECTORY_SEPARATOR . $path : $base;
    }

    public function environment(string ...$environments): string|bool
    {
        $env = defined('WP_ENV') ? WP_ENV : 'production';
        if (count($environments) > 0) {
            return in_array($env, $environments, true);
        }
        return $env;
    }

    public function runningInConsole(): bool
    {
        return false;
    }

    public function isProduction(): bool
    {
        return true;
    }
}
