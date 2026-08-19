<?php

declare(strict_types=1);

namespace Tests\Unit\Acf;

use Tests\Support\TestCase;
use WordpressStarter\Acf\FlexibleContent;
use WordpressStarter\ThemeContext;

/**
 * Derived themes used to copy getLayouts() just to insert a single line, which
 * turned this file into the most expensive merge conflict on every starter
 * update. The filter is the supported way in; these tests pin what it promises.
 */
final class FlexibleContentLayoutFilterTest extends TestCase
{
    private const EXPECTED_ORDER = [
        'hero',
        'one_column',
        'two_columns',
        'three_columns',
        'four_columns',
        'one_third_two_thirds',
        'two_thirds_one_third',
        'one_column_image',
        'two_columns_images',
        'three_columns_images',
        'four_columns_images',
        'accordion',
        'tabs',
        'cta',
        'button',
        'image',
        'video',
        'gallery',
        'before_after',
        'testimonials',
        'cards',
        'stats',
        'timeline',
        'team',
        'pricing_table',
        'contact_form',
        'map',
        'posts',
        'table',
        'divider',
        'logo_slider',
        'member_downloads',
    ];

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

    private function filterName(): string
    {
        return ThemeContext::prefix() . '_flexible_content_layouts';
    }

    /**
     * @param array<int, array<string, mixed>> $layouts
     *
     * @return array<int, string>
     */
    private function names(array $layouts): array
    {
        return array_map(static fn (array $layout): string => (string) $layout['name'], $layouts);
    }

    public function testWithoutFilterTheLayoutListIsUnchanged(): void
    {
        $names = $this->names(FlexibleContent::layouts());

        $this->assertCount(32, $names);
        $this->assertSame(self::EXPECTED_ORDER, $names);
    }

    public function testFilterCanAppendALayoutAtTheEnd(): void
    {
        add_filter($this->filterName(), static function (array $layouts): array {
            $layouts[] = [
                'key' => 'layout_precious_metals',
                'name' => 'precious_metals',
                'label' => 'Edelmetalle',
                'display' => 'block',
                'sub_fields' => [],
            ];

            return $layouts;
        });

        $names = $this->names(FlexibleContent::layouts());

        $this->assertCount(33, $names);
        $this->assertSame('precious_metals', end($names));
    }

    public function testFilterCanInsertALayoutAtAGivenPosition(): void
    {
        add_filter($this->filterName(), static function (array $layouts): array {
            array_splice($layouts, 1, 0, [[
                'key' => 'layout_precious_metals',
                'name' => 'precious_metals',
                'label' => 'Edelmetalle',
                'display' => 'block',
                'sub_fields' => [],
            ]]);

            return $layouts;
        });

        $names = $this->names(FlexibleContent::layouts());

        $this->assertSame('hero', $names[0]);
        $this->assertSame('precious_metals', $names[1]);
        $this->assertSame('one_column', $names[2]);
    }

    public function testFilterCanRemoveALayout(): void
    {
        add_filter($this->filterName(), static function (array $layouts): array {
            return array_filter(
                $layouts,
                static fn (array $layout): bool => $layout['name'] !== 'map'
            );
        });

        $names = $this->names(FlexibleContent::layouts());

        $this->assertNotContains('map', $names);
        $this->assertCount(31, $names);
    }

    public function testRemovingLayoutsKeepsTheListSequential(): void
    {
        add_filter($this->filterName(), static function (array $layouts): array {
            return array_filter(
                $layouts,
                static fn (array $layout): bool => $layout['name'] !== 'map'
            );
        });

        $layouts = FlexibleContent::layouts();

        $this->assertSame(range(0, count($layouts) - 1), array_keys($layouts));
    }

    public function testCachedListIsReturnedUnchangedOnRepeatedCalls(): void
    {
        $first = FlexibleContent::layouts();

        add_filter($this->filterName(), static fn (array $layouts): array => []);

        $this->assertSame($first, FlexibleContent::layouts());
    }
}
