<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\SeoServiceProvider;
use WordpressStarter\Services\StyleguidePage;

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

    public function testStyleguidePageGetsNoindexNofollow(): void
    {
        $GLOBALS['wp_mock_current_blog_id'] = 90210;
        $GLOBALS['wp_mock_options'][StyleguidePage::optionKey()] = 42;
        $GLOBALS['wp_mock_posts_by_id'][42] = ['post_type' => 'page', 'post_status' => 'private'];
        $GLOBALS['wp_mock_post_meta'][42][StyleguidePage::markerKey()] = '1';
        $GLOBALS['wp_mock_post_meta'][42]['_wp_page_template'] = StyleguidePage::TEMPLATE;
        $GLOBALS['wp_mock_queried_object_id'] = 42;
        $GLOBALS['wp_mock_is_singular'] = true;

        $this->provider->boot();

        $robots = apply_filters('wp_robots', ['index' => true]);

        $this->assertTrue($robots['noindex']);
        $this->assertTrue($robots['nofollow']);
    }

    /**
     * The '_wp_page_template' postmeta check is the sole decision: a page
     * whose template is not the styleguide template must resolve to
     * indexable via that single postmeta read, without ever consulting
     * StyleguidePage::find()'s option/marker/legacy resolution.
     */
    public function testRegularPageNeverCallsStyleguideFindLookup(): void
    {
        $GLOBALS['wp_mock_current_blog_id'] = 90213;
        $GLOBALS['wp_mock_posts_by_id'][7] = ['post_type' => 'page', 'post_status' => 'publish'];
        $GLOBALS['wp_mock_post_meta'][7]['_wp_page_template'] = 'page.blade.php';
        $GLOBALS['wp_mock_queried_object_id'] = 7;
        $GLOBALS['wp_mock_is_singular'] = true;

        $this->provider->boot();

        $robots = apply_filters('wp_robots', ['index' => true]);

        $this->assertArrayNotHasKey('noindex', $robots);
    }

    /**
     * A second page assigned the styleguide template (e.g. a duplicate made
     * while migrating) must be noindexed too, even though StyleguidePage::find()
     * resolves to a different page ID. The noindex decision is keyed on the
     * template meta alone, not on being THE page find() currently names.
     */
    public function testSecondPageWithStyleguideTemplateAlsoGetsNoindex(): void
    {
        $GLOBALS['wp_mock_current_blog_id'] = 90214;
        $GLOBALS['wp_mock_options'][StyleguidePage::optionKey()] = 42;
        $GLOBALS['wp_mock_posts_by_id'][42] = ['post_type' => 'page', 'post_status' => 'private'];
        $GLOBALS['wp_mock_post_meta'][42][StyleguidePage::markerKey()] = '1';
        $GLOBALS['wp_mock_post_meta'][42]['_wp_page_template'] = StyleguidePage::TEMPLATE;
        $GLOBALS['wp_mock_posts_by_id'][99] = ['post_type' => 'page', 'post_status' => 'publish'];
        $GLOBALS['wp_mock_post_meta'][99]['_wp_page_template'] = StyleguidePage::TEMPLATE;
        $GLOBALS['wp_mock_queried_object_id'] = 99;
        $GLOBALS['wp_mock_is_singular'] = true;

        $this->provider->boot();

        $robots = apply_filters('wp_robots', ['index' => true]);

        $this->assertTrue($robots['noindex']);
        $this->assertTrue($robots['nofollow']);
    }

    public function testRegularSingularPageIsNotAffectedByStyleguideOverride(): void
    {
        $GLOBALS['wp_mock_current_blog_id'] = 90211;
        $GLOBALS['wp_mock_options'][StyleguidePage::optionKey()] = 42;
        $GLOBALS['wp_mock_posts_by_id'][42] = ['post_type' => 'page', 'post_status' => 'private'];
        $GLOBALS['wp_mock_post_meta'][42][StyleguidePage::markerKey()] = '1';
        $GLOBALS['wp_mock_queried_object_id'] = 7;
        $GLOBALS['wp_mock_is_singular'] = true;

        $this->provider->boot();

        $robots = apply_filters('wp_robots', ['index' => true]);

        $this->assertArrayNotHasKey('noindex', $robots);
    }

    /**
     * @runInSeparateProcess
     */
    public function testWpseoRobotsFilterAddsNoindexForStyleguideWhenYoastActive(): void
    {
        if (!defined('WPSEO_VERSION')) {
            define('WPSEO_VERSION', '1.0');
        }

        $GLOBALS['wp_mock_current_blog_id'] = 90212;
        $GLOBALS['wp_mock_options'][StyleguidePage::optionKey()] = 42;
        $GLOBALS['wp_mock_posts_by_id'][42] = ['post_type' => 'page', 'post_status' => 'private'];
        $GLOBALS['wp_mock_post_meta'][42][StyleguidePage::markerKey()] = '1';
        $GLOBALS['wp_mock_post_meta'][42]['_wp_page_template'] = StyleguidePage::TEMPLATE;
        $GLOBALS['wp_mock_queried_object_id'] = 42;
        $GLOBALS['wp_mock_is_singular'] = true;

        $this->provider->boot();

        $robots = apply_filters('wpseo_robots', 'index, follow');

        $this->assertStringContainsString('noindex', $robots);
        $this->assertStringContainsString('nofollow', $robots);
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

    public function testEmitFaqSchemaEscapesScriptTagsInAnswer(): void
    {
        ob_start();
        SeoServiceProvider::emitFaqSchema([
            ['question' => 'Is this safe?', 'answer' => '</script><script>alert(1)</script>'],
        ]);
        $out = (string) ob_get_clean();

        $ldJsonBlock = $this->extractLdJsonBlock($out);

        // wp_kses_post() strips <script> (not in the post-context allowlist)
        // before the answer ever reaches the JSON-LD block, so only the inert
        // text remains in the payload - no literal or escaped script tag.
        $this->assertStringNotContainsString('</script><script>', $ldJsonBlock);
        $this->assertStringContainsString('"text":"alert(1)"', $ldJsonBlock);
    }

    public function testEmitFaqSchemaEscapesScriptTagsInQuestion(): void
    {
        ob_start();
        SeoServiceProvider::emitFaqSchema([
            ['question' => '</script><script>alert(1)</script>', 'answer' => 'A safe answer.'],
        ]);
        $out = (string) ob_get_clean();

        $ldJsonBlock = $this->extractLdJsonBlock($out);

        // wp_strip_all_tags() removes every tag from the question before it
        // reaches the JSON-LD block, so no script markup - literal or
        // escaped - can survive in the "name" field.
        $this->assertStringNotContainsString('</script><script>', $ldJsonBlock);
        $this->assertStringContainsString('"name":"alert(1)"', $ldJsonBlock);
    }

    public function testRenderJsonLdHexEscapesAngleBracketsAndAmpersand(): void
    {
        // With a faithful wp_kses, no kses-passed payload keeps a raw "<" or
        // ">" (core turns "< 2 >" into "&lt; 2 >" and a lone ">" into
        // "&gt;"), so JSON_HEX_TAG can no longer be pinned through a
        // sanitized field. Call the encoding seam directly instead: whatever
        // renderJsonLd() receives is exactly what wp_json_encode() sees.
        ob_start();
        $this->invokeStaticMethod(SeoServiceProvider::class, 'renderJsonLd', [
            [
                '@type' => 'Thing',
                'text' => '</script><script>alert(1)</script> & more',
            ],
        ]);
        $out = (string) ob_get_clean();

        $ldJsonBlock = $this->extractLdJsonBlock($out);

        $this->assertStringContainsString('\u003C', $ldJsonBlock);
        $this->assertStringContainsString('\u003E', $ldJsonBlock);
        $this->assertStringContainsString('\u0026', $ldJsonBlock);
        $this->assertStringNotContainsString('</script>', $ldJsonBlock);
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
        $this->assertStringContainsString('Designer \u0026 Developer', $out);
        $this->assertStringContainsString('linkedin.com', $out);
        $this->assertStringContainsString('"worksFor"', $out);
    }

    public function testEmitPersonSchemaEscapesScriptTagsInUrlAndSameAs(): void
    {
        ob_start();
        SeoServiceProvider::emitPersonSchema([
            'name' => 'Rafael Alex',
            'url' => '</script><script>alert(1)</script>',
            'sameAs' => ['</script><script>alert(2)</script>'],
        ]);
        $out = (string) ob_get_clean();

        $ldJsonBlock = $this->extractLdJsonBlock($out);

        // url and sameAs are copied into the schema without any wp_kses_post()
        // pass (see emitPersonSchema()), so JSON_HEX_TAG is the only thing
        // standing between an operator-supplied value and a script breakout.
        $this->assertStringNotContainsString('</script><script>', $ldJsonBlock);
        $this->assertStringContainsString('\u003C/script\u003E\u003Cscript\u003E', $ldJsonBlock);
    }

    public function testEmitPersonSchemaSkipsWithoutName(): void
    {
        ob_start();
        SeoServiceProvider::emitPersonSchema(['name' => '']);
        $out = (string) ob_get_clean();

        $this->assertSame('', $out);
    }

    public function testBreadcrumbSeparatorIsAlwaysAngleQuote(): void
    {
        $this->provider->boot();

        $this->assertSame('»', apply_filters('wpseo_breadcrumb_separator', ' > '));
    }

    // =====================================================================
    // Member-area / protected pages: getMetaDescription() must not leak
    // section content to an anonymous visitor, mirroring the existing
    // password-protection guard covered by SeoMetaDescriptionTest.
    // =====================================================================

    public function testProtectedPageFallsBackToTaglineForAnonymousVisitor(): void
    {
        $GLOBALS['wp_mock_is_singular'] = true;
        $GLOBALS['wp_mock_password_required'] = false;
        $GLOBALS['wp_mock_post_id'] = 601;
        $GLOBALS['wp_mock_bloginfo'] = ['description' => 'Die Standard-Beschreibung der Website'];
        $GLOBALS['wp_mock_fields']['page_is_protected:601'] = true;
        $GLOBALS['wp_mock_fields']['page_sections:601'] = [
            ['acf_fc_layout' => 'one_column', 'content' => 'Interne Zahlen für das laufende Quartal.'],
        ];

        $description = $this->invokeMethod($this->provider, 'getMetaDescription');

        $this->assertStringNotContainsString('Interne Zahlen', $description);
        $this->assertSame('Die Standard-Beschreibung der Website', $description);
    }

    public function testProtectedPageIsUnchangedForAuthenticatedMember(): void
    {
        $GLOBALS['wp_mock_is_singular'] = true;
        $GLOBALS['wp_mock_password_required'] = false;
        $GLOBALS['wp_mock_post_id'] = 602;
        $GLOBALS['wp_mock_current_user_id'] = 7;
        $GLOBALS['wp_mock_current_user_can'] = ['manage_options' => true];
        $GLOBALS['wp_mock_fields']['page_is_protected:602'] = true;
        $GLOBALS['wp_mock_fields']['page_sections:602'] = [
            ['acf_fc_layout' => 'one_column', 'content' => 'Für Mitglieder sichtbarer Text.'],
        ];

        $description = $this->invokeMethod($this->provider, 'getMetaDescription');

        $this->assertSame('Für Mitglieder sichtbarer Text.', $description);
    }

    /**
     * Slices out the <script type="application/ld+json"> block, matching the
     * pattern BreadcrumbsSchemaTest uses, so hex-escape assertions run
     * against only the JSON-LD payload rather than the whole rendered output.
     */
    private function extractLdJsonBlock(string $output): string
    {
        $ldJsonStart = strpos($output, '<script type="application/ld+json"');
        $this->assertNotFalse($ldJsonStart, 'ld+json script block not found');
        $ldJsonEnd = strpos($output, '</script>', $ldJsonStart);

        return substr($output, $ldJsonStart, $ldJsonEnd - $ldJsonStart);
    }
}
