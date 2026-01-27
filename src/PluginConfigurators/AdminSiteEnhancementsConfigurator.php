<?php

declare(strict_types=1);

namespace WordpressStarter\PluginConfigurators;

/**
 * Configures Admin and Site Enhancements (ASE) plugin
 *
 * Settings applied:
 * - SVG/AVIF Upload: Enabled for admin/editor
 * - Heartbeat: Optimized (60s admin, disabled frontend)
 * - Security: XML-RPC and Application Passwords disabled
 * - Admin UI: Wider content, notices in panel
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

        // ASE stores all options in a single serialized option
        $options = get_option('admin_site_enhancements', []);

        // === Content Management ===
        $options['enable_duplication'] = true;
        $options['duplication_redirect_destination'] = 'edit'; // Go to edit screen after duplication

        // === Media / Uploads ===
        // Enable SVG upload with sanitization
        $options['enable_svg_upload'] = true;
        $options['enable_svg_upload_for_roles'] = ['administrator', 'editor'];

        // Enable AVIF upload
        $options['enable_avif_upload'] = true;

        // === Admin Interface ===
        // Hide admin bar for non-logged-in users
        $options['hide_admin_bar_for_guests'] = true;

        // Hide WP logo from admin bar
        $options['hide_wp_logo_in_admin_bar'] = true;

        // Move admin notices to collapsible panel
        $options['admin_notices_to_panel'] = true;

        // Wider admin content area
        $options['wider_admin_content'] = true;

        // === Performance ===
        // Optimize heartbeat API
        $options['modify_heartbeat'] = true;
        $options['heartbeat_interval_admin'] = 60; // 60 seconds in admin
        $options['heartbeat_interval_post_editor'] = 30; // 30 seconds in editor
        $options['heartbeat_interval_frontend'] = 0; // Disable on frontend

        // === Security ===
        // Disable XML-RPC (security risk, rarely needed)
        $options['disable_xmlrpc'] = true;

        // Disable application passwords (use proper 2FA instead)
        $options['disable_application_passwords'] = true;

        // Disable REST API for non-logged-in users (partial)
        $options['disable_rest_api_for_guests'] = false; // Keep enabled for themes/plugins

        // === Login ===
        // Change login URL is NOT enabled by default (can break things)
        $options['change_login_url'] = false;

        // === Utilities ===
        // Enable maintenance mode toggle (but keep disabled)
        $options['maintenance_mode'] = false;

        update_option('admin_site_enhancements', $options);

        self::markConfigured();
    }

    public static function getConfigurationSummary(): string
    {
        return __('ASE: SVG/AVIF-Upload, Heartbeat optimiert, Admin-UI bereinigt', 'wp-starter');
    }
}
