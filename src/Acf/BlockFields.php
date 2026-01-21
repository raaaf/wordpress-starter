<?php

declare(strict_types=1);

namespace WordpressStarter\Acf;

/**
 * Registers ACF field groups for all theme blocks
 *
 * Uses shared FieldDefinitions to maintain consistency with FlexibleContent layouts.
 */
class BlockFields
{
    /**
     * Register all block field groups
     */
    public static function register(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        // Call directly since we're already inside acf/init
        self::registerFieldGroups();
    }

    /**
     * Register all field groups
     */
    public static function registerFieldGroups(): void
    {
        // Layout Blocks
        self::registerOneColumnBlock();
        self::registerTwoColumnsBlock();
        self::registerTwoColumnsImagesBlock();
        self::registerThreeColumnsBlock();
        self::registerFourColumnsBlock();
        self::registerOneThirdTwoThirdsBlock();
        self::registerTwoThirdsOneThirdBlock();

        // Content Blocks
        self::registerAccordionBlock();
        self::registerCtaBlock();
        self::registerImageBlock();
        self::registerDividerBlock();
        self::registerVideoBlock();
        self::registerHeroBlock();

        // New Blocks
        self::registerTestimonialsBlock();
        self::registerCardsBlock();
        self::registerGalleryBlock();
        self::registerLogoSliderBlock();
        self::registerMapBlock();
        self::registerTabsBlock();

        // Additional Blocks
        self::registerPricingTableBlock();
        self::registerTeamBlock();
        self::registerStatsBlock();
        self::registerTimelineBlock();
        self::registerPostsBlock();
        self::registerBeforeAfterBlock();
        self::registerTableBlock();

        // Component Blocks
        self::registerButtonBlock();

        // Conditional Blocks (require plugins)
        if (class_exists('WPCF7')) {
            self::registerContactFormBlock();
        }
    }

    /**
     * One Column Block
     */
    private static function registerOneColumnBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_one_column',
            'title' => 'Eine Spalte',
            'fields' => [
                FieldDefinitions::textField(
                    'field_block_one_column_label',
                    'Überschrift-Label',
                    'label',
                    false,
                    'Optionales Label über der Sektion (z.B. "Über uns").',
                    'z.B. Über uns'
                ),
                FieldDefinitions::wysiwygField(
                    'field_block_one_column_content',
                    'Inhalt',
                    'content',
                    true,
                    null,
                    'Der Textinhalt dieser Sektion.'
                ),
                FieldDefinitions::backgroundColorField('block_one_column'),
            ],
            'location' => self::blockLocation('acf/one-column'),
        ]);
    }

    /**
     * Two Columns Block
     */
    private static function registerTwoColumnsBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_two_columns',
            'title' => 'Zwei Spalten',
            'fields' => FieldDefinitions::twoColumnsFields('block_two_columns'),
            'location' => self::blockLocation('acf/two-columns'),
        ]);
    }

    /**
     * Two Columns Images Block
     */
    private static function registerTwoColumnsImagesBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_two_columns_images',
            'title' => 'Zwei Spalten mit Bildern',
            'fields' => FieldDefinitions::twoColumnsImagesFields('block_two_columns_images'),
            'location' => self::blockLocation('acf/two-columns-images'),
        ]);
    }

    /**
     * Three Columns Block
     */
    private static function registerThreeColumnsBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_three_columns',
            'title' => 'Drei Spalten',
            'fields' => FieldDefinitions::threeColumnsFields('block_three_columns'),
            'location' => self::blockLocation('acf/three-columns'),
        ]);
    }

    /**
     * Four Columns Block
     */
    private static function registerFourColumnsBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_four_columns',
            'title' => 'Vier Spalten',
            'fields' => FieldDefinitions::fourColumnsFields('block_four_columns'),
            'location' => self::blockLocation('acf/four-columns'),
        ]);
    }

    /**
     * One Third / Two Thirds Block
     */
    private static function registerOneThirdTwoThirdsBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_one_third_two_thirds',
            'title' => '1/3 + 2/3 Spalten',
            'fields' => FieldDefinitions::oneThirdTwoThirdsFields('block_one_third_two_thirds'),
            'location' => self::blockLocation('acf/one-third-two-thirds'),
        ]);
    }

    /**
     * Two Thirds / One Third Block
     */
    private static function registerTwoThirdsOneThirdBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_two_thirds_one_third',
            'title' => '2/3 + 1/3 Spalten',
            'fields' => FieldDefinitions::twoThirdsOneThirdFields('block_two_thirds_one_third'),
            'location' => self::blockLocation('acf/two-thirds-one-third'),
        ]);
    }

    /**
     * Accordion Block
     */
    private static function registerAccordionBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_accordion',
            'title' => 'Akkordeon (FAQ)',
            'fields' => FieldDefinitions::accordionFields('block_accordion'),
            'location' => self::blockLocation('acf/accordion'),
        ]);
    }

    /**
     * CTA Block
     */
    private static function registerCtaBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_cta',
            'title' => 'Handlungsaufforderung (CTA)',
            'fields' => FieldDefinitions::ctaBlockFields('block_cta'),
            'location' => self::blockLocation('acf/cta'),
        ]);
    }

    /**
     * Image Block
     */
    private static function registerImageBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_image',
            'title' => 'Bild',
            'fields' => FieldDefinitions::imageFields('block_image'),
            'location' => self::blockLocation('acf/image'),
        ]);
    }

    /**
     * Divider Block
     */
    private static function registerDividerBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_divider',
            'title' => 'Trenner / Abstand',
            'fields' => FieldDefinitions::dividerFields('block_divider'),
            'location' => self::blockLocation('acf/divider'),
        ]);
    }

    /**
     * Video Block
     */
    private static function registerVideoBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_video',
            'title' => 'Video',
            'fields' => FieldDefinitions::videoFields('block_video'),
            'location' => self::blockLocation('acf/video'),
        ]);
    }

    /**
     * Hero Block
     */
    private static function registerHeroBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_hero',
            'title' => 'Hero-Bereich',
            'fields' => FieldDefinitions::heroFields('block_hero'),
            'location' => self::blockLocation('acf/hero'),
        ]);
    }

    // =========================================================================
    // NEW BLOCKS
    // =========================================================================

    /**
     * Testimonials Block
     */
    private static function registerTestimonialsBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_testimonials',
            'title' => 'Kundenstimmen',
            'fields' => FieldDefinitions::testimonialsFields('block_testimonials'),
            'location' => self::blockLocation('acf/testimonials'),
        ]);
    }

    /**
     * Cards/Features Block
     */
    private static function registerCardsBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_cards',
            'title' => 'Karten / Features',
            'fields' => FieldDefinitions::cardsFields('block_cards'),
            'location' => self::blockLocation('acf/cards'),
        ]);
    }

    /**
     * Gallery Block
     */
    private static function registerGalleryBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_gallery',
            'title' => 'Galerie',
            'fields' => FieldDefinitions::galleryFields('block_gallery'),
            'location' => self::blockLocation('acf/gallery'),
        ]);
    }

    /**
     * Logo Slider Block
     */
    private static function registerLogoSliderBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_logo_slider',
            'title' => 'Logo-Slider',
            'fields' => FieldDefinitions::logoSliderFields('block_logo_slider'),
            'location' => self::blockLocation('acf/logo-slider'),
        ]);
    }

    /**
     * Contact Form Block
     */
    private static function registerContactFormBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_contact_form',
            'title' => 'Kontaktformular',
            'fields' => FieldDefinitions::contactFormFields('block_contact_form'),
            'location' => self::blockLocation('acf/contact-form'),
        ]);
    }

    /**
     * Map Block
     */
    private static function registerMapBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_map',
            'title' => 'Google Maps',
            'fields' => FieldDefinitions::mapFields('block_map'),
            'location' => self::blockLocation('acf/map'),
        ]);
    }

    /**
     * Tabs Block
     */
    private static function registerTabsBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_tabs',
            'title' => 'Tabs',
            'fields' => FieldDefinitions::tabsFields('block_tabs'),
            'location' => self::blockLocation('acf/tabs'),
        ]);
    }

    // =========================================================================
    // ADDITIONAL BLOCKS
    // =========================================================================

    /**
     * Pricing Table Block
     */
    private static function registerPricingTableBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_pricing_table',
            'title' => 'Preistabelle',
            'fields' => FieldDefinitions::pricingTableFields('block_pricing_table'),
            'location' => self::blockLocation('acf/pricing-table'),
        ]);
    }

    /**
     * Team Members Block
     */
    private static function registerTeamBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_team',
            'title' => 'Team',
            'fields' => FieldDefinitions::teamFields('block_team'),
            'location' => self::blockLocation('acf/team'),
        ]);
    }

    /**
     * Stats/Counter Block
     */
    private static function registerStatsBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_stats',
            'title' => 'Statistiken',
            'fields' => FieldDefinitions::statsFields('block_stats'),
            'location' => self::blockLocation('acf/stats'),
        ]);
    }

    /**
     * Timeline Block
     */
    private static function registerTimelineBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_timeline',
            'title' => 'Zeitstrahl',
            'fields' => FieldDefinitions::timelineFields('block_timeline'),
            'location' => self::blockLocation('acf/timeline'),
        ]);
    }

    /**
     * Blog Posts Block
     */
    private static function registerPostsBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_posts',
            'title' => 'Beiträge',
            'fields' => FieldDefinitions::postsFields('block_posts'),
            'location' => self::blockLocation('acf/posts'),
        ]);
    }

    /**
     * Before/After Slider Block
     */
    private static function registerBeforeAfterBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_before_after',
            'title' => 'Vorher/Nachher',
            'fields' => FieldDefinitions::beforeAfterFields('block_before_after'),
            'location' => self::blockLocation('acf/before-after'),
        ]);
    }

    /**
     * Table Block
     */
    private static function registerTableBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_table',
            'title' => 'Tabelle',
            'fields' => FieldDefinitions::tableFields('block_table'),
            'location' => self::blockLocation('acf/table'),
        ]);
    }

    // =========================================================================
    // COMPONENT BLOCKS
    // =========================================================================

    /**
     * Button Block
     */
    private static function registerButtonBlock(): void
    {
        acf_add_local_field_group([
            'key' => 'group_block_button',
            'title' => 'Button',
            'fields' => FieldDefinitions::buttonFields('block_button'),
            'location' => self::blockLocation('acf/button'),
        ]);
    }

    /**
     * Get block location array
     *
     * @param string $blockName Full block name (e.g., 'acf/hero')
     * @return array<int, array<int, array<string, string>>>
     */
    private static function blockLocation(string $blockName): array
    {
        return [
            [
                [
                    'param' => 'block',
                    'operator' => '==',
                    'value' => $blockName,
                ],
            ],
        ];
    }
}
