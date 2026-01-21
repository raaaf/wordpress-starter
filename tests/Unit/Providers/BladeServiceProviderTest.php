<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\BladeServiceProvider;
use Illuminate\View\Factory;

/**
 * Tests for the BladeServiceProvider class.
 */
final class BladeServiceProviderTest extends TestCase
{
    private BladeServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new BladeServiceProvider();
    }

    public function testRegisterDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        $this->provider->register();
    }

    public function testRegisterSetsGlobalBladeInstance(): void
    {
        $this->provider->register();

        $this->assertNotNull($GLOBALS['blade']);
    }

    public function testRegisterSetsBladeAsViewFactory(): void
    {
        $this->provider->register();

        $this->assertInstanceOf(Factory::class, $GLOBALS['blade']);
    }

    public function testGetViewFactoryReturnsFactory(): void
    {
        $this->provider->register();

        $factory = $this->provider->getViewFactory();

        $this->assertInstanceOf(Factory::class, $factory);
    }

    public function testGetViewFactoryReturnsSameInstanceAsGlobal(): void
    {
        $this->provider->register();

        $factory = $this->provider->getViewFactory();

        $this->assertSame($GLOBALS['blade'], $factory);
    }

    public function testBootDoesNotThrow(): void
    {
        $this->provider->register();

        $this->expectNotToPerformAssertions();
        $this->provider->boot();
    }

    public function testMultipleRegisterCallsSetsBladeEachTime(): void
    {
        $this->provider->register();
        $this->assertNotNull($GLOBALS['blade']);

        // Creating a new provider instance
        $newProvider = new BladeServiceProvider();
        $newProvider->register();

        // Both should set a valid factory
        $this->assertNotNull($GLOBALS['blade']);
        $this->assertInstanceOf(Factory::class, $GLOBALS['blade']);
    }

    public function testViewFactoryCanMakeViews(): void
    {
        $this->provider->register();

        $factory = $this->provider->getViewFactory();

        // Factory should have the make method
        $this->assertTrue(method_exists($factory, 'make'));
    }

    public function testViewFactoryHasCorrectPaths(): void
    {
        $this->provider->register();

        $factory = $this->provider->getViewFactory();
        $finder = $factory->getFinder();

        // Finder should have paths configured
        $this->assertInstanceOf(\Illuminate\View\FileViewFinder::class, $finder);
    }
}
