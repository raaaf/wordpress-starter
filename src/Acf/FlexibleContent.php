<?php

declare(strict_types=1);

namespace WordpressStarter\Acf;

/**
 * Registers flexible content layouts for the Page Builder template
 */
class FlexibleContent
{
    /**
     * Register flexible content fields
     */
    public static function register(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        add_action('acf/init', [self::class, 'registerPageBuilderGroup']);
    }

    /**
     * Register the main page builder field group
     */
    public static function registerPageBuilderGroup(): void
    {
        acf_add_local_field_group([
            'key' => 'group_page_builder',
            'title' => 'Seiteninhalt',
            'fields' => [
                [
                    'key' => 'field_page_sections',
                    'label' => 'Sektionen',
                    'name' => 'page_sections',
                    'type' => 'flexible_content',
                    'instructions' => 'Baue deine Seite, indem du Inhalts-Sektionen hinzufügst.',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'layouts' => self::getLayouts(),
                    'button_label' => 'Sektion hinzufügen',
                    'min' => '',
                    'max' => '',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'page_template',
                        'operator' => '==',
                        'value' => 'templates/page-flexible.blade.php',
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => ['the_content'],
            'active' => true,
            'description' => '',
        ]);
    }

    /**
     * Get all flexible content layouts
     *
     * @return array<int, array<string, mixed>>
     */
    private static function getLayouts(): array
    {
        return [
            self::heroLayout(),
            self::oneColumnLayout(),
            self::twoColumnsLayout(),
            self::twoColumnsImagesLayout(),
            self::threeColumnsLayout(),
            self::fourColumnsLayout(),
            self::oneThirdTwoThirdsLayout(),
            self::twoThirdsOneThirdLayout(),
            self::accordionLayout(),
            self::ctaLayout(),
            self::videoLayout(),
            self::imageLayout(),
            self::dividerLayout(),
        ];
    }

    /**
     * Hero Section layout
     *
     * @return array<string, mixed>
     */
    private static function heroLayout(): array
    {
        return [
            'key' => 'layout_hero',
            'name' => 'hero',
            'label' => 'Hero-Bereich',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::heroFields('hero'),
        ];
    }

    /**
     * One Column layout
     *
     * @return array<string, mixed>
     */
    private static function oneColumnLayout(): array
    {
        return [
            'key' => 'layout_one_column',
            'name' => 'one_column',
            'label' => 'Eine Spalte',
            'display' => 'block',
            'sub_fields' => [
                FieldDefinitions::wysiwygField(
                    'field_one_column_content',
                    'Inhalt',
                    'content',
                    true,
                    null,
                    'Der Textinhalt dieser Sektion.'
                ),
                FieldDefinitions::backgroundColorField('one_column'),
            ],
        ];
    }

    /**
     * Two Columns layout
     *
     * @return array<string, mixed>
     */
    private static function twoColumnsLayout(): array
    {
        return [
            'key' => 'layout_two_columns',
            'name' => 'two_columns',
            'label' => 'Zwei Spalten',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::twoColumnsFields('two_columns'),
        ];
    }

    /**
     * Two Columns with Images layout
     *
     * @return array<string, mixed>
     */
    private static function twoColumnsImagesLayout(): array
    {
        return [
            'key' => 'layout_two_columns_images',
            'name' => 'two_columns_images',
            'label' => 'Zwei Spalten mit Bildern',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::twoColumnsImagesFields('two_columns_images'),
        ];
    }

    /**
     * Three Columns layout
     *
     * @return array<string, mixed>
     */
    private static function threeColumnsLayout(): array
    {
        return [
            'key' => 'layout_three_columns',
            'name' => 'three_columns',
            'label' => 'Drei Spalten',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::threeColumnsFields('three_columns'),
        ];
    }

    /**
     * Four Columns layout
     *
     * @return array<string, mixed>
     */
    private static function fourColumnsLayout(): array
    {
        return [
            'key' => 'layout_four_columns',
            'name' => 'four_columns',
            'label' => 'Vier Spalten',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::fourColumnsFields('four_columns'),
        ];
    }

    /**
     * One Third / Two Thirds layout
     *
     * @return array<string, mixed>
     */
    private static function oneThirdTwoThirdsLayout(): array
    {
        return [
            'key' => 'layout_one_third_two_thirds',
            'name' => 'one_third_two_thirds',
            'label' => '1/3 + 2/3 Spalten',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::oneThirdTwoThirdsFields('one_third_two_thirds'),
        ];
    }

    /**
     * Two Thirds / One Third layout
     *
     * @return array<string, mixed>
     */
    private static function twoThirdsOneThirdLayout(): array
    {
        return [
            'key' => 'layout_two_thirds_one_third',
            'name' => 'two_thirds_one_third',
            'label' => '2/3 + 1/3 Spalten',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::twoThirdsOneThirdFields('two_thirds_one_third'),
        ];
    }

    /**
     * Accordion layout
     *
     * @return array<string, mixed>
     */
    private static function accordionLayout(): array
    {
        return [
            'key' => 'layout_accordion',
            'name' => 'accordion',
            'label' => 'Akkordeon (FAQ)',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::accordionFields('accordion'),
        ];
    }

    /**
     * CTA (Call to Action) layout
     *
     * @return array<string, mixed>
     */
    private static function ctaLayout(): array
    {
        return [
            'key' => 'layout_cta',
            'name' => 'cta',
            'label' => 'Handlungsaufforderung (CTA)',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::ctaFields('cta'),
        ];
    }

    /**
     * Video layout
     *
     * @return array<string, mixed>
     */
    private static function videoLayout(): array
    {
        return [
            'key' => 'layout_video',
            'name' => 'video',
            'label' => 'Video',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::videoFields('video'),
        ];
    }

    /**
     * Image layout
     *
     * @return array<string, mixed>
     */
    private static function imageLayout(): array
    {
        return [
            'key' => 'layout_image',
            'name' => 'image',
            'label' => 'Bild',
            'display' => 'block',
            'sub_fields' => [
                FieldDefinitions::imageField(
                    'field_image_image',
                    'Bild',
                    'image',
                    true,
                    'array',
                    null,
                    'Wähle oder lade ein Bild hoch.'
                ),
                FieldDefinitions::textField(
                    'field_image_caption',
                    'Bildunterschrift',
                    'caption',
                    false,
                    'Optionale Bildunterschrift.',
                    'z.B. Foto: Max Mustermann'
                ),
                FieldDefinitions::selectField(
                    'field_image_alignment',
                    'Ausrichtung',
                    'alignment',
                    [
                        'left' => 'Links',
                        'center' => 'Zentriert',
                        'right' => 'Rechts',
                        'wide' => 'Breit',
                        'full' => 'Volle Breite',
                    ],
                    'center',
                    false,
                    'Wie soll das Bild ausgerichtet werden?'
                ),
                FieldDefinitions::backgroundColorField('image'),
            ],
        ];
    }

    /**
     * Divider layout
     *
     * @return array<string, mixed>
     */
    private static function dividerLayout(): array
    {
        return [
            'key' => 'layout_divider',
            'name' => 'divider',
            'label' => 'Trenner / Abstand',
            'display' => 'block',
            'sub_fields' => [
                FieldDefinitions::selectField(
                    'field_divider_style',
                    'Stil',
                    'style',
                    [
                        'line' => 'Linie',
                        'dots' => 'Punkte',
                        'wave' => 'Welle',
                        'space' => 'Nur Abstand',
                    ],
                    'line',
                    false,
                    'Wähle den visuellen Stil des Trenners.'
                ),
                FieldDefinitions::numberField(
                    'field_divider_height',
                    'Höhe',
                    'height',
                    50,
                    10,
                    200,
                    10,
                    'px',
                    'Die Höhe des Trenners in Pixeln.'
                ),
                FieldDefinitions::backgroundColorField('divider'),
            ],
        ];
    }
}
