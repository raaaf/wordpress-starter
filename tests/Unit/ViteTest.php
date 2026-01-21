<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;
use WordpressStarter\Vite;
use WordpressStarter\Config;

/**
 * Tests for Vite class.
 */
final class ViteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetViteState();
        $this->setTemplateDirectory(__DIR__ . '/../fixtures');
    }

    protected function tearDown(): void
    {
        $this->resetViteState();
        parent::tearDown();
    }

    private function resetViteState(): void
    {
        $reflection = new \ReflectionClass(Vite::class);

        $manifestProperty = $reflection->getProperty('manifest');
        $manifestProperty->setAccessible(true);
        $manifestProperty->setValue(null, null);

        $isDevProperty = $reflection->getProperty('isDev');
        $isDevProperty->setAccessible(true);
        $isDevProperty->setValue(null, false);
    }

    private function setViteDevMode(bool $isDev): void
    {
        $reflection = new \ReflectionClass(Vite::class);
        $isDevProperty = $reflection->getProperty('isDev');
        $isDevProperty->setAccessible(true);
        $isDevProperty->setValue(null, $isDev);
    }

    public function testIsDevServerRunningReturnsFalseWhenServerDown(): void
    {
        // By default, no server should be running on a test port
        Config::set('vite', [
            'dev_server' => [
                'host' => 'localhost',
                'port' => 59999, // Unlikely to have a server here
            ],
        ]);

        $result = Vite::isDevServerRunning();

        $this->assertFalse($result);
    }

    public function testGetAssetUrlReturnsDevServerUrlInDevMode(): void
    {
        $this->setViteDevMode(true);
        Config::set('vite', [
            'dev_server' => [
                'host' => 'localhost',
                'port' => 5173,
            ],
        ]);

        $result = Vite::getAssetUrl('resources/js/app.ts');

        $this->assertSame('http://localhost:5173/resources/js/app.ts', $result);
    }

    public function testGetAssetUrlReturnsManifestUrlInProduction(): void
    {
        $this->setViteDevMode(false);

        $result = Vite::getAssetUrl('resources/js/app.ts');

        $this->assertStringContainsString('dist/assets/app-def456.js', $result);
    }

    public function testGetAssetUrlFallsBackToRawPathWhenNotInManifest(): void
    {
        $this->setViteDevMode(false);

        $result = Vite::getAssetUrl('resources/unknown/file.js');

        $this->assertStringContainsString('resources/unknown/file.js', $result);
        $this->assertStringNotContainsString('dist/', $result);
    }

    public function testGetAssetUrlStripsLeadingSlash(): void
    {
        $this->setViteDevMode(true);
        Config::set('vite', [
            'dev_server' => [
                'host' => 'localhost',
                'port' => 5173,
            ],
        ]);

        $result = Vite::getAssetUrl('/resources/js/app.ts');

        $this->assertSame('http://localhost:5173/resources/js/app.ts', $result);
    }

    public function testEnqueueAssetsLoadsFromManifestInProduction(): void
    {
        $this->setViteDevMode(false);

        Vite::enqueueAssets();

        $scripts = $this->getEnqueuedScripts();
        $styles = $this->getEnqueuedStyles();

        $this->assertArrayHasKey('app-js', $scripts);
        $this->assertArrayHasKey('app-css', $styles);
        $this->assertStringContainsString('app-def456.js', $scripts['app-js']['src']);
        $this->assertStringContainsString('app-abc123.css', $styles['app-css']['src']);
    }

    public function testEnqueueAssetsLoadsViteClientInDevMode(): void
    {
        $this->setViteDevMode(true);
        Config::set('vite', [
            'dev_server' => [
                'host' => 'localhost',
                'port' => 5173,
            ],
        ]);

        Vite::enqueueAssets();

        $scripts = $this->getEnqueuedScripts();

        $this->assertArrayHasKey('vite-client', $scripts);
        $this->assertStringContainsString('localhost:5173/@vite/client', $scripts['vite-client']['src']);
    }

    public function testEnqueueAssetsLoadsAppJsFromDevServer(): void
    {
        $this->setViteDevMode(true);
        Config::set('vite', [
            'dev_server' => [
                'host' => 'localhost',
                'port' => 5173,
            ],
        ]);

        Vite::enqueueAssets();

        $scripts = $this->getEnqueuedScripts();

        $this->assertArrayHasKey('app-js', $scripts);
        $this->assertStringContainsString('localhost:5173/resources/js/app.ts', $scripts['app-js']['src']);
    }

    public function testEnqueueEditorAssetsLoadsEditorStyle(): void
    {
        $this->setViteDevMode(false);

        Vite::enqueueEditorAssets();

        $styles = $this->getEnqueuedStyles();

        $this->assertArrayHasKey('editor-style', $styles);
        $this->assertStringContainsString('editor-style-ghi789.css', $styles['editor-style']['src']);
    }

    public function testEnqueueEditorAssetsLoadsFromDevServerInDevMode(): void
    {
        $this->setViteDevMode(true);
        Config::set('vite', [
            'dev_server' => [
                'host' => 'localhost',
                'port' => 5173,
            ],
        ]);

        Vite::enqueueEditorAssets();

        $styles = $this->getEnqueuedStyles();

        // In dev mode, editor-style is loaded from dev server
        $this->assertArrayHasKey('editor-style', $styles);
        $this->assertStringContainsString('localhost:5173', $styles['editor-style']['src']);
    }

    public function testInitRegistersEnqueueHooks(): void
    {
        Vite::init();

        $this->assertActionAdded('wp_enqueue_scripts');
        $this->assertActionAdded('enqueue_block_editor_assets');
    }
}
