<?php

declare(strict_types=1);

namespace WordpressStarter\Acf;

/**
 * Registers ACF options pages and their field groups
 *
 * Provides theme-wide settings for:
 * - Site Identity (Logo, Favicon)
 * - Contact Information
 * - Social Media Links
 * - Analytics & Tracking
 * - Legal Pages (Privacy, Imprint)
 */
class Options
{
    /**
     * Register ACF options pages
     */
    public static function register(): void
    {
        if (!function_exists('acf_add_options_page')) {
            return;
        }

        // Main theme options
        acf_add_options_page([
            'page_title' => __('Theme-Einstellungen', 'wp-starter'),
            'menu_title' => __('Theme-Einstellungen', 'wp-starter'),
            'menu_slug' => 'theme-options',
            'capability' => 'edit_posts',
            'redirect' => true,
            'icon_url' => 'dashicons-admin-generic',
            'position' => 2,
        ]);

        // Sub pages with translated labels and icons
        self::addSubPage(__('Allgemein', 'wp-starter'), 'general', 'dashicons-admin-home');
        self::addSubPage(__('Header', 'wp-starter'), 'header', 'dashicons-arrow-up-alt');
        self::addSubPage(__('Footer', 'wp-starter'), 'footer', 'dashicons-arrow-down-alt');
        self::addSubPage(__('Social Media', 'wp-starter'), 'social', 'dashicons-share');
        self::addSubPage(__('Analytics', 'wp-starter'), 'analytics', 'dashicons-chart-bar');
        self::addSubPage(__('Werkzeuge', 'wp-starter'), 'tools', 'dashicons-admin-tools');

        // Register field groups directly (we're already in acf/init)
        self::registerFieldGroups();

        // Register dynamic field filters
        self::registerFieldFilters();
    }

    /**
     * Register ACF field filters for dynamic choices
     */
    private static function registerFieldFilters(): void
    {
        // Populate footer menu select with registered nav menus
        add_filter('acf/load_field/key=field_options_footer_nav_menu', function (array $field): array {
            $locations = get_registered_nav_menus();
            $field['choices'] = [];

            foreach ($locations as $location => $description) {
                $field['choices'][$location] = $description;
            }

            return $field;
        });
    }

    /**
     * Add options sub page with optional icon
     */
    private static function addSubPage(string $title, string $slug, string $icon = ''): void
    {
        $config = [
            'page_title' => $title,
            'menu_title' => $icon ? '<span class="dashicons ' . $icon . '" style="font-size: 16px; width: 16px; height: 16px; margin-right: 6px; vertical-align: middle;"></span>' . $title : $title,
            'parent_slug' => 'theme-options',
            'menu_slug' => 'theme-options-' . $slug,
        ];

        acf_add_options_sub_page($config);
    }

    /**
     * Register all field groups for options pages
     */
    public static function registerFieldGroups(): void
    {
        self::registerGeneralFields();
        self::registerHeaderFields();
        self::registerFooterFields();
        self::registerSocialFields();
        self::registerAnalyticsFields();
        self::registerToolsFields();
    }

    /**
     * General Settings Fields
     */
    private static function registerGeneralFields(): void
    {
        acf_add_local_field_group([
            'key' => 'group_options_general',
            'title' => __('Allgemeine Einstellungen', 'wp-starter'),
            'fields' => [
                // Site Identity Tab
                [
                    'key' => 'field_options_tab_identity',
                    'label' => __('Website-Identität', 'wp-starter'),
                    'type' => 'tab',
                ],
                FieldDefinitions::infoBoxField(
                    'field_options_identity_info',
                    __('<strong>Website-Identität</strong><br>Logo und Favicon erscheinen im Header, Footer und Browser-Tab. Für beste Ergebnisse verwende SVG-Dateien oder PNGs mit transparentem Hintergrund.', 'wp-starter'),
                    'info'
                ),
                array_merge(
                    FieldDefinitions::imageField(
                        'field_options_logo',
                        __('Logo', 'wp-starter'),
                        'site_logo',
                        false,
                        'array',
                        null,
                        __('Das Hauptlogo der Website.', 'wp-starter')
                    ),
                    ['wrapper' => ['width' => '50']]
                ),
                array_merge(
                    FieldDefinitions::imageField(
                        'field_options_logo_dark',
                        __('Logo (Dunkel)', 'wp-starter'),
                        'site_logo_dark',
                        false,
                        'array',
                        null,
                        __('Für dunkle Hintergründe.', 'wp-starter')
                    ),
                    ['wrapper' => ['width' => '50']]
                ),
                FieldDefinitions::imageField(
                    'field_options_favicon',
                    __('Favicon', 'wp-starter'),
                    'site_favicon',
                    false,
                    'id',
                    null,
                    __('Das Favicon für Browser-Tabs. Empfohlen: 512x512px PNG.', 'wp-starter')
                ),
                FieldDefinitions::imageField(
                    'field_options_social_image',
                    __('Social Sharing Bild', 'wp-starter'),
                    'social_sharing_image',
                    false,
                    'id',
                    null,
                    __('Standardbild für Social Media Vorschauen (Facebook, Twitter, LinkedIn). Empfohlene Größe: 1200×630 Pixel (1.91:1). Mindestens 600×315 Pixel. Max. 5 MB. Formate: JPG oder PNG.', 'wp-starter')
                ),

                // Contact Tab (default selected)
                [
                    'key' => 'field_options_tab_contact',
                    'label' => __('Kontaktdaten', 'wp-starter'),
                    'type' => 'tab',
                    'selected' => 1,
                ],
                FieldDefinitions::infoBoxField(
                    'field_options_contact_info',
                    __('<strong>Kontaktdaten</strong><br>Diese Daten werden im Footer und auf der Kontaktseite verwendet. Halte sie aktuell!', 'wp-starter'),
                    'info'
                ),
                FieldDefinitions::textField(
                    'field_options_company_name',
                    __('Firmenname', 'wp-starter'),
                    'company_name',
                    false,
                    __('Der vollständige Firmenname für Impressum und Footer.', 'wp-starter'),
                    __('Musterfirma GmbH', 'wp-starter')
                ),
                FieldDefinitions::textareaField(
                    'field_options_address',
                    __('Adresse', 'wp-starter'),
                    'address',
                    3,
                    __('Die vollständige Geschäftsadresse.', 'wp-starter'),
                    __("Musterstraße 123\n12345 Musterstadt", 'wp-starter')
                ),
                array_merge(
                    FieldDefinitions::textField(
                        'field_options_phone',
                        __('Telefon', 'wp-starter'),
                        'phone',
                        false,
                        __('Haupttelefonnummer.', 'wp-starter'),
                        '+49 123 456789'
                    ),
                    ['wrapper' => ['width' => '50']]
                ),
                array_merge(
                    FieldDefinitions::emailField(
                        'field_options_email',
                        __('E-Mail', 'wp-starter'),
                        'email',
                        __('Haupt-E-Mail-Adresse.', 'wp-starter'),
                        'info@example.com'
                    ),
                    ['wrapper' => ['width' => '50']]
                ),
                FieldDefinitions::urlField(
                    'field_options_maps_link',
                    __('Google Maps Link', 'wp-starter'),
                    'maps_link',
                    __('Link zur Google Maps Position (für „Anfahrt" Button).', 'wp-starter'),
                    null,
                    'https://goo.gl/maps/...'
                ),

                // Tab: Darstellung
                [
                    'key' => 'field_options_tab_appearance',
                    'label' => __('Darstellung', 'wp-starter'),
                    'type' => 'tab',
                ],
                FieldDefinitions::selectField(
                    'field_options_color_scheme',
                    __('Farbschema', 'wp-starter'),
                    'color_scheme',
                    [
                        'system' => __('Systemeinstellung folgen', 'wp-starter'),
                        'light' => __('Hell (Light Mode)', 'wp-starter'),
                        'dark' => __('Dunkel (Dark Mode)', 'wp-starter'),
                    ],
                    'system',
                    false,
                    __('Bestimmt das Standard-Farbschema der Website. „Systemeinstellung" passt sich automatisch an die Browser-/OS-Einstellung des Besuchers an.', 'wp-starter')
                ),
            ],
            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'theme-options-general',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Header Settings Fields
     */
    private static function registerHeaderFields(): void
    {
        acf_add_local_field_group([
            'key' => 'group_options_header',
            'title' => __('Header-Einstellungen', 'wp-starter'),
            'fields' => [
                FieldDefinitions::infoBoxField(
                    'field_options_header_tip',
                    __('<strong>Tipp:</strong> Ein CTA-Button im Header erhöht die Conversion-Rate. Verwende eine klare Handlungsaufforderung wie „Jetzt anfragen" oder „Termin buchen".', 'wp-starter'),
                    'tip'
                ),
                array_merge(
                    FieldDefinitions::trueFalseField(
                        'field_options_header_sticky',
                        __('Sticky Header', 'wp-starter'),
                        'header_sticky',
                        true,
                        __('Header bleibt beim Scrollen oben fixiert.', 'wp-starter')
                    ),
                    ['wrapper' => ['width' => '50']]
                ),
                array_merge(
                    FieldDefinitions::trueFalseField(
                        'field_options_header_cta_show',
                        __('CTA-Button anzeigen', 'wp-starter'),
                        'header_cta_show',
                        true,
                        __('Zeigt einen Call-to-Action Button im Header.', 'wp-starter')
                    ),
                    ['wrapper' => ['width' => '50']]
                ),
                array_merge(
                    FieldDefinitions::linkField(
                        'field_options_header_cta',
                        __('CTA-Button', 'wp-starter'),
                        'header_cta',
                        false,
                        __('Link und Text für den Header-CTA-Button.', 'wp-starter')
                    ),
                    [
                        'conditional_logic' => [
                            [
                                [
                                    'field' => 'field_options_header_cta_show',
                                    'operator' => '==',
                                    'value' => '1',
                                ],
                            ],
                        ],
                    ]
                ),
            ],
            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'theme-options-header',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Footer Settings Fields
     */
    private static function registerFooterFields(): void
    {
        acf_add_local_field_group([
            'key' => 'group_options_footer',
            'title' => __('Footer-Einstellungen', 'wp-starter'),
            'fields' => [
                // === Spalte 1: Logo & Info ===
                [
                    'key' => 'field_options_footer_tab_info',
                    'label' => __('Info-Spalte', 'wp-starter'),
                    'type' => 'tab',
                    'selected' => 1,
                ],
                array_merge(
                    FieldDefinitions::trueFalseField(
                        'field_options_footer_logo_show',
                        __('Logo anzeigen', 'wp-starter'),
                        'footer_show_logo',
                        true,
                        __('Zeigt das Site-Logo im Footer.', 'wp-starter')
                    ),
                    ['wrapper' => ['width' => '50']]
                ),
                array_merge(
                    FieldDefinitions::trueFalseField(
                        'field_options_footer_company_show',
                        __('Firmenname anzeigen', 'wp-starter'),
                        'footer_show_company',
                        true,
                        __('Zeigt den Firmennamen.', 'wp-starter')
                    ),
                    ['wrapper' => ['width' => '50']]
                ),
                FieldDefinitions::wysiwygField(
                    'field_options_footer_text',
                    __('Footer-Text', 'wp-starter'),
                    'footer_text',
                    false,
                    null,
                    __('Optionaler Text im Footer (z.B. Firmenbeschreibung).', 'wp-starter')
                ),

                // === Spalte 2: Navigation ===
                [
                    'key' => 'field_options_footer_tab_nav',
                    'label' => __('Navigation', 'wp-starter'),
                    'type' => 'tab',
                ],
                FieldDefinitions::trueFalseField(
                    'field_options_footer_nav_show',
                    __('Navigation anzeigen', 'wp-starter'),
                    'footer_show_nav',
                    true,
                    __('Zeigt das Footer-Navigationsmenü.', 'wp-starter')
                ),
                array_merge(
                    FieldDefinitions::textField(
                        'field_options_footer_nav_title',
                        __('Überschrift', 'wp-starter'),
                        'footer_nav_title',
                        false,
                        __('Überschrift über dem Menü.', 'wp-starter'),
                        __('Navigation', 'wp-starter')
                    ),
                    [
                        'wrapper' => ['width' => '50'],
                        'conditional_logic' => [
                            [['field' => 'field_options_footer_nav_show', 'operator' => '==', 'value' => '1']],
                        ],
                    ]
                ),
                array_merge(
                    [
                        'key' => 'field_options_footer_nav_menu',
                        'label' => __('Menü', 'wp-starter'),
                        'name' => 'footer_nav_menu',
                        'type' => 'select',
                        'instructions' => __('Welches Menü anzeigen.', 'wp-starter'),
                        'required' => 0,
                        'choices' => [],
                        'default_value' => 'footer-menu',
                        'allow_null' => 0,
                        'ui' => 1,
                    ],
                    [
                        'wrapper' => ['width' => '50'],
                        'conditional_logic' => [
                            [['field' => 'field_options_footer_nav_show', 'operator' => '==', 'value' => '1']],
                        ],
                    ]
                ),

                // === Spalte 3: Kontakt ===
                [
                    'key' => 'field_options_footer_tab_contact',
                    'label' => __('Kontakt', 'wp-starter'),
                    'type' => 'tab',
                ],
                FieldDefinitions::trueFalseField(
                    'field_options_footer_contact_show',
                    __('Kontaktdaten anzeigen', 'wp-starter'),
                    'footer_show_contact',
                    true,
                    __('Zeigt Adresse, Telefon und E-Mail.', 'wp-starter')
                ),
                array_merge(
                    FieldDefinitions::infoBoxField(
                        'field_options_footer_contact_info',
                        sprintf(
                            /* translators: %s: URL to general settings page */
                            __('Verwendet Daten aus den <a href="%s">allgemeinen Einstellungen</a> (Kontaktdaten Tab).', 'wp-starter'),
                            \admin_url('admin.php?page=theme-options-general')
                        ),
                        'info'
                    ),
                    [
                        'conditional_logic' => [
                            [['field' => 'field_options_footer_contact_show', 'operator' => '==', 'value' => '1']],
                        ],
                    ]
                ),
                array_merge(
                    FieldDefinitions::textField(
                        'field_options_footer_contact_title',
                        __('Kontakt-Überschrift', 'wp-starter'),
                        'footer_contact_title',
                        false,
                        __('Überschrift über den Kontaktdaten.', 'wp-starter'),
                        __('Kontakt', 'wp-starter')
                    ),
                    [
                        'conditional_logic' => [
                            [['field' => 'field_options_footer_contact_show', 'operator' => '==', 'value' => '1']],
                        ],
                    ]
                ),

                // === Spalte 4: Social ===
                [
                    'key' => 'field_options_footer_tab_social',
                    'label' => __('Social Media', 'wp-starter'),
                    'type' => 'tab',
                ],
                FieldDefinitions::trueFalseField(
                    'field_options_footer_social_show',
                    __('Social Links anzeigen', 'wp-starter'),
                    'footer_show_social',
                    true,
                    __('Zeigt die Social Media Icons im Footer.', 'wp-starter')
                ),
                array_merge(
                    FieldDefinitions::infoBoxField(
                        'field_options_footer_social_info',
                        sprintf(
                            /* translators: %s: URL to social media settings page */
                            __('Verwendet Icons aus den <a href="%s">Social Media Einstellungen</a>.', 'wp-starter'),
                            \admin_url('admin.php?page=theme-options-social')
                        ),
                        'info'
                    ),
                    [
                        'conditional_logic' => [
                            [['field' => 'field_options_footer_social_show', 'operator' => '==', 'value' => '1']],
                        ],
                    ]
                ),
                array_merge(
                    FieldDefinitions::textField(
                        'field_options_footer_social_title',
                        __('Social-Überschrift', 'wp-starter'),
                        'footer_social_title',
                        false,
                        __('Überschrift über den Icons.', 'wp-starter'),
                        __('Folge uns', 'wp-starter')
                    ),
                    [
                        'conditional_logic' => [
                            [['field' => 'field_options_footer_social_show', 'operator' => '==', 'value' => '1']],
                        ],
                    ]
                ),

                // === Untere Leiste ===
                [
                    'key' => 'field_options_footer_tab_bottom',
                    'label' => __('Untere Leiste', 'wp-starter'),
                    'type' => 'tab',
                ],
                FieldDefinitions::textField(
                    'field_options_copyright',
                    __('Copyright-Text', 'wp-starter'),
                    'copyright_text',
                    false,
                    __('Der Copyright-Hinweis. {year} wird automatisch durch das aktuelle Jahr ersetzt.', 'wp-starter'),
                    __('© {year} Firmenname. Alle Rechte vorbehalten.', 'wp-starter')
                ),
                FieldDefinitions::trueFalseField(
                    'field_options_footer_legal_show',
                    __('Rechtliches Menü anzeigen', 'wp-starter'),
                    'footer_show_legal',
                    true,
                    __('Zeigt das Legal-Menü (Impressum, Datenschutz) in der unteren Leiste.', 'wp-starter')
                ),
            ],
            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'theme-options-footer',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Social Media Settings Fields
     */
    private static function registerSocialFields(): void
    {
        acf_add_local_field_group([
            'key' => 'group_options_social',
            'title' => __('Social Media Links', 'wp-starter'),
            'fields' => [
                FieldDefinitions::infoBoxField(
                    'field_options_social_info',
                    __('<strong>Social Media</strong><br>Diese Icons werden im Footer angezeigt (wenn aktiviert). Die Reihenfolge hier bestimmt die Anzeige-Reihenfolge.', 'wp-starter'),
                    'info'
                ),
                FieldDefinitions::repeaterField(
                    'field_options_social_links',
                    __('Social Media Kanäle', 'wp-starter'),
                    'social_links',
                    [
                        array_merge(
                            FieldDefinitions::selectField(
                                'field_options_social_platform',
                                __('Plattform', 'wp-starter'),
                                'platform',
                                [
                                    'facebook' => __('Facebook', 'wp-starter'),
                                    'instagram' => __('Instagram', 'wp-starter'),
                                    'linkedin' => __('LinkedIn', 'wp-starter'),
                                    'xing' => __('XING', 'wp-starter'),
                                    'twitter' => __('X (Twitter)', 'wp-starter'),
                                    'youtube' => __('YouTube', 'wp-starter'),
                                    'tiktok' => __('TikTok', 'wp-starter'),
                                    'pinterest' => __('Pinterest', 'wp-starter'),
                                    'threads' => __('Threads', 'wp-starter'),
                                ],
                                'linkedin',
                                true,
                                ''
                            ),
                            ['wrapper' => ['width' => '40']]
                        ),
                        array_merge(
                            FieldDefinitions::urlField(
                                'field_options_social_url',
                                __('Profil-URL', 'wp-starter'),
                                'url',
                                '',
                                null,
                                'https://linkedin.com/company/...'
                            ),
                            ['wrapper' => ['width' => '60']]
                        ),
                    ],
                    __('Kanal hinzufügen', 'wp-starter'),
                    0,
                    'table',
                    ''
                ),
            ],
            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'theme-options-social',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Analytics Settings Fields
     *
     * Dieses Theme ist Cookie-frei und unterstützt nur DSGVO-konforme Analytics.
     */
    private static function registerAnalyticsFields(): void
    {
        acf_add_local_field_group([
            'key' => 'group_options_analytics',
            'title' => __('Analytics (Cookie-frei)', 'wp-starter'),
            'fields' => [
                FieldDefinitions::infoBoxField(
                    'field_options_analytics_success',
                    __('<strong>Cookie-freie Website</strong><br>Dieses Theme verwendet ausschließlich DSGVO-konforme Analytics ohne Cookies. Kein Cookie-Banner erforderlich!', 'wp-starter'),
                    'success'
                ),
                FieldDefinitions::textField(
                    'field_options_pirsch_code',
                    __('Pirsch Site Code', 'wp-starter'),
                    'pirsch_code',
                    false,
                    __('Den Code findest du unter <a href="https://pirsch.io" target="_blank">pirsch.io</a> → Dashboard → Settings → Integration Code. Leer lassen wenn nicht benötigt.', 'wp-starter'),
                    __('z.B. abc123def456', 'wp-starter')
                ),
            ],
            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'theme-options-analytics',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Tools Settings Fields
     */
    private static function registerToolsFields(): void
    {
        // Check if styleguide page exists
        $styleguidePageId = get_option('wp_starter_styleguide_page_id');
        $styleguidePost = $styleguidePageId ? get_post($styleguidePageId) : null;

        // Check various states: exists, in trash, or missing
        $styleguideExists = $styleguidePost && $styleguidePost->post_status !== 'trash';
        $styleguideInTrash = $styleguidePost && $styleguidePost->post_status === 'trash';

        // Build status message based on state
        if ($styleguideInTrash) {
            // Page is in trash - offer to restore or delete permanently
            $restoreUrl = wp_nonce_url(
                admin_url('?wp-starter-restore-styleguide=1'),
                'wp-starter-restore-styleguide'
            );
            $deleteUrl = wp_nonce_url(
                admin_url('?wp-starter-delete-styleguide=1'),
                'wp-starter-delete-styleguide'
            );
            $statusMessage = sprintf(
                '<div style="padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #721c24;"><strong>⚠ Styleguide-Seite liegt im Papierkorb</strong></p>
                    <p style="margin: 10px 0 0 0;">
                        <a href="%s" class="button button-primary">Wiederherstellen</a>
                        <a href="%s" class="button" onclick="return confirm(\'Styleguide-Seite endgültig löschen?\');">Endgültig löschen</a>
                    </p>
                </div>',
                esc_url($restoreUrl),
                esc_url($deleteUrl)
            );
        } elseif ($styleguideExists) {
            $editUrl = get_edit_post_link( (int) $styleguidePageId, 'raw');
            $viewUrl = get_permalink( (int) $styleguidePageId);
            $statusMessage = sprintf(
                '<div style="padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #155724;"><strong>✓ Styleguide-Seite existiert</strong></p>
                    <p style="margin: 10px 0 0 0; display: flex; gap: 8px; flex-wrap: wrap;">
                        <a href="%s" class="button">Bearbeiten</a>
                        <a href="%s" class="button" target="_blank">Ansehen</a>
                    </p>
                </div>',
                esc_url($editUrl ?? ''),
                esc_url($viewUrl ?? '')
            );
        } else {
            // Clear the option if it references a non-existent page
            if ($styleguidePageId && !$styleguidePost) {
                delete_option('wp_starter_styleguide_page_id');
            }
            $createUrl = wp_nonce_url(
                admin_url('?wp-starter-create-styleguide=1'),
                'wp-starter-create-styleguide'
            );
            $statusMessage = sprintf(
                '<div style="padding: 15px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #856404;"><strong>Keine Styleguide-Seite vorhanden</strong></p>
                    <p style="margin: 10px 0 0 0;">
                        <a href="%s" class="button button-primary">Styleguide-Seite erstellen</a>
                    </p>
                </div>',
                esc_url($createUrl)
            );
        }

        // Regenerate option (always show)
        $regenerateUrl = wp_nonce_url(
            admin_url('?wp-starter-regenerate-styleguide=1'),
            'wp-starter-regenerate-styleguide'
        );
        $regenerateMessage = sprintf(
            '<div style="padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;">
                <p style="margin: 0;"><strong>Styleguide neu generieren</strong></p>
                <p style="margin: 10px 0; color: #6c757d;">Erstellt die Styleguide-Seite neu mit allen aktuellen Design-Tokens und Block-Beispielen. Die bestehende Seite wird ersetzt.</p>
                <p style="margin: 0;">
                    <a href="%s" class="button" onclick="return confirm(\'Styleguide-Seite wirklich neu erstellen? Die bestehende Seite wird gelöscht.\');">Neu generieren</a>
                </p>
            </div>',
            esc_url($regenerateUrl)
        );

        // Content Setup section
        $contentSetupComplete = get_option('wp_starter_content_setup_complete');
        $contentSetupUrl = wp_nonce_url(
            admin_url('?wp-starter-rerun-content-setup=1'),
            'wp-starter-rerun-content-setup'
        );

        if ($contentSetupComplete) {
            $contentSetupMessage = sprintf(
                '<div style="padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #155724;"><strong>✓ Content-Setup wurde bereits ausgeführt</strong></p>
                    <p style="margin: 10px 0 0 0;">
                        <a href="%s" class="button" onclick="return confirm(\'Content-Setup wirklich erneut ausführen? Bestehende Seiten bleiben erhalten, fehlende werden erstellt.\');">Erneut ausführen</a>
                    </p>
                </div>',
                esc_url($contentSetupUrl)
            );
        } else {
            $contentSetupMessage = sprintf(
                '<div style="padding: 15px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #856404;"><strong>Content-Setup wurde noch nicht ausgeführt</strong></p>
                    <p style="margin: 10px 0; color: #856404;">Erstellt Standardseiten (Startseite, Über uns, Kontakt, etc.) und richtet Menüs ein.</p>
                    <p style="margin: 10px 0 0 0;">
                        <a href="%s" class="button button-primary">Content-Setup jetzt ausführen</a>
                    </p>
                </div>',
                esc_url($contentSetupUrl)
            );
        }

        acf_add_local_field_group([
            'key' => 'group_options_tools',
            'title' => __('Werkzeuge', 'wp-starter'),
            'fields' => [
                // Content Setup Section
                [
                    'key' => 'field_options_tools_content_heading',
                    'label' => __('Content-Setup', 'wp-starter'),
                    'type' => 'message',
                    'message' => '<p>' . __('Erstellt Standardseiten und richtet die Navigation ein.', 'wp-starter') . '</p>',
                ],
                [
                    'key' => 'field_options_tools_content_status',
                    'label' => '',
                    'type' => 'message',
                    'message' => $contentSetupMessage,
                ],
                // Demo Content Section
                [
                    'key' => 'field_options_tools_demo_heading',
                    'label' => __('Demo-Inhalte', 'wp-starter'),
                    'type' => 'message',
                    'message' => '<p>' . __('Erstellt Beispiel-Blogbeiträge zum Testen des Blog-Layouts.', 'wp-starter') . '</p>',
                ],
                [
                    'key' => 'field_options_tools_demo_status',
                    'label' => '',
                    'type' => 'message',
                    'message' => self::getDemoContentMessage(),
                ],
                // Styleguide Section
                [
                    'key' => 'field_options_tools_styleguide_heading',
                    'label' => __('Styleguide', 'wp-starter'),
                    'type' => 'message',
                    'message' => '<p>' . __('Der Styleguide zeigt alle verfügbaren Design-Elemente des Themes: Farben, Typografie, Abstände, Komponenten und ACF-Block-Beispiele.', 'wp-starter') . '</p>',
                ],
                [
                    'key' => 'field_options_tools_styleguide_status',
                    'label' => '',
                    'type' => 'message',
                    'message' => $statusMessage . $regenerateMessage,
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'theme-options-tools',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get demo content status message for Tools page
     */
    private static function getDemoContentMessage(): string
    {
        // Count existing demo posts
        $existingPosts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1,
        ]);
        $postCount = count($existingPosts);

        $generateUrl = wp_nonce_url(
            admin_url('?wp-starter-generate-demo-posts=1'),
            'wp-starter-generate-demo-posts'
        );

        $deleteUrl = wp_nonce_url(
            admin_url('?wp-starter-delete-demo-posts=1'),
            'wp-starter-delete-demo-posts'
        );

        if ($postCount > 0) {
            return sprintf(
                '<div style="padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #155724;"><strong>✓ %d Blogbeiträge vorhanden</strong></p>
                    <p style="margin: 10px 0 0 0;">
                        <a href="%s" class="button button-primary">5 weitere generieren</a>
                        <a href="%s" class="button" style="margin-left: 5px;" onclick="return confirm(\'Alle Beiträge wirklich löschen?\');">Alle löschen</a>
                        <a href="%s" class="button" style="margin-left: 5px;">Beiträge ansehen</a>
                    </p>
                </div>',
                $postCount,
                esc_url($generateUrl),
                esc_url($deleteUrl),
                esc_url(admin_url('edit.php'))
            );
        }

        return sprintf(
            '<div style="padding: 15px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; margin-bottom: 20px;">
                <p style="margin: 0; color: #856404;"><strong>Keine Blogbeiträge vorhanden</strong></p>
                <p style="margin: 10px 0; color: #856404;">Erstellt 5 Beispiel-Blogbeiträge mit realistischem Inhalt zum Testen.</p>
                <p style="margin: 10px 0 0 0;">
                    <a href="%s" class="button button-primary">Demo-Beiträge erstellen</a>
                </p>
            </div>',
            esc_url($generateUrl)
        );
    }

    /**
     * Clear options cache
     */
    public static function clearCache(): void
    {
        if (function_exists('wp_cache_delete_group')) {
            \wp_cache_delete_group('theme');
        } elseif (function_exists('wp_cache_flush')) {
            \wp_cache_flush();
        }
    }

    /**
     * Hook to clear cache when options are updated
     */
    public static function initCacheClearing(): void
    {
        add_action('acf/save_post', function ($postId) {
            if ($postId === 'options') {
                self::clearCache();
            }
        });
    }
}
