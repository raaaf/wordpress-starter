<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\IconShortcodeServiceProvider;

final class IconShortcodeServiceProviderTest extends TestCase
{
    private IconShortcodeServiceProvider $provider;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new IconShortcodeServiceProvider();

        $this->tempDir = sys_get_temp_dir() . '/wp-starter-test-icon-' . bin2hex(random_bytes(4));
        mkdir($this->tempDir . '/resources/icons', 0o700, true);
        file_put_contents(
            $this->tempDir . '/resources/icons/test.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" /></svg>',
        );
        $this->setTemplateDirectory($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->cleanupTempDir($this->tempDir);
        parent::tearDown();
    }

    public function testRenderIconHappyPath(): void
    {
        $output = $this->provider->renderIcon([
            'name' => 'test',
            'size' => 'lg',
            'class' => 'text-brand',
        ]);

        $this->assertStringContainsString('<span class="inline-icon">', $output);
        $this->assertStringContainsString('<svg class="icon w-5 h-5 text-brand inline-block align-middle shrink-0" aria-hidden="true"', $output);
        $this->assertStringNotContainsString('width="24"', $output);
    }

    public function testRenderIconSanitizesClassAttributeBreakoutAttempt(): void
    {
        $output = $this->provider->renderIcon([
            'name' => 'test',
            'class' => 'a" onload="alert(1)',
        ]);

        $this->assertStringNotContainsString('onload="', $output);
        $this->assertStringNotContainsString('alert(1)', $output);
        $this->assertStringContainsString('<svg class="icon w-4 h-4 inline-block align-middle shrink-0" aria-hidden="true"', $output);
    }

    public function testRenderIconPreservesTailwindVariantAndFractionClasses(): void
    {
        $output = $this->provider->renderIcon([
            'name' => 'test',
            'class' => 'md:w-6 w-1/2',
        ]);

        $this->assertStringContainsString('<svg class="icon w-4 h-4 md:w-6 w-1/2 inline-block align-middle shrink-0" aria-hidden="true"', $output);
    }

    public function testRenderIconPreservesImportantPrefixAndArbitraryFunctionValue(): void
    {
        $output = $this->provider->renderIcon([
            'name' => 'test',
            'class' => '!mt-0 w-[calc(100%-1rem)]',
        ]);

        $this->assertStringContainsString('<svg class="icon w-4 h-4 !mt-0 w-[calc(100%-1rem)] inline-block align-middle shrink-0" aria-hidden="true"', $output);
    }
}
