<?php

declare(strict_types=1);

namespace WordpressStarter;

/**
 * Plugin Installer
 *
 * Handles bulk installation of plugins from WordPress.org using the Plugin Upgrader API.
 */
class PluginInstaller
{
    /**
     * Install a plugin from WordPress.org
     *
     * @param string $slug Plugin slug
     * @return array{success: bool, message: string}
     */
    public static function install(string $slug): array
    {
        if (!current_user_can('install_plugins')) {
            return [
                'success' => false,
                'message' => __('Sie haben keine Berechtigung, Plugins zu installieren.', 'wp-starter'),
            ];
        }

        // Sanitize slug - only allow alphanumeric and hyphens
        $slug = sanitize_title($slug);

        if (empty($slug)) {
            return [
                'success' => false,
                'message' => __('Ungültiger Plugin-Slug.', 'wp-starter'),
            ];
        }

        // Check if plugin is already installed
        if (self::isInstalled($slug)) {
            return [
                'success' => true,
                'message' => __('Plugin ist bereits installiert.', 'wp-starter'),
            ];
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';

        // Initialize WP_Filesystem - required for plugin installation
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // Use direct filesystem access (no FTP credentials needed for local)
        if (!WP_Filesystem(false, false, true)) {
            return [
                'success' => false,
                'message' => __('Dateisystem konnte nicht initialisiert werden.', 'wp-starter'),
            ];
        }

        // Get plugin info from WordPress.org
        $api = plugins_api('plugin_information', [
            'slug' => $slug,
            'fields' => [
                'short_description' => false,
                'sections' => false,
                'requires' => false,
                'rating' => false,
                'ratings' => false,
                'downloaded' => false,
                'last_updated' => false,
                'added' => false,
                'tags' => false,
                'compatibility' => false,
                'homepage' => false,
                'donate_link' => false,
            ],
        ]);

        if (is_wp_error($api)) {
            return [
                'success' => false,
                'message' => $api->get_error_message(),
            ];
        }

        // Install the plugin using quiet skin to suppress output
        $skin = self::createQuietSkin();
        $upgrader = new \Plugin_Upgrader($skin);
        /** @var object{download_link: string} $api */
        $result = $upgrader->install($api->download_link);

        if (is_wp_error($result)) {
            return [
                'success' => false,
                'message' => $result->get_error_message(),
            ];
        }

        // Check for skin errors (methods defined in anonymous class)
        // @phpstan-ignore method.notFound
        if ($skin->has_errors()) {
            // @phpstan-ignore method.notFound
            $errors = $skin->get_errors();
            $errorMessage = is_wp_error($errors[0])
                ? $errors[0]->get_error_message()
                : (string) $errors[0];
            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        }

        if ($result === false || $result === null) {
            return [
                'success' => false,
                'message' => __('Installation fehlgeschlagen.', 'wp-starter'),
            ];
        }

        // Clear plugin cache so WordPress sees the newly installed plugin
        wp_clean_plugins_cache();

        return [
            'success' => true,
            'message' => __('Plugin erfolgreich installiert.', 'wp-starter'),
        ];
    }

    /**
     * Activate a plugin
     *
     * @param string $slug Plugin slug
     * @return array{success: bool, message: string}
     */
    public static function activate(string $slug): array
    {
        if (!current_user_can('activate_plugins')) {
            return [
                'success' => false,
                'message' => __('Sie haben keine Berechtigung, Plugins zu aktivieren.', 'wp-starter'),
            ];
        }

        // Ensure is_plugin_active function is available
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $pluginFile = self::getPluginFile($slug);

        if (!$pluginFile) {
            return [
                'success' => false,
                'message' => __('Plugin-Datei nicht gefunden.', 'wp-starter'),
            ];
        }

        if (is_plugin_active($pluginFile)) {
            return [
                'success' => true,
                'message' => __('Plugin ist bereits aktiv.', 'wp-starter'),
            ];
        }

        $result = activate_plugin($pluginFile);

        if (is_wp_error($result)) {
            return [
                'success' => false,
                'message' => $result->get_error_message(),
            ];
        }

        return [
            'success' => true,
            'message' => __('Plugin erfolgreich aktiviert.', 'wp-starter'),
        ];
    }

    /**
     * Install and activate a plugin
     *
     * @param string $slug Plugin slug
     * @return array{success: bool, message: string, installed: bool, activated: bool}
     */
    public static function installAndActivate(string $slug): array
    {
        $installed = false;
        $activated = false;

        // Install if not already installed
        if (!self::isInstalled($slug)) {
            $installResult = self::install($slug);
            if (!$installResult['success']) {
                return [
                    'success' => false,
                    'message' => $installResult['message'],
                    'installed' => false,
                    'activated' => false,
                ];
            }
            $installed = true;
        }

        // Activate
        $activateResult = self::activate($slug);
        if (!$activateResult['success']) {
            return [
                'success' => false,
                'message' => $activateResult['message'],
                'installed' => $installed,
                'activated' => false,
            ];
        }
        $activated = true;

        return [
            'success' => true,
            'message' => __('Plugin erfolgreich installiert und aktiviert.', 'wp-starter'),
            'installed' => $installed,
            'activated' => $activated,
        ];
    }

    /**
     * Check if a plugin is installed
     */
    public static function isInstalled(string $slug): bool
    {
        return self::getPluginFile($slug) !== null;
    }

    /**
     * Get the plugin file path from slug
     */
    public static function getPluginFile(string $slug): ?string
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();

        foreach ($plugins as $file => $plugin) {
            // Check if the plugin folder matches the slug
            $pluginDir = dirname($file);
            if ($pluginDir === $slug || $file === $slug . '.php') {
                return $file;
            }
        }

        return null;
    }

    /**
     * Bulk install and activate multiple plugins
     *
     * @param array<string> $slugs Plugin slugs
     * @return array<string, array{success: bool, message: string}>
     */
    public static function bulkInstallAndActivate(array $slugs): array
    {
        $results = [];

        foreach ($slugs as $slug) {
            $results[$slug] = self::installAndActivate($slug);
        }

        return $results;
    }


    /**
     * Create a quiet skin for silent plugin installation
     *
     * Uses an anonymous class to avoid autoload issues with WP_Upgrader_Skin
     *
     * @return \WP_Upgrader_Skin
     */
    private static function createQuietSkin(): \WP_Upgrader_Skin
    {
        return new class() extends \WP_Upgrader_Skin {
            /** @var array<string|\WP_Error> */
            private array $errors = [];

            public function feedback($feedback, ...$args): void
            {
                // Suppress output
            }

            public function header(): void
            {
                // Suppress output
            }

            public function footer(): void
            {
                // Suppress output
            }

            /**
             * @param string|\WP_Error $errors
             */
            public function error($errors): void
            {
                if (is_string($errors)) {
                    $this->errors[] = $errors;
                } elseif (is_wp_error($errors)) {
                    $this->errors[] = $errors;
                }
            }

            /** @return array<string|\WP_Error> */
            public function get_errors(): array
            {
                return $this->errors;
            }

            public function has_errors(): bool
            {
                return !empty($this->errors);
            }
        };
    }
}
