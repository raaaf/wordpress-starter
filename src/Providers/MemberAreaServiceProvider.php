<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use RuntimeException;
use WordpressStarter\MemberArea\Access;
use WordpressStarter\MemberArea\Acf;
use WordpressStarter\MemberArea\Auth;
use WordpressStarter\MemberArea\Crypto;
use WordpressStarter\MemberArea\DownloadQuery;
use WordpressStarter\MemberArea\FileHandler;
use WordpressStarter\MemberArea\FolderSync;
use WordpressStarter\ThemeContext;

class MemberAreaServiceProvider extends ServiceProvider
{
    use AdminActionGuard;

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
        $this->registerHostKeyRetrust();
        $this->registerUserRole();
        $this->registerDownloadCacheInvalidation();
    }

    /**
     * Register the "Zugang Interner Bereich" WordPress user role.
     * The role has no capabilities — it exists solely for identification in the WP backend.
     * add_role() is a no-op if the role already exists.
     */
    private function registerUserRole(): void
    {
        add_action('after_setup_theme', static function (): void {
            add_role(
                'member_area_access',
                __('Zugang Interner Bereich', 'wp-starter'),
                [],
            );
        });

        // Block backend access for this role — frontend only.
        // Skip during AJAX requests (admin-ajax.php also fires admin_init): member-area
        // AJAX actions (login, download, downloads_query, logout) must not be 403'd here.
        add_action('admin_init', static function (): void {
            if (wp_doing_ajax()) {
                return;
            }
            $user = wp_get_current_user();
            if (in_array('member_area_access', (array) $user->roles, true)) {
                wp_die(
                    esc_html__('Du hast keinen Zugriff auf den Administrationsbereich.', 'wp-starter'),
                    403,
                );
            }
        });
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

        add_action('wp_ajax_member_get_nonces', [$this, 'handleGetNonces']);
        add_action('wp_ajax_nopriv_member_get_nonces', [$this, 'handleGetNonces']);

        add_action('wp_ajax_member_sync_now', [$this, 'handleManualSync']);
    }

    /**
     * Returns fresh nonces for member-area AJAX actions.
     * Required when the member-area page is served from a page cache: nonces embedded
     * in cached HTML expire and break login. The frontend fetches a fresh set right
     * before submitting. Response is explicitly non-cacheable.
     */
    public function handleGetNonces(): void
    {
        nocache_headers();
        \WordpressStarter\RateLimiter::enforce('member_get_nonces', 30, 60);

        wp_send_json_success([
            'login' => wp_create_nonce('member_area_login'),
            'logout' => wp_create_nonce('member_area_logout'),
            'downloads' => wp_create_nonce('member_downloads_query'),
        ]);
    }

    public function handleLogin(): void
    {
        \WordpressStarter\RateLimiter::enforce('member_login', 5, 300);

        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'member_area_login')) {
            wp_send_json_error(['message' => __('Ungültige Anfrage.', 'wp-starter')], 403);
        }

        $credential = sanitize_text_field(wp_unslash($_POST['credential'] ?? ''));
        // Passwords must not be sanitized — sanitize_text_field strips characters that
        // may be part of a valid password (e.g. <, >, &, multiple spaces).
        $password = isset($_POST['password']) ? wp_unslash($_POST['password']) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        if (empty($credential)) {
            wp_send_json_error(['message' => __('Bitte alle Felder ausfüllen.', 'wp-starter')], 400);
        }

        $result = Auth::login($credential, $password);

        if (is_wp_error($result)) {
            $errorCode = $result->get_error_code();
            // Auth::login() already collapses the enumeration-relevant wp_signon()
            // codes (invalid_username / invalid_email / incorrect_password / invalidcombo)
            // into 'member_login_failed', and password mode returns 'wrong_password' —
            // both are wrong-credential cases and get the same generic message. Any
            // other WP_Error (e.g. 'no_password', or a structural code passed through
            // unchanged from wp_signon()) uses its own message.
            $message = match ($errorCode) {
                'member_login_failed', 'wrong_password' => __('Falsches Passwort.', 'wp-starter'),
                'no_password' => $result->get_error_message(),
                default => __('Anmeldung fehlgeschlagen.', 'wp-starter'),
            };
            wp_send_json_error(['message' => $message], 401);
        }

        if (!$result) {
            wp_send_json_error(['message' => __('Kein Zugang — fehlende Berechtigung.', 'wp-starter')], 401);
        }

        $redirectUrl = wp_validate_redirect(sanitize_url(wp_unslash($_POST['redirect'] ?? '')), home_url('/'));

        wp_send_json_success(['redirect' => $redirectUrl]);
    }

    public function handleLogout(): void
    {
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? $_GET['nonce'] ?? ''));
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
                $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? ''));
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
            // Args ($postId) distinguish this debounce event from the daily cron above,
            // which shares the same hook name but is scheduled without args.
            if ($sourceType === 'sftp' && empty($sftpSource) && !wp_next_scheduled('member_area_sync_folders', [$postId])) {
                wp_schedule_single_event(time() + 5, 'member_area_sync_folders', [$postId]);
            }
        });
    }

    /**
     * Invalidate the cached member_download facets whenever a download is saved,
     * trashed or permanently deleted (facets have no per-user visibility filter,
     * so a single shared cache and a single invalidation point are sufficient).
     */
    private function registerDownloadCacheInvalidation(): void
    {
        add_action('save_post_member_download', [DownloadQuery::class, 'invalidateFacetsCache']);

        // before_delete_post (not deleted_post): the post row, and its type, is
        // already gone by the time deleted_post fires.
        add_action('before_delete_post', static function (int $postId): void {
            if (get_post_type($postId) === 'member_download') {
                DownloadQuery::invalidateFacetsCache();
            }
        });
    }

    private function registerPasswordEncryption(): void
    {
        // One-time migration: encrypt any existing plaintext passwords on first admin load.
        // Skip on AJAX (admin-ajax.php also fires admin_init, incl. unauthenticated requests)
        // and require manage_options, so the meta_query batch loop never runs on member AJAX.
        add_action('admin_init', static function (): void {
            if (wp_doing_ajax() || !current_user_can('manage_options')) {
                return;
            }
            if (get_option(ThemeContext::optionKey('sftp_passwords_encrypted')) === '1') {
                return;
            }

            $batchSize = 100;
            $paged = 1;

            do {
                $posts = get_posts([
                    'post_type' => 'member_download',
                    'post_status' => ['publish', 'draft'],
                    'posts_per_page' => $batchSize,
                    'paged' => $paged,
                    'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
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
                    } catch (RuntimeException $e) {
                        // AUTH_KEY not configured — skip, but make the condition visible
                        LogServiceProvider::warning('SFTP password migration skipped', [
                            'post_id' => $postId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $postsCount = count($posts);
                $paged++;
            } while ($postsCount === $batchSize);

            // Only mark the migration done once every batch has been processed —
            // if this admin_init run is interrupted mid-loop, the flag stays unset
            // and the next admin_init run resumes (already-encrypted values are skipped above).
            update_option(ThemeContext::optionKey('sftp_passwords_encrypted'), '1', autoload: false);
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
            } catch (RuntimeException $e) {
                // AUTH_KEY not configured — never store the plaintext password
                LogServiceProvider::error('SFTP password was not saved', ['error' => $e->getMessage()]);

                add_action('admin_notices', static function (): void {
                    echo '<div class="notice notice-error is-dismissible"><p>'
                        . esc_html__('Das SFTP-Passwort wurde nicht gespeichert: AUTH_KEY ist in der wp-config.php nicht konfiguriert. Bitte AUTH_KEY setzen und das Passwort erneut eingeben.', 'wp-starter')
                        . '</p></div>';
                });

                return '';
            }
        });
    }

    /**
     * SFTP host key mismatch transient key (see SftpClient::HOST_KEY_MISMATCH_TRANSIENT).
     */
    private const HOST_KEY_MISMATCH_TRANSIENT_SUFFIX = 'member_sftp_host_key_mismatch';

    /**
     * SFTP pinned host keys option key suffix (see SftpClient::HOST_KEYS_OPTION).
     */
    private const HOST_KEYS_OPTION_SUFFIX = 'member_sftp_host_keys';

    /**
     * Registers the admin_init handler for the "re-trust host key" action link
     * and the admin notice that offers it.
     *
     * SftpClient pins each SFTP host key trust-on-first-use, keyed by host:port.
     * On a mismatch (e.g. after a server migration) it stores nothing, throws,
     * and sets a transient naming the affected host:port. This notice surfaces
     * that transient to admins and lets them explicitly clear the stale pin so
     * the next connection re-pins against the new key.
     */
    private function registerHostKeyRetrust(): void
    {
        add_action('admin_init', function (): void {
            if (!self::verifyAdminAction(
                'member_sftp_retrust',
                'member_sftp_retrust',
                'manage_options',
                __('Keine Berechtigung.', 'wp-starter'),
            )) {
                return;
            }

            $hostPort = sanitize_text_field(wp_unslash($_GET['host'] ?? '')); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified via verifyAdminAction()
            $pinRemoved = false;
            if ($hostPort !== '') {
                $optionKey = ThemeContext::optionKey(self::HOST_KEYS_OPTION_SUFFIX);
                $hostKeys = get_option($optionKey, []);
                if (is_array($hostKeys) && isset($hostKeys[$hostPort])) {
                    unset($hostKeys[$hostPort]);
                    update_option($optionKey, $hostKeys, autoload: false);
                    $pinRemoved = true;
                }
            }

            // Only clear the mismatch notice once a matching pin was actually
            // removed; a host parameter that matches nothing (stale link,
            // tampered value) must leave the notice in place instead of
            // silently dismissing a real mismatch.
            if ($pinRemoved) {
                delete_transient(ThemeContext::prefix() . '_' . self::HOST_KEY_MISMATCH_TRANSIENT_SUFFIX);
            }

            wp_safe_redirect(remove_query_arg(['member_sftp_retrust', '_wpnonce', 'host']));
            exit;
        });

        add_action('admin_notices', [$this, 'displayHostKeyMismatchNotice']);
    }

    /**
     * Shows an admin notice with a re-trust link when SftpClient reported a
     * host key mismatch. manage_options only, mirroring the capability the
     * re-trust action itself requires.
     */
    public function displayHostKeyMismatchNotice(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $hostPort = get_transient(ThemeContext::prefix() . '_' . self::HOST_KEY_MISMATCH_TRANSIENT_SUFFIX);
        if (!is_string($hostPort) || $hostPort === '') {
            return;
        }

        $retrustUrl = wp_nonce_url(
            add_query_arg(['member_sftp_retrust' => '1', 'host' => $hostPort]),
            'member_sftp_retrust',
        );

        printf(
            '<div class="notice notice-error"><p>%s <a href="%s">%s</a></p></div>',
            sprintf(
                // translators: %s is the "host:port" of the SFTP server whose key changed.
                esc_html__('SSH-Host-Key von %s hat sich geändert. Wenn der Wechsel erwartet war (Serverumzug), Host-Key neu vertrauen.', 'wp-starter'),
                esc_html($hostPort),
            ),
            esc_url($retrustUrl),
            esc_html__('Host-Key neu vertrauen', 'wp-starter'),
        );
    }

    private function enqueueAssets(): void
    {
        add_action('wp_enqueue_scripts', function (): void {
            if (!$this->shouldEnqueueAssets()) {
                return;
            }

            // Member-area pages must not be served from a page cache:
            // cached HTML would carry stale nonces and break login after the next tick.
            if (!defined('DONOTCACHEPAGE')) {
                define('DONOTCACHEPAGE', true);
            }
            nocache_headers();

            // Nonces are intentionally omitted here — they are fetched on-demand
            // via the member_get_nonces AJAX endpoint to avoid stale-cache issues.
            wp_localize_script('app-js', 'memberAreaConfig', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'authMode' => Auth::getAuthMode(),
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
