<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Tests\Support\TestCase;
use WordpressStarter\Providers\BladeServiceProvider;

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

    public function testMultipleRegisterCallsSetsBladeEachTime(): void
    {
        $this->provider->register();
        $firstFactory = $GLOBALS['blade'];
        $this->assertNotNull($firstFactory);

        // A second register() call must replace the global with a NEW factory,
        // not leave the first one in place (a no-op register() would pass the
        // old assertions above unnoticed).
        $newProvider = new BladeServiceProvider();
        $newProvider->register();

        $this->assertNotSame($firstFactory, $GLOBALS['blade']);
        $this->assertSame($newProvider->getViewFactory(), $GLOBALS['blade']);
        $this->assertInstanceOf(Factory::class, $GLOBALS['blade']);
    }

    public function testViewFactoryCanMakeViews(): void
    {
        $this->provider->register();
        $this->provider->boot();

        $factory = $this->provider->getViewFactory();
        $factory->getFinder()->addLocation($this->templatesDir());

        $html = $factory->make('partials.empty-state', [
            'title' => 'Test Title',
            'text' => 'Test body text',
        ])->render();

        $this->assertNotSame('', trim($html));
        $this->assertStringContainsString('Test Title', $html);
    }

    public function testViewFactoryHasCorrectPaths(): void
    {
        $this->provider->register();

        $factory = $this->provider->getViewFactory();
        $finder = $factory->getFinder();

        $this->assertInstanceOf(FileViewFinder::class, $finder);
        $this->assertSame(
            [
                get_template_directory() . '/templates/',
                get_template_directory() . '/blocks/',
            ],
            $finder->getPaths(),
        );
    }

    public function testViewFactoryEscapesBenignExpression(): void
    {
        $this->provider->register();
        $this->provider->boot();

        $factory = $this->provider->getViewFactory();
        $dir = sys_get_temp_dir() . '/blade-escape-' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/escape-probe.blade.php', '{{ $html }}');
        $factory->getFinder()->addLocation($dir);

        try {
            $html = $factory->make('escape-probe', ['html' => '<b>x</b>'])->render();
        } finally {
            unlink($dir . '/escape-probe.blade.php');
            rmdir($dir);
        }

        $this->assertStringNotContainsString('<b>x</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', $html);
    }

    public function testDirectiveRegisteredAfterSecondRegisterCompilesOnTheServedCompiler(): void
    {
        // First boot (simulates an earlier test/request in the same process).
        ( new BladeServiceProvider() )->register();

        // Second boot must not leave the Blade facade pointing at the first
        // boot's compiler, or a directive registered afterwards would land
        // on a compiler nobody renders through.
        ( new BladeServiceProvider() )->register();

        Blade::directive('kses', fn ($expression) => "<?php echo wp_kses_post({$expression}); ?>");

        // Resolve through the container instance BladeServiceProvider made
        // global (not the `app()` helper, which resolves against
        // Application's own container instead).
        $servedCompiler = \Illuminate\Container\Container::getInstance()->make('blade.compiler');
        $this->assertStringContainsString(
            'wp_kses_post',
            $servedCompiler->compileString('@kses($text)'),
        );
    }

    private function templatesDir(): string
    {
        return dirname(__DIR__, 3) . '/templates';
    }
}
