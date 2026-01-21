<?php

declare(strict_types=1);

namespace Tests\Unit\Acf;

use Tests\Support\TestCase;
use WordpressStarter\Acf\Blocks;

/**
 * Tests for the ACF Blocks class.
 */
final class BlocksTest extends TestCase
{
    public function testGetBlockClassesIncludesAcfBlockClass(): void
    {
        $block = ['name' => 'acf/hero'];

        $classes = Blocks::getBlockClasses($block);

        $this->assertStringContainsString('acf-block', $classes);
    }

    public function testGetBlockClassesIncludesBlockName(): void
    {
        $block = ['name' => 'acf/hero'];

        $classes = Blocks::getBlockClasses($block);

        $this->assertStringContainsString('block-hero', $classes);
    }

    public function testGetBlockClassesIncludesAlignment(): void
    {
        $block = ['name' => 'acf/hero', 'align' => 'wide'];

        $classes = Blocks::getBlockClasses($block);

        $this->assertStringContainsString('alignwide', $classes);
    }

    public function testGetBlockClassesIncludesFullAlignment(): void
    {
        $block = ['name' => 'acf/hero', 'align' => 'full'];

        $classes = Blocks::getBlockClasses($block);

        $this->assertStringContainsString('alignfull', $classes);
    }

    public function testGetBlockClassesIncludesCustomClassName(): void
    {
        $block = ['name' => 'acf/cta', 'className' => 'my-custom-class'];

        $classes = Blocks::getBlockClasses($block);

        $this->assertStringContainsString('my-custom-class', $classes);
    }

    public function testGetBlockClassesWithMultipleAttributes(): void
    {
        $block = [
            'name' => 'acf/cards',
            'align' => 'wide',
            'className' => 'custom-cards featured',
        ];

        $classes = Blocks::getBlockClasses($block);

        $this->assertStringContainsString('acf-block', $classes);
        $this->assertStringContainsString('block-cards', $classes);
        $this->assertStringContainsString('alignwide', $classes);
        $this->assertStringContainsString('custom-cards featured', $classes);
    }

    public function testGetBlockCategoryReturnsLayoutForLayoutBlocks(): void
    {
        $layoutBlocks = [
            'one-column', 'two-columns', 'three-columns', 'four-columns',
            'one-third-two-thirds', 'two-thirds-one-third', 'two-columns-images',
            'divider',
        ];

        foreach ($layoutBlocks as $blockName) {
            $this->assertSame(
                'theme-layout',
                Blocks::getBlockCategory($blockName),
                "Block '{$blockName}' should be in theme-layout category"
            );
        }
    }

    public function testGetBlockCategoryReturnsContentForContentBlocks(): void
    {
        $contentBlocks = [
            'hero', 'cta', 'accordion', 'cards', 'testimonials', 'team',
            'pricing-table', 'stats', 'timeline', 'table', 'posts',
        ];

        foreach ($contentBlocks as $blockName) {
            $this->assertSame(
                'theme-content',
                Blocks::getBlockCategory($blockName),
                "Block '{$blockName}' should be in theme-content category"
            );
        }
    }

    public function testGetBlockCategoryReturnsMediaForMediaBlocks(): void
    {
        $mediaBlocks = ['image', 'video', 'gallery', 'logo-slider', 'before-after'];

        foreach ($mediaBlocks as $blockName) {
            $this->assertSame(
                'theme-media',
                Blocks::getBlockCategory($blockName),
                "Block '{$blockName}' should be in theme-media category"
            );
        }
    }

    public function testGetBlockCategoryReturnsInteractiveForInteractiveBlocks(): void
    {
        $interactiveBlocks = ['tabs', 'contact-form', 'map'];

        foreach ($interactiveBlocks as $blockName) {
            $this->assertSame(
                'theme-interactive',
                Blocks::getBlockCategory($blockName),
                "Block '{$blockName}' should be in theme-interactive category"
            );
        }
    }

    public function testGetBlockCategoryReturnsFallbackForUnknownBlocks(): void
    {
        $this->assertSame('theme', Blocks::getBlockCategory('unknown-block'));
        $this->assertSame('theme', Blocks::getBlockCategory('my-custom-block'));
    }

    public function testGetMissingRequirementsReturnsEmptyForNoRequirements(): void
    {
        $blockData = ['name' => 'test'];

        $missing = Blocks::getMissingRequirements($blockData);

        $this->assertSame([], $missing);
    }

    public function testGetMissingRequirementsReturnsEmptyForEmptyRequiresArray(): void
    {
        $blockData = ['name' => 'test', 'requires' => []];

        $missing = Blocks::getMissingRequirements($blockData);

        $this->assertSame([], $missing);
    }

    public function testGetMissingRequirementsReturnsMissingPlugins(): void
    {
        // Contact Form 7 is not available in tests
        $blockData = ['name' => 'contact-form', 'requires' => ['contact-form-7']];

        $missing = Blocks::getMissingRequirements($blockData);

        $this->assertContains('Contact Form 7', $missing);
    }

    public function testGetMissingRequirementsReturnsMultipleMissingPlugins(): void
    {
        $blockData = [
            'name' => 'test-block',
            'requires' => ['contact-form-7', 'woocommerce'],
        ];

        $missing = Blocks::getMissingRequirements($blockData);

        $this->assertCount(2, $missing);
        $this->assertContains('Contact Form 7', $missing);
        $this->assertContains('WooCommerce', $missing);
    }

    public function testRenderInnerBlocksReturnsInnerBlocksTag(): void
    {
        $result = Blocks::renderInnerBlocks();

        $this->assertStringContainsString('<InnerBlocks', $result);
        $this->assertStringContainsString('/>', $result);
    }

    public function testRenderInnerBlocksWithAllowedBlocks(): void
    {
        $result = Blocks::renderInnerBlocks([
            'allowedBlocks' => ['core/paragraph', 'core/heading'],
        ]);

        $this->assertStringContainsString('allowedBlocks', $result);
        // JSON encodes slashes, so we check for escaped versions
        $this->assertStringContainsString('paragraph', $result);
        $this->assertStringContainsString('heading', $result);
    }

    public function testRenderInnerBlocksWithTemplate(): void
    {
        $result = Blocks::renderInnerBlocks([
            'template' => [['core/paragraph', ['placeholder' => 'Add text...']]],
        ]);

        $this->assertStringContainsString('template', $result);
    }

    public function testRenderInnerBlocksWithTemplateLock(): void
    {
        $result = Blocks::renderInnerBlocks([
            'templateLock' => 'all',
        ]);

        $this->assertStringContainsString('templateLock', $result);
        $this->assertStringContainsString('all', $result);
    }

    public function testSupportsInnerBlocksReturnsTrueForJsxSupport(): void
    {
        $block = ['supports' => ['jsx' => true]];

        $this->assertTrue(Blocks::supportsInnerBlocks($block));
    }

    public function testSupportsInnerBlocksReturnsFalseWithoutJsxSupport(): void
    {
        $block = ['supports' => ['jsx' => false]];

        $this->assertFalse(Blocks::supportsInnerBlocks($block));
    }

    public function testSupportsInnerBlocksReturnsFalseForEmptySupports(): void
    {
        $block = ['supports' => []];

        $this->assertFalse(Blocks::supportsInnerBlocks($block));
    }

    public function testSupportsInnerBlocksReturnsFalseForMissingSupports(): void
    {
        $block = [];

        $this->assertFalse(Blocks::supportsInnerBlocks($block));
    }

    public function testGetBlockIconReturnsCorrectIconForKnownBlocks(): void
    {
        $expectedIcons = [
            'hero' => 'superhero-alt',
            'cta' => 'megaphone',
            'accordion' => 'list-view',
            'cards' => 'grid-view',
            'video' => 'video-alt3',
            'gallery' => 'images-alt2',
            'contact-form' => 'email-alt',
            'map' => 'location-alt',
        ];

        foreach ($expectedIcons as $blockName => $expectedIcon) {
            $this->assertSame(
                $expectedIcon,
                Blocks::getBlockIcon($blockName),
                "Block '{$blockName}' should have icon '{$expectedIcon}'"
            );
        }
    }

    public function testGetBlockIconReturnsFallbackForUnknownBlock(): void
    {
        $this->assertSame('block-default', Blocks::getBlockIcon('unknown-block'));
    }

    public function testGetBlockKeywordsReturnsArrayForKnownBlocks(): void
    {
        $keywords = Blocks::getBlockKeywords('hero');

        $this->assertIsArray($keywords);
        $this->assertNotEmpty($keywords);
        $this->assertContains('header', $keywords);
        $this->assertContains('banner', $keywords);
    }

    public function testGetBlockKeywordsReturnsFallbackForUnknownBlock(): void
    {
        $keywords = Blocks::getBlockKeywords('my-custom-block');

        $this->assertSame(['my-custom-block'], $keywords);
    }

    public function testGetBlockWrapperAttributesReturnsString(): void
    {
        $block = ['name' => 'acf/hero'];

        $attributes = Blocks::getBlockWrapperAttributes($block);

        $this->assertIsString($attributes);
    }

    public function testGetBlockWrapperAttributesIncludesClass(): void
    {
        $block = ['name' => 'acf/hero'];

        $attributes = Blocks::getBlockWrapperAttributes($block);

        $this->assertStringContainsString('class=', $attributes);
    }

    public function testGetBlockWrapperAttributesIncludesAnchor(): void
    {
        $block = ['name' => 'acf/hero', 'anchor' => 'my-hero'];

        $attributes = Blocks::getBlockWrapperAttributes($block);

        $this->assertStringContainsString('id=', $attributes);
        $this->assertStringContainsString('my-hero', $attributes);
    }
}
