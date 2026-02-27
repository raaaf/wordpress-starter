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
     * Get layout categories for ACF Extended modal organization
     *
     * @return array<string, string>
     */
    private static function getCategories(): array
    {
        return [
            'header' => __('Header', 'wp-starter'),
            'layout' => __('Layout', 'wp-starter'),
            'content' => __('Inhalte', 'wp-starter'),
            'media' => __('Medien', 'wp-starter'),
            'interactive' => __('Interaktiv', 'wp-starter'),
            'forms' => __('Formulare', 'wp-starter'),
            'posts' => __('Beiträge', 'wp-starter'),
            'misc' => __('Sonstiges', 'wp-starter'),
        ];
    }

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
        self::registerDefaultLayoutFilter();
        self::registerMemberDownloadsVisibilityFilter();
    }

    /**
     * Register filter to hide the member-downloads layout on non-member-area pages
     */
    private static function registerMemberDownloadsVisibilityFilter(): void
    {
        add_filter('acf/load_field/key=field_page_sections', function (array $field): array {
            if (!is_admin()) {
                return $field;
            }

            $postId = absint( wp_unslash( $_GET['post'] ?? $_POST['post_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
            if (!$postId) {
                return $field;
            }

            $isMemberArea = get_field('page_is_member_area', $postId);

            if (!$isMemberArea) {
                $field['layouts'] = array_values(array_filter(
                    $field['layouts'],
                    fn(array $layout) => $layout['name'] !== 'member_downloads'
                ));
            }

            return $field;
        }, 10);
    }

    /**
     * Register filter to prefill new pages with a Hero layout
     */
    private static function registerDefaultLayoutFilter(): void
    {
        add_filter('acf/load_value/key=field_page_sections', function (mixed $value, int $postId, array $field): mixed {
            // Only prefill if value is empty
            if (!empty($value)) {
                return $value;
            }

            // Only apply to pages in the admin
            if (!is_admin() || get_post_type($postId) !== 'page') {
                return $value;
            }

            // Only prefill for new pages (auto-draft status)
            $post = get_post($postId);
            if (!$post || $post->post_status !== 'auto-draft') {
                return $value;
            }

            // Prefill with empty Hero layout
            return [
                [
                    'acf_fc_layout' => 'hero',
                ],
            ];
        }, 10, 3);
    }

    /**
     * Register the main page builder field group
     */
    public static function registerPageBuilderGroup(): void
    {
        acf_add_local_field_group([
            'key' => 'group_page_builder',
            'title' => __('Seiteninhalt', 'wp-starter'),
            'fields' => [
                [
                    'key' => 'field_page_sections',
                    'label' => __('Sektionen', 'wp-starter'),
                    'name' => 'page_sections',
                    'type' => 'flexible_content',
                    'instructions' => __('Baue deine Seite, indem du Inhalts-Sektionen hinzufügst.', 'wp-starter'),
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'layouts' => self::getLayouts(),
                    'button_label' => __('Sektion hinzufügen', 'wp-starter'),
                    'min' => '',
                    'max' => '',

                    // ACF Extended: Modal for selecting layouts
                    'acfe_flexible_modal' => [
                        'acfe_flexible_modal_enabled' => true,
                        'acfe_flexible_modal_title' => __('Sektion auswählen', 'wp-starter'),
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

            // Member area layouts (filtered per-page via acf/load_field)
            self::memberDownloadsLayout(),
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
            'label' => __('Hero-Bereich', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::heroFields('flex_hero'),
            'acfe_flexible_category' => self::getCategories()['header'],
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
            'label' => __('Eine Spalte', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => [
                ...FieldDefinitions::sectionHeaderFields('flex_one_column'),
                FieldDefinitions::wysiwygField(
                    'field_flex_one_column_content',
                    __('Inhalt', 'wp-starter'),
                    'content',
                    true,
                    null,
                    __('Der Textinhalt dieser Sektion.', 'wp-starter')
                ),
                FieldDefinitions::backgroundColorField('flex_one_column'),
            ],
            'acfe_flexible_category' => self::getCategories()['layout'],
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
            'label' => __('Zwei Spalten', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::twoColumnsFields('flex_two_columns'),
            'acfe_flexible_category' => self::getCategories()['layout'],
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
            'label' => __('Zwei Spalten mit Bildern', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::twoColumnsImagesFields('flex_two_columns_images'),
            'acfe_flexible_category' => self::getCategories()['layout'],
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
            'label' => __('Drei Spalten', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::threeColumnsFields('flex_three_columns'),
            'acfe_flexible_category' => self::getCategories()['layout'],
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
            'label' => __('Vier Spalten', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::fourColumnsFields('flex_four_columns'),
            'acfe_flexible_category' => self::getCategories()['layout'],
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
            'label' => __('1/3 + 2/3 Spalten', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::oneThirdTwoThirdsFields('flex_one_third_two_thirds'),
            'acfe_flexible_category' => self::getCategories()['layout'],
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
            'label' => __('2/3 + 1/3 Spalten', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::twoThirdsOneThirdFields('flex_two_thirds_one_third'),
            'acfe_flexible_category' => self::getCategories()['layout'],
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
            'label' => __('Akkordeon (FAQ)', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::accordionFields('flex_accordion'),
            'acfe_flexible_category' => self::getCategories()['content'],
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
            'label' => __('Tabs', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::tabsFields('flex_tabs'),
            'acfe_flexible_category' => self::getCategories()['content'],
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
            'label' => __('Handlungsaufforderung (CTA)', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::ctaFields('flex_cta'),
            'acfe_flexible_category' => self::getCategories()['content'],
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
            'label' => __('Bild', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::imageFields('flex_image'),
            'acfe_flexible_category' => self::getCategories()['media'],
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
            'label' => __('Video', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::videoFields('flex_video'),
            'acfe_flexible_category' => self::getCategories()['media'],
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
            'label' => __('Bildergalerie', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::galleryFields('flex_gallery'),
            'acfe_flexible_category' => self::getCategories()['media'],
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
            'label' => __('Vorher/Nachher Vergleich', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::beforeAfterFields('flex_before_after'),
            'acfe_flexible_category' => self::getCategories()['media'],
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
            'label' => __('Kundenstimmen', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::testimonialsFields('flex_testimonials'),
            'acfe_flexible_category' => self::getCategories()['interactive'],
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
            'label' => __('Karten / Features', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::cardsFields('flex_cards'),
            'acfe_flexible_category' => self::getCategories()['interactive'],
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
            'label' => __('Statistiken / Zahlen', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::statsFields('flex_stats'),
            'acfe_flexible_category' => self::getCategories()['interactive'],
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
            'label' => __('Zeitstrahl', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::timelineFields('flex_timeline'),
            'acfe_flexible_category' => self::getCategories()['interactive'],
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
            'label' => __('Team', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::teamFields('flex_team'),
            'acfe_flexible_category' => self::getCategories()['interactive'],
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
            'label' => __('Preistabelle', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::pricingTableFields('flex_pricing_table'),
            'acfe_flexible_category' => self::getCategories()['interactive'],
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
            'label' => __('Kontaktformular', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::contactFormFields('flex_contact_form'),
            'acfe_flexible_category' => self::getCategories()['forms'],
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
            'label' => __('Karte (Google Maps)', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::mapFields('flex_map'),
            'acfe_flexible_category' => self::getCategories()['forms'],
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
            'label' => __('Beitrags-Liste', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::postsFields('flex_posts'),
            'acfe_flexible_category' => self::getCategories()['posts'],
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
            'label' => __('Tabelle', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::tableFields('flex_table'),
            'acfe_flexible_category' => self::getCategories()['posts'],
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
            'label' => __('Trenner / Abstand', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::dividerFields('flex_divider'),
            'acfe_flexible_category' => self::getCategories()['misc'],
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
            'label' => __('Logo-Slider', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::logoSliderFields('flex_logo_slider'),
            'acfe_flexible_category' => self::getCategories()['misc'],
        ];
    }

    // =========================================================================
    // MEMBER AREA LAYOUTS
    // =========================================================================

    /**
     * Member Downloads layout (only visible on pages with page_is_member_area = true)
     *
     * @return array<string, mixed>
     */
    private static function memberDownloadsLayout(): array
    {
        return [
            'key' => 'layout_member_downloads',
            'name' => 'member_downloads',
            'label' => __('Downloads (Interner Bereich)', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::memberDownloadsFields('flex_member_downloads'),
            'acfe_flexible_category' => self::getCategories()['interactive'],
        ];
    }
}
