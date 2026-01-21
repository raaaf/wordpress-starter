<?php

declare(strict_types=1);

namespace WordpressStarter\Acf;

/**
 * Shared ACF field definitions for Flexible Content layouts
 *
 * This class provides reusable field configurations for all
 * flexible content layouts used in the page builder.
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
     * Theme icon choices from resources/icons/
     */
    public const THEME_ICONS = [
        '' => '— Kein Icon —',
        'calendar' => 'Kalender',
        'check' => 'Häkchen',
        'chevron' => 'Pfeil',
        'close' => 'Schließen',
        'eye' => 'Auge',
        'lock' => 'Schloss',
        'mail' => 'E-Mail',
        'minus' => 'Minus',
        'phone' => 'Telefon',
        'plus' => 'Plus',
        'search' => 'Suche',
        'user' => 'Person',
        'warning' => 'Warnung',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
        'x' => 'X (Twitter)',
        'xing' => 'Xing',
        'youtube' => 'YouTube',
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
     * @param array<int, array<int, array<string, string>>>|null $conditionalLogic Conditional logic
     * @param string $instructions Field instructions
     * @param string|null $width Wrapper width percentage
     * @return array<string, mixed>
     */
    public static function imageField(
        string $key,
        string $label,
        string $name,
        bool $required = false,
        string $returnFormat = 'array',
        ?array $conditionalLogic = null,
        string $instructions = '',
        ?string $width = null
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

        if ($conditionalLogic !== null) {
            $field['conditional_logic'] = $conditionalLogic;
        }

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
     * Get button group field definition
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param array<int|string, string> $choices Choices array (value => label)
     * @param string $defaultValue Default selected value
     * @param string $instructions Field instructions
     * @param array<int, array<int, array<string, string>>>|null $conditionalLogic Conditional logic
     * @return array<string, mixed>
     */
    public static function buttonGroupField(
        string $key,
        string $label,
        string $name,
        array $choices,
        string $defaultValue = '',
        string $instructions = '',
        ?array $conditionalLogic = null
    ): array {
        $field = [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'button_group',
            'instructions' => $instructions,
            'choices' => $choices,
            'default_value' => $defaultValue,
            'return_format' => 'value',
            'layout' => 'horizontal',
        ];

        if ($conditionalLogic !== null) {
            $field['conditional_logic'] = $conditionalLogic;
        }

        return $field;
    }

    /**
     * Get icon radio field definition (horizontal layout with icon previews)
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param string $instructions Field instructions
     * @return array<string, mixed>
     */
    public static function iconRadioField(
        string $key,
        string $label,
        string $name,
        string $instructions = ''
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'radio',
            'instructions' => $instructions,
            'choices' => self::THEME_ICONS,
            'default_value' => '',
            'layout' => 'horizontal',
            'return_format' => 'value',
            'wrapper' => [
                'class' => 'acf-icon-radio-field',
            ],
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
     * Get tab field definition for grouping fields
     *
     * @param string $key Unique field key
     * @param string $label Tab label
     * @param string $placement Tab placement (top or left)
     * @return array<string, mixed>
     */
    public static function tabField(
        string $key,
        string $label,
        string $placement = 'top'
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'type' => 'tab',
            'placement' => $placement,
            'endpoint' => 0,
        ];
    }

    /**
     * Get message field definition for displaying help text
     *
     * @param string $key Unique field key
     * @param string $message The message content (HTML allowed)
     * @param string $label Optional label above the message
     * @return array<string, mixed>
     */
    public static function messageField(
        string $key,
        string $message,
        string $label = ''
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'type' => 'message',
            'message' => $message,
            'new_lines' => 'wpautop',
            'esc_html' => 0,
        ];
    }

    /**
     * Get styled info box field for contextual help messages
     *
     * @param string $key Unique field key
     * @param string $message The message content (HTML allowed)
     * @param string $type Box type: info, success, warning, tip
     * @return array<string, mixed>
     */
    public static function infoBoxField(
        string $key,
        string $message,
        string $type = 'info'
    ): array {
        $icons = [
            'info' => 'dashicons-info',
            'success' => 'dashicons-yes-alt',
            'warning' => 'dashicons-warning',
            'tip' => 'dashicons-lightbulb',
        ];

        $colors = [
            'info' => '#0073aa',
            'success' => '#00a32a',
            'warning' => '#dba617',
            'tip' => '#8c5ed5',
        ];

        $bgColors = [
            'info' => '#f0f6fc',
            'success' => '#edfaef',
            'warning' => '#fcf9e8',
            'tip' => '#f5f0fa',
        ];

        $icon = $icons[$type] ?? $icons['info'];
        $color = $colors[$type] ?? $colors['info'];
        $bgColor = $bgColors[$type] ?? $bgColors['info'];

        return [
            'key' => $key,
            'label' => '',
            'type' => 'message',
            'message' => sprintf(
                '<div style="display: flex; align-items: flex-start; gap: 12px; padding: 16px; background: %s; border-radius: 6px; border-left: 4px solid %s;">
                    <span class="dashicons %s" style="color: %s; margin-top: 2px;"></span>
                    <div style="flex: 1;">%s</div>
                </div>',
                $bgColor,
                $color,
                $icon,
                $color,
                $message
            ),
            'new_lines' => '',
            'esc_html' => 0,
        ];
    }

    /**
     * Get accordion field definition for collapsible sections inside repeaters
     *
     * @param string $key Unique field key
     * @param string $label Accordion section label
     * @param bool $open Whether the accordion is open by default
     * @param bool $multiExpand Allow multiple sections to be open at once
     * @param bool $endpoint Whether this is an endpoint (closes previous accordion)
     * @return array<string, mixed>
     */
    public static function accordionField(
        string $key,
        string $label,
        bool $open = false,
        bool $multiExpand = true,
        bool $endpoint = false
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'type' => 'accordion',
            'open' => $open ? 1 : 0,
            'multi_expand' => $multiExpand ? 1 : 0,
            'endpoint' => $endpoint ? 1 : 0,
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
     * Get range slider field definition
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @param int $step Step increment
     * @param int $defaultValue Default value
     * @param string $instructions Field instructions
     * @param string $append Text to append (e.g., '%')
     * @param array<int, array<int, array<string, string>>>|null $conditionalLogic Conditional logic
     * @return array<string, mixed>
     */
    public static function rangeField(
        string $key,
        string $label,
        string $name,
        int $min = 0,
        int $max = 100,
        int $step = 1,
        int $defaultValue = 50,
        string $instructions = '',
        string $append = '',
        ?array $conditionalLogic = null
    ): array {
        $field = [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'range',
            'instructions' => $instructions,
            'default_value' => $defaultValue,
            'min' => $min,
            'max' => $max,
            'step' => $step,
        ];

        if ($append !== '') {
            $field['append'] = $append;
        }

        if ($conditionalLogic !== null) {
            $field['conditional_logic'] = $conditionalLogic;
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
     * Get Hero layout fields with 3 variants: centered, split, background
     *
     * @param string $prefix Key prefix (e.g., 'hero' or 'block_hero')
     * @return array<int, array<string, mixed>>
     */
    public static function heroFields(string $prefix): array
    {
        // Conditional logic helpers
        $showOnSplit = [
            [
                [
                    'field' => "field_{$prefix}_variant",
                    'operator' => '==',
                    'value' => 'split',
                ],
            ],
        ];

        $showOnBackground = [
            [
                [
                    'field' => "field_{$prefix}_variant",
                    'operator' => '==',
                    'value' => 'background',
                ],
            ],
        ];

        $showOnCenteredOrSplit = [
            [
                [
                    'field' => "field_{$prefix}_variant",
                    'operator' => '==',
                    'value' => 'centered',
                ],
            ],
            [
                [
                    'field' => "field_{$prefix}_variant",
                    'operator' => '==',
                    'value' => 'split',
                ],
            ],
        ];

        return [
            // Variante (bestimmt welche Felder sichtbar sind)
            self::buttonGroupField(
                "field_{$prefix}_variant",
                'Variante',
                'variant',
                [
                    'centered' => 'Zentriert',
                    'split' => 'Geteilt',
                    'background' => 'Hintergrund',
                ],
                'centered',
                'Wähle das Layout für den Hero-Bereich.'
            ),

            // Inhalt
            self::textField(
                "field_{$prefix}_badge",
                'Badge',
                'badge',
                false,
                'Optionaler Badge-Text über der Überschrift.',
                'z.B. NEU, Coming Soon...'
            ),
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                true,
                'Die Hauptüberschrift des Hero-Bereichs.',
                'z.B. Willkommen bei...'
            ),
            self::textareaField(
                "field_{$prefix}_copy",
                'Copy',
                'copy',
                3,
                'Kurzer Beschreibungstext unter der Überschrift.',
                'z.B. Wir helfen Ihnen...'
            ),

            // Buttons (nebeneinander)
            [
                'key' => "field_{$prefix}_cta_primary",
                'label' => 'Primärer Button',
                'name' => 'cta_primary',
                'type' => 'link',
                'instructions' => 'Haupt-Button (orange, auffällig).',
                'required' => 0,
                'return_format' => 'array',
                'wrapper' => ['width' => '50'],
            ],
            [
                'key' => "field_{$prefix}_cta_secondary",
                'label' => 'Sekundärer Button',
                'name' => 'cta_secondary',
                'type' => 'link',
                'instructions' => 'Zweiter Button (dezent, Outline-Stil).',
                'required' => 0,
                'return_format' => 'array',
                'wrapper' => ['width' => '50'],
            ],

            // Bild (nur bei Split-Variante)
            self::imageField(
                "field_{$prefix}_image",
                'Bild',
                'image',
                false,
                'array',
                $showOnSplit,
                'Empfohlene Größe: mindestens 960×800 Pixel (6:5).'
            ),

            // Hintergrundbild (nur bei Background-Variante)
            self::imageField(
                "field_{$prefix}_background_image",
                'Hintergrundbild',
                'background_image',
                false,
                'array',
                $showOnBackground,
                'Empfohlene Größe: mindestens 1920×1080 Pixel (16:9).'
            ),

            // Overlay-Transparenz (nur bei Background-Variante)
            self::rangeField(
                "field_{$prefix}_overlay_opacity",
                'Overlay-Transparenz',
                'overlay_opacity',
                0,
                100,
                5,
                80,
                '0% = transparent, 100% = vollständig deckend.',
                '%',
                $showOnBackground
            ),

            // Hintergrundfarbe (nur bei Centered und Split)
            [
                'key' => "field_{$prefix}_background_color",
                'label' => 'Hintergrundfarbe',
                'name' => 'background_color',
                'type' => 'select',
                'instructions' => 'Wähle eine Hintergrundfarbe für den Hero-Bereich.',
                'choices' => [
                    'primary' => 'Standard (Weiß)',
                    'secondary' => 'Sekundär (Hellgrau)',
                    'tertiary' => 'Tertiär',
                    'brand' => 'Markenfarbe',
                    'brand-subtle' => 'Markenfarbe Dezent',
                    'inverse' => 'Dunkel (Invers)',
                ],
                'default_value' => 'primary',
                'ui' => 1,
                'conditional_logic' => $showOnCenteredOrSplit,
            ],
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
                    self::iconRadioField(
                        "field_{$prefix}_accordion_icon",
                        'Icon',
                        'icon',
                        'Optionales Icon vor dem Titel.'
                    ),
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
     * Get CTA Block fields (variant with WYSIWYG content and limited background colors)
     *
     * This variant is used for ACF blocks where:
     * - Content needs rich text editing (WYSIWYG instead of textarea)
     * - Button field is named 'cta' for template compatibility
     * - Only brand colors are available (design constraint)
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function ctaBlockFields(string $prefix): array
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
            self::wysiwygField(
                "field_{$prefix}_content",
                'Beschreibung',
                'content',
                false,
                null,
                'Kurzer Text, der zum Handeln auffordert.'
            ),
            self::linkField(
                "field_{$prefix}_cta",
                'Button',
                'cta',
                true,
                'Der Call-to-Action Button mit Link und Text.'
            ),
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
            self::buttonGroupField(
                "field_{$prefix}_source",
                'Video-Quelle',
                'source',
                [
                    'wordpress' => 'Mediathek',
                    'external' => 'YouTube / Vimeo',
                ],
                'wordpress',
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
            [
                'key' => "field_{$prefix}_show_border",
                'label' => 'Rahmen anzeigen',
                'name' => 'show_border',
                'type' => 'true_false',
                'instructions' => 'Zeigt einen dezenten Rahmen um das Bild.',
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => ['width' => '50'],
            ],
            [
                'key' => "field_{$prefix}_show_caption",
                'label' => 'Bildunterschrift anzeigen',
                'name' => 'show_caption',
                'type' => 'true_false',
                'instructions' => 'Zeigt die in der Mediathek hinterlegte Bildunterschrift.',
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => ['width' => '50'],
            ],
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
            self::buttonGroupField(
                "field_{$prefix}_variant",
                'Variante',
                'variant',
                [
                    'line' => 'Linie',
                    'logo' => 'Mit Logo',
                    'dots' => 'Punkte',
                ],
                'line',
                'Wähle das Aussehen des Trenners.'
            ),
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
                null,
                'Bild für die linke Karte.',
                '50'
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
                null,
                'Bild für die rechte Karte.',
                '50'
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
            self::buttonGroupField(
                "field_{$prefix}_columns",
                'Spalten',
                'columns',
                [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                ],
                '3',
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
                    self::iconRadioField(
                        "field_{$prefix}_card_icon",
                        'Icon',
                        'icon',
                        'Wähle ein Icon aus dem Theme.'
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
            self::buttonGroupField(
                "field_{$prefix}_columns",
                'Spalten',
                'columns',
                [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                '3',
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
            self::buttonGroupField(
                "field_{$prefix}_columns",
                'Spalten',
                'columns',
                [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                ],
                '3',
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
            // Tab: Formular
            self::tabField("field_{$prefix}_tab_form", 'Formular'),
            self::messageField(
                "field_{$prefix}_form_help",
                '<strong>So findest du die Formular-ID:</strong><br>1) Gehe zu <em>Formulare</em> im Menü<br>2) Wähle dein Formular aus<br>3) Die ID steht in der URL (z.B. post=<strong>123</strong>) oder im Shortcode'
            ),
            [
                'key' => "field_{$prefix}_form_id",
                'label' => 'Formular-ID',
                'name' => 'form_id',
                'type' => 'text',
                'instructions' => 'Trage nur die Zahl ein.',
                'placeholder' => 'z.B. 123',
                'required' => 1,
            ],

            // Tab: Inhalt
            self::tabField("field_{$prefix}_tab_content", 'Inhalt'),
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

            // Tab: Optionen
            self::tabField("field_{$prefix}_tab_options", 'Optionen'),
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
            // Tab: Karte
            self::tabField("field_{$prefix}_tab_map", 'Karte'),
            self::messageField(
                "field_{$prefix}_map_help",
                '<strong>So bekommst du die Einbettungs-URL:</strong><br>1) Öffne <a href="https://maps.google.com" target="_blank">Google Maps</a><br>2) Suche deinen Standort<br>3) Klicke auf "Teilen" → "Karte einbetten"<br>4) Kopiere die URL aus dem HTML-Code (beginnt mit https://www.google.com/maps/embed)'
            ),
            self::urlField(
                "field_{$prefix}_embed_url",
                'Google Maps Einbettungs-URL',
                'embed_url',
                'Der Block zeigt automatisch einen DSGVO-Hinweis.',
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

            // Tab: Inhalt
            self::tabField("field_{$prefix}_tab_content", 'Inhalt'),
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
            self::trueFalseField(
                "field_{$prefix}_show_directions_link",
                '"Route planen" Link anzeigen',
                'show_directions_link',
                true,
                'Zeigt einen Link zum Planen der Route an.'
            ),

            // Tab: Darstellung
            self::tabField("field_{$prefix}_tab_style", 'Darstellung'),
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
                    self::iconRadioField(
                        "field_{$prefix}_tab_icon",
                        'Icon',
                        'icon',
                        'Optionales Icon neben dem Tab-Titel.'
                    ),
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
                    // Accordion: Preis (offen)
                    self::accordionField("field_{$prefix}_acc_price", 'Preis', true),
                    [
                        'key' => "field_{$prefix}_plan_name",
                        'label' => 'Paketname',
                        'name' => 'name',
                        'type' => 'text',
                        'instructions' => 'Name des Pakets.',
                        'placeholder' => 'z.B. Basic, Professional',
                        'required' => 1,
                        'wrapper' => ['width' => '40'],
                    ],
                    [
                        'key' => "field_{$prefix}_plan_price",
                        'label' => 'Preis',
                        'name' => 'price',
                        'type' => 'text',
                        'instructions' => 'Der Preis inkl. Währung.',
                        'placeholder' => 'z.B. 49€',
                        'required' => 1,
                        'wrapper' => ['width' => '30'],
                    ],
                    [
                        'key' => "field_{$prefix}_plan_period",
                        'label' => 'Zeitraum',
                        'name' => 'period',
                        'type' => 'text',
                        'instructions' => 'Abrechnungszeitraum.',
                        'placeholder' => 'z.B. / Monat',
                        'wrapper' => ['width' => '30'],
                    ],
                    self::trueFalseField(
                        "field_{$prefix}_plan_featured",
                        'Hervorheben',
                        'is_featured',
                        false,
                        'Dieses Paket als "Empfohlen" hervorheben.'
                    ),
                    // Accordion: Leistungen
                    self::accordionField("field_{$prefix}_acc_features", 'Leistungen'),
                    self::wysiwygField(
                        "field_{$prefix}_plan_features",
                        'Leistungen',
                        'features',
                        true,
                        null,
                        'Liste der enthaltenen Leistungen (als Aufzählung).'
                    ),
                    // Accordion: Aktion
                    self::accordionField("field_{$prefix}_acc_cta", 'Aktion'),
                    self::linkField(
                        "field_{$prefix}_plan_cta",
                        'Button',
                        'cta',
                        false,
                        'Call-to-Action Button für dieses Paket.'
                    ),
                    // Accordion Ende
                    self::accordionField("field_{$prefix}_acc_end", '', false, true, true),
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
                    // Accordion: Person (offen)
                    self::accordionField("field_{$prefix}_acc_person", 'Person', true),
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
                    // Accordion: Details
                    self::accordionField("field_{$prefix}_acc_details", 'Details'),
                    self::textareaField(
                        "field_{$prefix}_member_bio",
                        'Kurzbiografie',
                        'bio',
                        2,
                        'Optionale kurze Beschreibung.',
                        'z.B. Seit 2020 im Unternehmen...'
                    ),
                    // Accordion: Kontakt
                    self::accordionField("field_{$prefix}_acc_contact", 'Kontakt'),
                    [
                        'key' => "field_{$prefix}_member_email",
                        'label' => 'E-Mail',
                        'name' => 'email',
                        'type' => 'email',
                        'instructions' => 'Direkte E-Mail-Adresse.',
                        'placeholder' => 'max@beispiel.de',
                        'wrapper' => ['width' => '50'],
                    ],
                    [
                        'key' => "field_{$prefix}_member_linkedin",
                        'label' => 'LinkedIn',
                        'name' => 'linkedin',
                        'type' => 'url',
                        'instructions' => 'Link zum LinkedIn-Profil.',
                        'placeholder' => 'https://linkedin.com/in/...',
                        'wrapper' => ['width' => '50'],
                    ],
                    // Accordion Ende
                    self::accordionField("field_{$prefix}_acc_end", '', false, true, true),
                ],
                'Mitglied hinzufügen',
                1,
                'block',
                'Füge Teammitglieder hinzu.'
            ),
            self::buttonGroupField(
                "field_{$prefix}_columns",
                'Spalten',
                'columns',
                [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                '3',
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
                    self::iconRadioField(
                        "field_{$prefix}_stat_icon",
                        'Icon',
                        'icon',
                        'Optionales Icon für diese Statistik.'
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
                    // Accordion: Event (offen)
                    self::accordionField("field_{$prefix}_acc_event", 'Event', true),
                    [
                        'key' => "field_{$prefix}_event_year",
                        'label' => 'Jahr / Datum',
                        'name' => 'year',
                        'type' => 'text',
                        'instructions' => 'Zeitpunkt des Ereignisses.',
                        'placeholder' => 'z.B. 2020',
                        'required' => 1,
                        'wrapper' => ['width' => '30'],
                    ],
                    [
                        'key' => "field_{$prefix}_event_title",
                        'label' => 'Titel',
                        'name' => 'title',
                        'type' => 'text',
                        'instructions' => 'Kurzer Titel des Ereignisses.',
                        'placeholder' => 'z.B. Firmengründung',
                        'required' => 1,
                        'wrapper' => ['width' => '70'],
                    ],
                    // Accordion: Details
                    self::accordionField("field_{$prefix}_acc_details", 'Details'),
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
                    // Accordion Ende
                    self::accordionField("field_{$prefix}_acc_end", '', false, true, true),
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
        // Conditional: Show category only for posts
        $showOnPosts = [
            [
                [
                    'field' => "field_{$prefix}_post_type",
                    'operator' => '==',
                    'value' => 'post',
                ],
            ],
        ];

        return [
            // Tab: Inhalt
            self::tabField("field_{$prefix}_tab_content", 'Inhalt'),
            self::textField(
                "field_{$prefix}_title",
                'Überschrift',
                'title',
                false,
                'Optionale Überschrift über den Beiträgen.',
                'z.B. Aktuelle Neuigkeiten'
            ),
            self::buttonGroupField(
                "field_{$prefix}_post_type",
                'Beitragstyp',
                'post_type',
                [
                    'post' => 'Blog-Beiträge',
                    'page' => 'Seiten',
                ],
                'post',
                'Welcher Beitragstyp angezeigt werden soll.'
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
                'conditional_logic' => $showOnPosts,
            ],
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

            // Tab: Anzeige
            self::tabField("field_{$prefix}_tab_display", 'Anzeige'),
            [
                'key' => "field_{$prefix}_show_excerpt",
                'label' => 'Auszug',
                'name' => 'show_excerpt',
                'type' => 'true_false',
                'instructions' => 'Zeigt einen kurzen Textauszug an.',
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => ['width' => '33'],
            ],
            [
                'key' => "field_{$prefix}_show_date",
                'label' => 'Datum',
                'name' => 'show_date',
                'type' => 'true_false',
                'instructions' => 'Zeigt das Veröffentlichungsdatum an.',
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => ['width' => '33'],
            ],
            [
                'key' => "field_{$prefix}_show_author",
                'label' => 'Autor',
                'name' => 'show_author',
                'type' => 'true_false',
                'instructions' => 'Zeigt den Autorennamen an.',
                'default_value' => 0,
                'ui' => 1,
                'wrapper' => ['width' => '34'],
            ],

            // Tab: Layout
            self::tabField("field_{$prefix}_tab_layout", 'Layout'),
            self::buttonGroupField(
                "field_{$prefix}_columns",
                'Spalten',
                'columns',
                [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                '3',
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
                null,
                'Das "Vorher"-Bild (links).',
                '50'
            ),
            self::imageField(
                "field_{$prefix}_image_after",
                'Nachher-Bild',
                'image_after',
                true,
                'id',
                null,
                'Das "Nachher"-Bild (rechts). Sollte gleiche Maße haben.',
                '50'
            ),
            [
                'key' => "field_{$prefix}_label_before",
                'label' => 'Label Vorher',
                'name' => 'label_before',
                'type' => 'text',
                'instructions' => 'Text für das Vorher-Label.',
                'placeholder' => 'Vorher',
                'wrapper' => ['width' => '50'],
            ],
            [
                'key' => "field_{$prefix}_label_after",
                'label' => 'Label Nachher',
                'name' => 'label_after',
                'type' => 'text',
                'instructions' => 'Text für das Nachher-Label.',
                'placeholder' => 'Nachher',
                'wrapper' => ['width' => '50'],
            ],
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
            [
                'key' => "field_{$prefix}_striped",
                'label' => 'Gestreifte Zeilen',
                'name' => 'striped',
                'type' => 'true_false',
                'instructions' => 'Abwechselnde Hintergrundfarben für bessere Lesbarkeit.',
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => ['width' => '50'],
            ],
            [
                'key' => "field_{$prefix}_bordered",
                'label' => 'Mit Rahmen',
                'name' => 'bordered',
                'type' => 'true_false',
                'instructions' => 'Zeigt Rahmenlinien um die Zellen.',
                'default_value' => 0,
                'ui' => 1,
                'wrapper' => ['width' => '50'],
            ],
            self::backgroundColorField($prefix),
        ];
    }

    // =========================================================================
    // COMPONENT BLOCKS
    // =========================================================================

    /**
     * Get Button block fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function buttonFields(string $prefix): array
    {
        return [
            self::linkField(
                "field_{$prefix}_button",
                'Button Link',
                'button',
                true,
                'Der Link und Text des Buttons.'
            ),
            self::buttonGroupField(
                "field_{$prefix}_variant",
                'Variante',
                'variant',
                [
                    'primary' => 'Primary',
                    'secondary' => 'Secondary',
                    'ghost' => 'Ghost',
                    'danger' => 'Danger',
                ],
                'primary',
                'Visueller Stil des Buttons.'
            ),
            self::buttonGroupField(
                "field_{$prefix}_size",
                'Größe',
                'size',
                [
                    'sm' => 'Klein',
                    'md' => 'Mittel',
                    'lg' => 'Groß',
                ],
                'md',
                'Größe des Buttons.'
            ),
            self::trueFalseField(
                "field_{$prefix}_full_width",
                'Volle Breite',
                'full_width',
                false,
                'Button nimmt die volle verfügbare Breite ein.'
            ),
        ];
    }
}
