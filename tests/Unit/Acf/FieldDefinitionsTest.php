<?php

declare(strict_types=1);

namespace Tests\Unit\Acf;

use Tests\Support\TestCase;
use WordpressStarter\Acf\FieldDefinitions;

/**
 * Tests for ACF FieldDefinitions helper class.
 */
final class FieldDefinitionsTest extends TestCase
{
    // ==========================================
    // BACKGROUND_COLORS constant tests
    // ==========================================

    public function testBackgroundColorsContainsAllExpectedColors(): void
    {
        $colors = FieldDefinitions::getBackgroundColors();

        $this->assertArrayHasKey('primary', $colors);
        $this->assertArrayHasKey('secondary', $colors);
        $this->assertArrayHasKey('tertiary', $colors);
        $this->assertArrayHasKey('brand', $colors);
        $this->assertArrayHasKey('brand-subtle', $colors);
        $this->assertArrayHasKey('inverse', $colors);
    }

    // ==========================================
    // THEME_ICONS constant tests
    // ==========================================

    public function testThemeIconsContainsAllExpectedIcons(): void
    {
        $icons = FieldDefinitions::getThemeIcons();

        $this->assertArrayHasKey('', $icons); // No icon option
        $this->assertArrayHasKey('check', $icons);
        $this->assertArrayHasKey('mail', $icons);
        $this->assertArrayHasKey('phone', $icons);
        $this->assertArrayHasKey('facebook', $icons);
        $this->assertArrayHasKey('linkedin', $icons);
    }

    // ==========================================
    // backgroundColorField() tests
    // ==========================================

    public function testBackgroundColorFieldReturnsValidStructure(): void
    {
        $field = FieldDefinitions::backgroundColorField('test_prefix');

        $this->assertSame('field_test_prefix_background_color', $field['key']);
        $this->assertSame('Hintergrundfarbe', $field['label']);
        $this->assertSame('background_color', $field['name']);
        $this->assertSame('select', $field['type']);
        $this->assertSame('primary', $field['default_value']);
        $this->assertSame(FieldDefinitions::getBackgroundColors(), $field['choices']);
    }

    // ==========================================
    // textField() tests
    // ==========================================

    public function testTextFieldReturnsValidStructure(): void
    {
        $field = FieldDefinitions::textField(
            'field_test_title',
            'Titel',
            'title',
            true,
            'Anweisungen',
            'Platzhalter'
        );

        $this->assertSame('field_test_title', $field['key']);
        $this->assertSame('Titel', $field['label']);
        $this->assertSame('title', $field['name']);
        $this->assertSame('text', $field['type']);
        $this->assertSame(1, $field['required']);
        $this->assertSame('Anweisungen', $field['instructions']);
        $this->assertSame('Platzhalter', $field['placeholder']);
    }

    public function testTextFieldWithDefaultsOmitsPlaceholder(): void
    {
        $field = FieldDefinitions::textField('field_key', 'Label', 'name');

        $this->assertArrayNotHasKey('placeholder', $field);
        $this->assertSame(0, $field['required']);
    }

    // ==========================================
    // wysiwygField() tests
    // ==========================================

    public function testWysiwygFieldReturnsValidStructure(): void
    {
        $field = FieldDefinitions::wysiwygField(
            'field_content',
            'Inhalt',
            'content',
            true,
            '50',
            'Bearbeitungsanweisungen'
        );

        $this->assertSame('field_content', $field['key']);
        $this->assertSame('Inhalt', $field['label']);
        $this->assertSame('content', $field['name']);
        $this->assertSame('wysiwyg', $field['type']);
        $this->assertSame(1, $field['required']);
        $this->assertSame('all', $field['tabs']);
        $this->assertSame('full', $field['toolbar']);
        $this->assertSame(['width' => '50'], $field['wrapper']);
    }

    public function testWysiwygFieldWithoutWidthOmitsWrapper(): void
    {
        $field = FieldDefinitions::wysiwygField('field_key', 'Label', 'name');

        $this->assertArrayNotHasKey('wrapper', $field);
    }

    // ==========================================
    // imageField() tests
    // ==========================================

    public function testImageFieldReturnsValidStructure(): void
    {
        $field = FieldDefinitions::imageField(
            'field_image',
            'Bild',
            'image',
            true,
            'id',
            null,
            'Bildanweisungen'
        );

        $this->assertSame('field_image', $field['key']);
        $this->assertSame('Bild', $field['label']);
        $this->assertSame('image', $field['name']);
        $this->assertSame('image', $field['type']);
        $this->assertSame(1, $field['required']);
        $this->assertSame('id', $field['return_format']);
        $this->assertSame('medium', $field['preview_size']);
    }

    // ==========================================
    // linkField() tests
    // ==========================================

    public function testLinkFieldReturnsValidStructure(): void
    {
        $field = FieldDefinitions::linkField(
            'field_cta',
            'Button',
            'cta',
            true,
            'Button-Anweisungen'
        );

        $this->assertSame('field_cta', $field['key']);
        $this->assertSame('Button', $field['label']);
        $this->assertSame('cta', $field['name']);
        $this->assertSame('link', $field['type']);
        $this->assertSame(1, $field['required']);
        $this->assertSame('array', $field['return_format']);
    }

    // ==========================================
    // selectField() tests
    // ==========================================

    public function testSelectFieldReturnsValidStructure(): void
    {
        $choices = ['option1' => 'Option 1', 'option2' => 'Option 2'];
        $field = FieldDefinitions::selectField(
            'field_select',
            'Auswahl',
            'selection',
            $choices,
            'option1',
            true,
            'Wähle eine Option'
        );

        $this->assertSame('field_select', $field['key']);
        $this->assertSame('Auswahl', $field['label']);
        $this->assertSame('selection', $field['name']);
        $this->assertSame('select', $field['type']);
        $this->assertSame($choices, $field['choices']);
        $this->assertSame('option1', $field['default_value']);
        $this->assertSame(1, $field['required']);
        $this->assertSame(1, $field['ui']);
    }

    // ==========================================
    // repeaterField() tests
    // ==========================================

    public function testRepeaterFieldReturnsValidStructure(): void
    {
        $subFields = [
            FieldDefinitions::textField('field_item_title', 'Titel', 'title', true),
        ];

        $field = FieldDefinitions::repeaterField(
            'field_items',
            'Einträge',
            'items',
            $subFields,
            'Eintrag hinzufügen',
            1,
            'block',
            'Füge Einträge hinzu'
        );

        $this->assertSame('field_items', $field['key']);
        $this->assertSame('Einträge', $field['label']);
        $this->assertSame('items', $field['name']);
        $this->assertSame('repeater', $field['type']);
        $this->assertSame(1, $field['min']);
        $this->assertSame('block', $field['layout']);
        $this->assertSame('Eintrag hinzufügen', $field['button_label']);
        $this->assertSame($subFields, $field['sub_fields']);
        $this->assertSame(1, $field['required']); // Required when min > 0
    }

    // ==========================================
    // ctaBlockFields() tests - NEW METHOD
    // ==========================================

    public function testCtaBlockFieldsReturnsCorrectStructure(): void
    {
        $fields = FieldDefinitions::ctaBlockFields('block_cta');

        // Returns 3 fields (background color now handled at layout level)
        $this->assertCount(3, $fields);

        // Title field
        $titleField = $fields[0];
        $this->assertSame('field_block_cta_title', $titleField['key']);
        $this->assertSame('Überschrift', $titleField['label']);
        $this->assertSame('title', $titleField['name']);
        $this->assertSame('text', $titleField['type']);
        $this->assertSame(1, $titleField['required']);

        // Content field (WYSIWYG, not textarea)
        $contentField = $fields[1];
        $this->assertSame('field_block_cta_content', $contentField['key']);
        $this->assertSame('Beschreibung', $contentField['label']);
        $this->assertSame('content', $contentField['name']);
        $this->assertSame('wysiwyg', $contentField['type']);
        $this->assertSame(0, $contentField['required']);

        // Button field (named 'cta' for template compatibility)
        $buttonField = $fields[2];
        $this->assertSame('field_block_cta_cta', $buttonField['key']);
        $this->assertSame('Button', $buttonField['label']);
        $this->assertSame('cta', $buttonField['name']);
        $this->assertSame('link', $buttonField['type']);
        $this->assertSame(1, $buttonField['required']);
    }

    public function testCtaBlockFieldsUsesWysiwygInsteadOfTextarea(): void
    {
        $fields = FieldDefinitions::ctaBlockFields('test');
        $contentField = $fields[1];

        $this->assertSame('wysiwyg', $contentField['type']);
        $this->assertNotSame('textarea', $contentField['type']);
    }

    public function testCtaBlockFieldsDiffersFromCtaFields(): void
    {
        $ctaFields = FieldDefinitions::ctaFields('test');
        $ctaBlockFields = FieldDefinitions::ctaBlockFields('test');

        // ctaFields uses textarea, ctaBlockFields uses wysiwyg
        $ctaContent = array_filter($ctaFields, fn($f) => $f['name'] === 'content');
        $ctaBlockContent = array_filter($ctaBlockFields, fn($f) => $f['name'] === 'content');

        $ctaContentField = array_values($ctaContent)[0];
        $ctaBlockContentField = array_values($ctaBlockContent)[0];

        $this->assertSame('textarea', $ctaContentField['type']);
        $this->assertSame('wysiwyg', $ctaBlockContentField['type']);

        // ctaFields uses 'button', ctaBlockFields uses 'cta'
        $ctaButton = array_filter($ctaFields, fn($f) => $f['name'] === 'button');
        $ctaBlockButton = array_filter($ctaBlockFields, fn($f) => $f['name'] === 'cta');

        $this->assertCount(1, $ctaButton);
        $this->assertCount(1, $ctaBlockButton);
    }

    // ==========================================
    // heroFields() tests
    // ==========================================

    public function testHeroFieldsContainsAllVariants(): void
    {
        $fields = FieldDefinitions::heroFields('hero');

        // Find the variant field
        $variantField = null;
        foreach ($fields as $field) {
            if (($field['name'] ?? '') === 'variant') {
                $variantField = $field;
                break;
            }
        }

        $this->assertNotNull($variantField);
        $this->assertArrayHasKey('centered', $variantField['choices']);
        $this->assertArrayHasKey('split', $variantField['choices']);
        $this->assertArrayHasKey('background', $variantField['choices']);
    }

    public function testHeroFieldsHasConditionalLogic(): void
    {
        $fields = FieldDefinitions::heroFields('hero');

        // Find image field (should be conditional on split variant)
        $imageField = null;
        foreach ($fields as $field) {
            if (($field['name'] ?? '') === 'image') {
                $imageField = $field;
                break;
            }
        }

        $this->assertNotNull($imageField);
        $this->assertArrayHasKey('conditional_logic', $imageField);
    }

    // ==========================================
    // accordionFields() tests
    // ==========================================

    public function testAccordionFieldsContainsRepeater(): void
    {
        $fields = FieldDefinitions::accordionFields('accordion');

        $this->assertCount(2, $fields);

        // First field should be the repeater
        $repeaterField = $fields[0];
        $this->assertSame('repeater', $repeaterField['type']);
        $this->assertSame('accordion', $repeaterField['name']);

        // Check sub-fields
        $subFields = $repeaterField['sub_fields'];
        $this->assertCount(3, $subFields);

        // Should have icon, title, content sub-fields
        $subFieldNames = array_map(fn($f) => $f['name'], $subFields);
        $this->assertContains('icon', $subFieldNames);
        $this->assertContains('title', $subFieldNames);
        $this->assertContains('content', $subFieldNames);
    }

    // ==========================================
    // infoBoxField() tests
    // ==========================================

    public function testInfoBoxFieldSupportsAllTypes(): void
    {
        $infoBox = FieldDefinitions::infoBoxField('field_info', 'Test message', 'info');
        $successBox = FieldDefinitions::infoBoxField('field_success', 'Test message', 'success');
        $warningBox = FieldDefinitions::infoBoxField('field_warning', 'Test message', 'warning');
        $tipBox = FieldDefinitions::infoBoxField('field_tip', 'Test message', 'tip');

        $this->assertSame('message', $infoBox['type']);
        $this->assertStringContainsString('#0073aa', $infoBox['message']); // Info color
        $this->assertStringContainsString('#00a32a', $successBox['message']); // Success color
        $this->assertStringContainsString('#dba617', $warningBox['message']); // Warning color
        $this->assertStringContainsString('#8c5ed5', $tipBox['message']); // Tip color
    }

    // ==========================================
    // Edge cases
    // ==========================================

    public function testFieldsWithDifferentPrefixesHaveUniqueKeys(): void
    {
        $fields1 = FieldDefinitions::ctaBlockFields('prefix_one');
        $fields2 = FieldDefinitions::ctaBlockFields('prefix_two');

        $keys1 = array_map(fn($f) => $f['key'], $fields1);
        $keys2 = array_map(fn($f) => $f['key'], $fields2);

        // No overlap in keys
        $this->assertEmpty(array_intersect($keys1, $keys2));
    }

    public function testAllFieldMethodsReturnArrays(): void
    {
        $this->assertIsArray(FieldDefinitions::backgroundColorField('test'));
        $this->assertIsArray(FieldDefinitions::textField('k', 'l', 'n'));
        $this->assertIsArray(FieldDefinitions::wysiwygField('k', 'l', 'n'));
        $this->assertIsArray(FieldDefinitions::imageField('k', 'l', 'n'));
        $this->assertIsArray(FieldDefinitions::linkField('k', 'l', 'n'));
        $this->assertIsArray(FieldDefinitions::selectField('k', 'l', 'n', []));
        $this->assertIsArray(FieldDefinitions::repeaterField('k', 'l', 'n', []));
        $this->assertIsArray(FieldDefinitions::ctaBlockFields('test'));
        $this->assertIsArray(FieldDefinitions::heroFields('test'));
    }
}
