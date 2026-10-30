<?php

/**
 * Frontend fallback while Advanced Custom Fields PRO is inactive
 * (ThemeServiceProvider::addTemplateFilter).
 *
 * Every Blade template reads ACF fields, so without the plugin each page
 * view would fail. This page answers 503 instead: search engines retry
 * later rather than indexing an error, and visitors see a short note.
 * Administrators also get the reason and a link; the backend shows the
 * missing plugin as a notice (PluginServiceProvider).
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
        <?php if (current_user_can('activate_plugins')) : ?>
            <p>
                <?php esc_html_e('Hinweis für Administratoren: Das Theme braucht das Plugin Advanced Custom Fields PRO. Bitte installieren und aktivieren.', 'wp-starter'); ?>
                <a href="<?php echo esc_url(admin_url('plugins.php')); ?>"><?php esc_html_e('Zu den Plugins', 'wp-starter'); ?></a>
            </p>
        <?php endif; ?>
    </main>
</body>
</html>
