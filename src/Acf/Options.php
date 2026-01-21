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
            'page_title' => 'Theme-Einstellungen',
            'menu_title' => 'Theme-Einstellungen',
            'menu_slug' => 'theme-options',
            'capability' => 'edit_posts',
            'redirect' => true,
            'icon_url' => 'dashicons-admin-generic',
            'position' => 2,
        ]);

        // Sub pages with German labels and icons
        self::addSubPage('Allgemein', 'general', 'dashicons-admin-home');
        self::addSubPage('Header', 'header', 'dashicons-arrow-up-alt');
        self::addSubPage('Footer', 'footer', 'dashicons-arrow-down-alt');
        self::addSubPage('Social Media', 'social', 'dashicons-share');
        self::addSubPage('Analytics', 'analytics', 'dashicons-chart-bar');
        self::addSubPage('Werkzeuge', 'tools', 'dashicons-admin-tools');

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
            'title' => 'Allgemeine Einstellungen',
            'fields' => [
                // Site Identity Tab
                [
                    'key' => 'field_options_tab_identity',
                    'label' => 'Website-Identität',
                    'type' => 'tab',
                ],
                FieldDefinitions::infoBoxField(
                    'field_options_identity_info',
                    '<strong>Website-Identität</strong><br>Logo und Favicon erscheinen im Header, Footer und Browser-Tab. Für beste Ergebnisse verwende SVG-Dateien oder PNGs mit transparentem Hintergrund.',
                    'info'
                ),
                array_merge(
                    FieldDefinitions::imageField(
                        'field_options_logo',
                        'Logo',
                        'site_logo',
                        false,
                        'array',
                        null,
                        'Das Hauptlogo der Website.'
                    ),
                    ['wrapper' => ['width' => '50']]
                ),
                array_merge(
                    FieldDefinitions::imageField(
                        'field_options_logo_dark',
                        'Logo (Dunkel)',
                        'site_logo_dark',
                        false,
                        'array',
                        null,
                        'Für dunkle Hintergründe.'
                    ),
                    ['wrapper' => ['width' => '50']]
                ),
                FieldDefinitions::imageField(
                    'field_options_favicon',
                    'Favicon',
                    'site_favicon',
                    false,
                    'id',
                    null,
                    'Das Favicon für Browser-Tabs. Empfohlen: 512x512px PNG.'
                ),

                // Contact Tab (default selected)
                [
                    'key' => 'field_options_tab_contact',
                    'label' => 'Kontaktdaten',
                    'type' => 'tab',
                    'selected' => 1,
                ],
                FieldDefinitions::infoBoxField(
                    'field_options_contact_info',
                    '<strong>Kontaktdaten</strong><br>Diese Daten werden im Footer und auf der Kontaktseite verwendet. Halte sie aktuell!',
                    'info'
                ),
                FieldDefinitions::textField(
                    'field_options_company_name',
                    'Firmenname',
                    'company_name',
                    false,
                    'Der vollständige Firmenname für Impressum und Footer.',
                    'Musterfirma GmbH'
                ),
                FieldDefinitions::textareaField(
                    'field_options_address',
                    'Adresse',
                    'address',
                    3,
                    'Die vollständige Geschäftsadresse.',
                    "Musterstraße 123\n12345 Musterstadt"
                ),
                array_merge(
                    FieldDefinitions::textField(
                        'field_options_phone',
                        'Telefon',
                        'phone',
                        false,
                        'Haupttelefonnummer.',
                        '+49 123 456789'
                    ),
                    ['wrapper' => ['width' => '50']]
                ),
                array_merge(
                    FieldDefinitions::emailField(
                        'field_options_email',
                        'E-Mail',
                        'email',
                        'Haupt-E-Mail-Adresse.',
                        'info@example.com'
                    ),
                    ['wrapper' => ['width' => '50']]
                ),
                FieldDefinitions::urlField(
                    'field_options_maps_link',
                    'Google Maps Link',
                    'maps_link',
                    'Link zur Google Maps Position (für "Anfahrt" Button).',
                    null,
                    'https://goo.gl/maps/...'
                ),

                // Tab: Darstellung
                [
                    'key' => 'field_options_tab_appearance',
                    'label' => 'Darstellung',
                    'type' => 'tab',
                ],
                FieldDefinitions::selectField(
                    'field_options_color_scheme',
                    'Farbschema',
                    'color_scheme',
                    [
                        'system' => 'Systemeinstellung folgen',
                        'light' => 'Hell (Light Mode)',
                        'dark' => 'Dunkel (Dark Mode)',
                    ],
                    'system',
                    false,
                    'Bestimmt das Standard-Farbschema der Website. "Systemeinstellung" passt sich automatisch an die Browser-/OS-Einstellung des Besuchers an.'
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
            'title' => 'Header-Einstellungen',
            'fields' => [
                FieldDefinitions::infoBoxField(
                    'field_options_header_tip',
                    '<strong>Tipp:</strong> Ein CTA-Button im Header erhöht die Conversion-Rate. Verwende eine klare Handlungsaufforderung wie "Jetzt anfragen" oder "Termin buchen".',
                    'tip'
                ),
                array_merge(
                    FieldDefinitions::trueFalseField(
                        'field_options_header_sticky',
                        'Sticky Header',
                        'header_sticky',
                        true,
                        'Header bleibt beim Scrollen oben fixiert.'
                    ),
                    ['wrapper' => ['width' => '50']]
                ),
                array_merge(
                    FieldDefinitions::trueFalseField(
                        'field_options_header_cta_show',
                        'CTA-Button anzeigen',
                        'header_cta_show',
                        true,
                        'Zeigt einen Call-to-Action Button im Header.'
                    ),
                    ['wrapper' => ['width' => '50']]
                ),
                array_merge(
                    FieldDefinitions::linkField(
                        'field_options_header_cta',
                        'CTA-Button',
                        'header_cta',
                        false,
                        'Link und Text für den Header-CTA-Button.'
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
            'title' => 'Footer-Einstellungen',
            'fields' => [
                // === Spalte 1: Logo & Info ===
                [
                    'key' => 'field_options_footer_tab_info',
                    'label' => 'Info-Spalte',
                    'type' => 'tab',
                    'selected' => 1,
                ],
                array_merge(
                    FieldDefinitions::trueFalseField(
                        'field_options_footer_logo_show',
                        'Logo anzeigen',
                        'footer_show_logo',
                        true,
                        'Zeigt das Site-Logo im Footer.'
                    ),
                    ['wrapper' => ['width' => '50']]
                ),
                array_merge(
                    FieldDefinitions::trueFalseField(
                        'field_options_footer_company_show',
                        'Firmenname anzeigen',
                        'footer_show_company',
                        true,
                        'Zeigt den Firmennamen.'
                    ),
                    ['wrapper' => ['width' => '50']]
                ),
                FieldDefinitions::wysiwygField(
                    'field_options_footer_text',
                    'Footer-Text',
                    'footer_text',
                    false,
                    null,
                    'Optionaler Text im Footer (z.B. Firmenbeschreibung).'
                ),

                // === Spalte 2: Navigation ===
                [
                    'key' => 'field_options_footer_tab_nav',
                    'label' => 'Navigation',
                    'type' => 'tab',
                ],
                FieldDefinitions::trueFalseField(
                    'field_options_footer_nav_show',
                    'Navigation anzeigen',
                    'footer_show_nav',
                    true,
                    'Zeigt das Footer-Navigationsmenü.'
                ),
                array_merge(
                    FieldDefinitions::textField(
                        'field_options_footer_nav_title',
                        'Überschrift',
                        'footer_nav_title',
                        false,
                        'Überschrift über dem Menü.',
                        'Navigation'
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
                        'label' => 'Menü',
                        'name' => 'footer_nav_menu',
                        'type' => 'select',
                        'instructions' => 'Welches Menü anzeigen.',
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
                    'label' => 'Kontakt',
                    'type' => 'tab',
                ],
                FieldDefinitions::trueFalseField(
                    'field_options_footer_contact_show',
                    'Kontaktdaten anzeigen',
                    'footer_show_contact',
                    true,
                    'Zeigt Adresse, Telefon und E-Mail.'
                ),
                array_merge(
                    FieldDefinitions::infoBoxField(
                        'field_options_footer_contact_info',
                        'Verwendet Daten aus den <a href="' . \admin_url('admin.php?page=theme-options-general') . '">allgemeinen Einstellungen</a> (Kontaktdaten Tab).',
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
                        'Kontakt-Überschrift',
                        'footer_contact_title',
                        false,
                        'Überschrift über den Kontaktdaten.',
                        'Kontakt'
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
                    'label' => 'Social Media',
                    'type' => 'tab',
                ],
                FieldDefinitions::trueFalseField(
                    'field_options_footer_social_show',
                    'Social Links anzeigen',
                    'footer_show_social',
                    true,
                    'Zeigt die Social Media Icons im Footer.'
                ),
                array_merge(
                    FieldDefinitions::infoBoxField(
                        'field_options_footer_social_info',
                        'Verwendet Icons aus den <a href="' . \admin_url('admin.php?page=theme-options-social') . '">Social Media Einstellungen</a>.',
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
                        'Social-Überschrift',
                        'footer_social_title',
                        false,
                        'Überschrift über den Icons.',
                        'Folge uns'
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
                    'label' => 'Untere Leiste',
                    'type' => 'tab',
                ],
                FieldDefinitions::textField(
                    'field_options_copyright',
                    'Copyright-Text',
                    'copyright_text',
                    false,
                    'Der Copyright-Hinweis. {year} wird automatisch durch das aktuelle Jahr ersetzt.',
                    '© {year} Firmenname. Alle Rechte vorbehalten.'
                ),
                FieldDefinitions::trueFalseField(
                    'field_options_footer_legal_show',
                    'Rechtliches Menü anzeigen',
                    'footer_show_legal',
                    true,
                    'Zeigt das Legal-Menü (Impressum, Datenschutz) in der unteren Leiste.'
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
            'title' => 'Social Media Links',
            'fields' => [
                FieldDefinitions::infoBoxField(
                    'field_options_social_info',
                    '<strong>Social Media</strong><br>Diese Icons werden im Footer angezeigt (wenn aktiviert). Die Reihenfolge hier bestimmt die Anzeige-Reihenfolge.',
                    'info'
                ),
                FieldDefinitions::repeaterField(
                    'field_options_social_links',
                    'Social Media Kanäle',
                    'social_links',
                    [
                        array_merge(
                            FieldDefinitions::selectField(
                                'field_options_social_platform',
                                'Plattform',
                                'platform',
                                [
                                    'facebook' => 'Facebook',
                                    'instagram' => 'Instagram',
                                    'linkedin' => 'LinkedIn',
                                    'xing' => 'XING',
                                    'twitter' => 'X (Twitter)',
                                    'youtube' => 'YouTube',
                                    'tiktok' => 'TikTok',
                                    'pinterest' => 'Pinterest',
                                    'threads' => 'Threads',
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
                                'Profil-URL',
                                'url',
                                '',
                                null,
                                'https://linkedin.com/company/...'
                            ),
                            ['wrapper' => ['width' => '60']]
                        ),
                    ],
                    'Kanal hinzufügen',
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
            'title' => 'Analytics (Cookie-frei)',
            'fields' => [
                FieldDefinitions::infoBoxField(
                    'field_options_analytics_success',
                    '<strong>Cookie-freie Website</strong><br>Dieses Theme verwendet ausschließlich DSGVO-konforme Analytics ohne Cookies. Kein Cookie-Banner erforderlich!',
                    'success'
                ),
                FieldDefinitions::textField(
                    'field_options_pirsch_code',
                    'Pirsch Site Code',
                    'pirsch_code',
                    false,
                    'Den Code findest du unter <a href="https://pirsch.io" target="_blank">pirsch.io</a> → Dashboard → Settings → Integration Code. Leer lassen wenn nicht benötigt.',
                    'z.B. abc123def456'
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
                    <p style="margin: 10px 0 0 0;">
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
            'title' => 'Werkzeuge',
            'fields' => [
                // Content Setup Section
                [
                    'key' => 'field_options_tools_content_heading',
                    'label' => 'Content-Setup',
                    'type' => 'message',
                    'message' => '<p>Erstellt Standardseiten und richtet die Navigation ein.</p>',
                ],
                [
                    'key' => 'field_options_tools_content_status',
                    'label' => '',
                    'type' => 'message',
                    'message' => $contentSetupMessage,
                ],
                // Styleguide Section
                [
                    'key' => 'field_options_tools_styleguide_heading',
                    'label' => 'Styleguide',
                    'type' => 'message',
                    'message' => '<p>Der Styleguide zeigt alle verfügbaren Design-Elemente des Themes: Farben, Typografie, Abstände, Komponenten und ACF-Block-Beispiele.</p>',
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
