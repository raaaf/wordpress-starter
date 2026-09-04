<?php

declare(strict_types=1);

namespace WordpressStarter\Acf;

final class PageSettings
{
    public static function register(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_page_settings',
            'title' => __('Seiteneinstellungen', 'wp-starter'),
            'fields' => [
                FieldDefinitions::trueFalseField(
                    'field_page_is_landing_page',
                    __('Als Landingpage anzeigen', 'wp-starter'),
                    'page_is_landing_page',
                    false,
                    __('Blendet Navigation, Brotkrumen, Fußnavigation und Social-Links aus. Logo, Kontakt und rechtliche Links bleiben sichtbar.', 'wp-starter'),
                ),
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
            'position' => 'side',
            'menu_order' => 5,
        ]);
    }

    public static function isLandingPage(): bool
    {
        return function_exists('is_page') && is_page() && function_exists('get_field') && (bool) get_field('page_is_landing_page');
    }
}
