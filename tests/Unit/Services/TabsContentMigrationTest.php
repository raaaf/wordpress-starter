<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use WordpressStarter\Acf\FlexibleContent;
use WordpressStarter\Services\TabsContentMigration;

/**
 * Der Tab-Text ist als Feld entfallen und zieht in ein verschachteltes
 * one_column-Modul an Position 0 des Tabs. Seiten, die vorher gespeichert
 * wurden, tragen den Text noch als Post-Meta; ohne die Verschiebung bliebe er
 * in der Datenbank liegen und erschiene nie wieder. Getestet wird nur der reine
 * Teil, plan(): Meta hinein, Operationen heraus.
 */
final class TabsContentMigrationTest extends TestCase
{
    private const CONTENT_KEY = 'field_tabsnested_one_column_content';

    /**
     * @param array<string, mixed> $meta
     *
     * @return array{set: array<string, mixed>, delete: array<int, string>, migrated: int}
     */
    private function plan(array $meta): array
    {
        // Standard: every section in these fixtures is a tabs layout.
        $meta += ['page_sections' => ['tabs', 'tabs', 'tabs']];

        return TabsContentMigration::plan($meta, FlexibleContent::NESTED_MODULES_KEY, self::CONTENT_KEY);
    }

    public function testTextOnlyTabBecomesAOneColumnModuleAtZero(): void
    {
        $plan = $this->plan([
            'page_sections_0_tabs_1_content' => '<p>Text</p>',
            '_page_sections_0_tabs_1_content' => 'field_flex_tabs_tab_content',
        ]);

        $this->assertSame(['one_column'], $plan['set']['page_sections_0_tabs_1_modules']);
        $this->assertSame(FlexibleContent::NESTED_MODULES_KEY, $plan['set']['_page_sections_0_tabs_1_modules']);
        $this->assertSame('<p>Text</p>', $plan['set']['page_sections_0_tabs_1_modules_0_content']);
        $this->assertSame(self::CONTENT_KEY, $plan['set']['_page_sections_0_tabs_1_modules_0_content']);
        $this->assertEqualsCanonicalizing(
            ['page_sections_0_tabs_1_content', '_page_sections_0_tabs_1_content'],
            $plan['delete'],
        );
        $this->assertSame(1, $plan['migrated']);
    }

    public function testExistingModulesShiftDownIncludingSubAndReferenceKeys(): void
    {
        $plan = $this->plan([
            'page_sections_0_tabs_0_content' => '<p>Text</p>',
            'page_sections_0_tabs_0_modules' => ['cards', 'button'],
            '_page_sections_0_tabs_0_modules' => FlexibleContent::NESTED_MODULES_KEY,
            'page_sections_0_tabs_0_modules_0_cards' => '2',
            'page_sections_0_tabs_0_modules_0_cards_1_title' => 'B',
            '_page_sections_0_tabs_0_modules_0_cards' => 'field_x',
            'page_sections_0_tabs_0_modules_1_variant' => 'secondary',
        ]);

        $this->assertSame(['one_column', 'cards', 'button'], $plan['set']['page_sections_0_tabs_0_modules']);
        $this->assertSame('2', $plan['set']['page_sections_0_tabs_0_modules_1_cards']);
        $this->assertSame('B', $plan['set']['page_sections_0_tabs_0_modules_1_cards_1_title']);
        $this->assertSame('field_x', $plan['set']['_page_sections_0_tabs_0_modules_1_cards']);
        $this->assertSame('secondary', $plan['set']['page_sections_0_tabs_0_modules_2_variant']);
        $this->assertSame('<p>Text</p>', $plan['set']['page_sections_0_tabs_0_modules_0_content']);
        $this->assertContains('page_sections_0_tabs_0_modules_0_cards', $plan['delete']);
        $this->assertNotContains('page_sections_0_tabs_0_modules_1_cards', $plan['delete']);
    }

    public function testEmptyContentOnlyDeletesTheOldKeys(): void
    {
        $plan = $this->plan([
            'page_sections_0_tabs_0_content' => "  \n ",
            '_page_sections_0_tabs_0_content' => 'field_flex_tabs_tab_content',
        ]);

        $this->assertSame([], $plan['set']);
        $this->assertCount(2, $plan['delete']);
        $this->assertSame(0, $plan['migrated']);
    }

    public function testTabsInDifferentSectionsAreHandledIndependently(): void
    {
        $plan = $this->plan([
            'page_sections_0_tabs_0_content' => '<p>A</p>',
            'page_sections_2_tabs_0_content' => '<p>B</p>',
            'page_sections_2_tabs_0_modules' => ['button'],
            'page_sections_2_tabs_0_modules_0_variant' => 'primary',
        ]);

        $this->assertSame(['one_column'], $plan['set']['page_sections_0_tabs_0_modules']);
        $this->assertSame('<p>A</p>', $plan['set']['page_sections_0_tabs_0_modules_0_content']);
        $this->assertSame(['one_column', 'button'], $plan['set']['page_sections_2_tabs_0_modules']);
        $this->assertSame('<p>B</p>', $plan['set']['page_sections_2_tabs_0_modules_0_content']);
        $this->assertSame('primary', $plan['set']['page_sections_2_tabs_0_modules_1_variant']);
        $this->assertArrayNotHasKey('page_sections_0_tabs_0_modules_1_variant', $plan['set']);
        $this->assertSame(2, $plan['migrated']);
    }

    public function testAlreadyMigratedTabOnlyDropsTheLegacyKeys(): void
    {
        $plan = $this->plan([
            'page_sections_0_tabs_0_content' => '<p>Text</p>',
            '_page_sections_0_tabs_0_content' => 'field_flex_tabs_tab_content',
            'page_sections_0_tabs_0_modules' => ['one_column', 'button'],
            'page_sections_0_tabs_0_modules_0_content' => '<p>Text</p>',
            'page_sections_0_tabs_0_modules_1_variant' => 'secondary',
        ]);

        $this->assertSame([], $plan['set']);
        $this->assertEqualsCanonicalizing(
            ['page_sections_0_tabs_0_content', '_page_sections_0_tabs_0_content'],
            $plan['delete'],
        );
        $this->assertSame(0, $plan['migrated']);
    }

    public function testTabsKeysOfANonTabsLayoutAreLeftUntouched(): void
    {
        $plan = $this->plan([
            'page_sections' => ['one_column', 'tabs'],
            'page_sections_0_tabs_0_content' => '<p>Fremd</p>',
            'page_sections_1_tabs_0_content' => '<p>Echt</p>',
        ]);

        $this->assertArrayNotHasKey('page_sections_0_tabs_0_modules', $plan['set']);
        $this->assertNotContains('page_sections_0_tabs_0_content', $plan['delete']);
        $this->assertSame('<p>Echt</p>', $plan['set']['page_sections_1_tabs_0_modules_0_content']);
        $this->assertSame(1, $plan['migrated']);
    }
}
