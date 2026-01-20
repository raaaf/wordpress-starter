<?php

declare(strict_types=1);

namespace WordpressStarter\Acf;

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
            'title' => 'Page Builder',
            'fields' => [
                [
                    'key' => 'field_page_sections',
                    'label' => 'Page Sections',
                    'name' => 'page_sections',
                    'type' => 'flexible_content',
                    'instructions' => 'Build your page by adding content sections',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'layouts' => self::getLayouts(),
                    'button_label' => 'Add Section',
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
     * Get the background color field definition
     *
     * @param string $prefix Unique prefix for the field key
     * @return array<string, mixed>
     */
    private static function getBackgroundColorField(string $prefix): array
    {
        return [
            'key' => "field_{$prefix}_background_color",
            'label' => 'Hintergrundfarbe',
            'name' => 'background_color',
            'type' => 'select',
            'choices' => [
                'primary' => 'Standard',
                'secondary' => 'Sekundär',
                'tertiary' => 'Tertiär',
                'brand' => 'Brand',
                'brand-subtle' => 'Brand Dezent',
                'inverse' => 'Invers',
            ],
            'default_value' => 'primary',
            'allow_null' => 0,
            'ui' => 1,
        ];
    }

    /**
     * Get all flexible content layouts
     *
     * @return array<int, array{key: string, name: string, label: string, display: string, sub_fields: array<int, array<string, mixed>>}>
     */
    private static function getLayouts(): array
    {
        return [
            // Hero Section
            [
                'key' => 'layout_hero',
                'name' => 'hero',
                'label' => 'Hero',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_hero_title',
                        'label' => 'Title',
                        'name' => 'title',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_hero_subtitle',
                        'label' => 'Subtitle',
                        'name' => 'subtitle',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'field_hero_content',
                        'label' => 'Content',
                        'name' => 'content',
                        'type' => 'wysiwyg',
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    [
                        'key' => 'field_hero_background_image',
                        'label' => 'Background Image',
                        'name' => 'background_image',
                        'type' => 'image',
                        'return_format' => 'array',
                        'preview_size' => 'medium',
                        'library' => 'all',
                    ],
                    [
                        'key' => 'field_hero_cta',
                        'label' => 'Call to Action',
                        'name' => 'cta',
                        'type' => 'link',
                        'return_format' => 'array',
                    ],
                    self::getBackgroundColorField('hero'),
                ],
            ],

            // One Column
            [
                'key' => 'layout_one_column',
                'name' => 'one_column',
                'label' => 'One Column',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_one_column_content',
                        'label' => 'Content',
                        'name' => 'content',
                        'type' => 'wysiwyg',
                        'required' => 1,
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    self::getBackgroundColorField('one_column'),
                ],
            ],

            // Two Columns
            [
                'key' => 'layout_two_columns',
                'name' => 'two_columns',
                'label' => 'Two Columns',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_two_columns_left',
                        'label' => 'Left Column',
                        'name' => 'left_column',
                        'type' => 'wysiwyg',
                        'required' => 1,
                        'wrapper' => ['width' => '50'],
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    [
                        'key' => 'field_two_columns_right',
                        'label' => 'Right Column',
                        'name' => 'right_column',
                        'type' => 'wysiwyg',
                        'required' => 1,
                        'wrapper' => ['width' => '50'],
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    self::getBackgroundColorField('two_columns'),
                ],
            ],

            // Two Columns with Images
            [
                'key' => 'layout_two_columns_images',
                'name' => 'two_columns_images',
                'label' => 'Two Columns with Images',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_two_columns_images_left_image',
                        'label' => 'Left Image',
                        'name' => 'left_image',
                        'type' => 'image',
                        'required' => 1,
                        'wrapper' => ['width' => '50'],
                        'return_format' => 'array',
                        'preview_size' => 'medium',
                        'library' => 'all',
                    ],
                    [
                        'key' => 'field_two_columns_images_left_content',
                        'label' => 'Left Content',
                        'name' => 'left_content',
                        'type' => 'wysiwyg',
                        'wrapper' => ['width' => '50'],
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    [
                        'key' => 'field_two_columns_images_right_image',
                        'label' => 'Right Image',
                        'name' => 'right_image',
                        'type' => 'image',
                        'required' => 1,
                        'wrapper' => ['width' => '50'],
                        'return_format' => 'array',
                        'preview_size' => 'medium',
                        'library' => 'all',
                    ],
                    [
                        'key' => 'field_two_columns_images_right_content',
                        'label' => 'Right Content',
                        'name' => 'right_content',
                        'type' => 'wysiwyg',
                        'wrapper' => ['width' => '50'],
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    self::getBackgroundColorField('two_columns_images'),
                ],
            ],

            // Three Columns
            [
                'key' => 'layout_three_columns',
                'name' => 'three_columns',
                'label' => 'Three Columns',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_three_columns_left',
                        'label' => 'Left Column',
                        'name' => 'left_column',
                        'type' => 'wysiwyg',
                        'required' => 1,
                        'wrapper' => ['width' => '33.333'],
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    [
                        'key' => 'field_three_columns_center',
                        'label' => 'Center Column',
                        'name' => 'center_column',
                        'type' => 'wysiwyg',
                        'required' => 1,
                        'wrapper' => ['width' => '33.333'],
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    [
                        'key' => 'field_three_columns_right',
                        'label' => 'Right Column',
                        'name' => 'right_column',
                        'type' => 'wysiwyg',
                        'required' => 1,
                        'wrapper' => ['width' => '33.333'],
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    self::getBackgroundColorField('three_columns'),
                ],
            ],

            // Four Columns
            [
                'key' => 'layout_four_columns',
                'name' => 'four_columns',
                'label' => 'Four Columns',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_four_columns_one',
                        'label' => 'Column 1',
                        'name' => 'column_1',
                        'type' => 'wysiwyg',
                        'required' => 1,
                        'wrapper' => ['width' => '25'],
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    [
                        'key' => 'field_four_columns_two',
                        'label' => 'Column 2',
                        'name' => 'column_2',
                        'type' => 'wysiwyg',
                        'required' => 1,
                        'wrapper' => ['width' => '25'],
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    [
                        'key' => 'field_four_columns_three',
                        'label' => 'Column 3',
                        'name' => 'column_3',
                        'type' => 'wysiwyg',
                        'required' => 1,
                        'wrapper' => ['width' => '25'],
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    [
                        'key' => 'field_four_columns_four',
                        'label' => 'Column 4',
                        'name' => 'column_4',
                        'type' => 'wysiwyg',
                        'required' => 1,
                        'wrapper' => ['width' => '25'],
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    self::getBackgroundColorField('four_columns'),
                ],
            ],

            // One Third / Two Thirds
            [
                'key' => 'layout_one_third_two_thirds',
                'name' => 'one_third_two_thirds',
                'label' => 'One Third / Two Thirds',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_one_third_left',
                        'label' => 'Left Column (1/3)',
                        'name' => 'left_column',
                        'type' => 'wysiwyg',
                        'required' => 1,
                        'wrapper' => ['width' => '33.333'],
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    [
                        'key' => 'field_two_thirds_right',
                        'label' => 'Right Column (2/3)',
                        'name' => 'right_column',
                        'type' => 'wysiwyg',
                        'required' => 1,
                        'wrapper' => ['width' => '66.667'],
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    self::getBackgroundColorField('one_third_two_thirds'),
                ],
            ],

            // Two Thirds / One Third
            [
                'key' => 'layout_two_thirds_one_third',
                'name' => 'two_thirds_one_third',
                'label' => 'Two Thirds / One Third',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_two_thirds_left',
                        'label' => 'Left Column (2/3)',
                        'name' => 'left_column',
                        'type' => 'wysiwyg',
                        'required' => 1,
                        'wrapper' => ['width' => '66.667'],
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    [
                        'key' => 'field_one_third_right',
                        'label' => 'Right Column (1/3)',
                        'name' => 'right_column',
                        'type' => 'wysiwyg',
                        'required' => 1,
                        'wrapper' => ['width' => '33.333'],
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    self::getBackgroundColorField('two_thirds_one_third'),
                ],
            ],

            // Accordion
            [
                'key' => 'layout_accordion',
                'name' => 'accordion',
                'label' => 'Accordion',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_accordion_items',
                        'label' => 'Accordion Items',
                        'name' => 'items',
                        'type' => 'repeater',
                        'required' => 1,
                        'min' => 1,
                        'layout' => 'block',
                        'button_label' => 'Add Item',
                        'sub_fields' => [
                            [
                                'key' => 'field_accordion_item_title',
                                'label' => 'Title',
                                'name' => 'title',
                                'type' => 'text',
                                'required' => 1,
                            ],
                            [
                                'key' => 'field_accordion_item_content',
                                'label' => 'Content',
                                'name' => 'content',
                                'type' => 'wysiwyg',
                                'required' => 1,
                                'tabs' => 'all',
                                'toolbar' => 'full',
                                'media_upload' => 1,
                            ],
                        ],
                    ],
                    self::getBackgroundColorField('accordion'),
                ],
            ],

            // CTA (Call to Action)
            [
                'key' => 'layout_cta',
                'name' => 'cta',
                'label' => 'Call to Action',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_cta_title',
                        'label' => 'Title',
                        'name' => 'title',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_cta_content',
                        'label' => 'Content',
                        'name' => 'content',
                        'type' => 'textarea',
                        'rows' => 3,
                    ],
                    [
                        'key' => 'field_cta_button',
                        'label' => 'Button',
                        'name' => 'button',
                        'type' => 'link',
                        'required' => 1,
                        'return_format' => 'array',
                    ],
                    self::getBackgroundColorField('cta'),
                ],
            ],

            // Video
            [
                'key' => 'layout_video',
                'name' => 'video',
                'label' => 'Video',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_video_type',
                        'label' => 'Video Type',
                        'name' => 'video_type',
                        'type' => 'select',
                        'required' => 1,
                        'choices' => [
                            'youtube' => 'YouTube',
                            'vimeo' => 'Vimeo',
                            'self' => 'Self Hosted',
                        ],
                        'default_value' => 'youtube',
                    ],
                    [
                        'key' => 'field_video_url',
                        'label' => 'Video URL',
                        'name' => 'video_url',
                        'type' => 'url',
                        'required' => 1,
                        'conditional_logic' => [
                            [
                                [
                                    'field' => 'field_video_type',
                                    'operator' => '!=',
                                    'value' => 'self',
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'field_video_file',
                        'label' => 'Video File',
                        'name' => 'video_file',
                        'type' => 'file',
                        'required' => 1,
                        'return_format' => 'array',
                        'library' => 'all',
                        'mime_types' => 'mp4,webm,ogg',
                        'conditional_logic' => [
                            [
                                [
                                    'field' => 'field_video_type',
                                    'operator' => '==',
                                    'value' => 'self',
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'field_video_poster',
                        'label' => 'Poster Image',
                        'name' => 'poster_image',
                        'type' => 'image',
                        'return_format' => 'array',
                        'preview_size' => 'medium',
                        'library' => 'all',
                    ],
                    self::getBackgroundColorField('video'),
                ],
            ],

            // Image
            [
                'key' => 'layout_image',
                'name' => 'image',
                'label' => 'Image',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_image_image',
                        'label' => 'Image',
                        'name' => 'image',
                        'type' => 'image',
                        'required' => 1,
                        'return_format' => 'array',
                        'preview_size' => 'large',
                        'library' => 'all',
                    ],
                    [
                        'key' => 'field_image_caption',
                        'label' => 'Caption',
                        'name' => 'caption',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'field_image_alignment',
                        'label' => 'Alignment',
                        'name' => 'alignment',
                        'type' => 'select',
                        'choices' => [
                            'left' => 'Left',
                            'center' => 'Center',
                            'right' => 'Right',
                            'wide' => 'Wide',
                            'full' => 'Full Width',
                        ],
                        'default_value' => 'center',
                    ],
                    self::getBackgroundColorField('image'),
                ],
            ],

            // Divider
            [
                'key' => 'layout_divider',
                'name' => 'divider',
                'label' => 'Divider',
                'display' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_divider_style',
                        'label' => 'Style',
                        'name' => 'style',
                        'type' => 'select',
                        'choices' => [
                            'line' => 'Line',
                            'dots' => 'Dots',
                            'wave' => 'Wave',
                            'space' => 'Empty Space',
                        ],
                        'default_value' => 'line',
                    ],
                    [
                        'key' => 'field_divider_height',
                        'label' => 'Height',
                        'name' => 'height',
                        'type' => 'number',
                        'default_value' => 50,
                        'min' => 10,
                        'max' => 200,
                        'step' => 10,
                        'append' => 'px',
                    ],
                    self::getBackgroundColorField('divider'),
                ],
            ],
        ];
    }
}