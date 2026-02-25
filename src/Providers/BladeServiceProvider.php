<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use Illuminate\Container\Container;
use Illuminate\View\Factory;
use Illuminate\Events\Dispatcher;
use Illuminate\View\FileViewFinder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Facade;

class BladeServiceProvider extends ServiceProvider
{
    private Container $container;
    private Factory $viewFactory;

    public function register(): void
    {
        $this->setupContainer();
        $this->registerBladeEngine();
        $this->setGlobalBladeInstance();
    }

    public function boot(): void
    {
        $this->registerBladeComponents();
    }

    private function setupContainer(): void
    {
        $this->container = new Container();
        // Set as global instance so ComponentTagCompiler can find it
        Container::setInstance($this->container);
        /** @phpstan-ignore argument.type */
        Facade::setFacadeApplication($this->container);
    }

    private function registerBladeEngine(): void
    {
        $filesystem = new Filesystem();
        $compiler = new BladeCompiler($filesystem, $this->getCompiledPath());

        $viewResolver = new EngineResolver();
        $viewResolver->register('blade', fn() => new CompilerEngine($compiler));
        $viewResolver->register('php', fn() => new PhpEngine($filesystem));

        $this->container->singleton('blade.compiler', fn() => $compiler);

        $viewFinder = new FileViewFinder($filesystem, $this->getViewPaths());
        $this->viewFactory = new Factory(
            $viewResolver,
            $viewFinder,
            new Dispatcher()
        );

        // Set container on view factory for component resolution
        $this->viewFactory->setContainer($this->container);

        // Register view factory under multiple aliases for Laravel compatibility
        $this->container->singleton('view', fn() => $this->viewFactory);
        $this->container->singleton(\Illuminate\Contracts\View\Factory::class, fn() => $this->viewFactory);
        $this->container->singleton(\Illuminate\View\Factory::class, fn() => $this->viewFactory);
        $this->container->singleton('view.finder', fn() => $viewFinder);
    }

    private function setGlobalBladeInstance(): void
    {
        $GLOBALS['blade'] = $this->viewFactory;
    }

    private function registerBladeComponents(): void
    {
        if (!class_exists('Illuminate\View\Factory')) {
            return;
        }

        // Core partials
        Blade::component('partials.the_loop', 'loop');

        // UI Components - use as <x-button>, <x-section>, etc.
        $components = [
            // Layout components
            'components.button' => 'button',
            'components.section' => 'section',
            'components.grid' => 'grid',
            'components.prose' => 'prose',
            'components.card' => 'card',
            // Form components
            'components.input' => 'input',
            'components.select' => 'select',
            'components.textarea' => 'textarea',
            'components.checkbox' => 'checkbox',
            'components.radio' => 'radio',
            'components.toggle' => 'toggle',
            // UI components
            'components.link' => 'link',
            'components.badge' => 'badge',
            'components.icon' => 'icon',
            'components.section-header' => 'section-header',
        ];

        foreach ($components as $view => $alias) {
            Blade::component($view, $alias);
        }
    }

    /**
     * @return array<int, string>
     */
    private function getViewPaths(): array
    {
        return [
            get_template_directory() . '/templates/',
            get_template_directory() . '/blocks/',
        ];
    }

    private function getCompiledPath(): string
    {
        return get_template_directory() . '/compiled/';
    }

    public function getViewFactory(): Factory
    {
        return $this->viewFactory;
    }
}
