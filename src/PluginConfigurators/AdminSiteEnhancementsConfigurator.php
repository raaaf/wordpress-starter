<?php

declare(strict_types=1);

namespace WordpressStarter\PluginConfigurators;

/**
 * Configures Admin and Site Enhancements (ASE) plugin
 *
 * Settings applied on first activation:
 *
 * Content Management:
 * - Content Duplication enabled
 * - Media Replacement enabled
 *
 * Admin Interface:
 * - Admin notices moved to collapsible panel
 * - Welcome panel disabled
 * - WP logo hidden from admin bar
 * - Admin bar hidden for non-admin/non-editor roles
 * - Wider admin menu
 * - Show ID column in list tables
 *
 * Performance:
 * - Heartbeat optimized (60s admin, 30s editor, disabled frontend)
 * - Emojis disabled
 * - Embeds disabled
 * - Frontend Dashicons disabled for guests
 * - Resource version numbers removed
 * - Revisions limited to 5
 *
 * Security:
 * - XML-RPC disabled
 * - Application Passwords disabled
 * - Login attempts limited (5 fails, 24h lockout)
 * - Author slugs obfuscated
 * - Email addresses obfuscated on frontend
 *
 * Media:
 * - SVG upload enabled (administrator, editor)
 * - AVIF upload enabled
 *
 * Utilities:
 * - Missed schedule posts auto-publish enabled
 *
 * @see https://wordpress.org/plugins/admin-site-enhancements/
 */
class AdminSiteEnhancementsConfigurator extends AbstractPluginConfigurator
{
    public static function getPluginSlug(): string
    {
        return 'admin-site-enhancements';
    }

    public static function isPluginActive(): bool
    {
        return defined('ASENHA_VERSION');
    }

    public static function configure(): void
    {
        if (!self::isPluginActive() || self::isConfigured()) {
            return;
        }

        $options = get_option('admin_site_enhancements', []);

        // === Content Management ===
        $options['enable_duplication'] = true;
        $options['duplication_redirect_destination'] = 'edit';
        $options['enable_media_replacement'] = true;

        // === Admin Interface ===
        $options['hide_admin_notices'] = true;
        $options['disable_welcome_panel_in_dashboard'] = true;
        $options['hide_ab_wp_logo_menu'] = true;
        $options['hide_admin_bar'] = true;
        $options['hide_admin_bar_for'] = ['subscriber'];
        $options['wider_admin_menu'] = true;
        $options['show_id_column'] = true;
        $options['show_last_modified_column'] = true;

        // === Performance ===
        $options['enable_heartbeat_control'] = true;
        $options['heartbeat_control_for_admin_pages'] = true;
        $options['heartbeat_interval_for_admin_pages'] = 60;
        $options['heartbeat_control_for_post_edit'] = true;
        $options['heartbeat_interval_for_post_edit'] = 30;
        $options['heartbeat_control_for_frontend'] = true;
        $options['heartbeat_interval_for_frontend'] = 0; // Disabled

        $options['disable_emoji_support'] = true;
        $options['disable_embeds'] = true;
        $options['disable_frontend_dashicons'] = true;
        $options['disable_resource_version_number'] = true;

        $options['enable_revisions_control'] = true;
        $options['revisions_max_number'] = 5;

        // === Security ===
        $options['disable_xmlrpc'] = true;
        $options['disable_application_passwords'] = true;

        $options['limit_login_attempts'] = true;
        $options['login_fails_allowed'] = 5;
        $options['login_lockout_maxcount'] = 24; // Hours

        $options['obfuscate_author_slugs'] = true;
        $options['obfuscate_email_address'] = true;

        // === Media ===
        $options['enable_svg_upload'] = true;
        $options['enable_svg_upload_for'] = ['administrator'];
        $options['enable_avif_upload'] = true;

        // === Utilities ===
        $options['enable_missed_schedule_posts_auto_publish'] = true;

        update_option('admin_site_enhancements', $options);

        self::markConfigured();
    }

    public static function getConfigurationSummary(): string
    {
        return __('ASE: SVG/AVIF-Upload, Heartbeat optimiert, Security-Grundschutz, Admin-UI bereinigt', 'wp-starter');
    }
}
