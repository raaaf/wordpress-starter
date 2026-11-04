<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\AcfServiceProvider;

/**
 * Saving a page must only fill empty section_anchor values, row by row.
 *
 * Rewriting the whole flexible field (update_field('page_sections', ...)) rebuilt
 * _page_sections_layout_meta from the rows, which carry no acf_fc_layout_disabled
 * or acf_fc_layout_custom_label keys. Every save therefore dropped the editor's
 * Rename and Disable of each layout.
 */
final class SectionAnchorGenerationTest extends TestCase
{
    private const POST_ID = 5;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetAnchorMocks();
    }

    protected function tearDown(): void
    {
        $this->resetAnchorMocks();
        parent::tearDown();
    }

    public function testEmptyAnchorsAreWrittenPerRowWithTheirOwnLayoutField(): void
    {
        $this->givenSections(['one_column', 'hero', 'one_column'], [0 => '', 2 => '']);

        AcfServiceProvider::ensureSectionAnchors(self::POST_ID);

        $this->assertSame(
            [
                'page_sections_0_section_anchor' => ['field_one_column_section_anchor', 'one-column-1'],
                'page_sections_1_section_anchor' => ['field_hero_section_anchor', 'hero-1'],
                'page_sections_2_section_anchor' => ['field_one_column_section_anchor', 'one-column-2'],
            ],
            $this->written()
        );
    }

    public function testFlexibleFieldIsNeverRewritten(): void
    {
        $this->givenSections(['one_column'], [0 => '']);

        AcfServiceProvider::ensureSectionAnchors(self::POST_ID);

        $this->assertSame([], $GLOBALS['wp_mock_updated_fields']);
    }

    public function testManualAnchorIsKeptAndStillCounts(): void
    {
        $this->givenSections(['one_column', 'one_column'], [0 => 'kontakt', 1 => '']);

        AcfServiceProvider::ensureSectionAnchors(self::POST_ID);

        $this->assertSame(
            ['page_sections_1_section_anchor' => ['field_one_column_section_anchor', 'one-column-2']],
            $this->written()
        );
    }

    public function testGeneratedAnchorSkipsAnchorsAlreadyStoredOnThePage(): void
    {
        // A row was deleted and a new one added: position 2 would be "hero-2", which row 0 already holds.
        $this->givenSections(['hero', 'hero'], [0 => 'hero-2', 1 => '']);

        AcfServiceProvider::ensureSectionAnchors(self::POST_ID);

        $this->assertSame(
            ['page_sections_1_section_anchor' => ['field_hero_section_anchor', 'hero-3']],
            $this->written()
        );
    }

    public function testLayoutWithoutAnchorFieldIsSkipped(): void
    {
        $this->givenSections(['one_column', 'spacer'], [0 => '']);

        AcfServiceProvider::ensureSectionAnchors(self::POST_ID);

        $this->assertSame(['page_sections_0_section_anchor'], array_keys($this->written()));
    }

    public function testRevisionIsIgnored(): void
    {
        $GLOBALS['wp_mock_revisions'][self::POST_ID] = 4;
        $this->givenSections(['one_column'], [0 => '']);

        AcfServiceProvider::ensureSectionAnchors(self::POST_ID);

        $this->assertSame([], $this->written());
        $this->assertSame([], $GLOBALS['wp_mock_updated_fields']);
    }

    public function testPageWithoutSectionsWritesNothing(): void
    {
        $this->givenSections([], []);

        AcfServiceProvider::ensureSectionAnchors(self::POST_ID);

        $this->assertSame([], $this->written());
        $this->assertSame([], $GLOBALS['wp_mock_updated_fields']);
    }

    /**
     * @param list<string>       $layouts Layout names in row order, as ACF stores them.
     * @param array<int, string> $anchors Stored section_anchor per row index; rows not listed have no meta entry.
     */
    private function givenSections(array $layouts, array $anchors): void
    {
        $id = self::POST_ID;
        $GLOBALS['wp_mock_acf_meta']["{$id}:page_sections"] = $layouts === [] ? '' : $layouts;
        foreach ($anchors as $index => $anchor) {
            $GLOBALS['wp_mock_acf_meta']["{$id}:page_sections_{$index}_section_anchor"] = $anchor;
        }

        $anchorField = static fn (string $layout): array => [
            'key' => "field_{$layout}_section_anchor",
            'name' => 'section_anchor',
        ];
        $GLOBALS['wp_mock_acf_fields']['field_page_sections'] = [
            'key' => 'field_page_sections',
            'name' => 'page_sections',
            'layouts' => [
                ['name' => 'one_column', 'sub_fields' => [['key' => 'field_one_column_title', 'name' => 'title'], $anchorField('one_column')]],
                ['name' => 'hero', 'sub_fields' => [$anchorField('hero')]],
                ['name' => 'spacer', 'sub_fields' => [['key' => 'field_spacer_height', 'name' => 'height']]],
            ],
        ];

        // The raw get_field() view, keyed by field key like ACF returns it. The old
        // implementation read this and rewrote the whole field.
        $GLOBALS['wp_mock_fields']["page_sections:{$id}"] = array_map(
            static fn (string $layout): array => ['acf_fc_layout' => $layout],
            $layouts
        );
    }

    /**
     * @return array<string, array{0: string, 1: mixed}> field name => [field key, value]
     */
    private function written(): array
    {
        $written = [];
        foreach ($GLOBALS['wp_mock_acf_written_values'] as $write) {
            $written[$write['name']] = [$write['key'], $write['value']];
        }

        return $written;
    }

    private function resetAnchorMocks(): void
    {
        $GLOBALS['wp_mock_acf_meta'] = [];
        $GLOBALS['wp_mock_acf_fields'] = [];
        $GLOBALS['wp_mock_acf_written_values'] = [];
        $GLOBALS['wp_mock_updated_fields'] = [];
        $GLOBALS['wp_mock_revisions'] = [];
        $GLOBALS['wp_mock_fields'] = [];
    }
}
