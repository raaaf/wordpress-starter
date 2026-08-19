<?php

declare(strict_types=1);

namespace WordpressStarter\Acf;

use WordpressStarter\Services\StyleguidePage;
use WordpressStarter\ThemeContext;

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
     * Cache for {@see getLayouts()} so repeated calls in one request are free.
     *
     * @var array<int, array<string, mixed>>|null
     */
    private static ?array $layoutCache = null;

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
            'internal' => __('Interner Bereich', 'wp-starter'),
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

            $postId = absint(wp_unslash($_GET['post'] ?? $_POST['post_id'] ?? 0)); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
            if (!$postId) {
                return $field;
            }

            $isMemberArea = get_field('page_is_member_area', $postId);

            if (!$isMemberArea) {
                $field['layouts'] = array_values(array_filter(
                    $field['layouts'],
                    fn (array $layout) => $layout['name'] !== 'member_downloads',
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
        // $postId ist bewusst mixed und nicht int: ACF ruft acf/load_value auch
        // mit Kennungen wie "acfe/flexible/..." auf, etwa wenn ACF Extended eine
        // Layout-Vorschau rendert. Eine int-Signatur wirft dort einen TypeError
        // und legt den kompletten Editier-Screen lahm.
        add_filter('acf/load_value/key=field_page_sections', function (mixed $value, mixed $postId, array $field): mixed {
            // Only prefill if value is empty
            if (!empty($value)) {
                return $value;
            }

            if (!is_numeric($postId)) {
                return $value;
            }

            $postId = (int) $postId;

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
                    'instructions' => self::sectionsInstructions(),
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'layouts' => self::layouts(),
                    'button_label' => __('Sektion hinzufügen', 'wp-starter'),
                    'min' => '',
                    'max' => '',

                    // ACF Extended: Modal for selecting layouts
                    'acfe_flexible_modal' => [
                        'acfe_flexible_modal_enabled' => true,
                        'acfe_flexible_modal_title' => __('Sektion auswählen', 'wp-starter'),
                        'acfe_flexible_modal_col' => '4',
                        'acfe_flexible_modal_categories' => true,
                        'acfe_flexible_modal_search' => true,
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
                    'acfe_flexible_layouts_thumbnails' => true,
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
     * Public accessor for the registered layout definitions.
     *
     * The styleguide field reference reads the definitions this class
     * registers with ACF, so it needs a way in without duplicating
     * getLayouts() or making it public outright.
     *
     * This is also the single place where the layout list can be changed from
     * outside. A derived theme adds, replaces or removes its own layouts here
     * instead of copying getLayouts() and editing one line in it, which is what
     * made this file collide on every starter update.
     *
     * Filter: "<theme_prefix>_flexible_content_layouts", in this theme
     * "wp_starter_flexible_content_layouts".
     *
     * In:  array<int, array<string, mixed>> - the layout definitions, each in
     *      the shape ACF expects (key, name, label, display, sub_fields,
     *      acfe_flexible_category).
     * Out: the same shape. Anything that is not an array is dropped, and the
     *      list is reindexed.
     *
     * The order of the array is the order of the tiles in the ACF selection
     * modal, so a layout appended at the end shows up last in its category.
     *
     * A filter, not an overridable method: derived themes copy this class into
     * their own namespace, they do not extend it, so there is nothing to
     * override.
     *
     * Example in a derived theme, registered before "acf/init" runs:
     *
     *     add_filter('goldene_strategie_flexible_content_layouts', function (array $layouts): array {
     *         $layouts[] = self::preciousMetalsLayout();
     *
     *         return $layouts;
     *     });
     *
     * @return array<int, array<string, mixed>>
     */
    public static function layouts(): array
    {
        if (self::$layoutCache === null) {
            // Filter before caching: whoever calls first would otherwise freeze
            // the unfiltered list for the rest of the request.
            $filtered = apply_filters(
                ThemeContext::prefix() . '_flexible_content_layouts',
                self::getLayouts()
            );

            self::$layoutCache = is_array($filtered)
                ? array_values(array_filter($filtered, 'is_array'))
                : self::getLayouts();
        }

        return self::$layoutCache;
    }

    /**
     * Drop the cached layout list. Only needed where one request registers the
     * field group more than once, which in practice means the test suite.
     */
    public static function resetLayoutCache(): void
    {
        self::$layoutCache = null;
    }

    /**
     * Get all flexible content layouts (32 total)
     *
     * @return array<int, array<string, mixed>>
     */
    private static function getLayouts(): array
    {
        return [
            // Header layouts
            self::heroLayout(),

            // Column-based layout options.
            // Reihenfolge: erst die reinen Spaltenlayouts nach Spaltenzahl, dann
            // dieselbe Reihe noch einmal mit Bild. Vorher wechselten sich beide
            // Varianten ab, was das Scannen von zehn aehnlichen Kacheln im
            // Auswahlmodal unnoetig schwer machte.
            self::oneColumnLayout(),
            self::twoColumnsLayout(),
            self::threeColumnsLayout(),
            self::fourColumnsLayout(),
            self::oneThirdTwoThirdsLayout(),
            self::twoThirdsOneThirdLayout(),
            self::oneColumnImageLayout(),
            self::twoColumnsImagesLayout(),
            self::threeColumnsImagesLayout(),
            self::fourColumnsImagesLayout(),

            // Content and text layouts
            self::accordionLayout(),
            self::tabsLayout(),
            self::ctaLayout(),
            self::buttonLayout(),

            // Media display layouts
            self::imageLayout(),
            self::videoLayout(),
            self::galleryLayout(),
            self::beforeAfterLayout(),

            // Interactive element layouts
            self::testimonialsLayout(),
            self::quoteLayout(),
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
            self::alertLayout(),
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
            'acfe_flexible_thumbnail' => 'hero.png',
            // Ein Hero je Seite. Mehrere ergeben mehrere Seitenaufmacher und,
            // je nach Variante, mehrere h1.
            'max' => 1,
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
                    __('Der Textinhalt dieser Sektion.', 'wp-starter'),
                ),
                FieldDefinitions::backgroundColorField('flex_one_column'),
                FieldDefinitions::sectionSpacingField('flex_one_column'),
                FieldDefinitions::sectionAnchorField('flex_one_column'),
            ],
            'acfe_flexible_category' => self::getCategories()['layout'],
            'acfe_flexible_thumbnail' => 'one_column.png',
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
            'acfe_flexible_thumbnail' => 'two_columns.png',
        ];
    }

    /**
     * One Column with Image layout
     *
     * @return array<string, mixed>
     */
    private static function oneColumnImageLayout(): array
    {
        return [
            'key' => 'layout_one_column_image',
            'name' => 'one_column_image',
            'label' => __('Eine Spalte mit Bild', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::oneColumnImageFields('flex_one_column_image'),
            'acfe_flexible_category' => self::getCategories()['layout'],
            'acfe_flexible_thumbnail' => 'one_column_image.png',
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
            'acfe_flexible_thumbnail' => 'two_columns_images.png',
        ];
    }

    /**
     * Three Columns with Images layout
     *
     * @return array<string, mixed>
     */
    private static function threeColumnsImagesLayout(): array
    {
        return [
            'key' => 'layout_three_columns_images',
            'name' => 'three_columns_images',
            'label' => __('Drei Spalten mit Bildern', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::threeColumnsImagesFields('flex_three_columns_images'),
            'acfe_flexible_category' => self::getCategories()['layout'],
            'acfe_flexible_thumbnail' => 'three_columns_images.png',
        ];
    }

    /**
     * Four Columns with Images layout
     *
     * @return array<string, mixed>
     */
    private static function fourColumnsImagesLayout(): array
    {
        return [
            'key' => 'layout_four_columns_images',
            'name' => 'four_columns_images',
            'label' => __('Vier Spalten mit Bildern', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::fourColumnsImagesFields('flex_four_columns_images'),
            'acfe_flexible_category' => self::getCategories()['layout'],
            'acfe_flexible_thumbnail' => 'four_columns_images.png',
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
            'acfe_flexible_thumbnail' => 'three_columns.png',
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
            'acfe_flexible_thumbnail' => 'four_columns.png',
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
            'acfe_flexible_thumbnail' => 'one_third_two_thirds.png',
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
            'acfe_flexible_thumbnail' => 'two_thirds_one_third.png',
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
            'acfe_flexible_thumbnail' => 'accordion.png',
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
            'acfe_flexible_thumbnail' => 'tabs.png',
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
            'acfe_flexible_thumbnail' => 'cta.png',
        ];
    }

    /**
     * Button layout
     *
     * @return array<string, mixed>
     */
    private static function buttonLayout(): array
    {
        return [
            'key' => 'layout_button',
            'name' => 'button',
            'label' => __('Button', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::buttonFields('flex_button'),
            'acfe_flexible_category' => self::getCategories()['content'],
            'acfe_flexible_thumbnail' => 'button.png',
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
            'acfe_flexible_thumbnail' => 'image.png',
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
            'acfe_flexible_thumbnail' => 'video.png',
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
            'acfe_flexible_thumbnail' => 'gallery.png',
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
            'acfe_flexible_thumbnail' => 'before_after.png',
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
            'acfe_flexible_thumbnail' => 'testimonials.png',
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
            'acfe_flexible_thumbnail' => 'cards.png',
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
            'acfe_flexible_thumbnail' => 'stats.png',
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
            'acfe_flexible_thumbnail' => 'timeline.png',
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
            'acfe_flexible_thumbnail' => 'team.png',
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
            'acfe_flexible_thumbnail' => 'pricing_table.png',
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
            'acfe_flexible_thumbnail' => 'contact_form.png',
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
            'acfe_flexible_thumbnail' => 'map.png',
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
            'acfe_flexible_thumbnail' => 'posts.png',
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
            'acfe_flexible_thumbnail' => 'table.png',
        ];
    }

    // =========================================================================
    // MISC LAYOUTS
    // =========================================================================

    /**
     * Pull quote layout
     *
     * @return array<string, mixed>
     */
    private static function quoteLayout(): array
    {
        return [
            'key' => 'layout_quote',
            'name' => 'quote',
            'label' => __('Einzelzitat', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::quoteFields('flex_quote'),
            'acfe_flexible_category' => self::getCategories()['content'],
        ];
    }

    /**
     * Alert (notice) layout
     *
     * @return array<string, mixed>
     */
    private static function alertLayout(): array
    {
        return [
            'key' => 'layout_alert',
            'name' => 'alert',
            'label' => __('Hinweis', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::alertFields('flex_alert'),
            'acfe_flexible_category' => self::getCategories()['content'],
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
            'label' => __('Trenner / Abstand', 'wp-starter'),
            'display' => 'block',
            'sub_fields' => FieldDefinitions::dividerFields('flex_divider'),
            'acfe_flexible_category' => self::getCategories()['misc'],
            'acfe_flexible_thumbnail' => 'divider.png',
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
            'acfe_flexible_thumbnail' => 'logo_slider.png',
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
            'acfe_flexible_category' => self::getCategories()['internal'],
            'acfe_flexible_thumbnail' => 'member_downloads.png',
        ];
    }

    /**
     * Hinweistext am Sektionsfeld.
     *
     * Verlinkt die Styleguide-Seite, sofern es eine gibt. Ohne diesen Link muss
     * ein Redakteur wissen, dass es sie ueberhaupt gibt, um ein Layout vor der
     * Auswahl anzusehen.
     */
    private static function sectionsInstructions(): string
    {
        $text = __('Bau die Seite aus Inhalts-Sektionen zusammen.', 'wp-starter');

        if (!class_exists(StyleguidePage::class)) {
            return $text;
        }

        // Nur auf echten Redaktions-Screens: is_admin() ist auf
        // /wp-admin/admin-ajax.php auch fuer anonyme Requests wahr, acf/init
        // feuert dort ebenfalls, also schliesst is_admin() allein den
        // Ajax-Vektor nicht. wp_doing_ajax() schliesst den anonymen
        // Ajax-Vektor, current_user_can('edit_posts') stellt sicher, dass
        // StyleguidePage::find() (adopt() schreibt Post-Meta/Options) nur
        // fuer Redakteure aufgeloest wird, nicht manage_options, weil der
        // Hinweis im Seiteneditor auch fuer Redakteure sichtbar sein soll.
        if (!is_admin() || wp_doing_ajax() || !current_user_can('edit_posts')) {
            return $text;
        }

        $pageId = StyleguidePage::find();
        $url = $pageId > 0 ? get_permalink($pageId) : '';
        if (!$url) {
            return $text;
        }

        return $text . ' ' . sprintf(
            /* translators: %s: URL of the styleguide page */
            __('Alle Layouts mit ihren Varianten zeigt der <a href="%s" target="_blank" rel="noopener">Styleguide</a>.', 'wp-starter'),
            esc_url($url),
        );
    }
}
