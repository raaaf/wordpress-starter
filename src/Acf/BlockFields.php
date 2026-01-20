<?php

declare(strict_types=1);

namespace WordpressStarter\Acf;

/**
 * Registers ACF field groups for all theme blocks
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

        add_action('acf/init', [self::class, 'registerFieldGroups']);
    }

    /**
     * Register all field groups
     */
    public static function registerFieldGroups(): void
    {
        // One Column Block
        acf_add_local_field_group([
            'key' => 'group_block_one_column',
            'title' => 'One Column Block',
            'fields' => [
                [
                    'key' => 'field_block_one_column_label',
                    'label' => 'Label',
                    'name' => 'label',
                    'type' => 'text',
                ],
                [
                    'key' => 'field_block_one_column_content',
                    'label' => 'Content',
                    'name' => 'content',
                    'type' => 'wysiwyg',
                    'required' => 1,
                    'tabs' => 'all',
                    'toolbar' => 'full',
                    'media_upload' => 1,
                ],
                self::getBackgroundColorField('block_one_column'),
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/one-column',
                    ],
                ],
            ],
        ]);

        // Two Columns Block
        acf_add_local_field_group([
            'key' => 'group_block_two_columns',
            'title' => 'Two Columns Block',
            'fields' => [
                [
                    'key' => 'field_block_two_columns_left',
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
                    'key' => 'field_block_two_columns_right',
                    'label' => 'Right Column',
                    'name' => 'right_column',
                    'type' => 'wysiwyg',
                    'required' => 1,
                    'wrapper' => ['width' => '50'],
                    'tabs' => 'all',
                    'toolbar' => 'full',
                    'media_upload' => 1,
                ],
                self::getBackgroundColorField('block_two_columns'),
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/two-columns',
                    ],
                ],
            ],
        ]);

        // Two Columns Images Block
        acf_add_local_field_group([
            'key' => 'group_block_two_columns_images',
            'title' => 'Two Columns Images Block',
            'fields' => [
                [
                    'key' => 'field_block_two_columns_images_image',
                    'label' => 'Image 1',
                    'name' => 'image',
                    'type' => 'image',
                    'required' => 1,
                    'wrapper' => ['width' => '50'],
                    'return_format' => 'id',
                    'preview_size' => 'medium',
                    'library' => 'all',
                ],
                [
                    'key' => 'field_block_two_columns_images_content',
                    'label' => 'Content 1',
                    'name' => 'content',
                    'type' => 'wysiwyg',
                    'wrapper' => ['width' => '50'],
                    'tabs' => 'all',
                    'toolbar' => 'full',
                    'media_upload' => 1,
                ],
                [
                    'key' => 'field_block_two_columns_images_image_2',
                    'label' => 'Image 2',
                    'name' => 'image_2',
                    'type' => 'image',
                    'required' => 1,
                    'wrapper' => ['width' => '50'],
                    'return_format' => 'id',
                    'preview_size' => 'medium',
                    'library' => 'all',
                ],
                [
                    'key' => 'field_block_two_columns_images_content_2',
                    'label' => 'Content 2',
                    'name' => 'content_2',
                    'type' => 'wysiwyg',
                    'wrapper' => ['width' => '50'],
                    'tabs' => 'all',
                    'toolbar' => 'full',
                    'media_upload' => 1,
                ],
                self::getBackgroundColorField('block_two_columns_images'),
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/two-columns-images',
                    ],
                ],
            ],
        ]);

        // Three Columns Block
        acf_add_local_field_group([
            'key' => 'group_block_three_columns',
            'title' => 'Three Columns Block',
            'fields' => [
                [
                    'key' => 'field_block_three_columns_left',
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
                    'key' => 'field_block_three_columns_center',
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
                    'key' => 'field_block_three_columns_right',
                    'label' => 'Right Column',
                    'name' => 'right_column',
                    'type' => 'wysiwyg',
                    'required' => 1,
                    'wrapper' => ['width' => '33.333'],
                    'tabs' => 'all',
                    'toolbar' => 'full',
                    'media_upload' => 1,
                ],
                self::getBackgroundColorField('block_three_columns'),
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/three-columns',
                    ],
                ],
            ],
        ]);

        // Four Columns Block
        acf_add_local_field_group([
            'key' => 'group_block_four_columns',
            'title' => 'Four Columns Block',
            'fields' => [
                [
                    'key' => 'field_block_four_columns_1',
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
                    'key' => 'field_block_four_columns_2',
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
                    'key' => 'field_block_four_columns_3',
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
                    'key' => 'field_block_four_columns_4',
                    'label' => 'Column 4',
                    'name' => 'column_4',
                    'type' => 'wysiwyg',
                    'required' => 1,
                    'wrapper' => ['width' => '25'],
                    'tabs' => 'all',
                    'toolbar' => 'full',
                    'media_upload' => 1,
                ],
                self::getBackgroundColorField('block_four_columns'),
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/four-columns',
                    ],
                ],
            ],
        ]);

        // One Third / Two Thirds Block
        acf_add_local_field_group([
            'key' => 'group_block_one_third_two_thirds',
            'title' => 'One Third / Two Thirds Block',
            'fields' => [
                [
                    'key' => 'field_block_one_third_left',
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
                    'key' => 'field_block_one_third_right',
                    'label' => 'Right Column (2/3)',
                    'name' => 'right_column',
                    'type' => 'wysiwyg',
                    'required' => 1,
                    'wrapper' => ['width' => '66.667'],
                    'tabs' => 'all',
                    'toolbar' => 'full',
                    'media_upload' => 1,
                ],
                self::getBackgroundColorField('block_one_third_two_thirds'),
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/one-third-two-thirds',
                    ],
                ],
            ],
        ]);

        // Two Thirds / One Third Block
        acf_add_local_field_group([
            'key' => 'group_block_two_thirds_one_third',
            'title' => 'Two Thirds / One Third Block',
            'fields' => [
                [
                    'key' => 'field_block_two_thirds_left',
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
                    'key' => 'field_block_two_thirds_right',
                    'label' => 'Right Column (1/3)',
                    'name' => 'right_column',
                    'type' => 'wysiwyg',
                    'required' => 1,
                    'wrapper' => ['width' => '33.333'],
                    'tabs' => 'all',
                    'toolbar' => 'full',
                    'media_upload' => 1,
                ],
                self::getBackgroundColorField('block_two_thirds_one_third'),
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/two-thirds-one-third',
                    ],
                ],
            ],
        ]);

        // Accordion Block
        acf_add_local_field_group([
            'key' => 'group_block_accordion',
            'title' => 'Accordion Block',
            'fields' => [
                [
                    'key' => 'field_block_accordion_items',
                    'label' => 'Accordion Items',
                    'name' => 'items',
                    'type' => 'repeater',
                    'required' => 1,
                    'min' => 1,
                    'layout' => 'block',
                    'button_label' => 'Add Item',
                    'sub_fields' => [
                        [
                            'key' => 'field_block_accordion_item_title',
                            'label' => 'Title',
                            'name' => 'title',
                            'type' => 'text',
                            'required' => 1,
                        ],
                        [
                            'key' => 'field_block_accordion_item_content',
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
                self::getBackgroundColorField('block_accordion'),
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/accordion',
                    ],
                ],
            ],
        ]);

        // CTA Block
        acf_add_local_field_group([
            'key' => 'group_block_cta',
            'title' => 'CTA Block',
            'fields' => [
                [
                    'key' => 'field_block_cta_title',
                    'label' => 'Title',
                    'name' => 'title',
                    'type' => 'text',
                    'required' => 1,
                ],
                [
                    'key' => 'field_block_cta_content',
                    'label' => 'Content',
                    'name' => 'content',
                    'type' => 'wysiwyg',
                    'tabs' => 'all',
                    'toolbar' => 'full',
                    'media_upload' => 1,
                ],
                [
                    'key' => 'field_block_cta_cta',
                    'label' => 'Call to Action',
                    'name' => 'cta',
                    'type' => 'link',
                    'required' => 1,
                    'return_format' => 'array',
                ],
                [
                    'key' => 'field_block_cta_background_color',
                    'label' => 'Hintergrundfarbe',
                    'name' => 'background_color',
                    'type' => 'select',
                    'choices' => [
                        'brand' => 'Brand',
                        'brand-secondary' => 'Brand Sekundär',
                    ],
                    'default_value' => 'brand',
                    'allow_null' => 0,
                    'ui' => 1,
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/cta',
                    ],
                ],
            ],
        ]);

        // Image Block
        acf_add_local_field_group([
            'key' => 'group_block_image',
            'title' => 'Image Block',
            'fields' => [
                [
                    'key' => 'field_block_image_image',
                    'label' => 'Image',
                    'name' => 'image',
                    'type' => 'image',
                    'required' => 1,
                    'return_format' => 'id',
                    'preview_size' => 'large',
                    'library' => 'all',
                ],
                [
                    'key' => 'field_block_image_show_border',
                    'label' => 'Show Border',
                    'name' => 'show_border',
                    'type' => 'true_false',
                    'default_value' => 1,
                    'ui' => 1,
                ],
                [
                    'key' => 'field_block_image_show_caption',
                    'label' => 'Show Caption',
                    'name' => 'show_caption',
                    'type' => 'true_false',
                    'default_value' => 1,
                    'ui' => 1,
                ],
                self::getBackgroundColorField('block_image'),
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/image',
                    ],
                ],
            ],
        ]);

        // Divider Block
        acf_add_local_field_group([
            'key' => 'group_block_divider',
            'title' => 'Divider Block',
            'fields' => [
                self::getBackgroundColorField('block_divider'),
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/divider',
                    ],
                ],
            ],
        ]);

        // Video Block
        acf_add_local_field_group([
            'key' => 'group_block_video',
            'title' => 'Video Block',
            'fields' => [
                [
                    'key' => 'field_block_video_source',
                    'label' => 'Video Source',
                    'name' => 'video_source',
                    'type' => 'select',
                    'required' => 1,
                    'choices' => [
                        'media' => 'Media Library',
                        'external' => 'External URL (YouTube/Vimeo)',
                    ],
                    'default_value' => 'media',
                    'ui' => 1,
                ],
                [
                    'key' => 'field_block_video_media',
                    'label' => 'Video File',
                    'name' => 'video',
                    'type' => 'file',
                    'return_format' => 'array',
                    'library' => 'all',
                    'mime_types' => 'mp4,webm,ogg',
                    'conditional_logic' => [
                        [
                            [
                                'field' => 'field_block_video_source',
                                'operator' => '==',
                                'value' => 'media',
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'field_block_video_url',
                    'label' => 'Video URL',
                    'name' => 'video_url',
                    'type' => 'url',
                    'instructions' => 'YouTube or Vimeo URL',
                    'conditional_logic' => [
                        [
                            [
                                'field' => 'field_block_video_source',
                                'operator' => '==',
                                'value' => 'external',
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'field_block_video_poster',
                    'label' => 'Poster Image',
                    'name' => 'poster',
                    'type' => 'image',
                    'return_format' => 'array',
                    'preview_size' => 'medium',
                    'library' => 'all',
                ],
                self::getBackgroundColorField('block_video'),
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/video',
                    ],
                ],
            ],
        ]);

        // Hero Block
        acf_add_local_field_group([
            'key' => 'group_block_hero',
            'title' => 'Hero Block',
            'fields' => [
                [
                    'key' => 'field_block_hero_title',
                    'label' => 'Title',
                    'name' => 'title',
                    'type' => 'text',
                    'required' => 1,
                ],
                [
                    'key' => 'field_block_hero_subtitle',
                    'label' => 'Subtitle',
                    'name' => 'subtitle',
                    'type' => 'text',
                ],
                [
                    'key' => 'field_block_hero_content',
                    'label' => 'Content',
                    'name' => 'content',
                    'type' => 'wysiwyg',
                    'tabs' => 'all',
                    'toolbar' => 'full',
                    'media_upload' => 1,
                ],
                [
                    'key' => 'field_block_hero_background_image',
                    'label' => 'Background Image',
                    'name' => 'background_image',
                    'type' => 'image',
                    'return_format' => 'array',
                    'preview_size' => 'medium',
                    'library' => 'all',
                ],
                [
                    'key' => 'field_block_hero_cta',
                    'label' => 'Call to Action',
                    'name' => 'cta',
                    'type' => 'link',
                    'return_format' => 'array',
                ],
                self::getBackgroundColorField('block_hero'),
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/hero',
                    ],
                ],
            ],
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
}
