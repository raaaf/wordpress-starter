<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

use WordpressStarter\Acf\FieldDefinitions;

class Acf
{
    public static function register(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        self::registerOptionsFields();
        self::registerPageFields();
        self::registerPasswordHashing();
        self::registerAllowedRolesChoices();
    }

    private static function registerOptionsFields(): void
    {
        acf_add_local_field_group([
            'key' => 'group_member_area_options',
            'title' => __('Interner Bereich — Einstellungen', 'wp-starter'),
            'fields' => [
                // Tab: Allgemein
                FieldDefinitions::tabField('field_member_tab_general', __('Allgemein', 'wp-starter')),

                FieldDefinitions::trueFalseField(
                    'field_member_area_active',
                    __('Interner Bereich aktiv', 'wp-starter'),
                    'member_area_active',
                    true,
                    __('Deaktivieren um den internen Bereich komplett auszuschalten — keine geschützten Seiten, kein Login.', 'wp-starter')
                ),

                // Visual separator for auth fields (no DB impact)
                [
                    'key'     => 'field_member_auth_heading',
                    'label'   => __('Authentifizierung', 'wp-starter'),
                    'name'    => '',
                    'type'    => 'message',
                    'message' => '',
                    'esc_html' => 0,
                ],

                [
                    'key'           => 'field_member_auth_mode',
                    'label'         => __('Authentifizierungs-Modus', 'wp-starter'),
                    'name'          => 'member_auth_mode',
                    'type'          => 'radio',
                    'instructions'  => __('Passwort: ein gemeinsames Passwort für alle. WordPress: jeder Nutzer meldet sich mit seinem WP-Konto an.', 'wp-starter'),
                    'choices'       => [
                        'password'  => __('Gemeinsames Passwort', 'wp-starter'),
                        'wordpress' => __('WordPress-Benutzer', 'wp-starter'),
                    ],
                    'default_value' => 'password',
                    'layout'        => 'vertical',
                    'wrapper'       => ['width' => '25'],
                ],
                [
                    'key'               => 'field_member_allowed_roles',
                    'label'             => __('Erlaubte Rollen', 'wp-starter'),
                    'name'              => 'member_allowed_roles',
                    'type'              => 'checkbox',
                    'instructions'      => __('Welche WordPress-Rollen haben Zugang? Leer = alle angemeldeten Benutzer.', 'wp-starter'),
                    'choices'           => [],
                    'wrapper'           => ['width' => '75'],
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_member_auth_mode',
                                'operator' => '==',
                                'value'    => 'wordpress',
                            ],
                        ],
                    ],
                ],
                [
                    'key'               => 'field_member_shared_password',
                    'label'             => __('Passwort', 'wp-starter'),
                    'name'              => 'member_shared_password',
                    'type'              => 'text',
                    'instructions'      => __('Wird verschlüsselt gespeichert. Leer lassen um nicht zu ändern.', 'wp-starter'),
                    'placeholder'       => __('Neues Passwort eingeben…', 'wp-starter'),
                    'wrapper'           => ['width' => '50'],
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_member_auth_mode',
                                'operator' => '==',
                                'value'    => 'password',
                            ],
                        ],
                    ],
                ],
                [
                    'key'               => 'field_member_cookie_ttl',
                    'label'             => __('Cookie-Gültigkeit (Stunden)', 'wp-starter'),
                    'name'              => 'member_cookie_ttl',
                    'type'              => 'number',
                    'instructions'      => __('Wie lange bleibt ein Benutzer nach dem Login eingeloggt.', 'wp-starter'),
                    'default_value'     => 14,
                    'min'               => 1,
                    'max'               => 8760,
                    'wrapper'           => ['width' => '25'],
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_member_auth_mode',
                                'operator' => '==',
                                'value'    => 'password',
                            ],
                        ],
                    ],
                ],

                FieldDefinitions::textField(
                    'field_member_login_title',
                    __('Login-Überschrift', 'wp-starter'),
                    'member_login_title',
                    false,
                    '',
                    __('Interner Bereich', 'wp-starter')
                ),

                [
                    'key'         => 'field_member_login_description',
                    'label'       => __('Login-Beschreibung', 'wp-starter'),
                    'name'        => 'member_login_description',
                    'type'        => 'textarea',
                    'rows'        => 3,
                    'placeholder' => __('Bitte melden Sie sich an, um auf den internen Bereich zuzugreifen.', 'wp-starter'),
                ],

                [
                    'key'          => 'field_member_area_intro',
                    'label'        => __('Bereichs-Einleitung', 'wp-starter'),
                    'name'         => 'member_area_intro',
                    'type'         => 'textarea',
                    'instructions' => __('Wird nach dem Login unterhalb der Überschrift angezeigt.', 'wp-starter'),
                    'rows'         => 3,
                    'placeholder'  => __('Willkommen im internen Bereich. Hier finden Sie alle Dokumente…', 'wp-starter'),
                ],


            ],
            'location' => [
                [
                    [
                        'param'    => 'options_page',
                        'operator' => '==',
                        'value'    => 'theme-options-member-area',
                    ],
                ],
            ],
        ]);
    }

    private static function registerPageFields(): void
    {
        acf_add_local_field_group([
            'key'    => 'group_member_page_protection',
            'title'  => __('Zugangskontrolle', 'wp-starter'),
            'fields' => [
                FieldDefinitions::trueFalseField(
                    'field_page_is_protected',
                    __('Geschützte Seite', 'wp-starter'),
                    'page_is_protected',
                    false,
                    __('Aktivieren um diese Seite passwortgeschützt zu machen.', 'wp-starter')
                ),
                FieldDefinitions::trueFalseField(
                    'field_page_is_member_area',
                    __('Interner Bereich', 'wp-starter'),
                    'page_is_member_area',
                    false,
                    __('Aktivieren um diese Seite als Dashboard des internen Bereichs zu verwenden.', 'wp-starter')
                ),
            ],
            'location' => [
                [
                    [
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'page',
                    ],
                ],
            ],
            'position' => 'side',
        ]);
    }

    private static function registerAllowedRolesChoices(): void
    {
        add_filter('acf/load_field/key=field_member_allowed_roles', static function (array $field): array {
            $field['choices'] = [];
            foreach (wp_roles()->roles as $slug => $role) {
                $field['choices'][$slug] = translate_user_role($role['name']);
            }
            return $field;
        });
    }

    private static function registerPasswordHashing(): void
    {
        // Never show the stored hash in the admin input field — only when rendering an ACF field form, not during AJAX
        add_filter('acf/load_value/key=field_member_shared_password', function ($value) {
            if (is_admin() && !wp_doing_ajax()) {
                return '';
            }
            return $value;
        }, 10, 1);

        // Hash password before saving to ACF options
        add_filter('acf/update_value/key=field_member_shared_password', function ($value) {
            if (empty($value)) {
                // Use get_option directly to bypass the load_value filter above
                return get_option('options_member_shared_password') ?: '';
            }
            return wp_hash_password($value);
        }, 10, 1);
    }
}
