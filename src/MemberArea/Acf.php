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
                    __('Deaktivieren um den internen Bereich komplett auszuschalten — keine geschützten Seiten, kein Login.', 'wp-starter'),
                ),

                // Visual separator for auth fields (no DB impact)
                [
                    'key' => 'field_member_auth_heading',
                    'label' => __('Authentifizierung', 'wp-starter'),
                    'name' => '',
                    'type' => 'message',
                    'message' => '',
                    'esc_html' => 0,
                ],

                FieldDefinitions::radioField(
                    'field_member_auth_mode',
                    __('Authentifizierungs-Modus', 'wp-starter'),
                    'member_auth_mode',
                    [
                        'password' => __('Gemeinsames Passwort', 'wp-starter'),
                        'wordpress' => __('WordPress-Benutzer', 'wp-starter'),
                    ],
                    'password',
                    'vertical',
                    __('Passwort: ein gemeinsames Passwort für alle. WordPress: jeder Nutzer meldet sich mit seinem WP-Konto an.', 'wp-starter'),
                    null,
                    ['width' => '25'],
                ),
                FieldDefinitions::checkboxField(
                    'field_member_allowed_roles',
                    __('Erlaubte Rollen', 'wp-starter'),
                    'member_allowed_roles',
                    [],
                    '',
                    'vertical',
                    'value',
                    __('Welche WordPress-Rollen haben Zugang? Leer = alle angemeldeten Benutzer.', 'wp-starter'),
                    [
                        [
                            [
                                'field' => 'field_member_auth_mode',
                                'operator' => '==',
                                'value' => 'wordpress',
                            ],
                        ],
                    ],
                    ['width' => '75'],
                ),
                FieldDefinitions::textField(
                    'field_member_shared_password',
                    __('Passwort', 'wp-starter'),
                    'member_shared_password',
                    false,
                    __('Wird verschlüsselt gespeichert. Leer lassen um nicht zu ändern.', 'wp-starter'),
                    __('Neues Passwort eingeben…', 'wp-starter'),
                    [
                        [
                            [
                                'field' => 'field_member_auth_mode',
                                'operator' => '==',
                                'value' => 'password',
                            ],
                        ],
                    ],
                    ['width' => '50'],
                ),
                FieldDefinitions::numberField(
                    'field_member_cookie_ttl',
                    __('Cookie-Gültigkeit (Stunden)', 'wp-starter'),
                    'member_cookie_ttl',
                    14,
                    1,
                    8760,
                    1,
                    '',
                    __('Wie lange bleibt ein Benutzer nach dem Login eingeloggt.', 'wp-starter'),
                    [
                        [
                            [
                                'field' => 'field_member_auth_mode',
                                'operator' => '==',
                                'value' => 'password',
                            ],
                        ],
                    ],
                    ['width' => '25'],
                ),

                FieldDefinitions::textField(
                    'field_member_login_title',
                    __('Login-Überschrift', 'wp-starter'),
                    'member_login_title',
                    false,
                    '',
                    __('Interner Bereich', 'wp-starter'),
                ),

                FieldDefinitions::textareaField(
                    'field_member_login_description',
                    __('Login-Beschreibung', 'wp-starter'),
                    'member_login_description',
                    3,
                    '',
                    __('Bitte melden Sie sich an, um auf den internen Bereich zuzugreifen.', 'wp-starter'),
                ),

                FieldDefinitions::textareaField(
                    'field_member_area_intro',
                    __('Bereichs-Einleitung', 'wp-starter'),
                    'member_area_intro',
                    3,
                    __('Wird nach dem Login unterhalb der Überschrift angezeigt.', 'wp-starter'),
                    __('Willkommen im internen Bereich. Hier finden Sie alle Dokumente…', 'wp-starter'),
                ),


            ],
            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'theme-options-member-area',
                    ],
                ],
            ],
        ]);
    }

    private static function registerPageFields(): void
    {
        acf_add_local_field_group([
            'key' => 'group_member_page_protection',
            'title' => __('Zugangskontrolle', 'wp-starter'),
            'fields' => [
                FieldDefinitions::trueFalseField(
                    'field_page_is_protected',
                    __('Geschützte Seite', 'wp-starter'),
                    'page_is_protected',
                    false,
                    __('Aktivieren um diese Seite passwortgeschützt zu machen.', 'wp-starter'),
                ),
                FieldDefinitions::trueFalseField(
                    'field_page_is_member_area',
                    __('Interner Bereich', 'wp-starter'),
                    'page_is_member_area',
                    false,
                    __('Aktivieren um diese Seite als Dashboard des internen Bereichs zu verwenden.', 'wp-starter'),
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
                return get_option('options_member_shared_password') ?: $value;
            }

            return wp_hash_password($value);
        }, 10, 1);
    }
}
