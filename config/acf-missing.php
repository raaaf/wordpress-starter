<?php

/**
 * Frontend fallback while Advanced Custom Fields PRO is inactive
 * (ThemeServiceProvider::addTemplateFilter).
 *
 * Every Blade template reads ACF fields, so without the plugin each page
 * view would fail. This page answers 503 instead: search engines retry
 * later rather than indexing an error, and visitors see a short note.
 * Users who may activate or install plugins also get the reason and links.
 * Only frontend templates land here (template_include), so wp-admin stays
 * reachable and shows the missing plugin as a notice (PluginServiceProvider).
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

status_header(503);
header('Retry-After: 3600');
nocache_headers();

$wpStarterSiteName = get_bloginfo('name');
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?php echo esc_html($wpStarterSiteName); ?></title>
    <style>
        body { margin: 0; padding: 4rem 1rem; font: 1rem/1.6 system-ui, sans-serif; color: #1c1917; background: #fafaf9; }
        main { max-width: 40rem; margin: 0 auto; }
        a { color: inherit; }
    </style>
</head>
<body>
    <main>
        <h1><?php echo esc_html($wpStarterSiteName); ?></h1>
        <p><?php esc_html_e('Diese Website wird gerade eingerichtet. Bitte versuche es später noch einmal.', 'wp-starter'); ?></p>
        <?php if (current_user_can('activate_plugins') || current_user_can('install_plugins')) : ?>
            <p>
                <?php esc_html_e('Hinweis für Administratoren: Das Theme braucht das Plugin Advanced Custom Fields PRO. Bitte installieren und aktivieren.', 'wp-starter'); ?>
            </p>
            <p>
                <?php if (current_user_can('activate_plugins')) : ?>
                    <a href="<?php echo esc_url(admin_url('plugins.php')); ?>"><?php esc_html_e('Zu den Plugins', 'wp-starter'); ?></a>
                <?php endif; ?>
                <?php if (current_user_can('install_plugins')) : ?>
                    <?php // ACF PRO is not on wordpress.org, so the upload tab, not the search. ?>
                    <a href="<?php echo esc_url(admin_url('plugin-install.php?tab=upload')); ?>"><?php esc_html_e('Plugin hochladen', 'wp-starter'); ?></a>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </main>
</body>
</html>
