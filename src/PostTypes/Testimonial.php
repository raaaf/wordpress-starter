<?php

declare(strict_types=1);

namespace WordpressStarter\PostTypes;

use WordpressStarter\Acf\FieldDefinitions;

/**
 * Testimonial Custom Post Type
 *
 * Manages customer testimonials/reviews for display on the website.
 */
class Testimonial extends AbstractPostType
{
    protected static string $postType = 'testimonial';
    protected static string $singular = 'Testimonial';
    protected static string $plural = 'Testimonials';
    protected static string $menuIcon = 'dashicons-format-quote';
    protected static int $menuPosition = 25;
    protected static bool $hasArchive = false;

    /** @var array<string> */
    protected static array $supports = ['title', 'thumbnail'];

    /** @var array<string, mixed>|false */
    protected static array|false $rewrite = ['slug' => 'testimonials'];

    /**
     * Register ACF fields for testimonials
     */
    public static function registerFields(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_testimonial',
            'title' => 'Testimonial Details',
            'fields' => self::getFieldDefinitions(),
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => self::$postType,
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
        ]);
    }

    /**
     * Get field definitions for testimonials
     *
     * @return array<int, array<string, mixed>>
     */
    private static function getFieldDefinitions(): array
    {
        return [
            FieldDefinitions::textField(
                'testimonial_author_name',
                'Name',
                'author_name',
                true,
                'Der Name des Kunden/der Kundin.'
            ),
            FieldDefinitions::textField(
                'testimonial_author_position',
                'Position / Unternehmen',
                'author_position',
                false,
                'z.B. "Geschäftsführer, Musterfirma GmbH"'
            ),
            FieldDefinitions::textareaField(
                'testimonial_content',
                'Testimonial Text',
                'content',
                4,
                'Das Testimonial/die Kundenbewertung.'
            ),
            FieldDefinitions::numberField(
                'testimonial_rating',
                'Bewertung',
                'rating',
                0,
                1,
                5,
                1,
                '',
                'Bewertung von 1-5 Sternen (optional).'
            ),
            FieldDefinitions::imageField(
                'testimonial_author_image',
                'Profilbild',
                'author_image',
                false,
                'Optionales Profilbild des Kunden.'
            ),
            FieldDefinitions::urlField(
                'testimonial_source_url',
                'Quell-URL',
                'source_url',
                'Link zur Original-Bewertung (z.B. Google, Trustpilot).'
            ),
        ];
    }

    /**
     * Get testimonials for display
     *
     * @param int $limit Number of testimonials to return (-1 for all)
     * @param string $orderby Order by field
     * @param string $order Order direction
     * @return array<int, array{
     *   id: int,
     *   author_name: string,
     *   author_position: string,
     *   content: string,
     *   rating: int|null,
     *   author_image: array<string, mixed>|null,
     *   source_url: string|null
     * }>
     */
    public static function getTestimonials(int $limit = -1, string $orderby = 'date', string $order = 'DESC'): array
    {
        $posts = self::all([
            'posts_per_page' => $limit,
            'orderby' => $orderby,
            'order' => $order,
        ]);

        $testimonials = [];
        foreach ($posts as $post) {
            $testimonials[] = [
                'id' => $post->ID,
                'author_name' => get_field('author_name', $post->ID) ?: '',
                'author_position' => get_field('author_position', $post->ID) ?: '',
                'content' => get_field('content', $post->ID) ?: '',
                'rating' => get_field('rating', $post->ID) ?: null,
                'author_image' => get_field('author_image', $post->ID) ?: null,
                'source_url' => get_field('source_url', $post->ID) ?: null,
            ];
        }

        return $testimonials;
    }

    /**
     * Get a random testimonial
     *
     * @return array<string, mixed>|null
     */
    public static function getRandom(): ?array
    {
        $testimonials = self::getTestimonials(-1, 'rand');
        return $testimonials[0] ?? null;
    }
}
