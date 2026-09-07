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

        $landingPageCondition = [
            'conditional_logic' => [
                [
                    ['field' => 'field_page_is_landing_page', 'operator' => '==', 'value' => '1'],
                ],
            ],
        ];

        acf_add_local_field_group([
            'key' => 'group_page_settings',
            'title' => __('Seiteneinstellungen', 'wp-starter'),
            'fields' => [
                FieldDefinitions::trueFalseField(
                    'field_page_is_landing_page',
                    __('Als Landingpage anzeigen', 'wp-starter'),
                    'page_is_landing_page',
                    false,
                    __('Blendet Navigation, Brotkrumen, Fußnavigation und Social-Links aus. Logo, Kontakt und rechtliche Links bleiben standardmäßig sichtbar.', 'wp-starter'),
                ),
                array_merge(
                    FieldDefinitions::trueFalseField(
                        'field_page_hide_global_notice',
                        __('Seitenweiten Hinweis ausblenden', 'wp-starter'),
                        'page_hide_global_notice',
                    ),
                    $landingPageCondition,
                ),
                array_merge(
                    FieldDefinitions::trueFalseField(
                        'field_page_reduced_footer',
                        __('Reduzierten Footer anzeigen', 'wp-starter'),
                        'page_reduced_footer',
                    ),
                    $landingPageCondition,
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

    public static function shouldHideGlobalNotice(): bool
    {
        return self::isLandingPage() && (bool) get_field('page_hide_global_notice');
    }

    public static function shouldReduceFooter(): bool
    {
        return self::isLandingPage() && (bool) get_field('page_reduced_footer');
    }

    public static function isLandingPage(): bool
    {
        return function_exists('is_page') && is_page() && function_exists('get_field') && (bool) get_field('page_is_landing_page');
    }
}
