<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp;

use Tests\Support\TestCase;
use WordpressStarter\Acf\FlexibleContent;
use WordpressStarter\Mcp\ContractProvider;
use WordpressStarter\Providers\AcfServiceProvider;

/**
 * The MCP plugin learns the theme's page structure only from the filter
 * wp_starter_mcp_contract; this pins version 1 of that contract.
 */
final class ContractProviderTest extends TestCase
{
    /** @return array<string, mixed> */
    private function contract(): array
    {
        ( new ContractProvider() )->boot();

        $contract = apply_filters('wp_starter_mcp_contract', null);
        $this->assertIsArray($contract);

        return $contract;
    }

    public function testAnswersTheLiteralFilterWithEveryKey(): void
    {
        $contract = $this->contract();

        $this->assertSame(
            ['version', 'page_field', 'page_field_key', 'writable', 'copy_meta', 'layouts', 'is_readable', 'after_write', 'is_allowed_embed_host', 'design_tokens', 'validate_sections'],
            array_keys($contract),
        );
        $this->assertSame(1, $contract['version']);
        $this->assertSame('page_sections', $contract['page_field']);
        $this->assertSame('field_page_sections', $contract['page_field_key']);
        $this->assertContains('page_is_member_area', $contract['copy_meta']);
        $this->assertContains('_yoast_wpseo_metadesc', $contract['copy_meta']);

        foreach (['layouts', 'is_readable', 'after_write', 'is_allowed_embed_host', 'design_tokens', 'validate_sections'] as $key) {
            $this->assertTrue(is_callable($contract[$key]), $key);
        }
    }

    public function testFilterNameIsNotDerivedFromTheThemeSlug(): void
    {
        // bin/setup.php renames the theme; the plugin must still find the filter.
        $GLOBALS['wp_mock_template'] = 'client-theme';
        \WordpressStarter\ThemeContext::reset();

        $this->assertSame(1, $this->contract()['version']);
    }

    public function testWritableExcludesFormsAndMemberModules(): void
    {
        $writable = $this->contract()['writable'];

        foreach (['newsletter', 'contact_form', 'member_downloads', 'download_list'] as $layout) {
            $this->assertNotContains($layout, $writable);
        }
        $this->assertContains('hero', $writable);
    }

    public function testWritableNamesExistingLayoutsOnly(): void
    {
        $registered = array_column(FlexibleContent::layouts(), 'name');

        $this->assertSame([], array_values(array_diff(ContractProvider::WRITABLE_LAYOUTS, $registered)));
        $readOnly = array_values(array_diff($registered, ContractProvider::WRITABLE_LAYOUTS));
        sort($readOnly);
        $this->assertSame(
            ['contact_form', 'download_list', 'member_downloads', 'newsletter'],
            $readOnly,
            'every other layout is writable; a new one has to be decided on here',
        );
    }

    public function testLayoutsAreReadLazily(): void
    {
        $this->contract();
        $this->assertSame(
            array_column(FlexibleContent::layouts(), 'name'),
            array_column(ContractProvider::layouts(), 'name'),
        );
    }

    public function testIsReadableOnlyForPublicContentPages(): void
    {
        $GLOBALS['wp_mock_posts_by_id'] = [
            1 => ['post_type' => 'page', 'post_status' => 'publish'],
            2 => ['post_type' => 'page', 'post_status' => 'private'],
            3 => ['post_type' => 'page', 'post_status' => 'publish', 'post_password' => 'geheim'],
            4 => ['post_type' => 'page', 'post_status' => 'publish'],
            5 => ['post_type' => 'page', 'post_status' => 'publish'],
            6 => ['post_type' => 'page', 'post_status' => 'publish'],
            7 => ['post_type' => 'post', 'post_status' => 'publish'],
            8 => ['post_type' => 'page', 'post_status' => 'publish'],
        ];
        $GLOBALS['wp_mock_fields']['page_is_member_area:4'] = true;
        $GLOBALS['wp_mock_fields']['page_is_protected:5'] = true;
        $GLOBALS['wp_mock_post_meta'][6]['_wp_page_template'] = 'page-styleguide.blade.php';
        $GLOBALS['wp_mock_post_meta'][8]['_wp_page_template'] = 'page-design-system.blade.php';

        $isReadable = $this->contract()['is_readable'];

        $this->assertTrue($isReadable(1));
        foreach ([2, 3, 4, 5, 6, 7, 8, 99] as $id) {
            $this->assertFalse($isReadable($id), "post {$id}");
        }
    }

    public function testAfterWriteFillsMissingAnchorsLikeSaving(): void
    {
        $GLOBALS['wp_mock_fields']['page_sections:12'] = [
            ['acf_fc_layout' => 'hero', 'section_anchor' => ''],
            ['acf_fc_layout' => 'cards', 'section_anchor' => 'leistungen'],
            ['acf_fc_layout' => 'hero'],
        ];

        ( $this->contract()['after_write'] )(12);

        $this->assertCount(1, $GLOBALS['wp_mock_updated_fields']);
        $update = $GLOBALS['wp_mock_updated_fields'][0];
        $this->assertSame('page_sections', $update['selector']);
        $this->assertSame(12, $update['post_id']);
        $this->assertSame(['hero-1', 'leistungen', 'hero-2'], array_column($update['value'], 'section_anchor'));
    }

    public function testEmbedHostTakesHostOrUrl(): void
    {
        $check = $this->contract()['is_allowed_embed_host'];

        $this->assertTrue($check('www.youtube-nocookie.com'));
        $this->assertTrue($check('https://player.vimeo.com/video/1'));
        $this->assertFalse($check('evil.example.org'));
        $this->assertFalse($check('http://player.vimeo.com/video/1'));
        $this->assertFalse($check(''));
    }

    public function testDesignTokensNameColoursAndEndpoint(): void
    {
        $GLOBALS['wp_mock_template_directory'] = dirname(__DIR__, 3);
        $tokens = ( $this->contract()['design_tokens'] )();

        $this->assertArrayHasKey('accent', $tokens['colors']);
        $this->assertStringContainsString('/v1/design-tokens/', $tokens['endpoint']);
        $this->assertSame(['primitives', 'light', 'dark'], $tokens['files']);
    }

    public function testValidateSectionsReportsUnwritableLayoutsAndBrokenTables(): void
    {
        $validate = $this->contract()['validate_sections'];

        $this->assertSame([], $validate([
            ['acf_fc_layout' => 'hero'],
            ['acf_fc_layout' => 'table', 'headers' => [['label' => 'A'], ['label' => 'B']], 'rows' => [['cells' => [['c' => 1], ['c' => 2]]]]],
        ]));

        $problems = $validate([
            ['acf_fc_layout' => 'newsletter'],
            ['acf_fc_layout' => 'table', 'field_flex_table_headers' => [['label' => 'A'], ['label' => 'B']], 'field_flex_table_rows' => [['cells' => [['c' => 1]]]]],
            'garbage',
        ]);

        $this->assertCount(3, $problems);
        $this->assertStringContainsString('"newsletter"', $problems[0]);
        $this->assertStringContainsString('Section 3', $problems[1]);
        $this->assertStringContainsString('1 cells for 2 columns', $problems[2]);
    }

    public function testSaveHookStillAddsAnchorsAndUnhooksDuringItsOwnWrite(): void
    {
        $provider = new AcfServiceProvider();
        $method = new \ReflectionMethod($provider, 'registerSectionAnchorGeneration');
        $method->invoke($provider);

        $callback = [AcfServiceProvider::class, 'ensureSectionAnchors'];
        $this->assertSame(20, has_action('acf/save_post', $callback));

        $GLOBALS['wp_mock_fields']['page_sections:5'] = [['acf_fc_layout' => 'cta']];
        do_action('acf/save_post', 5);

        $this->assertSame('cta-1', $GLOBALS['wp_mock_updated_fields'][0]['value'][0]['section_anchor']);
        $this->assertSame(20, has_action('acf/save_post', $callback), 'hooked again after the write');

        $GLOBALS['wp_mock_revisions'][6] = 5;
        $GLOBALS['wp_mock_fields']['page_sections:6'] = [['acf_fc_layout' => 'cta']];
        AcfServiceProvider::ensureSectionAnchors(6);
        $this->assertCount(1, $GLOBALS['wp_mock_updated_fields'], 'revisions are skipped');
    }
}
