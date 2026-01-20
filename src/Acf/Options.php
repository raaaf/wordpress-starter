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

        // Sub pages with German labels
        self::addSubPage('Allgemein', 'general');
        self::addSubPage('Header', 'header');
        self::addSubPage('Footer', 'footer');
        self::addSubPage('Social Media', 'social');
        self::addSubPage('Analytics', 'analytics');
        self::addSubPage('Rechtliches', 'legal');
        self::addSubPage('Werkzeuge', 'tools');

        // Register field groups directly (we're already in acf/init)
        self::registerFieldGroups();
    }

    /**
     * Add options sub page
     */
    private static function addSubPage(string $title, string $slug): void
    {
        acf_add_options_sub_page([
            'page_title' => $title,
            'menu_title' => $title,
            'parent_slug' => 'theme-options',
            'menu_slug' => 'theme-options-' . $slug,
        ]);
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
        self::registerLegalFields();
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
                FieldDefinitions::imageField(
                    'field_options_logo',
                    'Logo',
                    'site_logo',
                    false,
                    'array',
                    null,
                    'Das Hauptlogo der Website. Empfohlen: SVG oder PNG mit transparentem Hintergrund.'
                ),
                FieldDefinitions::imageField(
                    'field_options_logo_dark',
                    'Logo (Dunkler Hintergrund)',
                    'site_logo_dark',
                    false,
                    'array',
                    null,
                    'Alternative Logo-Version für dunkle Hintergründe.'
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

                // Contact Tab
                [
                    'key' => 'field_options_tab_contact',
                    'label' => 'Kontaktdaten',
                    'type' => 'tab',
                ],
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
                FieldDefinitions::textField(
                    'field_options_phone',
                    'Telefon',
                    'phone',
                    false,
                    'Haupttelefonnummer für Kontakt.',
                    '+49 123 456789'
                ),
                FieldDefinitions::emailField(
                    'field_options_email',
                    'E-Mail',
                    'email',
                    'Haupt-E-Mail-Adresse für Kontaktanfragen.',
                    'info@example.com'
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
                FieldDefinitions::trueFalseField(
                    'field_options_header_sticky',
                    'Sticky Header',
                    'header_sticky',
                    true,
                    'Header bleibt beim Scrollen oben fixiert.'
                ),
                FieldDefinitions::trueFalseField(
                    'field_options_header_cta_show',
                    'CTA-Button anzeigen',
                    'header_cta_show',
                    true,
                    'Zeigt einen Call-to-Action Button im Header.'
                ),
                FieldDefinitions::linkField(
                    'field_options_header_cta',
                    'CTA-Button',
                    'header_cta',
                    false,
                    'Link und Text für den Header-CTA-Button.'
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
                FieldDefinitions::wysiwygField(
                    'field_options_footer_text',
                    'Footer-Text',
                    'footer_text',
                    false,
                    null,
                    'Optionaler Text im Footer (z.B. Firmenbeschreibung).'
                ),
                FieldDefinitions::textField(
                    'field_options_copyright',
                    'Copyright-Text',
                    'copyright_text',
                    false,
                    'Der Copyright-Hinweis. {year} wird automatisch durch das aktuelle Jahr ersetzt.',
                    '© {year} Firmenname. Alle Rechte vorbehalten.'
                ),
                FieldDefinitions::trueFalseField(
                    'field_options_footer_contact_show',
                    'Kontaktdaten anzeigen',
                    'footer_show_contact',
                    true,
                    'Zeigt Adresse, Telefon und E-Mail im Footer.'
                ),
                FieldDefinitions::trueFalseField(
                    'field_options_footer_social_show',
                    'Social Links anzeigen',
                    'footer_show_social',
                    true,
                    'Zeigt die Social Media Icons im Footer.'
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
                FieldDefinitions::repeaterField(
                    'field_options_social_links',
                    'Social Media Kanäle',
                    'social_links',
                    [
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
                            'Wähle die Social Media Plattform.'
                        ),
                        FieldDefinitions::urlField(
                            'field_options_social_url',
                            'Profil-URL',
                            'url',
                            'Der vollständige Link zu eurem Profil.',
                            null,
                            'https://linkedin.com/company/...'
                        ),
                    ],
                    'Kanal hinzufügen',
                    0,
                    'table',
                    'Füge alle Social Media Kanäle hinzu, die im Footer und Header angezeigt werden sollen.'
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
                // Info-Nachricht
                [
                    'key' => 'field_options_analytics_info',
                    'label' => '',
                    'name' => '',
                    'type' => 'message',
                    'message' => '<p><strong>Cookie-freie Website</strong></p><p>Dieses Theme verwendet ausschließlich DSGVO-konforme Analytics ohne Cookies. Pirsch Analytics respektiert die Privatsphäre deiner Besucher und benötigt keinen Cookie-Banner.</p>',
                ],
                // Pirsch Analytics
                FieldDefinitions::textField(
                    'field_options_pirsch_code',
                    'Pirsch Site Code',
                    'pirsch_code',
                    false,
                    'DSGVO-konforme Analyse ohne Cookies. Den Code findest du unter pirsch.io → Dashboard → Settings → Integration Code. Leer lassen wenn nicht benötigt.',
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
     * Legal Settings Fields
     */
    private static function registerLegalFields(): void
    {
        acf_add_local_field_group([
            'key' => 'group_options_legal',
            'title' => 'Rechtliche Einstellungen',
            'fields' => [
                FieldDefinitions::postObjectField(
                    'field_options_privacy_page',
                    'Datenschutz-Seite',
                    'privacy_page',
                    ['page'],
                    'Wähle die Seite mit der Datenschutzerklärung.'
                ),
                FieldDefinitions::postObjectField(
                    'field_options_imprint_page',
                    'Impressum-Seite',
                    'imprint_page',
                    ['page'],
                    'Wähle die Seite mit dem Impressum.'
                ),
                FieldDefinitions::postObjectField(
                    'field_options_terms_page',
                    'AGB-Seite',
                    'terms_page',
                    ['page'],
                    'Optionale Seite mit den Allgemeinen Geschäftsbedingungen.'
                ),
                FieldDefinitions::wysiwygField(
                    'field_options_cookie_notice',
                    'Cookie-Hinweis',
                    'cookie_notice_text',
                    false,
                    null,
                    'Text für den Cookie-Hinweis Banner (falls verwendet).'
                ),
            ],
            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'theme-options-legal',
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
            $editUrl = get_edit_post_link((int) $styleguidePageId, 'raw');
            $viewUrl = get_permalink((int) $styleguidePageId);
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

        acf_add_local_field_group([
            'key' => 'group_options_tools',
            'title' => 'Werkzeuge',
            'fields' => [
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
        wp_cache_delete_group('theme');
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
