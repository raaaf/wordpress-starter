<?php

declare(strict_types=1);

namespace WordpressStarter;

use WordpressStarter\Providers\ServiceProvider;
use WordpressStarter\Providers\BladeServiceProvider;
use WordpressStarter\Providers\MenuServiceProvider;
use WordpressStarter\Providers\ThemeServiceProvider;
use WordpressStarter\Providers\SecurityServiceProvider;
use WordpressStarter\Providers\AcfServiceProvider;

class Application
{
    private array $providers = [];
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
            SecurityServiceProvider::class,
            BladeServiceProvider::class,
            AcfServiceProvider::class,
            MenuServiceProvider::class,
            ThemeServiceProvider::class,
        ];
    }

    public function boot(): void
    {
        foreach ($this->providers as $providerClass) {
            /** @var ServiceProvider $provider */
            $provider = new $providerClass();
            $provider->register();
        }

        foreach ($this->providers as $providerClass) {
            /** @var ServiceProvider $provider */
            $provider = new $providerClass();
            $provider->boot();
        }
    }

    public function getProvider(string $providerClass): ?ServiceProvider
    {
        foreach ($this->providers as $registeredClass) {
            if ($registeredClass === $providerClass) {
                return new $providerClass();
            }
        }
        return null;
    }
}