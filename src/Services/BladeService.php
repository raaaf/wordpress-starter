<?php

declare(strict_types=1);

namespace WordpressStarter\Services;

use Illuminate\View\Factory;

/**
 * Service wrapper for Blade templating engine.
 *
 * Provides a clean interface for working with Blade templates
 * without relying on global variables.
 */
class BladeService
{
    private Factory $viewFactory;

    public function __construct(Factory $viewFactory)
    {
        $this->viewFactory = $viewFactory;
    }

    /**
     * Get the underlying view factory.
     */
    public function getViewFactory(): Factory
    {
        return $this->viewFactory;
    }

    /**
     * Render a view and return the HTML.
     *
     * @param string $view The view name (e.g., 'partials.header')
     * @param array<string, mixed> $data Data to pass to the view
     * @return string Rendered HTML
     */
    public function render(string $view, array $data = []): string
    {
        return $this->viewFactory->make($view, $data)->render();
    }

    /**
     * Check if a view exists.
     *
     * @param string $view The view name
     */
    public function exists(string $view): bool
    {
        return $this->viewFactory->exists($view);
    }

    /**
     * Create a view instance without rendering.
     *
     * @param string $view The view name
     * @param array<string, mixed> $data Data to pass to the view
     * @return \Illuminate\Contracts\View\View
     */
    public function make(string $view, array $data = []): \Illuminate\Contracts\View\View
    {
        return $this->viewFactory->make($view, $data);
    }

    /**
     * Share data with all views.
     *
     * @param string|array<string, mixed> $key
     * @param mixed $value
     */
    public function share(string|array $key, mixed $value = null): void
    {
        $this->viewFactory->share($key, $value);
    }

    /**
     * Add a location to the view finder.
     *
     * @param string $path The path to add
     */
    public function addLocation(string $path): void
    {
        $this->viewFactory->addLocation($path);
    }
}
