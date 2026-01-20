<?php

declare(strict_types=1);

namespace WordpressStarter\Acf;

/**
 * Shared ACF field definitions used by both FlexibleContent and BlockFields
 *
 * This class provides reusable field configurations to eliminate code duplication
 * between flexible content layouts and Gutenberg blocks.
 *
 * All labels are in German for better user experience in German-speaking projects.
 */
class FieldDefinitions
{
    /**
     * Background color choices that map to design tokens
     */
    public const BACKGROUND_COLORS = [
        'primary' => 'Standard (Weiß)',
        'secondary' => 'Sekundär (Hellgrau)',
        'tertiary' => 'Tertiär',
        'brand' => 'Markenfarbe',
        'brand-subtle' => 'Markenfarbe Dezent',
        'inverse' => 'Dunkel (Invers)',
    ];

    /**
     * Get background color field definition
     *
     * @param string $prefix Unique prefix for the field key
     * @return array<string, mixed>
     */
    public static function backgroundColorField(string $prefix): array
    {
        return [
            'key' => "field_{$prefix}_background_color",
            'label' => 'Hintergrundfarbe',
            'name' => 'background_color',
            'type' => 'select',
            'instructions' => 'Wähle die Hintergrundfarbe für diesen Abschnitt.',
            'choices' => self::BACKGROUND_COLORS,
            'default_value' => 'primary',
            'allow_null' => 0,
            'ui' => 1,
        ];
    }

    /**
     * Get WYSIWYG content field definition
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param bool $required Whether field is required
     * @param string|null $width Wrapper width percentage
     * @param string $instructions Field instructions
     * @return array<string, mixed>
     */
    public static function wysiwygField(
        string $key,
        string $label,
        string $name,
        bool $required = true,
        ?string $width = null,
        string $instructions = ''
    ): array {
        $field = [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'wysiwyg',
            'instructions' => $instructions,
            'required' => $required ? 1 : 0,
            'tabs' => 'all',
            'toolbar' => 'full',
            'media_upload' => 1,
        ];

        if ($width !== null) {
            $field['wrapper'] = ['width' => $width];
        }

        return $field;
    }

    /**
     * Get text field definition
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param bool $required Whether field is required
     * @param string $instructions Field instructions
     * @param string $placeholder Placeholder text
     * @return array<string, mixed>
     */
    public static function textField(
        string $key,
        string $label,
        string $name,
        bool $required = false,
        string $instructions = '',
        string $placeholder = ''
    ): array {
        $field = [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'text',
            'instructions' => $instructions,
            'required' => $required ? 1 : 0,
        ];

        if ($placeholder !== '') {
            $field['placeholder'] = $placeholder;
        }

        return $field;
    }

    /**
     * Get image field definition
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param bool $required Whether field is required
     * @param string $returnFormat Return format (id, array, url)
     * @param string|null $width Wrapper width percentage
     * @param string $instructions Field instructions
     * @return array<string, mixed>
     */
    public static function imageField(
        string $key,
        string $label,
        string $name,
        bool $required = false,
        string $returnFormat = 'array',
        ?string $width = null,
        string $instructions = ''
    ): array {
        $field = [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'image',
            'instructions' => $instructions,
            'required' => $required ? 1 : 0,
            'return_format' => $returnFormat,
            'preview_size' => 'medium',
            'library' => 'all',
        ];

        if ($width !== null) {
            $field['wrapper'] = ['width' => $width];
        }

        return $field;
    }

    /**
     * Get link field definition
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param bool $required Whether field is required
     * @param string $instructions Field instructions
     * @return array<string, mixed>
     */
    public static function linkField(
        string $key,
        string $label,
        string $name,
        bool $required = false,
        string $instructions = ''
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'link',
            'instructions' => $instructions,
            'required' => $required ? 1 : 0,
            'return_format' => 'array',
        ];
    }

    /**
     * Get URL field definition
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param string $instructions Field instructions
     * @param array<int, array<int, array<string, string>>>|null $conditionalLogic Conditional logic
     * @param string $placeholder Placeholder text
     * @return array<string, mixed>
     */
    public static function urlField(
        string $key,
        string $label,
        string $name,
        string $instructions = '',
        ?array $conditionalLogic = null,
        string $placeholder = ''
    ): array {
        $field = [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'url',
            'instructions' => $instructions,
        ];

        if ($placeholder !== '') {
            $field['placeholder'] = $placeholder;
        }

        if ($conditionalLogic !== null) {
            $field['conditional_logic'] = $conditionalLogic;
        }

        return $field;
    }

    /**
     * Get select field definition
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param array<int|string, string> $choices Select choices (keys can be numeric or string)
     * @param string $defaultValue Default value
     * @param bool $required Whether field is required
     * @param string $instructions Field instructions
     * @return array<string, mixed>
     */
    public static function selectField(
        string $key,
        string $label,
        string $name,
        array $choices,
        string $defaultValue = '',
        bool $required = false,
        string $instructions = ''
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'select',
            'instructions' => $instructions,
            'required' => $required ? 1 : 0,
            'choices' => $choices,
            'default_value' => $defaultValue,
            'ui' => 1,
        ];
    }

    /**
     * Get file field definition
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param string $mimeTypes Allowed mime types
     * @param string $returnFormat Return format (array, url, id)
     * @param array<int, array<int, array<string, string>>>|null $conditionalLogic Conditional logic
     * @param string $instructions Field instructions
     * @return array<string, mixed>
     */
    public static function fileField(
        string $key,
        string $label,
        string $name,
        string $mimeTypes = '',
        string $returnFormat = 'array',
        ?array $conditionalLogic = null,
        string $instructions = ''
    ): array {
        $field = [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'file',
            'instructions' => $instructions,
            'return_format' => $returnFormat,
            'library' => 'all',
        ];

        if ($mimeTypes !== '') {
            $field['mime_types'] = $mimeTypes;
        }

        if ($conditionalLogic !== null) {
            $field['conditional_logic'] = $conditionalLogic;
        }

        return $field;
    }

    /**
     * Get true/false field definition
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param bool $defaultValue Default value
     * @param string $instructions Field instructions
     * @return array<string, mixed>
     */
    public static function trueFalseField(
        string $key,
        string $label,
        string $name,
        bool $defaultValue = false,
        string $instructions = ''
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'true_false',
            'instructions' => $instructions,
            'default_value' => $defaultValue ? 1 : 0,
            'ui' => 1,
        ];
    }

    /**
     * Get number field definition
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param int $defaultValue Default value
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @param int $step Step increment
     * @param string $append Text to append (e.g., 'px')
     * @param string $instructions Field instructions
     * @return array<string, mixed>
     */
    public static function numberField(
        string $key,
        string $label,
        string $name,
        int $defaultValue = 0,
        int $min = 0,
        int $max = 100,
        int $step = 1,
        string $append = '',
        string $instructions = ''
    ): array {
        $field = [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'number',
            'instructions' => $instructions,
            'default_value' => $defaultValue,
            'min' => $min,
            'max' => $max,
            'step' => $step,
        ];

        if ($append !== '') {
            $field['append'] = $append;
        }

        return $field;
    }

    /**
     * Get textarea field definition
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param int $rows Number of rows
     * @param string $instructions Field instructions
     * @param string $placeholder Placeholder text
     * @return array<string, mixed>
     */
    public static function textareaField(
        string $key,
        string $label,
        string $name,
        int $rows = 4,
        string $instructions = '',
        string $placeholder = ''
    ): array {
        $field = [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'textarea',
            'instructions' => $instructions,
            'rows' => $rows,
        ];

        if ($placeholder !== '') {
            $field['placeholder'] = $placeholder;
        }

        return $field;
    }

    /**
     * Get repeater field definition
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param array<int, array<string, mixed>> $subFields Sub-fields configuration
     * @param string $buttonLabel Add row button label
     * @param int $min Minimum rows
     * @param string $layout Layout style (table, block, row)
     * @param string $instructions Field instructions
     * @return array<string, mixed>
     */
    public static function repeaterField(
        string $key,
        string $label,
        string $name,
        array $subFields,
        string $buttonLabel = 'Eintrag hinzufügen',
        int $min = 0,
        string $layout = 'block',
        string $instructions = ''
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'repeater',
            'instructions' => $instructions,
            'required' => $min > 0 ? 1 : 0,
            'min' => $min,
            'layout' => $layout,
            'button_label' => $buttonLabel,
            'sub_fields' => $subFields,
        ];
    }

    /**
     * Get email field definition
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param string $instructions Field instructions
     * @param string $placeholder Placeholder text
     * @return array<string, mixed>
     */
    public static function emailField(
        string $key,
        string $label,
        string $name,
        string $instructions = '',
        string $placeholder = ''
    ): array {
        $field = [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'email',
            'instructions' => $instructions,
        ];

        if ($placeholder !== '') {
            $field['placeholder'] = $placeholder;
        }

        return $field;
    }

    /**
     * Get post object field definition (for selecting pages/posts)
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param array<int, string> $postTypes Post types to query
     * @param string $instructions Field instructions
     * @return array<string, mixed>
     */
    public static function postObjectField(
        string $key,
        string $label,
        string $name,
        array $postTypes = ['page'],
        string $instructions = ''
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'post_object',
            'instructions' => $instructions,
            'post_type' => $postTypes,
            'return_format' => 'id',
            'ui' => 1,
        ];
    }

    // =========================================================================
    // LAYOUT FIELD SETS - With clear German labels and descriptions
    // =========================================================================

    /**
     * Get Hero layout fields
     *
     * @param string $prefix Key prefix (e.g., 'hero' or 'block_hero')
     * @return array<int, array<string, mixed>>
     */
    public static function heroFields(string $prefix): array
    {
        return [
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                true,
                'Die Hauptüberschrift des Hero-Bereichs.',
                'z.B. Willkommen bei...'
            ),
            self::textField(
                "field_{$prefix}_subtitle",
                'Unterüberschrift',
                'subtitle',
                false,
                'Optionale Unterüberschrift oder Slogan.',
                'z.B. Ihr Partner für...'
            ),
            self::wysiwygField(
                "field_{$prefix}_content",
                'Inhalt',
                'content',
                false,
                null,
                'Optionaler Fließtext unter der Überschrift.'
            ),
            self::imageField(
                "field_{$prefix}_background_image",
                'Hintergrundbild',
                'background_image',
                false,
                'array',
                null,
                'Empfohlene Größe: mindestens 1920x800 Pixel.'
            ),
            self::linkField(
                "field_{$prefix}_cta",
                'Call-to-Action Button',
                'cta',
                false,
                'Link und Text für den Haupt-Button.'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Two Columns layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function twoColumnsFields(string $prefix): array
    {
        return [
            self::wysiwygField(
                "field_{$prefix}_column_1",
                'Spalte 1 (links)',
                'column_1',
                true,
                '50',
                'Inhalt der linken Spalte (50% Breite).'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_2",
                'Spalte 2 (rechts)',
                'column_2',
                true,
                '50',
                'Inhalt der rechten Spalte (50% Breite).'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Three Columns layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function threeColumnsFields(string $prefix): array
    {
        return [
            self::wysiwygField(
                "field_{$prefix}_column_1",
                'Spalte 1',
                'column_1',
                true,
                '33.333',
                'Inhalt der ersten Spalte (1/3 Breite).'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_2",
                'Spalte 2',
                'column_2',
                true,
                '33.333',
                'Inhalt der mittleren Spalte (1/3 Breite).'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_3",
                'Spalte 3',
                'column_3',
                true,
                '33.333',
                'Inhalt der dritten Spalte (1/3 Breite).'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Four Columns layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function fourColumnsFields(string $prefix): array
    {
        return [
            self::wysiwygField(
                "field_{$prefix}_column_1",
                'Spalte 1',
                'column_1',
                true,
                '25',
                'Inhalt der ersten Spalte (1/4 Breite).'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_2",
                'Spalte 2',
                'column_2',
                true,
                '25',
                'Inhalt der zweiten Spalte (1/4 Breite).'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_3",
                'Spalte 3',
                'column_3',
                true,
                '25',
                'Inhalt der dritten Spalte (1/4 Breite).'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_4",
                'Spalte 4',
                'column_4',
                true,
                '25',
                'Inhalt der vierten Spalte (1/4 Breite).'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Accordion layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function accordionFields(string $prefix): array
    {
        return [
            self::repeaterField(
                "field_{$prefix}_accordion",
                'Accordion-Einträge',
                'accordion',
                [
                    self::textField(
                        "field_{$prefix}_accordion_title",
                        'Titel',
                        'title',
                        true,
                        'Der klickbare Titel des Accordion-Elements.',
                        'z.B. Wie funktioniert...?'
                    ),
                    self::wysiwygField(
                        "field_{$prefix}_accordion_content",
                        'Inhalt',
                        'content',
                        true,
                        null,
                        'Der ausgeklappte Inhalt des Accordion-Elements.'
                    ),
                ],
                'Eintrag hinzufügen',
                1,
                'block',
                'Füge beliebig viele auf- und zuklappbare Elemente hinzu.'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get CTA layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function ctaFields(string $prefix): array
    {
        return [
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                true,
                'Die Hauptüberschrift des Call-to-Action Bereichs.',
                'z.B. Jetzt starten!'
            ),
            self::textareaField(
                "field_{$prefix}_content",
                'Beschreibung',
                'content',
                3,
                'Kurzer Text, der zum Handeln auffordert.',
                'z.B. Kontaktieren Sie uns für ein unverbindliches Angebot.'
            ),
            self::linkField(
                "field_{$prefix}_button",
                'Button',
                'button',
                true,
                'Der Call-to-Action Button mit Link und Text.'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Video layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function videoFields(string $prefix): array
    {
        return [
            self::selectField(
                "field_{$prefix}_source",
                'Video-Quelle',
                'source',
                [
                    'wordpress' => 'Mediathek (selbst gehostet)',
                    'external' => 'YouTube / Vimeo',
                ],
                'wordpress',
                true,
                'Wähle, woher das Video kommt.'
            ),
            self::fileField(
                "field_{$prefix}_video",
                'Video-Datei',
                'video',
                'mp4,webm,ogg',
                'url',
                [[['field' => "field_{$prefix}_source", 'operator' => '==', 'value' => 'wordpress']]],
                'Lade eine MP4, WebM oder OGG Datei hoch.'
            ),
            self::urlField(
                "field_{$prefix}_video_url",
                'Video-URL',
                'video_url',
                'Füge die YouTube oder Vimeo URL ein.',
                [[['field' => "field_{$prefix}_source", 'operator' => '==', 'value' => 'external']]],
                'https://www.youtube.com/watch?v=...'
            ),
            self::imageField(
                "field_{$prefix}_poster",
                'Vorschaubild',
                'poster',
                false,
                'array',
                null,
                'Wird angezeigt, bevor das Video geladen wird.'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Image layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function imageFields(string $prefix): array
    {
        return [
            self::imageField(
                "field_{$prefix}_image",
                'Bild',
                'image',
                true,
                'id',
                null,
                'Das anzuzeigende Bild.'
            ),
            self::trueFalseField(
                "field_{$prefix}_show_border",
                'Rahmen anzeigen',
                'show_border',
                true,
                'Zeigt einen dezenten Rahmen um das Bild.'
            ),
            self::trueFalseField(
                "field_{$prefix}_show_caption",
                'Bildunterschrift anzeigen',
                'show_caption',
                true,
                'Zeigt die in der Mediathek hinterlegte Bildunterschrift.'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Divider layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function dividerFields(string $prefix): array
    {
        return [
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get One Third / Two Thirds layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function oneThirdTwoThirdsFields(string $prefix): array
    {
        return [
            self::wysiwygField(
                "field_{$prefix}_column_1",
                'Linke Spalte (schmal)',
                'column_1',
                true,
                '33.333',
                'Inhalt der schmalen linken Spalte (ca. 1/3 der Breite).'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_2",
                'Rechte Spalte (breit)',
                'column_2',
                true,
                '66.667',
                'Inhalt der breiten rechten Spalte (ca. 2/3 der Breite).'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Two Thirds / One Third layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function twoThirdsOneThirdFields(string $prefix): array
    {
        return [
            self::wysiwygField(
                "field_{$prefix}_column_1",
                'Linke Spalte (breit)',
                'column_1',
                true,
                '66.667',
                'Inhalt der breiten linken Spalte (ca. 2/3 der Breite).'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_2",
                'Rechte Spalte (schmal)',
                'column_2',
                true,
                '33.333',
                'Inhalt der schmalen rechten Spalte (ca. 1/3 der Breite).'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Two Columns with Images layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function twoColumnsImagesFields(string $prefix): array
    {
        return [
            self::imageField(
                "field_{$prefix}_image_1",
                'Bild 1',
                'image_1',
                true,
                'id',
                '50',
                'Bild für die linke Karte.'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_1",
                'Inhalt 1',
                'column_1',
                false,
                '50',
                'Text unter dem ersten Bild.'
            ),
            self::imageField(
                "field_{$prefix}_image_2",
                'Bild 2',
                'image_2',
                true,
                'id',
                '50',
                'Bild für die rechte Karte.'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_2",
                'Inhalt 2',
                'column_2',
                false,
                '50',
                'Text unter dem zweiten Bild.'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    // =========================================================================
    // NEW BLOCKS - Testimonials, Cards, Gallery, Logo-Slider, Contact, Map, Tabs
    // =========================================================================

    /**
     * Get Testimonials layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function testimonialsFields(string $prefix): array
    {
        return [
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                false,
                'Optionale Überschrift über den Testimonials.',
                'z.B. Das sagen unsere Kunden'
            ),
            self::repeaterField(
                "field_{$prefix}_testimonials",
                'Kundenstimmen',
                'testimonials',
                [
                    self::textareaField(
                        "field_{$prefix}_testimonial_quote",
                        'Zitat',
                        'quote',
                        3,
                        'Das Zitat oder die Bewertung des Kunden.',
                        'z.B. Die Zusammenarbeit war hervorragend...'
                    ),
                    self::textField(
                        "field_{$prefix}_testimonial_author",
                        'Name',
                        'author',
                        true,
                        'Name der Person.',
                        'z.B. Max Mustermann'
                    ),
                    self::textField(
                        "field_{$prefix}_testimonial_role",
                        'Position / Firma',
                        'role',
                        false,
                        'Position oder Firmenname.',
                        'z.B. Geschäftsführer, Musterfirma GmbH'
                    ),
                    self::imageField(
                        "field_{$prefix}_testimonial_image",
                        'Foto',
                        'image',
                        false,
                        'id',
                        null,
                        'Optionales Foto der Person.'
                    ),
                ],
                'Kundenstimme hinzufügen',
                1,
                'block',
                'Füge Kundenstimmen und Bewertungen hinzu.'
            ),
            self::selectField(
                "field_{$prefix}_columns",
                'Spalten',
                'columns',
                [
                    '1' => '1 Spalte',
                    '2' => '2 Spalten',
                    '3' => '3 Spalten',
                ],
                '3',
                false,
                'Anzahl der Spalten für die Darstellung.'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Cards/Features layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function cardsFields(string $prefix): array
    {
        return [
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                false,
                'Optionale Überschrift über den Karten.',
                'z.B. Unsere Leistungen'
            ),
            self::repeaterField(
                "field_{$prefix}_cards",
                'Karten',
                'cards',
                [
                    self::imageField(
                        "field_{$prefix}_card_icon",
                        'Icon',
                        'icon',
                        false,
                        'id',
                        null,
                        'Icon oder kleines Bild für die Karte.'
                    ),
                    self::textField(
                        "field_{$prefix}_card_title",
                        'Titel',
                        'title',
                        true,
                        'Titel der Karte.',
                        'z.B. Beratung'
                    ),
                    self::textareaField(
                        "field_{$prefix}_card_content",
                        'Beschreibung',
                        'content',
                        3,
                        'Kurze Beschreibung.',
                        'z.B. Wir beraten Sie umfassend...'
                    ),
                    self::linkField(
                        "field_{$prefix}_card_link",
                        'Link',
                        'link',
                        false,
                        'Optionaler Link zu mehr Informationen.'
                    ),
                ],
                'Karte hinzufügen',
                1,
                'block',
                'Füge beliebig viele Karten hinzu.'
            ),
            self::selectField(
                "field_{$prefix}_columns",
                'Spalten',
                'columns',
                [
                    '2' => '2 Spalten',
                    '3' => '3 Spalten',
                    '4' => '4 Spalten',
                ],
                '3',
                false,
                'Anzahl der Spalten für die Darstellung.'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Gallery layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function galleryFields(string $prefix): array
    {
        return [
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                false,
                'Optionale Überschrift über der Galerie.',
                'z.B. Unsere Projekte'
            ),
            [
                'key' => "field_{$prefix}_images",
                'label' => 'Bilder',
                'name' => 'images',
                'type' => 'gallery',
                'instructions' => 'Wähle die Bilder für die Galerie. Klick auf ein Bild öffnet die Lightbox.',
                'required' => 1,
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
                'min' => 1,
            ],
            self::selectField(
                "field_{$prefix}_columns",
                'Spalten',
                'columns',
                [
                    '2' => '2 Spalten',
                    '3' => '3 Spalten',
                    '4' => '4 Spalten',
                    '5' => '5 Spalten',
                ],
                '3',
                false,
                'Anzahl der Spalten für die Darstellung.'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Logo Slider layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function logoSliderFields(string $prefix): array
    {
        return [
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                false,
                'Optionale Überschrift über dem Logo-Slider.',
                'z.B. Unsere Partner'
            ),
            self::repeaterField(
                "field_{$prefix}_logos",
                'Logos',
                'logos',
                [
                    self::imageField(
                        "field_{$prefix}_logo_image",
                        'Logo',
                        'logo',
                        true,
                        'id',
                        null,
                        'Das Logo (idealerweise mit transparentem Hintergrund).'
                    ),
                    self::textField(
                        "field_{$prefix}_logo_name",
                        'Name',
                        'name',
                        false,
                        'Name des Partners (für Barrierefreiheit).',
                        'z.B. Musterfirma GmbH'
                    ),
                    self::urlField(
                        "field_{$prefix}_logo_link",
                        'Website',
                        'link',
                        'Optionaler Link zur Partner-Website.',
                        null,
                        'https://...'
                    ),
                ],
                'Logo hinzufügen',
                1,
                'table',
                'Füge Partner- oder Kundenlogos hinzu.'
            ),
            self::trueFalseField(
                "field_{$prefix}_autoplay",
                'Automatisch abspielen',
                'autoplay',
                true,
                'Logos automatisch durchlaufen lassen.'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Contact Form layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function contactFormFields(string $prefix): array
    {
        return [
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                false,
                'Überschrift für den Kontaktbereich.',
                'z.B. Kontaktieren Sie uns'
            ),
            self::wysiwygField(
                "field_{$prefix}_content",
                'Einleitungstext',
                'content',
                false,
                null,
                'Optionaler Text über dem Formular.'
            ),
            self::textField(
                "field_{$prefix}_form_id",
                'Formular-ID',
                'form_id',
                true,
                'So findest du die ID: Gehe zu Formulare → wähle dein Formular → die ID steht in der URL (z.B. post=123) oder im Shortcode [contact-form-7 id="123"]. Trage nur die Zahl ein.',
                'z.B. 123'
            ),
            self::trueFalseField(
                "field_{$prefix}_show_contact_info",
                'Kontaktdaten anzeigen',
                'show_contact_info',
                true,
                'Zeigt die Kontaktdaten aus den Theme-Einstellungen an.'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Google Maps layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function mapFields(string $prefix): array
    {
        return [
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                false,
                'Optionale Überschrift über der Karte.',
                'z.B. So finden Sie uns'
            ),
            self::textareaField(
                "field_{$prefix}_address",
                'Adresse',
                'address',
                2,
                'Die vollständige Adresse (für den "Route planen" Link).',
                'Musterstraße 123, 12345 Musterstadt'
            ),
            self::urlField(
                "field_{$prefix}_embed_url",
                'Google Maps Einbettungs-URL',
                'embed_url',
                'So geht\'s: 1) Google Maps öffnen 2) Standort suchen 3) Auf "Teilen" klicken 4) "Karte einbetten" wählen 5) Die URL aus dem HTML-Code kopieren (beginnt mit https://www.google.com/maps/embed). Der Block zeigt automatisch einen DSGVO-Hinweis.',
                null,
                'https://www.google.com/maps/embed?pb=...'
            ),
            self::numberField(
                "field_{$prefix}_height",
                'Höhe',
                'height',
                400,
                200,
                800,
                50,
                'px',
                'Höhe der Karte in Pixeln.'
            ),
            self::trueFalseField(
                "field_{$prefix}_show_directions_link",
                '"Route planen" Link anzeigen',
                'show_directions_link',
                true,
                'Zeigt einen Link zum Planen der Route an.'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Tabs layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function tabsFields(string $prefix): array
    {
        return [
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                false,
                'Optionale Überschrift über den Tabs.',
                'z.B. Häufige Fragen'
            ),
            self::repeaterField(
                "field_{$prefix}_tabs",
                'Tabs',
                'tabs',
                [
                    self::textField(
                        "field_{$prefix}_tab_title",
                        'Tab-Titel',
                        'title',
                        true,
                        'Der Titel des Tabs (im Tab-Button sichtbar).',
                        'z.B. Übersicht'
                    ),
                    self::wysiwygField(
                        "field_{$prefix}_tab_content",
                        'Inhalt',
                        'content',
                        true,
                        null,
                        'Der Inhalt, der angezeigt wird, wenn dieser Tab aktiv ist.'
                    ),
                ],
                'Tab hinzufügen',
                2,
                'block',
                'Füge mindestens 2 Tabs hinzu.'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    // =========================================================================
    // ADDITIONAL BLOCKS - Pricing, Team, Stats, Timeline, Posts, Before/After, Table
    // =========================================================================

    /**
     * Get Pricing Table layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function pricingTableFields(string $prefix): array
    {
        return [
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                false,
                'Optionale Überschrift über der Preistabelle.',
                'z.B. Unsere Pakete'
            ),
            self::repeaterField(
                "field_{$prefix}_plans",
                'Preispakete',
                'plans',
                [
                    self::textField(
                        "field_{$prefix}_plan_name",
                        'Paketname',
                        'name',
                        true,
                        'Name des Pakets.',
                        'z.B. Basic, Professional, Enterprise'
                    ),
                    self::textField(
                        "field_{$prefix}_plan_price",
                        'Preis',
                        'price',
                        true,
                        'Der Preis inkl. Währung.',
                        'z.B. 49€, 199€, Auf Anfrage'
                    ),
                    self::textField(
                        "field_{$prefix}_plan_period",
                        'Zeitraum',
                        'period',
                        false,
                        'Der Abrechnungszeitraum.',
                        'z.B. Monat, Jahr, einmalig'
                    ),
                    self::wysiwygField(
                        "field_{$prefix}_plan_features",
                        'Leistungen',
                        'features',
                        true,
                        null,
                        'Liste der enthaltenen Leistungen (als Aufzählung).'
                    ),
                    self::linkField(
                        "field_{$prefix}_plan_cta",
                        'Button',
                        'cta',
                        false,
                        'Call-to-Action Button für dieses Paket.'
                    ),
                    self::trueFalseField(
                        "field_{$prefix}_plan_featured",
                        'Hervorheben',
                        'is_featured',
                        false,
                        'Dieses Paket als "Empfohlen" hervorheben.'
                    ),
                ],
                'Paket hinzufügen',
                1,
                'block',
                'Füge Preispakete hinzu (empfohlen: 3 Pakete).'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Team Members layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function teamFields(string $prefix): array
    {
        return [
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                false,
                'Optionale Überschrift über dem Team.',
                'z.B. Unser Team'
            ),
            self::repeaterField(
                "field_{$prefix}_members",
                'Teammitglieder',
                'members',
                [
                    self::imageField(
                        "field_{$prefix}_member_image",
                        'Foto',
                        'image',
                        false,
                        'id',
                        null,
                        'Portraitfoto (quadratisch empfohlen).'
                    ),
                    self::textField(
                        "field_{$prefix}_member_name",
                        'Name',
                        'name',
                        true,
                        'Vollständiger Name.',
                        'z.B. Max Mustermann'
                    ),
                    self::textField(
                        "field_{$prefix}_member_position",
                        'Position',
                        'position',
                        false,
                        'Jobtitel oder Rolle.',
                        'z.B. Geschäftsführer'
                    ),
                    self::textareaField(
                        "field_{$prefix}_member_bio",
                        'Kurzbiografie',
                        'bio',
                        2,
                        'Optionale kurze Beschreibung.',
                        'z.B. Seit 2020 im Unternehmen...'
                    ),
                    self::emailField(
                        "field_{$prefix}_member_email",
                        'E-Mail',
                        'email',
                        'Direkte E-Mail-Adresse.',
                        'max@beispiel.de'
                    ),
                    self::urlField(
                        "field_{$prefix}_member_linkedin",
                        'LinkedIn',
                        'linkedin',
                        'Link zum LinkedIn-Profil.',
                        null,
                        'https://linkedin.com/in/...'
                    ),
                ],
                'Mitglied hinzufügen',
                1,
                'block',
                'Füge Teammitglieder hinzu.'
            ),
            self::selectField(
                "field_{$prefix}_columns",
                'Spalten',
                'columns',
                [
                    '2' => '2 Spalten',
                    '3' => '3 Spalten',
                    '4' => '4 Spalten',
                ],
                '3',
                false,
                'Anzahl der Spalten für die Darstellung.'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Stats/Counter layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function statsFields(string $prefix): array
    {
        return [
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                false,
                'Optionale Überschrift über den Statistiken.',
                'z.B. Zahlen & Fakten'
            ),
            self::repeaterField(
                "field_{$prefix}_stats",
                'Statistiken',
                'stats',
                [
                    self::numberField(
                        "field_{$prefix}_stat_number",
                        'Zahl',
                        'number',
                        0,
                        0,
                        999999999,
                        1,
                        '',
                        'Die anzuzeigende Zahl (wird animiert).'
                    ),
                    self::textField(
                        "field_{$prefix}_stat_suffix",
                        'Suffix',
                        'suffix',
                        false,
                        'Optionales Suffix nach der Zahl.',
                        'z.B. +, %, Jahre'
                    ),
                    self::textField(
                        "field_{$prefix}_stat_label",
                        'Beschriftung',
                        'label',
                        true,
                        'Was diese Zahl bedeutet.',
                        'z.B. Zufriedene Kunden'
                    ),
                    self::textField(
                        "field_{$prefix}_stat_icon",
                        'Icon (Emoji)',
                        'icon',
                        false,
                        'Optionales Emoji als Icon. Emoji-Tastatur öffnen: Mac: Ctrl+Cmd+Leertaste, Windows: Win+Punkt (.)',
                        'z.B. 🏆, ⭐, 📈'
                    ),
                ],
                'Statistik hinzufügen',
                1,
                'table',
                'Füge Kennzahlen hinzu (empfohlen: 3-4).'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Timeline layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function timelineFields(string $prefix): array
    {
        return [
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                false,
                'Optionale Überschrift über dem Zeitstrahl.',
                'z.B. Unsere Geschichte'
            ),
            self::repeaterField(
                "field_{$prefix}_events",
                'Ereignisse',
                'events',
                [
                    self::textField(
                        "field_{$prefix}_event_year",
                        'Jahr / Datum',
                        'year',
                        true,
                        'Zeitpunkt des Ereignisses.',
                        'z.B. 2020, Januar 2021, Phase 1'
                    ),
                    self::textField(
                        "field_{$prefix}_event_title",
                        'Titel',
                        'title',
                        true,
                        'Kurzer Titel des Ereignisses.',
                        'z.B. Firmengründung'
                    ),
                    self::wysiwygField(
                        "field_{$prefix}_event_content",
                        'Beschreibung',
                        'content',
                        false,
                        null,
                        'Detaillierte Beschreibung des Ereignisses.'
                    ),
                    self::imageField(
                        "field_{$prefix}_event_image",
                        'Bild',
                        'image',
                        false,
                        'id',
                        null,
                        'Optionales Bild zum Ereignis.'
                    ),
                ],
                'Ereignis hinzufügen',
                2,
                'block',
                'Füge Ereignisse in chronologischer Reihenfolge hinzu.'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Blog Posts layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function postsFields(string $prefix): array
    {
        return [
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                false,
                'Optionale Überschrift über den Beiträgen.',
                'z.B. Aktuelle Neuigkeiten'
            ),
            self::selectField(
                "field_{$prefix}_post_type",
                'Beitragstyp',
                'post_type',
                [
                    'post' => 'Blog-Beiträge',
                    'page' => 'Seiten',
                ],
                'post',
                false,
                'Welcher Beitragstyp angezeigt werden soll.'
            ),
            self::numberField(
                "field_{$prefix}_posts_per_page",
                'Anzahl',
                'posts_per_page',
                3,
                1,
                12,
                1,
                'Beiträge',
                'Wie viele Beiträge angezeigt werden sollen.'
            ),
            [
                'key' => "field_{$prefix}_category",
                'label' => 'Kategorie',
                'name' => 'category',
                'type' => 'taxonomy',
                'instructions' => 'Optional: Nur Beiträge aus dieser Kategorie anzeigen.',
                'taxonomy' => 'category',
                'field_type' => 'select',
                'allow_null' => 1,
                'return_format' => 'id',
            ],
            self::trueFalseField(
                "field_{$prefix}_show_excerpt",
                'Auszug anzeigen',
                'show_excerpt',
                true,
                'Zeigt einen kurzen Textauszug an.'
            ),
            self::trueFalseField(
                "field_{$prefix}_show_date",
                'Datum anzeigen',
                'show_date',
                true,
                'Zeigt das Veröffentlichungsdatum an.'
            ),
            self::trueFalseField(
                "field_{$prefix}_show_author",
                'Autor anzeigen',
                'show_author',
                false,
                'Zeigt den Autorennamen an.'
            ),
            self::selectField(
                "field_{$prefix}_columns",
                'Spalten',
                'columns',
                [
                    '2' => '2 Spalten',
                    '3' => '3 Spalten',
                    '4' => '4 Spalten',
                ],
                '3',
                false,
                'Anzahl der Spalten für die Darstellung.'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Before/After Slider layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function beforeAfterFields(string $prefix): array
    {
        return [
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                false,
                'Optionale Überschrift über dem Vergleich.',
                'z.B. Vorher vs. Nachher'
            ),
            self::imageField(
                "field_{$prefix}_image_before",
                'Vorher-Bild',
                'image_before',
                true,
                'id',
                '50',
                'Das "Vorher"-Bild (links).'
            ),
            self::imageField(
                "field_{$prefix}_image_after",
                'Nachher-Bild',
                'image_after',
                true,
                'id',
                '50',
                'Das "Nachher"-Bild (rechts). Sollte gleiche Maße haben.'
            ),
            self::textField(
                "field_{$prefix}_label_before",
                'Label Vorher',
                'label_before',
                false,
                'Text für das Vorher-Label.',
                'Vorher'
            ),
            self::textField(
                "field_{$prefix}_label_after",
                'Label Nachher',
                'label_after',
                false,
                'Text für das Nachher-Label.',
                'Nachher'
            ),
            self::backgroundColorField($prefix),
        ];
    }

    /**
     * Get Table layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function tableFields(string $prefix): array
    {
        return [
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                false,
                'Optionale Überschrift über der Tabelle.',
                'z.B. Preisübersicht'
            ),
            self::repeaterField(
                "field_{$prefix}_headers",
                'Spaltenüberschriften',
                'headers',
                [
                    self::textField(
                        "field_{$prefix}_header_label",
                        'Spaltenname',
                        'label',
                        true,
                        'Name der Spalte.',
                        'z.B. Produkt, Preis, Menge'
                    ),
                ],
                'Spalte hinzufügen',
                1,
                'table',
                'Definiere die Spalten der Tabelle.'
            ),
            self::repeaterField(
                "field_{$prefix}_rows",
                'Zeilen',
                'rows',
                [
                    self::repeaterField(
                        "field_{$prefix}_row_cells",
                        'Zellen',
                        'cells',
                        [
                            self::textareaField(
                                "field_{$prefix}_cell_content",
                                'Inhalt',
                                'content',
                                1,
                                'Inhalt der Zelle (HTML erlaubt).',
                                ''
                            ),
                        ],
                        'Zelle hinzufügen',
                        1,
                        'table',
                        ''
                    ),
                ],
                'Zeile hinzufügen',
                1,
                'row',
                'Füge Datenzeilen hinzu.'
            ),
            self::trueFalseField(
                "field_{$prefix}_striped",
                'Gestreifte Zeilen',
                'striped',
                true,
                'Abwechselnde Hintergrundfarben für bessere Lesbarkeit.'
            ),
            self::trueFalseField(
                "field_{$prefix}_bordered",
                'Mit Rahmen',
                'bordered',
                false,
                'Zeigt Rahmenlinien um die Zellen.'
            ),
            self::backgroundColorField($prefix),
        ];
    }
}
