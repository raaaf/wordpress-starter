<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use ReflectionMethod;
use Tests\Support\TestCase;
use WordpressStarter\Providers\LlmsTxtProvider;

/**
 * Tests for the LlmsTxtProvider class.
 */
final class LlmsTxtProviderTest extends TestCase
{
    private LlmsTxtProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new LlmsTxtProvider();
    }

    public function testBootRegistersTemplateRedirectAction(): void
    {
        $this->provider->boot();

        $this->assertActionAdded('template_redirect');
    }

    public function testBootRegistersCacheFlushHooks(): void
    {
        $this->provider->boot();

        $this->assertActionAdded('save_post');
        $this->assertActionAdded('switch_theme');
        $this->assertActionAdded('acf/save_post');
    }

    public function testFlushCacheDeletesTransients(): void
    {
        set_transient('wp_starter_llms_txt_index', 'cached-index', 3600);
        set_transient('wp_starter_llms_txt_full', 'cached-full', 3600);

        $this->provider->flushCache();

        $this->assertFalse(get_transient('wp_starter_llms_txt_index'));
        $this->assertFalse(get_transient('wp_starter_llms_txt_full'));
    }

    public function testRegisterIsNoop(): void
    {
        $this->expectNotToPerformAssertions();
        $this->provider->register();
    }

    public function testKeyPageLinksExcludePasswordProtectedFrontPage(): void
    {
        $GLOBALS['wp_mock_options']['page_on_front'] = 42;
        $GLOBALS['wp_mock_post_fields'][42] = ['post_status' => 'publish', 'post_password' => 'secret'];
        $GLOBALS['wp_mock_titles'][42] = 'Protected Front Page';
        $GLOBALS['wp_mock_permalinks'][42] = 'https://example.com/';

        $links = $this->invokeRenderKeyPageLinks();

        $this->assertSame([], $links);
    }

    public function testKeyPageLinksExcludeUnpublishedFrontPage(): void
    {
        $GLOBALS['wp_mock_options']['page_on_front'] = 42;
        $GLOBALS['wp_mock_post_fields'][42] = ['post_status' => 'draft', 'post_password' => ''];
        $GLOBALS['wp_mock_titles'][42] = 'Draft Front Page';
        $GLOBALS['wp_mock_permalinks'][42] = 'https://example.com/';

        $links = $this->invokeRenderKeyPageLinks();

        $this->assertSame([], $links);
    }

    /**
     * @return string[]
     */
    private function invokeRenderKeyPageLinks(): array
    {
        $reflection = new ReflectionMethod(LlmsTxtProvider::class, 'renderKeyPageLinks');
        $reflection->setAccessible(true);

        return $reflection->invoke($this->provider);
    }
}
