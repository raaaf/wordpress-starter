<?php

declare(strict_types=1);

namespace WordpressStarter\Acf;

/**
 * Registers flexible content layouts for page building
 *
 * All pages use Flexible Content as the primary content builder.
 * Each layout corresponds to a template in templates/flexible/.
 *
 * ACF Extended enhances the editing experience with:
 * - Modal selection for choosing layouts
 * - Modal editing for individual layouts
 * - Copy/paste between pages
 * - Layout categories and thumbnails
 */
class FlexibleContent
{
    /**
     * Layout categories for ACF Extended modal organization
     */
    private const CATEGORIES = [
        'header' => 'Header',
        'layout' => 'Layout',
        'content' => 'Inhalte',
        'media' => 'Medien',
        'interactive' => 'Interaktiv',
        'forms' => 'Formulare',
        'posts' => 'Beiträge',
        'misc' => 'Sonstiges',
    ];

    /**
     * Register flexible content fields
     * Called from acf/init hook in AcfServiceProvider
     */
    public static function register(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        // Register directly - we're already inside acf/init
        self::registerPageBuilderGroup();
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

                    // ACF Extended: Modal for selecting layouts
                    'acfe_flexible_modal' => [
                        'acfe_flexible_modal_enabled' => true,
                        'acfe_flexible_modal_title' => 'Sektion auswählen',
                        'acfe_flexible_modal_col' => '4',
                        'acfe_flexible_modal_categories' => true,
                    ],

                    // ACF Extended: Modal for editing layouts
                    'acfe_flexible_modal_edit' => [
                        'acfe_flexible_modal_edit_enabled' => true,
                        'acfe_flexible_modal_edit_size' => 'large',
                    ],

                    // ACF Extended: Additional features
                    'acfe_flexible_copy_paste' => true,
                    'acfe_flexible_layouts_state' => 'collapse',
                    'acfe_flexible_stylised_button' => true,
                    'acfe_flexible_title_edition' => true,
                    'acfe_flexible_layouts_templates' => false,
                    'acfe_flexible_layouts_previews' => false,
                    'acfe_flexible_hide_empty_message' => false,
                    'acfe_flexible_empty_message' => '',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'page',
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
     * Get all flexible content layouts (28 total)
     *
     * @return array<int, array<string, mixed>>
     */
    private static function getLayouts(): array
    {
        return [
            // Header layouts
            self::heroLayout(),

            // Column-based layout options
            self::oneColumnLayout(),
            self::twoColumnsLayout(),
            self::twoColumnsImagesLayout(),
            self::threeColumnsLayout(),
            self::fourColumnsLayout(),
            self::oneThirdTwoThirdsLayout(),
            self::twoThirdsOneThirdLayout(),

            // Content and text layouts
            self::accordionLayout(),
            self::tabsLayout(),
            self::ctaLayout(),

            // Media display layouts
            self::imageLayout(),
            self::videoLayout(),
            self::galleryLayout(),
            self::beforeAfterLayout(),

            // Interactive element layouts
            self::testimonialsLayout(),
            self::cardsLayout(),
            self::statsLayout(),
            self::timelineLayout(),
            self::teamLayout(),
            self::pricingTableLayout(),

            // Form-related layouts
            self::contactFormLayout(),
            self::mapLayout(),

            // Post and data display layouts
            self::postsLayout(),
            self::tableLayout(),

            // Miscellaneous utility layouts
            self::dividerLayout(),
            self::logoSliderLayout(),
        ];
    }

    // =========================================================================
    // HEADER LAYOUTS
    // =========================================================================

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
            'sub_fields' => FieldDefinitions::heroFields('flex_hero'),
            'acfe_flexible_category' => self::CATEGORIES['header'],
        ];
    }

    // =========================================================================
    // LAYOUT LAYOUTS
    // =========================================================================

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
                    'field_flex_one_column_content',
                    'Inhalt',
                    'content',
                    true,
                    null,
                    'Der Textinhalt dieser Sektion.'
                ),
                FieldDefinitions::backgroundColorField('flex_one_column'),
            ],
            'acfe_flexible_category' => self::CATEGORIES['layout'],
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
            'sub_fields' => FieldDefinitions::twoColumnsFields('flex_two_columns'),
            'acfe_flexible_category' => self::CATEGORIES['layout'],
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
            'sub_fields' => FieldDefinitions::twoColumnsImagesFields('flex_two_columns_images'),
            'acfe_flexible_category' => self::CATEGORIES['layout'],
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
            'sub_fields' => FieldDefinitions::threeColumnsFields('flex_three_columns'),
            'acfe_flexible_category' => self::CATEGORIES['layout'],
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
            'sub_fields' => FieldDefinitions::fourColumnsFields('flex_four_columns'),
            'acfe_flexible_category' => self::CATEGORIES['layout'],
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
            'sub_fields' => FieldDefinitions::oneThirdTwoThirdsFields('flex_one_third_two_thirds'),
            'acfe_flexible_category' => self::CATEGORIES['layout'],
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
            'sub_fields' => FieldDefinitions::twoThirdsOneThirdFields('flex_two_thirds_one_third'),
            'acfe_flexible_category' => self::CATEGORIES['layout'],
        ];
    }

    // =========================================================================
    // CONTENT LAYOUTS
    // =========================================================================

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
            'sub_fields' => FieldDefinitions::accordionFields('flex_accordion'),
            'acfe_flexible_category' => self::CATEGORIES['content'],
        ];
    }

    /**
     * Tabs layout
     *
     * @return array<string, mixed>
     */
    private static function tabsLayout(): array
    {
        return [
            'key' => 'layout_tabs',
            'name' => 'tabs',
            'label' => 'Tabs',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::tabsFields('flex_tabs'),
            'acfe_flexible_category' => self::CATEGORIES['content'],
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
            'sub_fields' => FieldDefinitions::ctaFields('flex_cta'),
            'acfe_flexible_category' => self::CATEGORIES['content'],
        ];
    }

    // =========================================================================
    // MEDIA LAYOUTS
    // =========================================================================

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
            'sub_fields' => FieldDefinitions::imageFields('flex_image'),
            'acfe_flexible_category' => self::CATEGORIES['media'],
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
            'sub_fields' => FieldDefinitions::videoFields('flex_video'),
            'acfe_flexible_category' => self::CATEGORIES['media'],
        ];
    }

    /**
     * Gallery layout
     *
     * @return array<string, mixed>
     */
    private static function galleryLayout(): array
    {
        return [
            'key' => 'layout_gallery',
            'name' => 'gallery',
            'label' => 'Bildergalerie',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::galleryFields('flex_gallery'),
            'acfe_flexible_category' => self::CATEGORIES['media'],
        ];
    }

    /**
     * Before/After Slider layout
     *
     * @return array<string, mixed>
     */
    private static function beforeAfterLayout(): array
    {
        return [
            'key' => 'layout_before_after',
            'name' => 'before_after',
            'label' => 'Vorher/Nachher Vergleich',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::beforeAfterFields('flex_before_after'),
            'acfe_flexible_category' => self::CATEGORIES['media'],
        ];
    }

    // =========================================================================
    // INTERACTIVE LAYOUTS
    // =========================================================================

    /**
     * Testimonials layout
     *
     * @return array<string, mixed>
     */
    private static function testimonialsLayout(): array
    {
        return [
            'key' => 'layout_testimonials',
            'name' => 'testimonials',
            'label' => 'Kundenstimmen',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::testimonialsFields('flex_testimonials'),
            'acfe_flexible_category' => self::CATEGORIES['interactive'],
        ];
    }

    /**
     * Cards/Features layout
     *
     * @return array<string, mixed>
     */
    private static function cardsLayout(): array
    {
        return [
            'key' => 'layout_cards',
            'name' => 'cards',
            'label' => 'Karten / Features',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::cardsFields('flex_cards'),
            'acfe_flexible_category' => self::CATEGORIES['interactive'],
        ];
    }

    /**
     * Stats/Counter layout
     *
     * @return array<string, mixed>
     */
    private static function statsLayout(): array
    {
        return [
            'key' => 'layout_stats',
            'name' => 'stats',
            'label' => 'Statistiken / Zahlen',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::statsFields('flex_stats'),
            'acfe_flexible_category' => self::CATEGORIES['interactive'],
        ];
    }

    /**
     * Timeline layout
     *
     * @return array<string, mixed>
     */
    private static function timelineLayout(): array
    {
        return [
            'key' => 'layout_timeline',
            'name' => 'timeline',
            'label' => 'Zeitstrahl',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::timelineFields('flex_timeline'),
            'acfe_flexible_category' => self::CATEGORIES['interactive'],
        ];
    }

    /**
     * Team Members layout
     *
     * @return array<string, mixed>
     */
    private static function teamLayout(): array
    {
        return [
            'key' => 'layout_team',
            'name' => 'team',
            'label' => 'Team',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::teamFields('flex_team'),
            'acfe_flexible_category' => self::CATEGORIES['interactive'],
        ];
    }

    /**
     * Pricing Table layout
     *
     * @return array<string, mixed>
     */
    private static function pricingTableLayout(): array
    {
        return [
            'key' => 'layout_pricing_table',
            'name' => 'pricing_table',
            'label' => 'Preistabelle',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::pricingTableFields('flex_pricing_table'),
            'acfe_flexible_category' => self::CATEGORIES['interactive'],
        ];
    }

    // =========================================================================
    // FORMS LAYOUTS
    // =========================================================================

    /**
     * Contact Form layout
     *
     * @return array<string, mixed>
     */
    private static function contactFormLayout(): array
    {
        return [
            'key' => 'layout_contact_form',
            'name' => 'contact_form',
            'label' => 'Kontaktformular',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::contactFormFields('flex_contact_form'),
            'acfe_flexible_category' => self::CATEGORIES['forms'],
        ];
    }

    /**
     * Google Maps layout
     *
     * @return array<string, mixed>
     */
    private static function mapLayout(): array
    {
        return [
            'key' => 'layout_map',
            'name' => 'map',
            'label' => 'Karte (Google Maps)',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::mapFields('flex_map'),
            'acfe_flexible_category' => self::CATEGORIES['forms'],
        ];
    }

    // =========================================================================
    // POSTS LAYOUTS
    // =========================================================================

    /**
     * Blog Posts layout
     *
     * @return array<string, mixed>
     */
    private static function postsLayout(): array
    {
        return [
            'key' => 'layout_posts',
            'name' => 'posts',
            'label' => 'Beitrags-Liste',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::postsFields('flex_posts'),
            'acfe_flexible_category' => self::CATEGORIES['posts'],
        ];
    }

    /**
     * Table layout
     *
     * @return array<string, mixed>
     */
    private static function tableLayout(): array
    {
        return [
            'key' => 'layout_table',
            'name' => 'table',
            'label' => 'Tabelle',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::tableFields('flex_table'),
            'acfe_flexible_category' => self::CATEGORIES['posts'],
        ];
    }

    // =========================================================================
    // MISC LAYOUTS
    // =========================================================================

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
            'sub_fields' => FieldDefinitions::dividerFields('flex_divider'),
            'acfe_flexible_category' => self::CATEGORIES['misc'],
        ];
    }

    /**
     * Logo Slider layout
     *
     * @return array<string, mixed>
     */
    private static function logoSliderLayout(): array
    {
        return [
            'key' => 'layout_logo_slider',
            'name' => 'logo_slider',
            'label' => 'Logo-Slider',
            'display' => 'block',
            'sub_fields' => FieldDefinitions::logoSliderFields('flex_logo_slider'),
            'acfe_flexible_category' => self::CATEGORIES['misc'],
        ];
    }
}
