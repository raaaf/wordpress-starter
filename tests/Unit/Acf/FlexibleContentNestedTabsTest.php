<?php

declare(strict_types=1);

namespace Tests\Unit\Acf;

use Tests\Support\TestCase;
use WordpressStarter\Acf\FlexibleContent;
use WordpressStarter\ThemeContext;

/**
 * Jeder Tab des Layouts "tabs" nimmt weitere Module auf (Feld "modules").
 * Die Module sind dieselben Layouts wie auf oberster Ebene, aber ohne
 * Darstellungsfelder (im Panel zeichnet kein Modul eine eigene Sektion),
 * ohne seitenweite Layouts und mit eigenen Feld-Keys, weil ACF globale
 * Eindeutigkeit verlangt.
 *
 * Diese Tests pinnen die Regeln, nicht die Layoutliste.
 */
final class FlexibleContentNestedTabsTest extends TestCase
{
    /**
     * Bewusst doppelt zu FlexibleContent::NESTED_EXCLUDED_LAYOUTS: der Test
     * haelt den Vertrag fest, nicht die Konstante.
     *
     * @var array<int, string>
     */
    private const EXCLUDED = ['hero', 'tabs', 'divider', 'map', 'contact_form', 'newsletter', 'logo_slider'];

    /** @var array<int, string> */
    private const DISPLAY_FIELDS = ['background_color', 'section_spacing', 'section_width', 'section_anchor'];

    protected function setUp(): void
    {
        parent::setUp();
        FlexibleContent::resetLayoutCache();
    }

    protected function tearDown(): void
    {
        FlexibleContent::resetLayoutCache();
        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    private function nestedField(): array
    {
        foreach (FlexibleContent::layouts() as $layout) {
            if ($layout['name'] !== 'tabs') {
                continue;
            }

            foreach ($layout['sub_fields'] as $field) {
                if (( $field['name'] ?? '' ) !== 'tabs') {
                    continue;
                }

                foreach ($field['sub_fields'] as $sub) {
                    if (( $sub['name'] ?? '' ) === 'modules') {
                        return $sub;
                    }
                }
            }
        }

        $this->fail('Das Feld "modules" fehlt im Tabs-Repeater.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function nestedLayouts(): array
    {
        return $this->nestedField()['layouts'];
    }

    /**
     * @param array<mixed> $tree
     *
     * @return array<int, string>
     */
    private function keys(array $tree): array
    {
        $keys = [];

        array_walk_recursive($tree, static function ($value, $name) use (&$keys): void {
            if ($name === 'key' && is_string($value)) {
                $keys[] = $value;
            }
        });

        return $keys;
    }

    public function testTabsRepeaterCarriesTheModulesField(): void
    {
        $field = $this->nestedField();

        $this->assertSame('flexible_content', $field['type']);
        $this->assertSame('field_flex_tabs_tab_modules', $field['key']);
    }

    public function testExcludedLayoutsAreNotOffered(): void
    {
        $names = array_column($this->nestedLayouts(), 'name');

        foreach (self::EXCLUDED as $excluded) {
            $this->assertNotContains($excluded, $names, $excluded);
        }

        $this->assertContains('cards', $names);
    }

    public function testNestedLayoutsHaveNoDisplayFields(): void
    {
        $found = [];

        foreach ($this->nestedLayouts() as $layout) {
            array_walk_recursive($layout, static function ($value, $name) use (&$found, $layout): void {
                if ($name === 'name' && in_array($value, self::DISPLAY_FIELDS, true)) {
                    $found[] = $layout['name'] . '.' . $value;
                }
            });
        }

        $this->assertSame([], $found);
    }

    public function testFieldKeysAreGloballyUnique(): void
    {
        $nestedKeys = $this->keys($this->nestedLayouts());
        $this->assertSame($nestedKeys, array_values(array_unique($nestedKeys)));

        $topLevelKeys = [];
        foreach (FlexibleContent::layouts() as $layout) {
            if ($layout['name'] === 'tabs') {
                continue;
            }
            $topLevelKeys = array_merge($topLevelKeys, $this->keys($layout));
        }

        $this->assertSame([], array_intersect($nestedKeys, $topLevelKeys));
    }

    public function testConditionalLogicPointsInsideTheSameNestedLayout(): void
    {
        $checked = 0;

        foreach ($this->nestedLayouts() as $layout) {
            $own = $this->keys($layout);
            $references = [];

            array_walk_recursive($layout, static function ($value, $name) use (&$references): void {
                if ($name === 'field' && is_string($value) && str_starts_with($value, 'field_')) {
                    $references[] = $value;
                }
            });

            foreach ($references as $reference) {
                $this->assertContains($reference, $own, $layout['name']);
                ++$checked;
            }
        }

        $this->assertGreaterThan(0, $checked, 'Kein conditional_logic gefunden, der Test prueft nichts.');
    }

    public function testLayoutAddedThroughTheFilterIsOffered(): void
    {
        add_filter(ThemeContext::prefix() . '_flexible_content_layouts', static function (array $layouts): array {
            $layouts[] = [
                'key' => 'layout_precious_metals',
                'name' => 'precious_metals',
                'label' => 'Edelmetalle',
                'display' => 'block',
                'sub_fields' => [],
            ];

            return $layouts;
        });

        $this->assertContains('precious_metals', array_column($this->nestedLayouts(), 'name'));
    }
}
