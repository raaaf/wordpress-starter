<?php

declare(strict_types=1);

namespace WordpressStarter\PostTypes;

use WordpressStarter\Acf\FieldDefinitions;
use WordpressStarter\Security;

class MemberDownload extends AbstractPostType
{
    protected static string $postType = 'member_download';

    protected static string $singular = 'Dokument';

    protected static string $genus = 'n';

    protected static string $plural = 'Dokumente';

    protected static string $menuIcon = 'dashicons-download';

    protected static int $menuPosition = 30;

    protected static bool $public = false;

    protected static bool $showInRest = false;

    /**
     * Entries can hold plaintext SFTP host/username and an encrypted
     * password ciphertext. capability_type 'post' alone maps edit_post to
     * the primitive edit_posts, letting any Contributor/Author rewrite
     * SFTP credentials. Restrict every capability to manage_options.
     */
    protected static ?string $requiredCapability = 'manage_options';

    protected static bool $hasArchive = false;

    protected static array|false $rewrite = false;

    protected static array $taxonomies = ['download_category'];

    /** @var array<string> */
    protected static array $supports = ['title'];

    /**
     * WordPress admin color palette values, reused across sync status indicators
     * in renderSyncColumn(), renderSyncNoticeMetaBox() and the inline JS in enqueueSyncScript().
     * SYNCED and PENDING are darker admin shades that reach 4.5:1 as text on white.
     */
    private const COLOR_SYNCED = '#007017';

    private const COLOR_PENDING = '#996800';

    private const COLOR_ERROR = '#d63638';

    private const COLOR_INFO = '#2271b1';

    public static function register(): void
    {
        parent::register();
        static::registerAdminColumns();
    }

    public static function registerAdminHooks(): void
    {
        add_action('add_meta_boxes_' . static::$postType, [static::class, 'addSyncNoticeMetaBox']);
        add_action('admin_footer-post.php', [static::class, 'enqueueSyncScript']);
        add_action('admin_footer-post-new.php', [static::class, 'enqueueSyncScript']);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function adminColumns(): array
    {
        return [
            'sftp_sync' => [
                'label' => __('Sync', 'wp-starter'),
                'before' => 'date',
                'render' => [static::class, 'renderSyncColumn'],
            ],
        ];
    }

    public static function renderSyncColumn(int $postId): void
    {
        if (get_post_meta($postId, 'download_source_type', true) !== 'sftp') {
            echo '–';

            return;
        }

        $sftpSource = get_post_meta($postId, 'download_sftp_source', true);
        if (!empty($sftpSource)) {
            // Child entry
            echo '<span style="color:' . esc_attr(self::COLOR_INFO) . '">&#8618; ' . esc_html($sftpSource) . '</span>';

            return;
        }

        // Parent entry
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- syncStatusLabel() returns escaped markup
        echo '<span style="color:' . esc_attr(self::syncStatusColor($postId)) . '">' . self::syncStatusLabel($postId) . '</span>';
    }

    /**
     * True for an SFTP entry that owns a folder (not one of its imported child entries).
     */
    private static function isSftpParent(int $postId): bool
    {
        return get_post_meta($postId, 'download_source_type', true) === 'sftp'
            && empty(get_post_meta($postId, 'download_sftp_source', true));
    }

    private static function isSynced(int $postId): bool
    {
        return get_post_meta($postId, '_sftp_synced', true) === '1';
    }

    private static function syncStatusColor(int $postId): string
    {
        return self::isSynced($postId) ? self::COLOR_SYNCED : self::COLOR_PENDING;
    }

    /**
     * Escaped status label with icon, so the state never relies on colour alone.
     */
    private static function syncStatusLabel(int $postId): string
    {
        return self::isSynced($postId)
            ? '&#10003; ' . esc_html__('Synchronisiert', 'wp-starter')
            : '&#9733; ' . esc_html__('Ausstehend', 'wp-starter');
    }

    public static function addSyncNoticeMetaBox(): void
    {
        global $post;
        if (!$post instanceof \WP_Post || !current_user_can('manage_options') || !self::isSftpParent($post->ID)) {
            return;
        }

        add_meta_box(
            'member_download_sync_notice',
            __('SFTP-Sync-Konfiguration', 'wp-starter'),
            [static::class, 'renderSyncNoticeMetaBox'],
            static::$postType,
            'side',
            'high',
        );
    }

    public static function renderSyncNoticeMetaBox(\WP_Post $post): void
    {
        $host = get_post_meta($post->ID, 'download_sftp_host', true) ?: '–';
        $path = get_post_meta($post->ID, 'download_sftp_path', true) ?: '–';

        $label = self::isSynced($post->ID)
            ? __('Dieses Dokument wurde bereits synchronisiert. Neue Dateien im konfigurierten Ordner werden einmal täglich automatisch importiert.', 'wp-starter')
            : __('Dieses Dokument wurde noch nicht synchronisiert. Dateien aus dem konfigurierten Ordner werden beim nächsten täglichen Abgleich importiert, oder sofort mit dem Button unten.', 'wp-starter');

        echo '<p style="margin-top:0;color:' . esc_attr(self::syncStatusColor($post->ID)) . ';font-weight:600;">'
            . self::syncStatusLabel($post->ID) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper
            . '</p>';
        echo '<p style="margin:0 0 8px;">' . esc_html($label) . '</p>';
        echo '<p style="margin:0 0 12px;font-size:12px;color:#646970;">'
            . esc_html__('Host:', 'wp-starter') . ' <code>' . esc_html($host) . '</code><br>'
            . esc_html__('Pfad:', 'wp-starter') . ' <code>' . esc_html($path) . '</code>'
            . '</p>';

        $nonce = wp_create_nonce('member_sync_now');
        echo '<button type="button" id="member-sync-now-btn" class="button button-secondary" style="width:100%;" '
            . 'data-nonce="' . esc_attr($nonce) . '" '
            . 'data-ajax-url="' . esc_attr(admin_url('admin-ajax.php')) . '">'
            . esc_html__('Jetzt synchronisieren', 'wp-starter')
            . '</button>';
        echo '<p id="member-sync-status" role="status" aria-live="polite" style="margin:8px 0 0;font-size:12px;"></p>';
    }

    public static function enqueueSyncScript(): void
    {
        global $post, $typenow;

        if ($typenow !== static::$postType || !$post instanceof \WP_Post || !current_user_can('manage_options') || !self::isSftpParent($post->ID)) {
            return;
        }

        $nonce = Security::getNonce();
        ?>
        <script nonce="<?php echo esc_attr($nonce); ?>">
        (function () {
            var btn = document.getElementById('member-sync-now-btn');
            var status = document.getElementById('member-sync-status');
            if (!btn) return;

            var idleLabel = '<?php echo esc_js(__('Jetzt synchronisieren', 'wp-starter')); ?>';

            function showStatus(color, message) {
                status.style.color = color;
                status.textContent = message;
            }

            function fail(message) {
                btn.removeAttribute('aria-disabled');
                btn.textContent = idleLabel;
                showStatus('<?php echo esc_js(self::COLOR_ERROR); ?>', message);
                btn.focus();
            }

            btn.addEventListener('click', function () {
                if (btn.getAttribute('aria-disabled') === 'true') return;

                // aria-disabled instead of disabled keeps keyboard focus on the button.
                btn.setAttribute('aria-disabled', 'true');
                btn.textContent = '<?php echo esc_js(__('Synchronisiere…', 'wp-starter')); ?>';
                showStatus('', '');

                var body = new FormData();
                body.append('action', 'member_sync_now');
                body.append('nonce', btn.dataset.nonce);

                fetch(btn.dataset.ajaxUrl, { method: 'POST', body: body })
                    .then(function (r) { return r.json().catch(function () { return null; }); })
                    .then(function (data) {
                        var message = data && data.data && data.data.message;
                        if (data && data.success) {
                            btn.textContent = '<?php echo esc_js(__('Fertig – Seite wird neu geladen…', 'wp-starter')); ?>';
                            showStatus('<?php echo esc_js(self::COLOR_SYNCED); ?>', message || '');
                            setTimeout(function () { window.location.reload(); }, 1500);
                        } else if (data) {
                            fail(message || '<?php echo esc_js(__('Die Synchronisierung ist fehlgeschlagen. Prüfe Host, Benutzername, Passwort und Pfad und versuche es erneut.', 'wp-starter')); ?>');
                        } else {
                            fail('<?php echo esc_js(__('Der Server hat die Synchronisierung nicht abgeschlossen. Versuche es erneut. Bleibt der Fehler, wende dich an den Administrator.', 'wp-starter')); ?>');
                        }
                    })
                    .catch(function () {
                        fail('<?php echo esc_js(__('Keine Verbindung zum Server. Prüfe deine Internetverbindung und versuche es erneut.', 'wp-starter')); ?>');
                    });
            });
        }());
        </script>
        <?php
    }

    public static function registerFields(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        $sftpCondition = [
            [
                [
                    'field' => 'field_mdl_source_type',
                    'operator' => '==',
                    'value' => 'sftp',
                ],
            ],
        ];

        acf_add_local_field_group([
            'key' => 'group_member_download_fields',
            'title' => __('Dokument', 'wp-starter'),
            'fields' => [
                FieldDefinitions::radioField(
                    'field_mdl_source_type',
                    __('Quelle', 'wp-starter'),
                    'download_source_type',
                    [
                        'upload' => __('Hochgeladene Datei', 'wp-starter'),
                        'external' => __('Externe URL', 'wp-starter'),
                        'sftp' => __('SFTP-Ordner', 'wp-starter'),
                    ],
                    'upload',
                    'horizontal',
                ),
                FieldDefinitions::textareaField(
                    'field_mdl_description',
                    __('Beschreibung', 'wp-starter'),
                    'download_description',
                    2,
                ),
                // upload: WP media file
                FieldDefinitions::fileField(
                    'field_mdl_file',
                    __('Datei', 'wp-starter'),
                    'download_file',
                    false,
                    'pdf,doc,docx,xls,xlsx,zip',
                    'array',
                    [
                        [
                            [
                                'field' => 'field_mdl_source_type',
                                'operator' => '==',
                                'value' => 'upload',
                            ],
                        ],
                    ],
                    __('Erlaubt: PDF, Word, Excel, ZIP', 'wp-starter'),
                ),
                // external: direct URL
                FieldDefinitions::textField(
                    'field_mdl_external_url',
                    __('Externe URL', 'wp-starter'),
                    'download_external_url',
                    false,
                    __('Direkte URL zur Datei (muss https:// verwenden).', 'wp-starter'),
                    'https://example.com/dokument.pdf',
                    [
                        [
                            [
                                'field' => 'field_mdl_source_type',
                                'operator' => '==',
                                'value' => 'external',
                            ],
                        ],
                    ],
                ),
                // sftp: SFTP directory listing
                FieldDefinitions::textField(
                    'field_mdl_sftp_host',
                    __('SFTP-Host', 'wp-starter'),
                    'download_sftp_host',
                    false,
                    __('Hostname des SFTP-Servers, z.B. sftp.example.com oder storagebox.hetzner.de', 'wp-starter'),
                    'sftp.example.com',
                    $sftpCondition,
                ),
                FieldDefinitions::numberField(
                    'field_mdl_sftp_port',
                    __('SFTP-Port', 'wp-starter'),
                    'download_sftp_port',
                    22,
                    1,
                    65535,
                    1,
                    '',
                    __('Standard: 22', 'wp-starter'),
                    $sftpCondition,
                    ['width' => '25'],
                ),
                FieldDefinitions::textField(
                    'field_mdl_sftp_username',
                    __('SFTP-Benutzername', 'wp-starter'),
                    'download_sftp_username',
                    false,
                    __('Bei Hetzner Storage Box: dein Hauptbenutzer, z.B. u123456, oder ein Unterbenutzer.', 'wp-starter'),
                    'u123456',
                    $sftpCondition,
                    ['width' => '37'],
                ),
                FieldDefinitions::passwordField(
                    'field_mdl_sftp_password',
                    __('SFTP-Passwort', 'wp-starter'),
                    'download_sftp_password',
                    __('Wird verschlüsselt in der Datenbank gespeichert.', 'wp-starter'),
                    '',
                    $sftpCondition,
                    ['width' => '38'],
                ),
                FieldDefinitions::textField(
                    'field_mdl_sftp_path',
                    __('Remote-Pfad', 'wp-starter'),
                    'download_sftp_path',
                    false,
                    __('Absoluter Pfad auf dem Server, z.B. /dokumente/', 'wp-starter'),
                    '/dokumente/',
                    $sftpCondition,
                ),
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => static::$postType,
                    ],
                ],
            ],
        ]);

        // Sidebar: status fields shown on all member_download entries
        acf_add_local_field_group([
            'key' => 'group_member_download_status',
            'title' => __('Status', 'wp-starter'),
            'fields' => [
                FieldDefinitions::trueFalseField(
                    'field_mdl_available',
                    __('Verfügbar', 'wp-starter'),
                    'download_available',
                    true,
                ),
                FieldDefinitions::textField(
                    'field_mdl_last_modified',
                    __('Zuletzt geändert', 'wp-starter'),
                    'download_last_modified',
                    false,
                    '',
                    '',
                    null,
                    null,
                    true,
                ),
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => static::$postType,
                    ],
                ],
            ],
            'position' => 'side',
        ]);

        // Sidebar: SFTP import details, only shown on child entries (download_sftp_source is set)
        acf_add_local_field_group([
            'key' => 'group_member_download_sftp_info',
            'title' => __('SFTP-Import', 'wp-starter'),
            'fields' => [
                FieldDefinitions::textField(
                    'field_mdl_sftp_source',
                    __('SFTP-Quelle', 'wp-starter'),
                    'download_sftp_source',
                    false,
                    '',
                    '',
                    null,
                    null,
                    true,
                ),
                FieldDefinitions::textField(
                    'field_mdl_sftp_remote_file',
                    __('Remote-Datei', 'wp-starter'),
                    'download_sftp_remote_file',
                    false,
                    '',
                    '',
                    null,
                    null,
                    true,
                ),
                FieldDefinitions::textField(
                    'field_mdl_sftp_identifier',
                    __('SFTP-Kennung', 'wp-starter'),
                    'download_sftp_identifier',
                    false,
                    __('Interne Kennung, die diese Datei dem Ordner zuordnet. Nicht ändern.', 'wp-starter'),
                    '',
                    null,
                    null,
                    true,
                ),
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => static::$postType,
                    ],
                ],
            ],
            'position' => 'side',
        ]);

        // Remove the SFTP import meta box entirely on parent entries (download_sftp_source is empty)
        add_action('add_meta_boxes', static function (): void {
            global $post;
            if (!$post instanceof \WP_Post || $post->post_type !== static::$postType) {
                return;
            }

            $sftpSource = get_post_meta($post->ID, 'download_sftp_source', true);
            if (empty($sftpSource)) {
                remove_meta_box('acf-group_member_download_sftp_info', static::$postType, 'side');
            }
        }, 99);
    }
}
