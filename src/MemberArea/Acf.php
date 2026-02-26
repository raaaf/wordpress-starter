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

                // Group: Authentifizierung
                [
                    'key'        => 'field_member_group_auth',
                    'label'      => __('Authentifizierung', 'wp-starter'),
                    'name'       => 'member_group_auth',
                    'type'       => 'group',
                    'layout'     => 'block',
                    'sub_fields' => [
                        [
                            'key'           => 'field_member_auth_mode',
                            'label'         => __('Authentifizierungs-Modus', 'wp-starter'),
                            'name'          => 'member_auth_mode',
                            'type'          => 'select',
                            'instructions'  => __('Passwort: ein gemeinsames. WordPress: eigenes WP-Konto.', 'wp-starter'),
                            'choices'       => [
                                'password'  => __('Gemeinsames Passwort', 'wp-starter'),
                                'wordpress' => __('WordPress-Benutzer', 'wp-starter'),
                            ],
                            'default_value' => 'password',
                            'allow_null'    => 0,
                            'ui'            => 1,
                            'wrapper'       => ['width' => '25'],
                        ],
                        [
                            'key'               => 'field_member_allowed_roles',
                            'label'             => __('Erlaubte Rollen', 'wp-starter'),
                            'name'              => 'member_allowed_roles',
                            'type'              => 'checkbox',
                            'instructions'      => __('Welche WordPress-Rollen haben Zugang? Leer = alle angemeldeten Benutzer.', 'wp-starter'),
                            'choices'           => [
                                'subscriber'    => __('Abonnent', 'wp-starter'),
                                'contributor'   => __('Mitarbeiter', 'wp-starter'),
                                'author'        => __('Autor', 'wp-starter'),
                                'editor'        => __('Redakteur', 'wp-starter'),
                                'administrator' => __('Administrator', 'wp-starter'),
                            ],
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
                    ],
                ],

                // Tab: Dokumente
                FieldDefinitions::tabField('field_member_tab_documents', __('Dokumente', 'wp-starter')),

                [
                    'key'          => 'field_member_downloads',
                    'label'        => __('Dokumente', 'wp-starter'),
                    'name'         => 'member_downloads',
                    'type'         => 'repeater',
                    'instructions' => __('Dateien die im internen Bereich heruntergeladen werden können.', 'wp-starter'),
                    'layout'       => 'block',
                    'button_label' => __('Dokument hinzufügen', 'wp-starter'),
                    'sub_fields'   => [
                        FieldDefinitions::textField(
                            'field_download_title',
                            __('Titel', 'wp-starter'),
                            'download_title',
                            true
                        ),
                        [
                            'key'   => 'field_download_description',
                            'label' => __('Beschreibung', 'wp-starter'),
                            'name'  => 'download_description',
                            'type'  => 'textarea',
                            'rows'  => 2,
                        ],
                        // Source type selector
                        [
                            'key'           => 'field_download_source_type',
                            'label'         => __('Quelle', 'wp-starter'),
                            'name'          => 'download_source_type',
                            'type'          => 'select',
                            'choices'       => [
                                'upload'   => __('Hochgeladene Datei', 'wp-starter'),
                                'external' => __('Externe URL', 'wp-starter'),
                                'folder'   => __('HTTP-Ordner', 'wp-starter'),
                            ],
                            'default_value' => 'upload',
                            'allow_null'    => 0,
                            'ui'            => 1,
                        ],
                        // upload: WP media file
                        FieldDefinitions::fileField(
                            'field_download_file',
                            __('Datei', 'wp-starter'),
                            'download_file',
                            'pdf,doc,docx,xls,xlsx,zip',
                            'array',
                            [
                                [
                                    [
                                        'field'    => 'field_download_source_type',
                                        'operator' => '==',
                                        'value'    => 'upload',
                                    ],
                                ],
                            ],
                            __('Erlaubt: PDF, Word, Excel, ZIP', 'wp-starter')
                        ),
                        // external: direct download URL
                        [
                            'key'               => 'field_download_external_url',
                            'label'             => __('Externe URL', 'wp-starter'),
                            'name'              => 'download_external_url',
                            'type'              => 'url',
                            'instructions'      => __('Direkte URL zur Datei (muss https:// verwenden).', 'wp-starter'),
                            'placeholder'       => 'https://example.com/dokument.pdf',
                            'conditional_logic' => [
                                [
                                    [
                                        'field'    => 'field_download_source_type',
                                        'operator' => '==',
                                        'value'    => 'external',
                                    ],
                                ],
                            ],
                        ],
                        // folder: HTTP directory listing
                        [
                            'key'               => 'field_download_folder_url',
                            'label'             => __('Ordner-URL', 'wp-starter'),
                            'name'              => 'download_folder_url',
                            'type'              => 'url',
                            'instructions'      => __('URL zum HTTP-Verzeichnis mit Directory Listing (Apache/Nginx).', 'wp-starter'),
                            'placeholder'       => 'https://example.com/dokumente/',
                            'conditional_logic' => [
                                [
                                    [
                                        'field'    => 'field_download_source_type',
                                        'operator' => '==',
                                        'value'    => 'folder',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'key'               => 'field_download_folder_username',
                            'label'             => __('HTTP Basic Auth — Benutzername', 'wp-starter'),
                            'name'              => 'download_folder_username',
                            'type'              => 'text',
                            'instructions'      => __('Optional — nur bei passwortgeschütztem Verzeichnis.', 'wp-starter'),
                            'conditional_logic' => [
                                [
                                    [
                                        'field'    => 'field_download_source_type',
                                        'operator' => '==',
                                        'value'    => 'folder',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'key'               => 'field_download_folder_password',
                            'label'             => __('HTTP Basic Auth — Passwort', 'wp-starter'),
                            'name'              => 'download_folder_password',
                            'type'              => 'text',
                            'instructions'      => __('Optional — nur bei passwortgeschütztem Verzeichnis. Wird im Klartext gespeichert.', 'wp-starter'),
                            'conditional_logic' => [
                                [
                                    [
                                        'field'    => 'field_download_source_type',
                                        'operator' => '==',
                                        'value'    => 'folder',
                                    ],
                                ],
                            ],
                        ],
                        // Cron metadata (readonly, set by FolderSync)
                        [
                            'key'               => 'field_download_last_modified',
                            'label'             => __('Zuletzt geändert', 'wp-starter'),
                            'name'              => 'download_last_modified',
                            'type'              => 'text',
                            'instructions'      => __('Wird automatisch vom Server aktualisiert (ISO 8601).', 'wp-starter'),
                            'readonly'          => 1,
                            'conditional_logic' => [
                                [
                                    [
                                        'field'    => 'field_download_source_type',
                                        'operator' => '!=',
                                        'value'    => 'upload',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'key'               => 'field_download_available',
                            'label'             => __('Verfügbar', 'wp-starter'),
                            'name'              => 'download_available',
                            'type'              => 'true_false',
                            'instructions'      => __('Wird automatisch vom Server geprüft.', 'wp-starter'),
                            'default_value'     => 1,
                            'ui'                => 1,
                            'conditional_logic' => [
                                [
                                    [
                                        'field'    => 'field_download_source_type',
                                        'operator' => '!=',
                                        'value'    => 'upload',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'key'               => 'field_download_folder_source',
                            'label'             => __('Ordner-Quelle', 'wp-starter'),
                            'name'              => 'download_folder_source',
                            'type'              => 'text',
                            'instructions'      => __('URL des Eltern-Ordners — wird beim Auto-Import gesetzt.', 'wp-starter'),
                            'readonly'          => 1,
                            'conditional_logic' => [
                                [
                                    [
                                        'field'    => 'field_download_source_type',
                                        'operator' => '==',
                                        'value'    => 'folder',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'key'           => 'field_download_category',
                            'label'         => __('Kategorie', 'wp-starter'),
                            'name'          => 'download_category',
                            'type'          => 'select',
                            'choices'       => [
                                'general' => __('Allgemein', 'wp-starter'),
                                'reports' => __('Berichte', 'wp-starter'),
                                'forms'   => __('Formulare', 'wp-starter'),
                            ],
                            'default_value' => 'general',
                            'allow_null'    => 0,
                            'ui'            => 1,
                        ],
                        [
                            'key'           => 'field_download_sort',
                            'label'         => __('Sortierung', 'wp-starter'),
                            'name'          => 'download_sort',
                            'type'          => 'number',
                            'default_value' => 0,
                            'min'           => 0,
                        ],
                    ],
                ],

                // Tab: Erscheinungsbild
                FieldDefinitions::tabField('field_member_tab_appearance', __('Erscheinungsbild', 'wp-starter')),

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

    private static function registerPasswordHashing(): void
    {
        // Hash password before saving to ACF options
        add_filter('acf/update_value/key=field_member_shared_password', function ($value) {
            if (empty($value)) {
                // Return existing hash if field is left empty
                return get_field('member_shared_password', 'option') ?: '';
            }
            return wp_hash_password($value);
        }, 10, 1);
    }
}
