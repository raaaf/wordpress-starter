<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\MemberArea\Acf;
use WordpressStarter\MemberArea\Access;
use WordpressStarter\MemberArea\Auth;
use WordpressStarter\MemberArea\Crypto;
use WordpressStarter\MemberArea\DownloadQuery;
use WordpressStarter\MemberArea\FileHandler;
use WordpressStarter\MemberArea\FolderSync;
use WordpressStarter\Vite;

class MemberAreaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        add_action('acf/init', [Acf::class, 'register']);
    }

    public function boot(): void
    {
        // ACF flag is checked inside each hook callback (after ACF has loaded)
        Access::register();
        $this->registerAjaxHandlers();
        $this->enqueueAssets();
        $this->registerLogoutHandler();
        $this->registerCronJobs();
        $this->registerPasswordEncryption();
    }

    private static function isActiveInBackend(): bool
    {
        if (!function_exists('get_field')) {
            return true;
        }
        $flag = get_field('member_area_active', 'option');
        // Treat null (not yet saved) as active
        return $flag === null || (bool) $flag;
    }

    private function registerAjaxHandlers(): void
    {
        add_action('wp_ajax_nopriv_member_login', [$this, 'handleLogin']);
        add_action('wp_ajax_member_login', [$this, 'handleLogin']);

        add_action('wp_ajax_member_download', [FileHandler::class, 'handleDownload']);
        add_action('wp_ajax_nopriv_member_download', [FileHandler::class, 'handleDownload']);

        add_action('wp_ajax_member_downloads_query', [DownloadQuery::class, 'handle']);
        add_action('wp_ajax_nopriv_member_downloads_query', [DownloadQuery::class, 'handle']);

        add_action('wp_ajax_member_logout', [$this, 'handleLogout']);
        add_action('wp_ajax_nopriv_member_logout', [$this, 'handleLogout']);

        add_action('wp_ajax_member_sync_now', [$this, 'handleManualSync']);
    }

    public function handleLogin(): void
    {
        \WordpressStarter\RateLimiter::enforce('member_login', 5, 300);

        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if (!wp_verify_nonce($nonce, 'member_area_login')) {
            wp_send_json_error(['message' => __('Ungültige Anfrage.', 'wp-starter')], 403);
        }

        $credential = sanitize_text_field( wp_unslash( $_POST['credential'] ?? '' ) );
        $password    = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : null;

        if (empty($credential)) {
            wp_send_json_error(['message' => __('Bitte alle Felder ausfüllen.', 'wp-starter')], 400);
        }

        $result = Auth::login($credential, $password);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => __('Falsches Passwort.', 'wp-starter')], 401);
        }

        if (!$result) {
            wp_send_json_error(['message' => __('Anmeldung fehlgeschlagen.', 'wp-starter')], 401);
        }

        $redirectUrl = sanitize_url( wp_unslash( $_POST['redirect'] ?? '' ) );
        if (empty($redirectUrl)) {
            $redirectUrl = home_url('/');
        }

        wp_send_json_success(['redirect' => $redirectUrl]);
    }

    public function handleLogout(): void
    {
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? $_GET['nonce'] ?? '' ) );
        if (!wp_verify_nonce($nonce, 'member_area_logout')) {
            wp_send_json_error(['message' => __('Ungültige Anfrage.', 'wp-starter')], 403);
        }

        Auth::logout();

        wp_send_json_success(['redirect' => home_url('/')]);
    }

    private function registerLogoutHandler(): void
    {
        add_action('template_redirect', function (): void {
            if (isset($_GET['member_logout'])) {
                $nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );
                if (wp_verify_nonce($nonce, 'member_area_logout')) {
                    Auth::logout();
                    wp_safe_redirect(home_url('/'));
                    exit;
                }
            }
        });
    }

    public function handleManualSync(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'wp-starter')], 403);
        }

        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'member_sync_now')) {
            wp_send_json_error(['message' => __('Ungültige Anfrage.', 'wp-starter')], 403);
        }

        FolderSync::run();

        wp_send_json_success(['message' => __('Synchronisation abgeschlossen.', 'wp-starter')]);
    }

    private function registerCronJobs(): void
    {
        add_action('init', function (): void {
            if (!wp_next_scheduled('member_area_sync_folders')) {
                wp_schedule_event(time(), 'daily', 'member_area_sync_folders');
            }
        });

        add_action('member_area_sync_folders', [FolderSync::class, 'run']);

        // Auto-sync when a new SFTP parent entry is saved
        add_action('save_post_member_download', function (int $postId): void {
            if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
                return;
            }
            if (get_post_status($postId) !== 'publish') {
                return;
            }
            $sourceType = get_post_meta($postId, 'download_source_type', true);
            $sftpSource = get_post_meta($postId, 'download_sftp_source', true);

            // Only trigger for parent SFTP entries (no download_sftp_source = parent)
            if ($sourceType === 'sftp' && empty($sftpSource)) {
                wp_schedule_single_event(time() + 5, 'member_area_sync_folders');
            }
        });
    }

    private function registerPasswordEncryption(): void
    {
        // One-time migration: encrypt any existing plaintext passwords on first admin load
        add_action('admin_init', static function (): void {
            if (get_option('_member_sftp_passwords_encrypted') === '1') {
                return;
            }

            $posts = get_posts([
                'post_type'      => 'member_download',
                'post_status'    => ['publish', 'draft'],
                'posts_per_page' => -1,
                'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                    ['key' => 'download_source_type', 'value' => 'sftp'],
                    ['key' => 'download_sftp_password', 'compare' => 'EXISTS'],
                ],
                'fields' => 'ids',
            ]);

            foreach ($posts as $postId) {
                $pw = get_post_meta($postId, 'download_sftp_password', true);
                if (!is_string($pw) || $pw === '' || Crypto::isEncrypted($pw)) {
                    continue;
                }
                try {
                    update_post_meta($postId, 'download_sftp_password', Crypto::encrypt($pw));
                } catch (\RuntimeException $e) {
                    // AUTH_KEY not configured — skip silently
                }
            }

            update_option('_member_sftp_passwords_encrypted', '1', autoload: false);
        });

        // Encrypt the SFTP password when ACF saves the field
        add_filter('acf/update_value/name=download_sftp_password', static function (mixed $value): mixed {
            if (!is_string($value) || $value === '') {
                return $value;
            }

            // Already encrypted — don't double-encrypt (e.g. on re-save without changing the value)
            if (Crypto::isEncrypted($value)) {
                return $value;
            }

            try {
                return Crypto::encrypt($value);
            } catch (\RuntimeException $e) {
                // AUTH_KEY not configured — store plaintext rather than silently break sync
                return $value;
            }
        });
    }

    private function enqueueAssets(): void
    {
        add_action('wp_enqueue_scripts', function (): void {
            if (!$this->shouldEnqueueAssets()) {
                return;
            }

            // member-area.ts is imported by app.ts and bundled together.
            // We only need to pass the config to the already-enqueued app-js handle.
            wp_localize_script('app-js', 'memberAreaConfig', [
                'ajaxUrl'        => admin_url('admin-ajax.php'),
                'nonce'          => wp_create_nonce('member_area_login'),
                'authMode'       => Auth::getAuthMode(),
                'logoutNonce'    => wp_create_nonce('member_area_logout'),
                'downloadsNonce' => wp_create_nonce('member_downloads_query'),
            ]);
        });
    }

    private function shouldEnqueueAssets(): bool
    {
        if (!self::isActiveInBackend()) {
            return false;
        }

        if (!is_singular()) {
            return false;
        }

        // Check for member area page (ACF field)
        if (is_page() && get_field('page_is_member_area')) {
            return true;
        }

        // Check if current page is protected
        if (is_page() && get_field('page_is_protected')) {
            return true;
        }

        return false;
    }
}
