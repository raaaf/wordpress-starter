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

    public function testIsReadableForPublishedAndDraftContentPages(): void
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
            9 => ['post_type' => 'page', 'post_status' => 'draft'],
            10 => ['post_type' => 'page', 'post_status' => 'pending'],
            11 => ['post_type' => 'page', 'post_status' => 'trash'],
        ];
        $GLOBALS['wp_mock_fields']['page_is_member_area:4'] = true;
        $GLOBALS['wp_mock_fields']['page_is_protected:5'] = true;
        $GLOBALS['wp_mock_post_meta'][6]['_wp_page_template'] = 'page-styleguide.blade.php';
        $GLOBALS['wp_mock_post_meta'][8]['_wp_page_template'] = 'page-design-system.blade.php';

        $isReadable = $this->contract()['is_readable'];

        foreach ([1, 9, 10] as $id) {
            $this->assertTrue($isReadable($id), "post {$id}");
        }
        foreach ([2, 3, 4, 5, 6, 7, 8, 11, 99] as $id) {
            $this->assertFalse($isReadable($id), "post {$id}");
        }
    }

    public function testAfterWriteFillsMissingAnchorsLikeSaving(): void
    {
        $this->givenStoredSections(12, ['hero', 'cards', 'hero'], [0 => '', 1 => 'leistungen']);

        ( $this->contract()['after_write'] )(12);

        $this->assertSame([], $GLOBALS['wp_mock_updated_fields'], 'the flexible field is not rewritten');
        $written = array_column($GLOBALS['wp_mock_acf_written_values'], 'value', 'name');
        $this->assertSame(
            ['page_sections_0_section_anchor' => 'hero-1', 'page_sections_2_section_anchor' => 'hero-2'],
            $written
        );
    }

    /**
     * @param list<string>       $layouts
     * @param array<int, string> $anchors
     */
    private function givenStoredSections(int $postId, array $layouts, array $anchors): void
    {
        $GLOBALS['wp_mock_acf_meta'] = ["{$postId}:page_sections" => $layouts];
        foreach ($anchors as $index => $anchor) {
            $GLOBALS['wp_mock_acf_meta']["{$postId}:page_sections_{$index}_section_anchor"] = $anchor;
        }
        $GLOBALS['wp_mock_acf_written_values'] = [];

        $anchorField = static fn (string $layout): array => ['key' => "field_{$layout}_section_anchor", 'name' => 'section_anchor'];
        $GLOBALS['wp_mock_acf_fields']['field_page_sections'] = [
            'key' => 'field_page_sections',
            'name' => 'page_sections',
            'layouts' => [
                ['name' => 'hero', 'sub_fields' => [$anchorField('hero')]],
                ['name' => 'cards', 'sub_fields' => [$anchorField('cards')]],
            ],
        ];
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

    public function testSaveHookFillsAnchorsThroughTheSameCallback(): void
    {
        $provider = new AcfServiceProvider();
        $method = new \ReflectionMethod($provider, 'registerSectionAnchorGeneration');
        $method->invoke($provider);

        $callback = [AcfServiceProvider::class, 'ensureSectionAnchors'];
        $this->assertSame(20, has_action('acf/save_post', $callback));

        $this->givenStoredSections(5, ['hero'], [0 => '']);
        do_action('acf/save_post', 5);

        $this->assertSame(
            ['page_sections_0_section_anchor' => 'hero-1'],
            array_column($GLOBALS['wp_mock_acf_written_values'], 'value', 'name')
        );
        $this->assertSame([], $GLOBALS['wp_mock_updated_fields']);
    }
}
