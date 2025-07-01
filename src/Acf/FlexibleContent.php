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
     * Get all flexible content layouts
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
                    [
                        'key' => 'field_cta_background_color',
                        'label' => 'Background Color',
                        'name' => 'background_color',
                        'type' => 'color_picker',
                        'default_value' => '#f8f9fa',
                    ],
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
                ],
            ],
        ];
    }
}