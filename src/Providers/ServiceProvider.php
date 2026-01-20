<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

abstract class ServiceProvider
{
    /**
     * Register services.
     */
    abstract public function register(): void;

    /**
     * Bootstrap services.
     */
    abstract public function boot(): void;
}
