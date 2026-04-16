<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\SeoServiceProvider;

/**
 * Tests for the AI crawler policy, FAQ and Person schema helpers added to
 * SeoServiceProvider. The existing SEO behavior is covered by runtime testing
 * against the WordPress request cycle and is out of scope here.
 */
final class SeoServiceProviderTest extends TestCase
{
    private SeoServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new SeoServiceProvider();
    }

    public function testBootRegistersRobotsFilter(): void
    {
        $this->provider->boot();

        $this->assertFilterAdded('robots_txt');
    }

    public function testAiCrawlerPolicyAddsKnownAgents(): void
    {
        $this->provider->boot();

        $output = apply_filters('robots_txt', "User-agent: *\nAllow: /\n", true);

        $this->assertIsString($output);
        $this->assertStringContainsString('GPTBot', $output);
        $this->assertStringContainsString('ClaudeBot', $output);
        $this->assertStringContainsString('PerplexityBot', $output);
        $this->assertStringContainsString('Google-Extended', $output);
        $this->assertStringContainsString('CCBot', $output);
        $this->assertStringContainsString('# AI crawlers (managed by theme)', $output);
    }

    public function testAiCrawlerPolicySkipsWhenSiteIsNotPublic(): void
    {
        $this->provider->boot();

        $output = apply_filters('robots_txt', "User-agent: *\nDisallow: /\n", false);

        $this->assertStringNotContainsString('GPTBot', $output);
    }

    public function testAiCrawlerListIsFilterable(): void
    {
        add_filter('wp_starter_ai_crawlers', function (array $crawlers): array {
            return ['CustomBot'];
        });

        $this->provider->boot();

        $output = apply_filters('robots_txt', "User-agent: *\n", true);

        $this->assertStringContainsString('User-agent: CustomBot', $output);
        $this->assertStringNotContainsString('GPTBot', $output);
    }

    public function testEmitFaqSchemaRendersJsonLd(): void
    {
        ob_start();
        SeoServiceProvider::emitFaqSchema([
            ['question' => 'What is GEO?', 'answer' => 'Generative Engine Optimization.'],
            ['question' => 'Is Claude supported?', 'answer' => 'Yes, ClaudeBot is allowed.'],
        ]);
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('application/ld+json', $out);
        $this->assertStringContainsString('"@type":"FAQPage"', $out);
        $this->assertStringContainsString('"Question"', $out);
        $this->assertStringContainsString('What is GEO?', $out);
    }

    public function testEmitFaqSchemaSkipsEmptyEntries(): void
    {
        ob_start();
        SeoServiceProvider::emitFaqSchema([
            ['question' => '', 'answer' => 'orphan answer'],
            ['question' => 'Orphan question', 'answer' => ''],
        ]);
        $out = (string) ob_get_clean();

        $this->assertSame('', $out);
    }

    public function testEmitPersonSchemaIncludesOptionalFields(): void
    {
        ob_start();
        SeoServiceProvider::emitPersonSchema([
            'name' => 'Rafael Alex',
            'jobTitle' => 'Designer & Developer',
            'url' => 'https://rafaelalex.de',
            'sameAs' => ['https://linkedin.com/in/rafaelalex'],
            'worksFor' => 'Rafael Alex Studio',
        ]);
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('"@type":"Person"', $out);
        $this->assertStringContainsString('Rafael Alex', $out);
        $this->assertStringContainsString('Designer & Developer', $out);
        $this->assertStringContainsString('linkedin.com', $out);
        $this->assertStringContainsString('"worksFor"', $out);
    }

    public function testEmitPersonSchemaSkipsWithoutName(): void
    {
        ob_start();
        SeoServiceProvider::emitPersonSchema(['name' => '']);
        $out = (string) ob_get_clean();

        $this->assertSame('', $out);
    }
}
