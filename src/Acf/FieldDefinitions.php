<?php

declare(strict_types=1);

namespace WordpressStarter\Acf;

/**
 * Shared ACF field definitions for Flexible Content layouts
 *
 * This class provides reusable field configurations for all
 * flexible content layouts used in the page builder.
 *
 * All labels use WordPress translation functions for i18n support.
 */
class FieldDefinitions
{
    /**
     * Get background color choices that map to design tokens
     *
     * @return array<string, string>
     */
    public static function getBackgroundColors(): array
    {
        return [
            'primary' => __('Standard (Weiß)', 'wp-starter'),
            'secondary' => __('Sekundär (Hellgrau)', 'wp-starter'),
            'tertiary' => __('Tertiär', 'wp-starter'),
            'brand' => __('Markenfarbe', 'wp-starter'),
            'brand-subtle' => __('Markenfarbe Dezent', 'wp-starter'),
            'inverse' => __('Dunkel (Invers)', 'wp-starter'),
        ];
    }

    /**
     * Get theme icon choices from resources/icons/
     *
     * @return array<string, string>
     */
    public static function getThemeIcons(): array
    {
        return [
            '' => __('— Kein Icon —', 'wp-starter'),
            'calendar' => __('Kalender', 'wp-starter'),
            'check' => __('Häkchen', 'wp-starter'),
            'chevron' => __('Pfeil', 'wp-starter'),
            'close' => __('Schließen', 'wp-starter'),
            'eye' => __('Auge', 'wp-starter'),
            'lock' => __('Schloss', 'wp-starter'),
            'mail' => __('E-Mail', 'wp-starter'),
            'minus' => __('Minus', 'wp-starter'),
            'phone' => __('Telefon', 'wp-starter'),
            'plus' => __('Plus', 'wp-starter'),
            'search' => __('Suche', 'wp-starter'),
            'user' => __('Person', 'wp-starter'),
            'warning' => __('Warnung', 'wp-starter'),
            'download' => __('Download', 'wp-starter'),
            'logout' => __('Abmelden', 'wp-starter'),
            'shield' => __('Schild', 'wp-starter'),
            'facebook' => __('Facebook', 'wp-starter'),
            'instagram' => __('Instagram', 'wp-starter'),
            'linkedin' => __('LinkedIn', 'wp-starter'),
            'x' => __('X (Twitter)', 'wp-starter'),
            'xing' => __('Xing', 'wp-starter'),
            'youtube' => __('YouTube', 'wp-starter'),
        ];
    }

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
            'label' => __('Hintergrundfarbe', 'wp-starter'),
            'name' => 'background_color',
            'type' => 'select',
            'instructions' => __('Wähle die Hintergrundfarbe für diesen Abschnitt.', 'wp-starter'),
            'choices' => self::getBackgroundColors(),
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
     * Get color picker field definition
     *
     * @param string $key Unique field key
     * @param string $label Field label
     * @param string $name Field name
     * @param string $defaultValue Default color value (hex)
     * @param bool $enableOpacity Enable RGBA opacity support
     * @param string $instructions Field instructions
     * @return array<string, mixed>
     */
    public static function colorPickerField(
        string $key,
        string $label,
        string $name,
        string $defaultValue = '#ffffff',
        bool $enableOpacity = false,
        string $instructions = ''
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'color_picker',
            'instructions' => $instructions,
            'default_value' => $defaultValue,
            'enable_opacity' => $enableOpacity ? 1 : 0,
            'return_format' => 'string',
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
            'choices' => self::getThemeIcons(),
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
        int|float $defaultValue = 0,
        int|float $min = 0,
        int|float $max = 100,
        int|float $step = 1,
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
        ?string $buttonLabel = null,
        int $min = 0,
        string $layout = 'block',
        string $instructions = '',
        ?string $width = null
    ): array {
        $field = [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'repeater',
            'instructions' => $instructions,
            'required' => $min > 0 ? 1 : 0,
            'min' => $min,
            'layout' => $layout,
            'button_label' => $buttonLabel ?? __('Eintrag hinzufügen', 'wp-starter'),
            'sub_fields' => $subFields,
        ];

        if ($width !== null) {
            $field['wrapper'] = ['width' => $width];
        }

        return $field;
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
                __('Variante', 'wp-starter'),
                'variant',
                [
                    'centered' => __('Zentriert', 'wp-starter'),
                    'split' => __('Geteilt', 'wp-starter'),
                    'background' => __('Hintergrund', 'wp-starter'),
                ],
                'centered',
                __('Wähle das Layout für den Hero-Bereich.', 'wp-starter')
            ),

            // Inhalt
            self::textField(
                "field_{$prefix}_badge",
                __('Badge', 'wp-starter'),
                'badge',
                false,
                __('Optionaler Badge-Text über der Überschrift.', 'wp-starter'),
                __('z.B. NEU, Coming Soon...', 'wp-starter')
            ),
            self::textField(
                "field_{$prefix}_title",
                __('Überschrift', 'wp-starter'),
                'title',
                true,
                __('Die Hauptüberschrift des Hero-Bereichs.', 'wp-starter'),
                __('z.B. Willkommen bei...', 'wp-starter')
            ),
            self::textareaField(
                "field_{$prefix}_copy",
                __('Copy', 'wp-starter'),
                'copy',
                3,
                __('Kurzer Beschreibungstext unter der Überschrift.', 'wp-starter'),
                __('z.B. Wir helfen Ihnen...', 'wp-starter')
            ),

            // Buttons (nebeneinander)
            [
                'key' => "field_{$prefix}_cta_primary",
                'label' => __('Primärer Button', 'wp-starter'),
                'name' => 'cta_primary',
                'type' => 'link',
                'instructions' => __('Haupt-Button (orange, auffällig).', 'wp-starter'),
                'required' => 0,
                'return_format' => 'array',
                'wrapper' => ['width' => '50'],
            ],
            [
                'key' => "field_{$prefix}_cta_secondary",
                'label' => __('Sekundärer Button', 'wp-starter'),
                'name' => 'cta_secondary',
                'type' => 'link',
                'instructions' => __('Zweiter Button (dezent, Outline-Stil).', 'wp-starter'),
                'required' => 0,
                'return_format' => 'array',
                'wrapper' => ['width' => '50'],
            ],

            // Bild (nur bei Split-Variante)
            self::imageField(
                "field_{$prefix}_image",
                __('Bild', 'wp-starter'),
                'image',
                false,
                'array',
                $showOnSplit,
                __('Empfohlene Größe: mindestens 960×800 Pixel (6:5).', 'wp-starter')
            ),

            // Hintergrundbild (nur bei Background-Variante)
            self::imageField(
                "field_{$prefix}_background_image",
                __('Hintergrundbild', 'wp-starter'),
                'background_image',
                false,
                'array',
                $showOnBackground,
                __('Empfohlene Größe: mindestens 1920×1080 Pixel (16:9).', 'wp-starter')
            ),

            // Overlay-Transparenz (nur bei Background-Variante)
            self::rangeField(
                "field_{$prefix}_overlay_opacity",
                __('Overlay-Transparenz', 'wp-starter'),
                'overlay_opacity',
                0,
                100,
                5,
                80,
                __('0% = transparent, 100% = vollständig deckend.', 'wp-starter'),
                '%',
                $showOnBackground
            ),

            // Hintergrundfarbe (nur bei Centered und Split)
            [
                'key' => "field_{$prefix}_background_color",
                'label' => __('Hintergrundfarbe', 'wp-starter'),
                'name' => 'background_color',
                'type' => 'select',
                'instructions' => __('Wähle eine Hintergrundfarbe für den Hero-Bereich.', 'wp-starter'),
                'choices' => self::getBackgroundColors(),
                'default_value' => 'primary',
                'ui' => 1,
                'conditional_logic' => $showOnCenteredOrSplit,
            ],
            self::sectionAnchorField($prefix),
        ];
    }

    /**
     * Get Section Header fields (toggle + chip + headline + description)
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function sectionHeaderFields(string $prefix): array
    {
        $showWhenEnabled = [[['field' => "field_{$prefix}_show_section_header", 'operator' => '==', 'value' => '1']]];

        $chipField = self::textField(
            "field_{$prefix}_section_chip",
            __('Chip', 'wp-starter'),
            'section_chip',
            false,
            __('Optionaler Chip/Badge über der Überschrift.', 'wp-starter')
        );
        $chipField['conditional_logic'] = $showWhenEnabled;
        $chipField['wrapper'] = ['width' => '40'];

        $headlineField = self::textField(
            "field_{$prefix}_section_headline",
            __('Überschrift', 'wp-starter'),
            'section_headline',
            false,
            __('H2-Überschrift. Nutze [br] für Zeilenumbrüche.', 'wp-starter')
        );
        $headlineField['conditional_logic'] = $showWhenEnabled;
        $headlineField['wrapper'] = ['width' => '40'];

        $descriptionField = self::textareaField(
            "field_{$prefix}_section_description",
            __('Beschreibung', 'wp-starter'),
            'section_description',
            3,
            __('Optionale Beschreibung unter der Überschrift.', 'wp-starter')
        );
        $descriptionField['conditional_logic'] = $showWhenEnabled;

        $alignmentField = self::buttonGroupField(
            "field_{$prefix}_section_alignment",
            __('Ausrichtung', 'wp-starter'),
            'section_alignment',
            [
                'left'   => __('Linksbündig', 'wp-starter'),
                'center' => __('Zentriert', 'wp-starter'),
            ],
            'center',
            __('Textausrichtung des Section Headers.', 'wp-starter'),
            $showWhenEnabled
        );
        $alignmentField['wrapper'] = ['width' => '20'];

        return [
            self::trueFalseField(
                "field_{$prefix}_show_section_header",
                __('Section Header anzeigen', 'wp-starter'),
                'show_section_header',
                false,
                __('Zeigt Chip, Überschrift und Beschreibung über dem Inhalt an.', 'wp-starter')
            ),
            $alignmentField,
            $chipField,
            $headlineField,
            $descriptionField,
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
            ...self::sectionHeaderFields($prefix),
            self::wysiwygField(
                "field_{$prefix}_column_1",
                __('Spalte 1 (links)', 'wp-starter'),
                'column_1',
                false,
                '50',
                __('Inhalt der linken Spalte (50% Breite).', 'wp-starter')
            ),
            self::wysiwygField(
                "field_{$prefix}_column_2",
                __('Spalte 2 (rechts)', 'wp-starter'),
                'column_2',
                false,
                '50',
                __('Inhalt der rechten Spalte (50% Breite).', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
            ...self::sectionHeaderFields($prefix),
            self::wysiwygField(
                "field_{$prefix}_column_1",
                __('Spalte 1', 'wp-starter'),
                'column_1',
                false,
                '33.333',
                __('Inhalt der ersten Spalte (1/3 Breite).', 'wp-starter')
            ),
            self::wysiwygField(
                "field_{$prefix}_column_2",
                __('Spalte 2', 'wp-starter'),
                'column_2',
                false,
                '33.333',
                __('Inhalt der mittleren Spalte (1/3 Breite).', 'wp-starter')
            ),
            self::wysiwygField(
                "field_{$prefix}_column_3",
                __('Spalte 3', 'wp-starter'),
                'column_3',
                false,
                '33.333',
                __('Inhalt der dritten Spalte (1/3 Breite).', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
            ...self::sectionHeaderFields($prefix),
            self::wysiwygField(
                "field_{$prefix}_column_1",
                __('Spalte 1', 'wp-starter'),
                'column_1',
                false,
                '25',
                __('Inhalt der ersten Spalte (1/4 Breite).', 'wp-starter')
            ),
            self::wysiwygField(
                "field_{$prefix}_column_2",
                __('Spalte 2', 'wp-starter'),
                'column_2',
                false,
                '25',
                __('Inhalt der zweiten Spalte (1/4 Breite).', 'wp-starter')
            ),
            self::wysiwygField(
                "field_{$prefix}_column_3",
                __('Spalte 3', 'wp-starter'),
                'column_3',
                false,
                '25',
                __('Inhalt der dritten Spalte (1/4 Breite).', 'wp-starter')
            ),
            self::wysiwygField(
                "field_{$prefix}_column_4",
                __('Spalte 4', 'wp-starter'),
                'column_4',
                false,
                '25',
                __('Inhalt der vierten Spalte (1/4 Breite).', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
                __('Accordion-Einträge', 'wp-starter'),
                'accordion',
                [
                    self::iconRadioField(
                        "field_{$prefix}_accordion_icon",
                        __('Icon', 'wp-starter'),
                        'icon',
                        __('Optionales Icon vor dem Titel.', 'wp-starter')
                    ),
                    self::textField(
                        "field_{$prefix}_accordion_title",
                        __('Titel', 'wp-starter'),
                        'title',
                        true,
                        __('Der klickbare Titel des Accordion-Elements.', 'wp-starter'),
                        __('z.B. Wie funktioniert...?', 'wp-starter')
                    ),
                    self::wysiwygField(
                        "field_{$prefix}_accordion_content",
                        __('Inhalt', 'wp-starter'),
                        'content',
                        true,
                        null,
                        __('Der ausgeklappte Inhalt des Accordion-Elements.', 'wp-starter')
                    ),
                ],
                __('Eintrag hinzufügen', 'wp-starter'),
                1,
                'block',
                __('Füge beliebig viele auf- und zuklappbare Elemente hinzu.', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
                __('Überschrift', 'wp-starter'),
                'title',
                true,
                __('Die Hauptüberschrift des Call-to-Action Bereichs.', 'wp-starter'),
                __('z.B. Jetzt starten!', 'wp-starter')
            ),
            self::textareaField(
                "field_{$prefix}_content",
                __('Beschreibung', 'wp-starter'),
                'content',
                3,
                __('Kurzer Text, der zum Handeln auffordert.', 'wp-starter'),
                __('z.B. Kontaktieren Sie uns für ein unverbindliches Angebot.', 'wp-starter')
            ),
            self::linkField(
                "field_{$prefix}_button",
                __('Button', 'wp-starter'),
                'button',
                true,
                __('Der Call-to-Action Button mit Link und Text.', 'wp-starter')
            ),
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
                __('Überschrift', 'wp-starter'),
                'title',
                true,
                __('Die Hauptüberschrift des Call-to-Action Bereichs.', 'wp-starter'),
                __('z.B. Jetzt starten!', 'wp-starter')
            ),
            self::wysiwygField(
                "field_{$prefix}_content",
                __('Beschreibung', 'wp-starter'),
                'content',
                false,
                null,
                __('Kurzer Text, der zum Handeln auffordert.', 'wp-starter')
            ),
            self::linkField(
                "field_{$prefix}_cta",
                __('Button', 'wp-starter'),
                'cta',
                true,
                __('Der Call-to-Action Button mit Link und Text.', 'wp-starter')
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
                __('Video-Quelle', 'wp-starter'),
                'source',
                [
                    'wordpress' => __('Mediathek', 'wp-starter'),
                    'external' => __('YouTube / Vimeo', 'wp-starter'),
                    'url' => __('Externer Link', 'wp-starter'),
                ],
                'wordpress',
                __('Wähle, woher das Video kommt.', 'wp-starter')
            ),
            self::fileField(
                "field_{$prefix}_video",
                __('Video-Datei', 'wp-starter'),
                'video',
                'mp4,webm,ogg',
                'url',
                [[['field' => "field_{$prefix}_source", 'operator' => '==', 'value' => 'wordpress']]],
                __('Lade eine MP4, WebM oder OGG Datei hoch.', 'wp-starter')
            ),
            self::urlField(
                "field_{$prefix}_video_url",
                __('Video-URL', 'wp-starter'),
                'video_url',
                __('Füge die YouTube oder Vimeo URL ein.', 'wp-starter'),
                [[['field' => "field_{$prefix}_source", 'operator' => '==', 'value' => 'external']]],
                'https://www.youtube.com/watch?v=...'
            ),
            self::urlField(
                "field_{$prefix}_video_file_url",
                __('Video-Datei-URL', 'wp-starter'),
                'video_file_url',
                __('Direkter Link zu einer Videodatei (MP4, WebM, OGG).', 'wp-starter'),
                [[['field' => "field_{$prefix}_source", 'operator' => '==', 'value' => 'url']]],
                'https://cdn.example.com/video.mp4'
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
                __('Bild', 'wp-starter'),
                'image',
                true,
                'id',
                null,
                __('Das anzuzeigende Bild.', 'wp-starter')
            ),
            [
                'key' => "field_{$prefix}_show_border",
                'label' => __('Rahmen anzeigen', 'wp-starter'),
                'name' => 'show_border',
                'type' => 'true_false',
                'instructions' => __('Zeigt einen dezenten Rahmen um das Bild.', 'wp-starter'),
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => ['width' => '50'],
            ],
            [
                'key' => "field_{$prefix}_show_caption",
                'label' => __('Bildunterschrift anzeigen', 'wp-starter'),
                'name' => 'show_caption',
                'type' => 'true_false',
                'instructions' => __('Zeigt die in der Mediathek hinterlegte Bildunterschrift.', 'wp-starter'),
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => ['width' => '50'],
            ],
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
                "field_{$prefix}_style",
                __('Stil', 'wp-starter'),
                'style',
                [
                    'line' => __('Linie', 'wp-starter'),
                    'dots' => __('Punkte', 'wp-starter'),
                    'wave' => __('Welle', 'wp-starter'),
                    'space' => __('Abstand', 'wp-starter'),
                ],
                'line',
                __('Wähle das Aussehen des Trenners.', 'wp-starter')
            ),
            [
                'key' => "field_{$prefix}_height",
                'label' => __('Höhe', 'wp-starter'),
                'name' => 'height',
                'type' => 'number',
                'instructions' => __('Höhe in Pixel (Standard: 50)', 'wp-starter'),
                'default_value' => 50,
                'min' => 10,
                'max' => 200,
                'step' => 10,
                'append' => 'px',
            ],
            self::sectionAnchorField($prefix),
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
            ...self::sectionHeaderFields($prefix),
            self::wysiwygField(
                "field_{$prefix}_column_1",
                __('Linke Spalte (schmal)', 'wp-starter'),
                'column_1',
                false,
                '33.333',
                __('Inhalt der schmalen linken Spalte (ca. 1/3 der Breite).', 'wp-starter')
            ),
            self::wysiwygField(
                "field_{$prefix}_column_2",
                __('Rechte Spalte (breit)', 'wp-starter'),
                'column_2',
                false,
                '66.667',
                __('Inhalt der breiten rechten Spalte (ca. 2/3 der Breite).', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
            ...self::sectionHeaderFields($prefix),
            self::wysiwygField(
                "field_{$prefix}_column_1",
                __('Linke Spalte (breit)', 'wp-starter'),
                'column_1',
                false,
                '66.667',
                __('Inhalt der breiten linken Spalte (ca. 2/3 der Breite).', 'wp-starter')
            ),
            self::wysiwygField(
                "field_{$prefix}_column_2",
                __('Rechte Spalte (schmal)', 'wp-starter'),
                'column_2',
                false,
                '33.333',
                __('Inhalt der schmalen rechten Spalte (ca. 1/3 der Breite).', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
        ];
    }

    /**
     * Get Two Columns with Images layout fields
     *
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function oneColumnImageFields(string $prefix): array
    {
        return [
            ...self::sectionHeaderFields($prefix),
            self::imageField(
                "field_{$prefix}_image",
                __('Bild', 'wp-starter'),
                'image',
                false,
                'id',
                null,
                __('Bild für die Karte.', 'wp-starter'),
                '25'
            ),
            self::wysiwygField(
                "field_{$prefix}_content",
                __('Inhalt', 'wp-starter'),
                'content',
                false,
                '75',
                __('Text unter dem Bild.', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_accordion",
                __('Akkordeon', 'wp-starter'),
                'accordion',
                [
                    self::textField(
                        "field_{$prefix}_accordion_title",
                        __('Titel', 'wp-starter'),
                        'title',
                        true,
                        __('Der klickbare Titel des Akkordeon-Elements.', 'wp-starter')
                    ),
                    self::wysiwygField(
                        "field_{$prefix}_accordion_content",
                        __('Inhalt', 'wp-starter'),
                        'content',
                        true,
                        null,
                        __('Der ausgeklappte Inhalt.', 'wp-starter')
                    ),
                ],
                __('Eintrag hinzufügen', 'wp-starter'),
                0,
                'block',
                __('Auf- und zuklappbare Elemente unter dem Bild.', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
        ];
    }

    /**
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function twoColumnsImagesFields(string $prefix): array
    {
        return [
            ...self::sectionHeaderFields($prefix),
            self::imageField(
                "field_{$prefix}_image_1",
                __('Bild 1', 'wp-starter'),
                'image_1',
                false,
                'id',
                null,
                __('Bild für die linke Karte.', 'wp-starter'),
                '25'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_1",
                __('Inhalt 1', 'wp-starter'),
                'column_1',
                false,
                '75',
                __('Text unter dem ersten Bild.', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_accordion_1",
                __('Akkordeon 1', 'wp-starter'),
                'accordion_1',
                [
                    self::textField(
                        "field_{$prefix}_accordion_1_title",
                        __('Titel', 'wp-starter'),
                        'title',
                        true,
                        __('Der klickbare Titel des Akkordeon-Elements.', 'wp-starter')
                    ),
                    self::wysiwygField(
                        "field_{$prefix}_accordion_1_content",
                        __('Inhalt', 'wp-starter'),
                        'content',
                        true,
                        null,
                        __('Der ausgeklappte Inhalt.', 'wp-starter')
                    ),
                ],
                __('Eintrag hinzufügen', 'wp-starter'),
                0,
                'block',
                __('Auf- und zuklappbare Elemente unter dem Bild.', 'wp-starter')
            ),
            self::imageField(
                "field_{$prefix}_image_2",
                __('Bild 2', 'wp-starter'),
                'image_2',
                false,
                'id',
                null,
                __('Bild für die rechte Karte.', 'wp-starter'),
                '25'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_2",
                __('Inhalt 2', 'wp-starter'),
                'column_2',
                false,
                '75',
                __('Text unter dem zweiten Bild.', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_accordion_2",
                __('Akkordeon 2', 'wp-starter'),
                'accordion_2',
                [
                    self::textField(
                        "field_{$prefix}_accordion_2_title",
                        __('Titel', 'wp-starter'),
                        'title',
                        true,
                        __('Der klickbare Titel des Akkordeon-Elements.', 'wp-starter')
                    ),
                    self::wysiwygField(
                        "field_{$prefix}_accordion_2_content",
                        __('Inhalt', 'wp-starter'),
                        'content',
                        true,
                        null,
                        __('Der ausgeklappte Inhalt.', 'wp-starter')
                    ),
                ],
                __('Eintrag hinzufügen', 'wp-starter'),
                0,
                'block',
                __('Auf- und zuklappbare Elemente unter dem Bild.', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
        ];
    }

    /**
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function threeColumnsImagesFields(string $prefix): array
    {
        return [
            ...self::sectionHeaderFields($prefix),
            self::imageField(
                "field_{$prefix}_image_1",
                __('Bild 1', 'wp-starter'),
                'image_1',
                false,
                'id',
                null,
                __('Bild für die linke Karte.', 'wp-starter'),
                '25'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_1",
                __('Inhalt 1', 'wp-starter'),
                'column_1',
                false,
                '75',
                __('Text unter dem ersten Bild.', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_accordion_1",
                __('Akkordeon 1', 'wp-starter'),
                'accordion_1',
                [
                    self::textField(
                        "field_{$prefix}_accordion_1_title",
                        __('Titel', 'wp-starter'),
                        'title',
                        true,
                        __('Der klickbare Titel des Akkordeon-Elements.', 'wp-starter')
                    ),
                    self::wysiwygField(
                        "field_{$prefix}_accordion_1_content",
                        __('Inhalt', 'wp-starter'),
                        'content',
                        true,
                        null,
                        __('Der ausgeklappte Inhalt.', 'wp-starter')
                    ),
                ],
                __('Eintrag hinzufügen', 'wp-starter'),
                0,
                'block',
                __('Auf- und zuklappbare Elemente unter dem Bild.', 'wp-starter')
            ),
            self::imageField(
                "field_{$prefix}_image_2",
                __('Bild 2', 'wp-starter'),
                'image_2',
                false,
                'id',
                null,
                __('Bild für die mittlere Karte.', 'wp-starter'),
                '25'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_2",
                __('Inhalt 2', 'wp-starter'),
                'column_2',
                false,
                '75',
                __('Text unter dem zweiten Bild.', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_accordion_2",
                __('Akkordeon 2', 'wp-starter'),
                'accordion_2',
                [
                    self::textField(
                        "field_{$prefix}_accordion_2_title",
                        __('Titel', 'wp-starter'),
                        'title',
                        true,
                        __('Der klickbare Titel des Akkordeon-Elements.', 'wp-starter')
                    ),
                    self::wysiwygField(
                        "field_{$prefix}_accordion_2_content",
                        __('Inhalt', 'wp-starter'),
                        'content',
                        true,
                        null,
                        __('Der ausgeklappte Inhalt.', 'wp-starter')
                    ),
                ],
                __('Eintrag hinzufügen', 'wp-starter'),
                0,
                'block',
                __('Auf- und zuklappbare Elemente unter dem Bild.', 'wp-starter')
            ),
            self::imageField(
                "field_{$prefix}_image_3",
                __('Bild 3', 'wp-starter'),
                'image_3',
                false,
                'id',
                null,
                __('Bild für die rechte Karte.', 'wp-starter'),
                '25'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_3",
                __('Inhalt 3', 'wp-starter'),
                'column_3',
                false,
                '75',
                __('Text unter dem dritten Bild.', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_accordion_3",
                __('Akkordeon 3', 'wp-starter'),
                'accordion_3',
                [
                    self::textField(
                        "field_{$prefix}_accordion_3_title",
                        __('Titel', 'wp-starter'),
                        'title',
                        true,
                        __('Der klickbare Titel des Akkordeon-Elements.', 'wp-starter')
                    ),
                    self::wysiwygField(
                        "field_{$prefix}_accordion_3_content",
                        __('Inhalt', 'wp-starter'),
                        'content',
                        true,
                        null,
                        __('Der ausgeklappte Inhalt.', 'wp-starter')
                    ),
                ],
                __('Eintrag hinzufügen', 'wp-starter'),
                0,
                'block',
                __('Auf- und zuklappbare Elemente unter dem Bild.', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
        ];
    }

    /**
     * @param string $prefix Key prefix
     * @return array<int, array<string, mixed>>
     */
    public static function fourColumnsImagesFields(string $prefix): array
    {
        return [
            ...self::sectionHeaderFields($prefix),
            self::imageField(
                "field_{$prefix}_image_1",
                __('Bild 1', 'wp-starter'),
                'image_1',
                false,
                'id',
                null,
                __('Bild für die erste Karte.', 'wp-starter'),
                '25'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_1",
                __('Inhalt 1', 'wp-starter'),
                'column_1',
                false,
                '75',
                __('Text unter dem ersten Bild.', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_accordion_1",
                __('Akkordeon 1', 'wp-starter'),
                'accordion_1',
                [
                    self::textField(
                        "field_{$prefix}_accordion_1_title",
                        __('Titel', 'wp-starter'),
                        'title',
                        true,
                        __('Der klickbare Titel des Akkordeon-Elements.', 'wp-starter')
                    ),
                    self::wysiwygField(
                        "field_{$prefix}_accordion_1_content",
                        __('Inhalt', 'wp-starter'),
                        'content',
                        true,
                        null,
                        __('Der ausgeklappte Inhalt.', 'wp-starter')
                    ),
                ],
                __('Eintrag hinzufügen', 'wp-starter'),
                0,
                'block',
                __('Auf- und zuklappbare Elemente unter dem Bild.', 'wp-starter')
            ),
            self::imageField(
                "field_{$prefix}_image_2",
                __('Bild 2', 'wp-starter'),
                'image_2',
                false,
                'id',
                null,
                __('Bild für die zweite Karte.', 'wp-starter'),
                '25'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_2",
                __('Inhalt 2', 'wp-starter'),
                'column_2',
                false,
                '75',
                __('Text unter dem zweiten Bild.', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_accordion_2",
                __('Akkordeon 2', 'wp-starter'),
                'accordion_2',
                [
                    self::textField(
                        "field_{$prefix}_accordion_2_title",
                        __('Titel', 'wp-starter'),
                        'title',
                        true,
                        __('Der klickbare Titel des Akkordeon-Elements.', 'wp-starter')
                    ),
                    self::wysiwygField(
                        "field_{$prefix}_accordion_2_content",
                        __('Inhalt', 'wp-starter'),
                        'content',
                        true,
                        null,
                        __('Der ausgeklappte Inhalt.', 'wp-starter')
                    ),
                ],
                __('Eintrag hinzufügen', 'wp-starter'),
                0,
                'block',
                __('Auf- und zuklappbare Elemente unter dem Bild.', 'wp-starter')
            ),
            self::imageField(
                "field_{$prefix}_image_3",
                __('Bild 3', 'wp-starter'),
                'image_3',
                false,
                'id',
                null,
                __('Bild für die dritte Karte.', 'wp-starter'),
                '25'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_3",
                __('Inhalt 3', 'wp-starter'),
                'column_3',
                false,
                '75',
                __('Text unter dem dritten Bild.', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_accordion_3",
                __('Akkordeon 3', 'wp-starter'),
                'accordion_3',
                [
                    self::textField(
                        "field_{$prefix}_accordion_3_title",
                        __('Titel', 'wp-starter'),
                        'title',
                        true,
                        __('Der klickbare Titel des Akkordeon-Elements.', 'wp-starter')
                    ),
                    self::wysiwygField(
                        "field_{$prefix}_accordion_3_content",
                        __('Inhalt', 'wp-starter'),
                        'content',
                        true,
                        null,
                        __('Der ausgeklappte Inhalt.', 'wp-starter')
                    ),
                ],
                __('Eintrag hinzufügen', 'wp-starter'),
                0,
                'block',
                __('Auf- und zuklappbare Elemente unter dem Bild.', 'wp-starter')
            ),
            self::imageField(
                "field_{$prefix}_image_4",
                __('Bild 4', 'wp-starter'),
                'image_4',
                false,
                'id',
                null,
                __('Bild für die vierte Karte.', 'wp-starter'),
                '25'
            ),
            self::wysiwygField(
                "field_{$prefix}_column_4",
                __('Inhalt 4', 'wp-starter'),
                'column_4',
                false,
                '75',
                __('Text unter dem vierten Bild.', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_accordion_4",
                __('Akkordeon 4', 'wp-starter'),
                'accordion_4',
                [
                    self::textField(
                        "field_{$prefix}_accordion_4_title",
                        __('Titel', 'wp-starter'),
                        'title',
                        true,
                        __('Der klickbare Titel des Akkordeon-Elements.', 'wp-starter')
                    ),
                    self::wysiwygField(
                        "field_{$prefix}_accordion_4_content",
                        __('Inhalt', 'wp-starter'),
                        'content',
                        true,
                        null,
                        __('Der ausgeklappte Inhalt.', 'wp-starter')
                    ),
                ],
                __('Eintrag hinzufügen', 'wp-starter'),
                0,
                'block',
                __('Auf- und zuklappbare Elemente unter dem Bild.', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
        // Conditional logic: show only when source is 'manual'
        $showOnManual = [[['field' => "field_{$prefix}_source", 'operator' => '==', 'value' => 'manual']]];

        return [
            self::textField(
                "field_{$prefix}_title",
                __('Überschrift', 'wp-starter'),
                'title',
                false,
                __('Optionale Überschrift über den Testimonials.', 'wp-starter'),
                __('z.B. Das sagen unsere Kunden', 'wp-starter')
            ),
            self::buttonGroupField(
                "field_{$prefix}_source",
                __('Datenquelle', 'wp-starter'),
                'source',
                [
                    'manual' => __('Manuell eingeben', 'wp-starter'),
                    'cpt' => __('Aus Testimonials-Verwaltung', 'wp-starter'),
                ],
                'manual',
                __('Wähle, woher die Testimonials kommen sollen.', 'wp-starter')
            ),
            [
                'key' => "field_{$prefix}_testimonials",
                'label' => __('Kundenstimmen', 'wp-starter'),
                'name' => 'testimonials',
                'type' => 'repeater',
                'instructions' => __('Füge Kundenstimmen und Bewertungen hinzu.', 'wp-starter'),
                'min' => 1,
                'layout' => 'block',
                'button_label' => __('Kundenstimme hinzufügen', 'wp-starter'),
                'conditional_logic' => $showOnManual,
                'sub_fields' => [
                    self::textareaField(
                        "field_{$prefix}_testimonial_quote",
                        __('Zitat', 'wp-starter'),
                        'quote',
                        3,
                        __('Das Zitat oder die Bewertung des Kunden.', 'wp-starter'),
                        __('z.B. Die Zusammenarbeit war hervorragend...', 'wp-starter')
                    ),
                    self::textField(
                        "field_{$prefix}_testimonial_author",
                        __('Name', 'wp-starter'),
                        'author',
                        true,
                        __('Name der Person.', 'wp-starter'),
                        __('z.B. Max Mustermann', 'wp-starter')
                    ),
                    self::textField(
                        "field_{$prefix}_testimonial_role",
                        __('Position / Firma', 'wp-starter'),
                        'role',
                        false,
                        __('Position oder Firmenname.', 'wp-starter'),
                        __('z.B. Geschäftsführer, Musterfirma GmbH', 'wp-starter')
                    ),
                    self::imageField(
                        "field_{$prefix}_testimonial_image",
                        __('Foto', 'wp-starter'),
                        'image',
                        false,
                        'id',
                        null,
                        __('Optionales Foto der Person.', 'wp-starter')
                    ),
                ],
            ],
            self::buttonGroupField(
                "field_{$prefix}_columns",
                __('Spalten', 'wp-starter'),
                'columns',
                [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                ],
                '3',
                __('Anzahl der Spalten für die Darstellung.', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
                __('Überschrift', 'wp-starter'),
                'title',
                false,
                __('Optionale Überschrift über den Karten.', 'wp-starter'),
                __('z.B. Unsere Leistungen', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_cards",
                __('Karten', 'wp-starter'),
                'cards',
                [
                    self::iconRadioField(
                        "field_{$prefix}_card_icon",
                        __('Icon', 'wp-starter'),
                        'icon',
                        __('Wähle ein Icon aus dem Theme.', 'wp-starter')
                    ),
                    self::textField(
                        "field_{$prefix}_card_title",
                        __('Titel', 'wp-starter'),
                        'title',
                        true,
                        __('Titel der Karte.', 'wp-starter'),
                        __('z.B. Beratung', 'wp-starter')
                    ),
                    self::textareaField(
                        "field_{$prefix}_card_content",
                        __('Beschreibung', 'wp-starter'),
                        'content',
                        3,
                        __('Kurze Beschreibung.', 'wp-starter'),
                        __('z.B. Wir beraten Sie umfassend...', 'wp-starter')
                    ),
                    self::linkField(
                        "field_{$prefix}_card_link",
                        __('Link', 'wp-starter'),
                        'link',
                        false,
                        __('Optionaler Link zu mehr Informationen.', 'wp-starter')
                    ),
                ],
                __('Karte hinzufügen', 'wp-starter'),
                1,
                'block',
                __('Füge beliebig viele Karten hinzu.', 'wp-starter')
            ),
            self::buttonGroupField(
                "field_{$prefix}_columns",
                __('Spalten', 'wp-starter'),
                'columns',
                [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                '3',
                __('Anzahl der Spalten für die Darstellung.', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
                __('Überschrift', 'wp-starter'),
                'title',
                false,
                __('Optionale Überschrift über der Galerie.', 'wp-starter'),
                __('z.B. Unsere Projekte', 'wp-starter')
            ),
            [
                'key' => "field_{$prefix}_images",
                'label' => __('Bilder', 'wp-starter'),
                'name' => 'images',
                'type' => 'gallery',
                'instructions' => __('Wähle die Bilder für die Galerie. Klick auf ein Bild öffnet die Lightbox.', 'wp-starter'),
                'required' => 1,
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
                'min' => 1,
            ],
            self::buttonGroupField(
                "field_{$prefix}_columns",
                __('Spalten', 'wp-starter'),
                'columns',
                [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                ],
                '3',
                __('Anzahl der Spalten für die Darstellung.', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
                __('Überschrift', 'wp-starter'),
                'title',
                false,
                __('Optionale Überschrift über dem Logo-Slider.', 'wp-starter'),
                __('z.B. Unsere Partner', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_logos",
                __('Logos', 'wp-starter'),
                'logos',
                [
                    self::imageField(
                        "field_{$prefix}_logo_image",
                        __('Logo', 'wp-starter'),
                        'logo',
                        true,
                        'id',
                        null,
                        __('Das Logo (idealerweise mit transparentem Hintergrund).', 'wp-starter')
                    ),
                    self::textField(
                        "field_{$prefix}_logo_name",
                        __('Name', 'wp-starter'),
                        'name',
                        false,
                        __('Name des Partners (für Barrierefreiheit).', 'wp-starter'),
                        __('z.B. Musterfirma GmbH', 'wp-starter')
                    ),
                    self::urlField(
                        "field_{$prefix}_logo_link",
                        __('Website', 'wp-starter'),
                        'link',
                        __('Optionaler Link zur Partner-Website.', 'wp-starter'),
                        null,
                        'https://...'
                    ),
                ],
                __('Logo hinzufügen', 'wp-starter'),
                1,
                'table',
                __('Füge Partner- oder Kundenlogos hinzu.', 'wp-starter')
            ),
            self::trueFalseField(
                "field_{$prefix}_autoplay",
                __('Automatisch abspielen', 'wp-starter'),
                'autoplay',
                true,
                __('Logos automatisch durchlaufen lassen.', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
            self::tabField("field_{$prefix}_tab_form", __('Formular', 'wp-starter')),
            self::messageField(
                "field_{$prefix}_form_help",
                __('<strong>So findest du die Formular-ID:</strong><br>1) Gehe zu <em>Formulare</em> im Menü<br>2) Wähle dein Formular aus<br>3) Die ID steht in der URL (z.B. post=<strong>123</strong>) oder im Shortcode', 'wp-starter')
            ),
            [
                'key' => "field_{$prefix}_form_id",
                'label' => __('Formular-ID', 'wp-starter'),
                'name' => 'form_id',
                'type' => 'text',
                'instructions' => __('Trage nur die Zahl ein.', 'wp-starter'),
                'placeholder' => __('z.B. 123', 'wp-starter'),
                'required' => 1,
            ],

            // Tab: Inhalt
            self::tabField("field_{$prefix}_tab_content", __('Inhalt', 'wp-starter')),
            self::textField(
                "field_{$prefix}_title",
                __('Überschrift', 'wp-starter'),
                'title',
                false,
                __('Überschrift für den Kontaktbereich.', 'wp-starter'),
                __('z.B. Kontaktieren Sie uns', 'wp-starter')
            ),
            self::wysiwygField(
                "field_{$prefix}_content",
                __('Einleitungstext', 'wp-starter'),
                'content',
                false,
                null,
                __('Optionaler Text über dem Formular.', 'wp-starter')
            ),

            // Tab: Optionen
            self::tabField("field_{$prefix}_tab_options", __('Optionen', 'wp-starter')),
            self::trueFalseField(
                "field_{$prefix}_show_contact_info",
                __('Kontaktdaten anzeigen', 'wp-starter'),
                'show_contact_info',
                true,
                __('Zeigt die Kontaktdaten aus den Theme-Einstellungen an.', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
            self::tabField("field_{$prefix}_tab_map", __('Karte', 'wp-starter')),
            self::messageField(
                "field_{$prefix}_map_help",
                __('<strong>So bekommst du die Einbettungs-URL:</strong><br>1) Öffne <a href="https://maps.google.com" target="_blank">Google Maps</a><br>2) Suche deinen Standort<br>3) Klicke auf "Teilen" → "Karte einbetten"<br>4) Kopiere die URL aus dem HTML-Code (beginnt mit https://www.google.com/maps/embed)', 'wp-starter')
            ),
            self::urlField(
                "field_{$prefix}_embed_url",
                __('Google Maps Einbettungs-URL', 'wp-starter'),
                'embed_url',
                __('Der Block zeigt automatisch einen DSGVO-Hinweis.', 'wp-starter'),
                null,
                'https://www.google.com/maps/embed?pb=...'
            ),
            self::numberField(
                "field_{$prefix}_height",
                __('Höhe', 'wp-starter'),
                'height',
                400,
                200,
                800,
                50,
                'px',
                __('Höhe der Karte in Pixeln.', 'wp-starter')
            ),

            // Tab: Inhalt
            self::tabField("field_{$prefix}_tab_content", __('Inhalt', 'wp-starter')),
            self::textField(
                "field_{$prefix}_title",
                __('Überschrift', 'wp-starter'),
                'title',
                false,
                __('Optionale Überschrift über der Karte.', 'wp-starter'),
                __('z.B. So finden Sie uns', 'wp-starter')
            ),
            self::textareaField(
                "field_{$prefix}_address",
                __('Adresse', 'wp-starter'),
                'address',
                2,
                __('Die vollständige Adresse (für den "Route planen" Link).', 'wp-starter'),
                __('Musterstraße 123, 12345 Musterstadt', 'wp-starter')
            ),
            self::trueFalseField(
                "field_{$prefix}_show_directions_link",
                __('„Route planen" Link anzeigen', 'wp-starter'),
                'show_directions_link',
                true,
                __('Zeigt einen Link zum Planen der Route an.', 'wp-starter')
            ),

            // Tab: Darstellung
            self::tabField("field_{$prefix}_tab_style", __('Darstellung', 'wp-starter')),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
                __('Überschrift', 'wp-starter'),
                'title',
                false,
                __('Optionale Überschrift über den Tabs.', 'wp-starter'),
                __('z.B. Häufige Fragen', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_tabs",
                __('Tabs', 'wp-starter'),
                'tabs',
                [
                    self::iconRadioField(
                        "field_{$prefix}_tab_icon",
                        __('Icon', 'wp-starter'),
                        'icon',
                        __('Optionales Icon neben dem Tab-Titel.', 'wp-starter')
                    ),
                    self::textField(
                        "field_{$prefix}_tab_title",
                        __('Tab-Titel', 'wp-starter'),
                        'title',
                        true,
                        __('Der Titel des Tabs (im Tab-Button sichtbar).', 'wp-starter'),
                        __('z.B. Übersicht', 'wp-starter')
                    ),
                    self::wysiwygField(
                        "field_{$prefix}_tab_content",
                        __('Inhalt', 'wp-starter'),
                        'content',
                        true,
                        null,
                        __('Der Inhalt, der angezeigt wird, wenn dieser Tab aktiv ist.', 'wp-starter')
                    ),
                ],
                __('Tab hinzufügen', 'wp-starter'),
                2,
                'block',
                __('Füge mindestens 2 Tabs hinzu.', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
                __('Überschrift', 'wp-starter'),
                'title',
                false,
                __('Optionale Überschrift über der Preistabelle.', 'wp-starter'),
                __('z.B. Unsere Pakete', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_plans",
                __('Preispakete', 'wp-starter'),
                'plans',
                [
                    // Accordion: Preis (offen)
                    self::accordionField("field_{$prefix}_acc_price", __('Preis', 'wp-starter'), true),
                    [
                        'key' => "field_{$prefix}_plan_name",
                        'label' => __('Paketname', 'wp-starter'),
                        'name' => 'name',
                        'type' => 'text',
                        'instructions' => __('Name des Pakets.', 'wp-starter'),
                        'placeholder' => __('z.B. Basic, Professional', 'wp-starter'),
                        'required' => 1,
                        'wrapper' => ['width' => '40'],
                    ],
                    [
                        'key' => "field_{$prefix}_plan_price",
                        'label' => __('Preis', 'wp-starter'),
                        'name' => 'price',
                        'type' => 'text',
                        'instructions' => __('Der Preis inkl. Währung.', 'wp-starter'),
                        'placeholder' => __('z.B. 49€', 'wp-starter'),
                        'required' => 1,
                        'wrapper' => ['width' => '30'],
                    ],
                    [
                        'key' => "field_{$prefix}_plan_period",
                        'label' => __('Zeitraum', 'wp-starter'),
                        'name' => 'period',
                        'type' => 'text',
                        'instructions' => __('Abrechnungszeitraum.', 'wp-starter'),
                        'placeholder' => __('z.B. / Monat', 'wp-starter'),
                        'wrapper' => ['width' => '30'],
                    ],
                    self::trueFalseField(
                        "field_{$prefix}_plan_featured",
                        __('Hervorheben', 'wp-starter'),
                        'is_featured',
                        false,
                        __('Dieses Paket als „Empfohlen" hervorheben.', 'wp-starter')
                    ),
                    // Accordion: Leistungen
                    self::accordionField("field_{$prefix}_acc_features", __('Leistungen', 'wp-starter')),
                    self::wysiwygField(
                        "field_{$prefix}_plan_features",
                        __('Leistungen', 'wp-starter'),
                        'features',
                        true,
                        null,
                        __('Liste der enthaltenen Leistungen (als Aufzählung).', 'wp-starter')
                    ),
                    // Accordion: Aktion
                    self::accordionField("field_{$prefix}_acc_cta", __('Aktion', 'wp-starter')),
                    self::linkField(
                        "field_{$prefix}_plan_cta",
                        __('Button', 'wp-starter'),
                        'cta',
                        false,
                        __('Call-to-Action Button für dieses Paket.', 'wp-starter')
                    ),
                    // Accordion Ende
                    self::accordionField("field_{$prefix}_acc_end", '', false, true, true),
                ],
                __('Paket hinzufügen', 'wp-starter'),
                1,
                'block',
                __('Füge Preispakete hinzu (empfohlen: 3 Pakete).', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
        // Conditional logic: show only when source is 'manual'
        $showOnManual = [[['field' => "field_{$prefix}_source", 'operator' => '==', 'value' => 'manual']]];

        return [
            self::textField(
                "field_{$prefix}_title",
                __('Überschrift', 'wp-starter'),
                'title',
                false,
                __('Optionale Überschrift über dem Team.', 'wp-starter'),
                __('z.B. Unser Team', 'wp-starter')
            ),
            self::buttonGroupField(
                "field_{$prefix}_source",
                __('Datenquelle', 'wp-starter'),
                'source',
                [
                    'manual' => __('Manuell eingeben', 'wp-starter'),
                    'cpt' => __('Aus Team-Verwaltung', 'wp-starter'),
                ],
                'manual',
                __('Wähle, woher die Teammitglieder kommen sollen.', 'wp-starter')
            ),
            [
                'key' => "field_{$prefix}_members",
                'label' => __('Teammitglieder', 'wp-starter'),
                'name' => 'members',
                'type' => 'repeater',
                'instructions' => __('Füge Teammitglieder hinzu.', 'wp-starter'),
                'min' => 1,
                'layout' => 'block',
                'button_label' => __('Mitglied hinzufügen', 'wp-starter'),
                'conditional_logic' => $showOnManual,
                'sub_fields' => [
                    // Accordion: Person (offen)
                    self::accordionField("field_{$prefix}_acc_person", __('Person', 'wp-starter'), true),
                    self::imageField(
                        "field_{$prefix}_member_image",
                        __('Foto', 'wp-starter'),
                        'image',
                        false,
                        'id',
                        null,
                        __('Portraitfoto (quadratisch empfohlen).', 'wp-starter')
                    ),
                    self::textField(
                        "field_{$prefix}_member_name",
                        __('Name', 'wp-starter'),
                        'name',
                        true,
                        __('Vollständiger Name.', 'wp-starter'),
                        __('z.B. Max Mustermann', 'wp-starter')
                    ),
                    self::textField(
                        "field_{$prefix}_member_position",
                        __('Position', 'wp-starter'),
                        'position',
                        false,
                        __('Jobtitel oder Rolle.', 'wp-starter'),
                        __('z.B. Geschäftsführer', 'wp-starter')
                    ),
                    // Accordion: Details
                    self::accordionField("field_{$prefix}_acc_details", __('Details', 'wp-starter')),
                    self::textareaField(
                        "field_{$prefix}_member_bio",
                        __('Kurzbiografie', 'wp-starter'),
                        'bio',
                        2,
                        __('Optionale kurze Beschreibung.', 'wp-starter'),
                        __('z.B. Seit 2020 im Unternehmen...', 'wp-starter')
                    ),
                    // Accordion: Kontakt
                    self::accordionField("field_{$prefix}_acc_contact", __('Kontakt', 'wp-starter')),
                    [
                        'key' => "field_{$prefix}_member_email",
                        'label' => __('E-Mail', 'wp-starter'),
                        'name' => 'email',
                        'type' => 'email',
                        'instructions' => __('Direkte E-Mail-Adresse.', 'wp-starter'),
                        'placeholder' => 'max@beispiel.de',
                        'wrapper' => ['width' => '50'],
                    ],
                    [
                        'key' => "field_{$prefix}_member_linkedin",
                        'label' => __('LinkedIn', 'wp-starter'),
                        'name' => 'linkedin',
                        'type' => 'url',
                        'instructions' => __('Link zum LinkedIn-Profil.', 'wp-starter'),
                        'placeholder' => 'https://linkedin.com/in/...',
                        'wrapper' => ['width' => '50'],
                    ],
                    // Accordion Ende
                    self::accordionField("field_{$prefix}_acc_end", '', false, true, true),
                ],
            ],
            self::buttonGroupField(
                "field_{$prefix}_columns",
                __('Spalten', 'wp-starter'),
                'columns',
                [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                '3',
                __('Anzahl der Spalten für die Darstellung.', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
                __('Überschrift', 'wp-starter'),
                'title',
                false,
                __('Optionale Überschrift über den Statistiken.', 'wp-starter'),
                __('z.B. Zahlen & Fakten', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_stats",
                __('Statistiken', 'wp-starter'),
                'stats',
                [
                    self::numberField(
                        "field_{$prefix}_stat_number",
                        __('Zahl', 'wp-starter'),
                        'number',
                        0,
                        0,
                        999999999,
                        0.01,
                        '',
                        __('Die anzuzeigende Zahl (wird animiert).', 'wp-starter')
                    ),
                    self::textField(
                        "field_{$prefix}_stat_suffix",
                        __('Suffix', 'wp-starter'),
                        'suffix',
                        false,
                        __('Optionales Suffix nach der Zahl.', 'wp-starter'),
                        __('z.B. +, %, Jahre', 'wp-starter')
                    ),
                    self::textField(
                        "field_{$prefix}_stat_label",
                        __('Beschriftung', 'wp-starter'),
                        'label',
                        true,
                        __('Was diese Zahl bedeutet.', 'wp-starter'),
                        __('z.B. Zufriedene Kunden', 'wp-starter')
                    ),
                    self::iconRadioField(
                        "field_{$prefix}_stat_icon",
                        __('Icon', 'wp-starter'),
                        'icon',
                        __('Optionales Icon für diese Statistik.', 'wp-starter')
                    ),
                ],
                __('Statistik hinzufügen', 'wp-starter'),
                1,
                'table',
                __('Füge Kennzahlen hinzu (empfohlen: 3-4).', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
                __('Überschrift', 'wp-starter'),
                'title',
                false,
                __('Optionale Überschrift über dem Zeitstrahl.', 'wp-starter'),
                __('z.B. Unsere Geschichte', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_events",
                __('Ereignisse', 'wp-starter'),
                'events',
                [
                    // Accordion: Event (offen)
                    self::accordionField("field_{$prefix}_acc_event", __('Event', 'wp-starter'), true),
                    [
                        'key' => "field_{$prefix}_event_year",
                        'label' => __('Jahr / Datum', 'wp-starter'),
                        'name' => 'year',
                        'type' => 'text',
                        'instructions' => __('Zeitpunkt des Ereignisses.', 'wp-starter'),
                        'placeholder' => __('z.B. 2020', 'wp-starter'),
                        'required' => 1,
                        'wrapper' => ['width' => '30'],
                    ],
                    [
                        'key' => "field_{$prefix}_event_title",
                        'label' => __('Titel', 'wp-starter'),
                        'name' => 'title',
                        'type' => 'text',
                        'instructions' => __('Kurzer Titel des Ereignisses.', 'wp-starter'),
                        'placeholder' => __('z.B. Firmengründung', 'wp-starter'),
                        'required' => 1,
                        'wrapper' => ['width' => '70'],
                    ],
                    // Accordion: Details
                    self::accordionField("field_{$prefix}_acc_details", __('Details', 'wp-starter')),
                    self::wysiwygField(
                        "field_{$prefix}_event_content",
                        __('Beschreibung', 'wp-starter'),
                        'content',
                        false,
                        null,
                        __('Detaillierte Beschreibung des Ereignisses.', 'wp-starter')
                    ),
                    self::imageField(
                        "field_{$prefix}_event_image",
                        __('Bild', 'wp-starter'),
                        'image',
                        false,
                        'id',
                        null,
                        __('Optionales Bild zum Ereignis.', 'wp-starter')
                    ),
                    // Accordion Ende
                    self::accordionField("field_{$prefix}_acc_end", '', false, true, true),
                ],
                __('Ereignis hinzufügen', 'wp-starter'),
                2,
                'block',
                __('Füge Ereignisse in chronologischer Reihenfolge hinzu.', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
            self::tabField("field_{$prefix}_tab_content", __('Inhalt', 'wp-starter')),
            self::textField(
                "field_{$prefix}_title",
                __('Überschrift', 'wp-starter'),
                'title',
                false,
                __('Optionale Überschrift über den Beiträgen.', 'wp-starter'),
                __('z.B. Aktuelle Neuigkeiten', 'wp-starter')
            ),
            self::buttonGroupField(
                "field_{$prefix}_post_type",
                __('Beitragstyp', 'wp-starter'),
                'post_type',
                [
                    'post' => __('Blog-Beiträge', 'wp-starter'),
                    'page' => __('Seiten', 'wp-starter'),
                ],
                'post',
                __('Welcher Beitragstyp angezeigt werden soll.', 'wp-starter')
            ),
            [
                'key' => "field_{$prefix}_category",
                'label' => __('Kategorie', 'wp-starter'),
                'name' => 'category',
                'type' => 'taxonomy',
                'instructions' => __('Optional: Nur Beiträge aus dieser Kategorie anzeigen.', 'wp-starter'),
                'taxonomy' => 'category',
                'field_type' => 'select',
                'allow_null' => 1,
                'return_format' => 'id',
                'conditional_logic' => $showOnPosts,
            ],
            self::numberField(
                "field_{$prefix}_posts_per_page",
                __('Anzahl', 'wp-starter'),
                'posts_per_page',
                3,
                1,
                12,
                1,
                __('Beiträge', 'wp-starter'),
                __('Wie viele Beiträge angezeigt werden sollen.', 'wp-starter')
            ),

            // Tab: Anzeige
            self::tabField("field_{$prefix}_tab_display", __('Anzeige', 'wp-starter')),
            [
                'key' => "field_{$prefix}_show_excerpt",
                'label' => __('Auszug', 'wp-starter'),
                'name' => 'show_excerpt',
                'type' => 'true_false',
                'instructions' => __('Zeigt einen kurzen Textauszug an.', 'wp-starter'),
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => ['width' => '33'],
            ],
            [
                'key' => "field_{$prefix}_show_date",
                'label' => __('Datum', 'wp-starter'),
                'name' => 'show_date',
                'type' => 'true_false',
                'instructions' => __('Zeigt das Veröffentlichungsdatum an.', 'wp-starter'),
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => ['width' => '33'],
            ],
            [
                'key' => "field_{$prefix}_show_author",
                'label' => __('Autor', 'wp-starter'),
                'name' => 'show_author',
                'type' => 'true_false',
                'instructions' => __('Zeigt den Autorennamen an.', 'wp-starter'),
                'default_value' => 0,
                'ui' => 1,
                'wrapper' => ['width' => '34'],
            ],

            // Tab: Layout
            self::tabField("field_{$prefix}_tab_layout", __('Layout', 'wp-starter')),
            self::buttonGroupField(
                "field_{$prefix}_columns",
                __('Spalten', 'wp-starter'),
                'columns',
                [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                '3',
                __('Anzahl der Spalten für die Darstellung.', 'wp-starter')
            ),
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
                __('Überschrift', 'wp-starter'),
                'title',
                false,
                __('Optionale Überschrift über dem Vergleich.', 'wp-starter'),
                __('z.B. Vorher vs. Nachher', 'wp-starter')
            ),
            self::imageField(
                "field_{$prefix}_image_before",
                __('Vorher-Bild', 'wp-starter'),
                'image_before',
                true,
                'id',
                null,
                __('Das „Vorher"-Bild (links).', 'wp-starter'),
                '50'
            ),
            self::imageField(
                "field_{$prefix}_image_after",
                __('Nachher-Bild', 'wp-starter'),
                'image_after',
                true,
                'id',
                null,
                __('Das „Nachher"-Bild (rechts). Sollte gleiche Maße haben.', 'wp-starter'),
                '50'
            ),
            [
                'key' => "field_{$prefix}_label_before",
                'label' => __('Label Vorher', 'wp-starter'),
                'name' => 'label_before',
                'type' => 'text',
                'instructions' => __('Text für das Vorher-Label.', 'wp-starter'),
                'placeholder' => __('Vorher', 'wp-starter'),
                'wrapper' => ['width' => '50'],
            ],
            [
                'key' => "field_{$prefix}_label_after",
                'label' => __('Label Nachher', 'wp-starter'),
                'name' => 'label_after',
                'type' => 'text',
                'instructions' => __('Text für das Nachher-Label.', 'wp-starter'),
                'placeholder' => __('Nachher', 'wp-starter'),
                'wrapper' => ['width' => '50'],
            ],
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
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
                __('Überschrift', 'wp-starter'),
                'title',
                false,
                __('Optionale Überschrift über der Tabelle.', 'wp-starter'),
                __('z.B. Preisübersicht', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_headers",
                __('Spaltenüberschriften', 'wp-starter'),
                'headers',
                [
                    self::textField(
                        "field_{$prefix}_header_label",
                        __('Spaltenname', 'wp-starter'),
                        'label',
                        true,
                        __('Name der Spalte.', 'wp-starter'),
                        __('z.B. Produkt, Preis, Menge', 'wp-starter')
                    ),
                ],
                __('Spalte hinzufügen', 'wp-starter'),
                1,
                'table',
                __('Definiere die Spalten der Tabelle.', 'wp-starter')
            ),
            self::repeaterField(
                "field_{$prefix}_rows",
                __('Zeilen', 'wp-starter'),
                'rows',
                [
                    self::repeaterField(
                        "field_{$prefix}_row_cells",
                        __('Zellen', 'wp-starter'),
                        'cells',
                        [
                            self::textareaField(
                                "field_{$prefix}_cell_content",
                                __('Inhalt', 'wp-starter'),
                                'content',
                                1,
                                __('Inhalt der Zelle (HTML erlaubt).', 'wp-starter'),
                                ''
                            ),
                        ],
                        __('Zelle hinzufügen', 'wp-starter'),
                        1,
                        'table',
                        ''
                    ),
                ],
                __('Zeile hinzufügen', 'wp-starter'),
                1,
                'row',
                __('Füge Datenzeilen hinzu.', 'wp-starter')
            ),
            [
                'key' => "field_{$prefix}_striped",
                'label' => __('Gestreifte Zeilen', 'wp-starter'),
                'name' => 'striped',
                'type' => 'true_false',
                'instructions' => __('Abwechselnde Hintergrundfarben für bessere Lesbarkeit.', 'wp-starter'),
                'default_value' => 1,
                'ui' => 1,
                'wrapper' => ['width' => '50'],
            ],
            [
                'key' => "field_{$prefix}_bordered",
                'label' => __('Mit Rahmen', 'wp-starter'),
                'name' => 'bordered',
                'type' => 'true_false',
                'instructions' => __('Zeigt Rahmenlinien um die Zellen.', 'wp-starter'),
                'default_value' => 0,
                'ui' => 1,
                'wrapper' => ['width' => '50'],
            ],
            self::backgroundColorField($prefix),
            self::sectionAnchorField($prefix),
        ];
    }

    /**
     * Member Downloads block fields
     *
     * No configuration needed — the downloads component is self-contained.
     *
     * @param string $prefix Unique prefix for the field key
     * @return array<int, array<string, mixed>>
     */
    public static function memberDownloadsFields(string $prefix): array
    {
        return [
            self::sectionAnchorField($prefix),
        ];
    }

    /**
     * Get section anchor field for manual anchor-ID override
     *
     * @param string $prefix Key prefix
     * @return array<string, mixed>
     */
    public static function sectionAnchorField(string $prefix): array
    {
        return self::textField(
            "field_{$prefix}_section_anchor",
            __('Anker-ID', 'wp-starter'),
            'section_anchor',
            false,
            __('Optionale ID für Anker-Links (z.B. "kontakt"). Wird automatisch generiert wenn leer.', 'wp-starter'),
            'z.B. kontakt'
        );
    }
}
