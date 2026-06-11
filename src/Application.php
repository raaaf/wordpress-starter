<?php

declare(strict_types=1);

namespace WordpressStarter;

use Closure;
use Illuminate\Container\Container;
use WordpressStarter\Providers\AcfServiceProvider;
use WordpressStarter\Providers\AssetOptimizationServiceProvider;
use WordpressStarter\Providers\BladeServiceProvider;
use WordpressStarter\Providers\BrandingServiceProvider;
use WordpressStarter\Providers\CronServiceProvider;
use WordpressStarter\Providers\DesignTokenServiceProvider;
use WordpressStarter\Providers\EditorIntegrationServiceProvider;
use WordpressStarter\Providers\EditorStylesServiceProvider;
use WordpressStarter\Providers\IconShortcodeServiceProvider;
use WordpressStarter\Providers\ImageServiceProvider;
use WordpressStarter\Providers\LlmsTxtProvider;
use WordpressStarter\Providers\LogServiceProvider;
use WordpressStarter\Providers\MediaServiceProvider;
use WordpressStarter\Providers\MemberAreaServiceProvider;
use WordpressStarter\Providers\MenuServiceProvider;
use WordpressStarter\Providers\PluginConfiguratorServiceProvider;
use WordpressStarter\Providers\PluginServiceProvider;
use WordpressStarter\Providers\PostTypeServiceProvider;
use WordpressStarter\Providers\SecurityServiceProvider;
use WordpressStarter\Providers\SeoServiceProvider;
use WordpressStarter\Providers\ServiceProvider;
use WordpressStarter\Providers\ThemeServiceProvider;
use WordpressStarter\Providers\ThemeUpdateProvider;
use WordpressStarter\Providers\WelcomeServiceProvider;

class Application
{
    /** @var array<class-string<ServiceProvider>> */
    private array $providers = [];

    /** @var array<class-string<ServiceProvider>, ServiceProvider> */
    private array $providerInstances = [];

    private static ?self $instance = null;

    private Container $container;

    private function __construct()
    {
        // Initialize the container
        $this->container = new Container();
        Container::setInstance($this->container);

        // Register self in container
        $this->container->instance(self::class, $this);

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
            PluginConfiguratorServiceProvider::class, // not admin-gated: registers frontend CF7 filters (wpcf7_form_elements, wpcf7_spam)
            WelcomeServiceProvider::class, // not admin-gated: acf/init prefill fires on frontend and fills options the frontend renders
            SecurityServiceProvider::class,
            BladeServiceProvider::class,
            AcfServiceProvider::class,
            MenuServiceProvider::class,
            ThemeServiceProvider::class,
            MediaServiceProvider::class,
            AssetOptimizationServiceProvider::class,
            EditorIntegrationServiceProvider::class,
            BrandingServiceProvider::class,
            SeoServiceProvider::class,
            LlmsTxtProvider::class,
            ImageServiceProvider::class,
            ThemeUpdateProvider::class,
            PostTypeServiceProvider::class,
            LogServiceProvider::class,
            CronServiceProvider::class,
            EditorStylesServiceProvider::class,
            IconShortcodeServiceProvider::class,
            MemberAreaServiceProvider::class,
        ];

        // Admin-only providers: every hook they register is admin-side
        // (admin_*, after_switch_theme, wp_ajax_* — admin-ajax requests have
        // is_admin() === true, so AJAX handlers keep working). Skipping them on
        // frontend requests avoids needless hook registration and config I/O.
        if (is_admin()) {
            $this->providers[] = PluginServiceProvider::class;
            $this->providers[] = DesignTokenServiceProvider::class;
        }
    }

    public function boot(): void
    {
        ThemeContext::migrate();

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

    /**
     * Get the IoC container instance.
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Resolve a class from the container.
     *
     * @template T
     *
     * @param class-string<T> $abstract
     *
     * @return T
     */
    public function make(string $abstract): mixed
    {
        return $this->container->make($abstract);
    }

    /**
     * Register a binding in the container.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     */
    public function bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void
    {
        $this->container->bind($abstract, $concrete, $shared);
    }

    /**
     * Register a shared binding (singleton) in the container.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->container->singleton($abstract, $concrete);
    }

    /**
     * Register an existing instance in the container.
     *
     * @template T
     *
     * @param string $abstract
     * @param T $instance
     *
     * @return T
     */
    public function instance(string $abstract, mixed $instance): mixed
    {
        return $this->container->instance($abstract, $instance);
    }
}
