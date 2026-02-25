<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;
use WordpressStarter\Application;
use WordpressStarter\Providers\ThemeServiceProvider;
use WordpressStarter\Providers\BladeServiceProvider;
use WordpressStarter\Providers\SecurityServiceProvider;

/**
 * Tests for the Application class.
 */
final class ApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetApplicationInstance();
    }

    protected function tearDown(): void
    {
        $this->resetApplicationInstance();
        parent::tearDown();
    }

    /**
     * Reset the Application singleton instance between tests.
     */
    private function resetApplicationInstance(): void
    {
        $reflection = new \ReflectionClass(Application::class);
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = Application::getInstance();
        $instance2 = Application::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function testGetInstanceReturnsApplicationInstance(): void
    {
        $instance = Application::getInstance();

        $this->assertInstanceOf(Application::class, $instance);
    }

    public function testBootCanBeCalledWithoutErrors(): void
    {
        $app = Application::getInstance();

        // Boot should not throw any exceptions
        $this->expectNotToPerformAssertions();
        $app->boot();
    }

    public function testBootRegistersActions(): void
    {
        $app = Application::getInstance();
        $app->boot();

        // After boot, various actions should be registered
        $this->assertArrayHasKey('actions', $GLOBALS['wp_mock_hooks']);
    }

    public function testGetProviderReturnsNullForUnregisteredProvider(): void
    {
        $app = Application::getInstance();

        // A non-existent provider should return null
        $provider = $app->getProvider('NonExistentProvider');

        $this->assertNull($provider);
    }

    public function testGetProviderReturnsRegisteredProvider(): void
    {
        $app = Application::getInstance();
        $app->boot();

        $provider = $app->getProvider(ThemeServiceProvider::class);

        $this->assertInstanceOf(ThemeServiceProvider::class, $provider);
    }

    public function testGetProviderReturnsSameInstanceOnMultipleCalls(): void
    {
        $app = Application::getInstance();
        $app->boot();

        $provider1 = $app->getProvider(BladeServiceProvider::class);
        $provider2 = $app->getProvider(BladeServiceProvider::class);

        $this->assertSame($provider1, $provider2);
    }

    public function testSecurityProviderIsRegistered(): void
    {
        $app = Application::getInstance();
        $app->boot();

        $provider = $app->getProvider(SecurityServiceProvider::class);

        $this->assertInstanceOf(SecurityServiceProvider::class, $provider);
    }

    public function testAllProvidersAreAccessible(): void
    {
        $app = Application::getInstance();
        $app->boot();

        $providerClasses = [
            \WordpressStarter\Providers\PluginServiceProvider::class,
            \WordpressStarter\Providers\WelcomeServiceProvider::class,
            \WordpressStarter\Providers\SecurityServiceProvider::class,
            \WordpressStarter\Providers\BladeServiceProvider::class,
            \WordpressStarter\Providers\AcfServiceProvider::class,
            \WordpressStarter\Providers\MenuServiceProvider::class,
            \WordpressStarter\Providers\ThemeServiceProvider::class,
            \WordpressStarter\Providers\EditorStylesServiceProvider::class,
        ];

        foreach ($providerClasses as $providerClass) {
            $provider = $app->getProvider($providerClass);
            $this->assertInstanceOf($providerClass, $provider, "Provider {$providerClass} should be registered");
        }
    }
}
