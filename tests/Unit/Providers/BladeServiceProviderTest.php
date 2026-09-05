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

    public function testViewFactoryDoesNotDoubleEncodeEscUrlEntities(): void
    {
        $this->provider->register();
        $this->provider->boot();

        $factory = $this->provider->getViewFactory();
        $dir = sys_get_temp_dir() . '/blade-double-encode-' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/esc-url-probe.blade.php', '{{ esc_url($url) }}');
        $factory->getFinder()->addLocation($dir);

        try {
            $html = $factory->make('esc-url-probe', ['url' => 'https://x.test/?a=1&b=2'])->render();
        } finally {
            unlink($dir . '/esc-url-probe.blade.php');
            rmdir($dir);
        }

        $this->assertStringNotContainsString('&amp;#038;', $html);
        $this->assertStringContainsString('&#038;', $html);
    }

    public function testViewFactoryStillEscapesRawHtmlWithoutDoubleEncoding(): void
    {
        $this->provider->register();
        $this->provider->boot();

        $factory = $this->provider->getViewFactory();
        $dir = sys_get_temp_dir() . '/blade-double-encode-' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/raw-html-probe.blade.php', '{{ $html }}');
        $factory->getFinder()->addLocation($dir);

        try {
            $html = $factory->make('raw-html-probe', ['html' => '<b>'])->render();
        } finally {
            unlink($dir . '/raw-html-probe.blade.php');
            rmdir($dir);
        }

        $this->assertStringContainsString('&lt;b&gt;', $html);
    }

    public function testViewFactoryDoesNotReEncodeExistingEntities(): void
    {
        $this->provider->register();
        $this->provider->boot();

        $factory = $this->provider->getViewFactory();
        $dir = sys_get_temp_dir() . '/blade-double-encode-' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/entity-probe.blade.php', '{{ $entity }}');
        $factory->getFinder()->addLocation($dir);

        try {
            $html = $factory->make('entity-probe', ['entity' => '&amp;'])->render();
        } finally {
            unlink($dir . '/entity-probe.blade.php');
            rmdir($dir);
        }

        $this->assertSame('&amp;', $html);
    }

    public function testViewFactoryPreservesPreEscapedScriptEntitiesAsEntities(): void
    {
        $this->provider->register();
        $this->provider->boot();

        $factory = $this->provider->getViewFactory();
        $dir = sys_get_temp_dir() . '/blade-double-encode-' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/pre-escaped-script-probe.blade.php', '{{ $value }}');
        $factory->getFinder()->addLocation($dir);

        try {
            $html = $factory->make('pre-escaped-script-probe', [
                'value' => '&lt;script&gt;alert(1)&lt;/script&gt;',
            ])->render();
        } finally {
            unlink($dir . '/pre-escaped-script-probe.blade.php');
            rmdir($dir);
        }

        // Same double_encode=false contract as
        // testViewFactoryDoesNotReEncodeExistingEntities(), exercised with a
        // full script-tag shape instead of a single entity: {{ }} matches
        // WordPress esc_html() semantics, escaping raw <>"'& but never
        // decoding an entity that already arrived escaped. A value that is
        // already entity-encoded when it reaches the view (e.g. read back
        // from something that itself called esc_html()) must stay entities,
        // not turn into a live <script> tag.
        $this->assertStringNotContainsString('<script', $html);
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
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
