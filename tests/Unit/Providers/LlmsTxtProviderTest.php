<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

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

    public function testRegisterAddsNoHooks(): void
    {
        $this->provider->register();

        $this->assertSame([], $GLOBALS['wp_mock_hooks']['actions']);
        $this->assertSame([], $GLOBALS['wp_mock_hooks']['filters']);
    }

    public function testKeyPageLinksIncludesPublishedPublicFrontPage(): void
    {
        $GLOBALS['wp_mock_options']['page_on_front'] = 42;
        $GLOBALS['wp_mock_post_fields'][42] = ['post_status' => 'publish', 'post_password' => ''];
        $GLOBALS['wp_mock_titles'][42] = 'Home';
        $GLOBALS['wp_mock_permalinks'][42] = 'https://example.com/';

        $links = $this->invokeRenderKeyPageLinks();

        $this->assertSame(['- [Home](https://example.com/)'], $links);
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

    public function testKeyPageLinksExcludePasswordProtectedPostsPage(): void
    {
        $GLOBALS['wp_mock_options']['page_for_posts'] = 55;
        $GLOBALS['wp_mock_post_fields'][55] = ['post_status' => 'publish', 'post_password' => 'secret'];
        $GLOBALS['wp_mock_titles'][55] = 'Protected Blog Page';
        $GLOBALS['wp_mock_permalinks'][55] = 'https://example.com/blog/';

        $links = $this->invokeRenderKeyPageLinks();

        $this->assertSame([], $links);
    }

    public function testKeyPageLinksExcludeUnpublishedPostsPage(): void
    {
        $GLOBALS['wp_mock_options']['page_for_posts'] = 55;
        $GLOBALS['wp_mock_post_fields'][55] = ['post_status' => 'draft', 'post_password' => ''];
        $GLOBALS['wp_mock_titles'][55] = 'Draft Blog Page';
        $GLOBALS['wp_mock_permalinks'][55] = 'https://example.com/blog/';

        $links = $this->invokeRenderKeyPageLinks();

        $this->assertSame([], $links);
    }

    public function testRenderPostLinksExcludesPasswordProtectedPost(): void
    {
        $GLOBALS['wp_mock_posts']['post'] = [(object) ['ID' => 77]];
        $GLOBALS['wp_mock_post_fields'][77] = ['post_status' => 'publish', 'post_password' => 'secret'];
        $GLOBALS['wp_mock_titles'][77] = 'Protected Post';
        $GLOBALS['wp_mock_permalinks'][77] = 'https://example.com/protected/';

        $lines = $this->invokeMethod($this->provider, 'renderPostLinks', ['post']);

        $this->assertSame([], $lines);
    }

    /** get_posts() only queries by post_status/has_password; linkLineForPost() enforces the guard itself, so a non-public post reaching renderPostLinks() must still be dropped. */
    public function testRenderPostLinksExcludesNonPublicPost(): void
    {
        $GLOBALS['wp_mock_posts']['post'] = [(object) ['ID' => 88]];
        $GLOBALS['wp_mock_post_fields'][88] = ['post_status' => 'draft', 'post_password' => ''];
        $GLOBALS['wp_mock_titles'][88] = 'Draft Post';
        $GLOBALS['wp_mock_permalinks'][88] = 'https://example.com/draft/';

        $lines = $this->invokeMethod($this->provider, 'renderPostLinks', ['post']);

        $this->assertSame([], $lines);
    }

    public function testRenderPostLinksIncludesPublishedPublicPost(): void
    {
        $GLOBALS['wp_mock_posts']['post'] = [(object) ['ID' => 99]];
        $GLOBALS['wp_mock_post_fields'][99] = [
            'post_status' => 'publish',
            'post_password' => '',
            'post_excerpt' => 'A short summary.',
        ];
        $GLOBALS['wp_mock_titles'][99] = 'Public Post';
        $GLOBALS['wp_mock_permalinks'][99] = 'https://example.com/public-post/';

        $lines = $this->invokeMethod($this->provider, 'renderPostLinks', ['post']);

        $this->assertSame(
            ['- [Public Post](https://example.com/public-post/): A short summary.'],
            $lines,
        );
    }

    /**
     * @return string[]
     */
    private function invokeRenderKeyPageLinks(): array
    {
        return $this->invokeMethod($this->provider, 'renderKeyPageLinks');
    }
}
