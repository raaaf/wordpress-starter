<?php

declare(strict_types=1);

namespace WordpressStarter\PluginConfigurators;

/**
 * Configures iThemes Security Pro plugin
 *
 * Settings applied on first activation:
 *
 * Brute Force Protection:
 * - Max login attempts per host: 5 (lockout after 5 fails)
 * - Max login attempts per user: 10
 * - Check period: 5 minutes
 * - Auto-ban "admin" username attempts
 *
 * WordPress Tweaks:
 * - File editor disabled (wp-admin theme/plugin editor)
 * - XML-RPC fully disabled
 * - Unused author pages disabled
 *
 * System Tweaks:
 * - System files protected (readme.html, wp-config.php etc.)
 * - Directory browsing disabled
 * - PHP execution disabled in uploads, plugins, themes directories
 *
 * Ban Users:
 * - Ban lists enabled
 * - HackRepair.com default ban list included
 *
 * Two-Factor:
 * - NOT configured here — remains optional for each user
 *   Enable manually via Security → Settings → Two-Factor
 *
 * @see https://ithemes.com/security/
 */
class IThemesSecurityConfigurator extends AbstractPluginConfigurator
{
    public static function getPluginSlug(): string
    {
        return 'ithemes-security-pro';
    }

    public static function isPluginActive(): bool
    {
        return class_exists('ITSEC_Modules');
    }

    public static function configure(): void
    {
        if (!self::isPluginActive() || self::isConfigured()) {
            return;
        }

        self::configureBruteForce();
        self::configureWordPressTweaks();
        self::configureSystemTweaks();
        self::configureBanUsers();

        self::markConfigured();
    }

    /**
     * Brute force login protection
     */
    private static function configureBruteForce(): void
    {
        $current = \ITSEC_Modules::get_settings('brute-force');

        \ITSEC_Modules::set_settings('brute-force', array_merge($current, [
            'auto_ban_admin'    => true,  // Ban any IP trying to login as "admin"
            'max_attempts_host' => 5,     // 5 failed attempts → host locked out
            'max_attempts_user' => 10,    // 10 failed attempts → user locked out
            'check_period'      => 5,     // Remember bad logins for 5 minutes
        ]));
    }

    /**
     * WordPress core hardening tweaks
     */
    private static function configureWordPressTweaks(): void
    {
        $current = \ITSEC_Modules::get_settings('wordpress-tweaks');

        \ITSEC_Modules::set_settings('wordpress-tweaks', array_merge($current, [
            'file_editor'              => true,      // Disable wp-admin theme/plugin editor
            'disable_xmlrpc'           => 'disable', // Fully disable XML-RPC
            'disable_unused_author_pages' => true,   // Hide author pages for users with no posts
        ]));
    }

    /**
     * Server-level file system hardening
     */
    private static function configureSystemTweaks(): void
    {
        $current = \ITSEC_Modules::get_settings('system-tweaks');

        \ITSEC_Modules::set_settings('system-tweaks', array_merge($current, [
            'protect_files'       => true, // Block access to readme.html, wp-config.php etc.
            'directory_browsing'  => true, // Disable directory listing
            'uploads_php'         => true, // Block PHP execution in uploads/
            'plugins_php'         => true, // Block PHP execution in plugins/
            'themes_php'          => true, // Block PHP execution in themes/
        ]));
    }

    /**
     * IP ban list configuration
     */
    private static function configureBanUsers(): void
    {
        $current = \ITSEC_Modules::get_settings('ban-users');

        \ITSEC_Modules::set_settings('ban-users', array_merge($current, [
            'enable_ban_lists' => true, // Enable ban list feature
            'default'          => true, // Include HackRepair.com known-bad-actor list
        ]));
    }

    public static function getConfigurationSummary(): string
    {
        return __('iThemes Security: Brute-Force-Schutz, WordPress-Härtung, Datei-Schutz, Ban-Listen aktiv', 'wp-starter');
    }
}
