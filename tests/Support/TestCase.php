<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionClass;
use ReflectionMethod;
use WordpressStarter\Application;

/**
 * Base test case class for all tests.
 *
 * Provides common setup and teardown functionality,
 * and includes the WordPressMocks trait for easy mocking.
 */
abstract class TestCase extends BaseTestCase
{
    use WordPressMocks;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetAllMocks();
    }

    protected function tearDown(): void
    {
        $this->resetAllMocks();
        parent::tearDown();
    }

    /**
     * Invokes a private/protected static method via reflection.
     *
     * @param array<int, mixed> $args
     */
    protected function invokeStaticMethod(string $class, string $method, array $args = []): mixed
    {
        $reflection = new ReflectionMethod($class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke(null, ...$args);
    }

    /**
     * Invokes a private/protected instance method via reflection.
     *
     * @param array<int, mixed> $args
     */
    protected function invokeMethod(object $instance, string $method, array $args = []): mixed
    {
        $reflection = new ReflectionMethod($instance, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($instance, ...$args);
    }

    /**
     * Resets private/protected static properties on a class via reflection.
     *
     * @param array<string, mixed> $propertiesToValues
     */
    protected function resetStaticProperties(string $class, array $propertiesToValues): void
    {
        $reflection = new ReflectionClass($class);

        foreach ($propertiesToValues as $property => $value) {
            $reflectionProperty = $reflection->getProperty($property);
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue(null, $value);
        }
    }

    /**
     * Renders a Blade view for tests, booting the application and
     * registering the templates directory with the view finder.
     *
     * @param array<string, mixed> $data
     */
    protected function renderTemplate(string $view, array $data = [], ?string $location = null): string
    {
        $app = Application::getInstance();
        $app->boot();

        $factory = blade();
        $factory->getFinder()->addLocation($location ?? dirname(__DIR__, 2) . '/templates');

        return $factory->make($view, $data)->render();
    }
}
