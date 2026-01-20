<?php

declare(strict_types=1);

namespace WordpressStarter;

use WordpressStarter\Providers\ServiceProvider;
use WordpressStarter\Providers\BladeServiceProvider;
use WordpressStarter\Providers\MenuServiceProvider;
use WordpressStarter\Providers\ThemeServiceProvider;
use WordpressStarter\Providers\SecurityServiceProvider;
use WordpressStarter\Providers\AcfServiceProvider;
use WordpressStarter\Providers\AnalyticsServiceProvider;
use WordpressStarter\Providers\PluginServiceProvider;

class Application
{
    /** @var array<class-string<ServiceProvider>> */
    private array $providers = [];

    /** @var array<class-string<ServiceProvider>, ServiceProvider> */
    private array $providerInstances = [];

    private static ?self $instance = null;

    private function __construct()
    {
        // Load configuration first
        Config::load();
        $this->registerProviders();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function registerProviders(): void
    {
        $this->providers = [
            PluginServiceProvider::class,
            SecurityServiceProvider::class,
            BladeServiceProvider::class,
            AcfServiceProvider::class,
            MenuServiceProvider::class,
            ThemeServiceProvider::class,
            AnalyticsServiceProvider::class,
        ];
    }

    public function boot(): void
    {
        // Phase 1: Instantiate and register all providers
        foreach ($this->providers as $providerClass) {
            $provider = $this->resolveProvider($providerClass);
            $provider->register();
        }

        // Phase 2: Boot all providers (after all are registered)
        foreach ($this->providers as $providerClass) {
            $provider = $this->resolveProvider($providerClass);
            $provider->boot();
        }
    }

    /**
     * Get or create a provider instance (singleton per provider class)
     *
     * @param class-string<ServiceProvider> $providerClass
     */
    private function resolveProvider(string $providerClass): ServiceProvider
    {
        if (!isset($this->providerInstances[$providerClass])) {
            $this->providerInstances[$providerClass] = new $providerClass();
        }
        return $this->providerInstances[$providerClass];
    }

    /**
     * Get a registered provider instance
     *
     * @param class-string<ServiceProvider> $providerClass
     */
    public function getProvider(string $providerClass): ?ServiceProvider
    {
        if (in_array($providerClass, $this->providers, true)) {
            return $this->resolveProvider($providerClass);
        }
        return null;
    }
}