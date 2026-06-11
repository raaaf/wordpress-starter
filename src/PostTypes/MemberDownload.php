<?php

declare(strict_types=1);

namespace WordpressStarter\PostTypes;

use WordpressStarter\Acf\FieldDefinitions;

class MemberDownload extends AbstractPostType
{
    protected static string $postType = 'member_download';

    protected static string $singular = 'Dokument';

    protected static string $plural = 'Dokumente';

    protected static string $menuIcon = 'dashicons-download';

    protected static int $menuPosition = 30;

    protected static bool $public = false;

    protected static bool $showInRest = false;

    protected static bool $hasArchive = false;

    protected static array|false $rewrite = false;

    protected static array $taxonomies = ['download_category'];

    /** @var array<string> */
    protected static array $supports = ['title'];

    public static function registerAdminHooks(): void
    {
        add_filter('manage_member_download_posts_columns', [static::class, 'addSyncColumn']);
        add_action('manage_member_download_posts_custom_column', [static::class, 'renderSyncColumn'], 10, 2);
        add_action('add_meta_boxes_member_download', [static::class, 'addSyncNoticeMetaBox']);
        add_action('admin_footer-post.php', [static::class, 'enqueueSyncScript']);
        add_action('admin_footer-post-new.php', [static::class, 'enqueueSyncScript']);
    }

    /**
     * @param array<string, string> $columns
     *
     * @return array<string, string>
     */
    public static function addSyncColumn(array $columns): array
    {
        $columns['sftp_sync'] = __('Sync', 'wp-starter');

        return $columns;
    }

    public static function renderSyncColumn(string $column, int $postId): void
    {
        if ($column !== 'sftp_sync') {
            return;
        }

        $sourceType = get_post_meta($postId, 'download_source_type', true);
        if ($sourceType !== 'sftp') {
            echo '—';

            return;
        }

        $sftpSource = get_post_meta($postId, 'download_sftp_source', true);
        $isSynced = get_post_meta($postId, '_sftp_synced', true) === '1';

        if (!empty($sftpSource)) {
            // Child entry
            echo '<span style="color:#2271b1;">&#8618; ' . esc_html($sftpSource) . '</span>';

            return;
        }

        // Parent entry
        if ($isSynced) {
            echo '<span style="color:#00a32a;">&#10003; ' . esc_html__('Synchronisiert', 'wp-starter') . '</span>';
        } else {
            echo '<span style="color:#dba617;">&#9733; ' . esc_html__('Ausstehend', 'wp-starter') . '</span>';
        }
    }

    public static function addSyncNoticeMetaBox(): void
    {
        global $post;
        if (!$post instanceof \WP_Post) {
            return;
        }

        $sftpSource = get_post_meta($post->ID, 'download_sftp_source', true);
        $sourceType = get_post_meta($post->ID, 'download_source_type', true);

        if ($sourceType !== 'sftp' || !empty($sftpSource)) {
            return;
        }

        add_meta_box(
            'member_download_sync_notice',
            __('SFTP-Sync-Konfiguration', 'wp-starter'),
            [static::class, 'renderSyncNoticeMetaBox'],
            'member_download',
            'side',
            'high',
        );
    }

    public static function renderSyncNoticeMetaBox(\WP_Post $post): void
    {
        $isSynced = get_post_meta($post->ID, '_sftp_synced', true) === '1';
        $host = get_post_meta($post->ID, 'download_sftp_host', true) ?: '—';
        $path = get_post_meta($post->ID, 'download_sftp_path', true) ?: '—';

        $color = $isSynced ? '#00a32a' : '#dba617';
        $label = $isSynced
            ? __('Dieser Eintrag wurde bereits synchronisiert. Neue Dateien im konfigurierten Ordner werden beim nächsten Cronjob-Lauf automatisch importiert.', 'wp-starter')
            : __('Dieser Eintrag wurde noch nicht synchronisiert. Beim nächsten Cronjob-Lauf werden Dateien aus dem konfigurierten Ordner importiert.', 'wp-starter');

        echo '<p style="margin-top:0;color:' . esc_attr($color) . ';font-weight:600;">'
            . ( $isSynced ? '&#10003; ' . esc_html__('Synchronisiert', 'wp-starter') : '&#9733; ' . esc_html__('Ausstehend', 'wp-starter') )
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
        echo '<p id="member-sync-status" style="margin:8px 0 0;font-size:12px;display:none;"></p>';
    }

    public static function enqueueSyncScript(): void
    {
        global $post, $typenow;

        if ($typenow !== 'member_download' || !$post instanceof \WP_Post) {
            return;
        }

        $sftpSource = get_post_meta($post->ID, 'download_sftp_source', true);
        $sourceType = get_post_meta($post->ID, 'download_source_type', true);

        if ($sourceType !== 'sftp' || !empty($sftpSource)) {
            return;
        }

        ?>
        <script>
        (function () {
            var btn = document.getElementById('member-sync-now-btn');
            var status = document.getElementById('member-sync-status');
            if (!btn) return;

            btn.addEventListener('click', function () {
                btn.disabled = true;
                btn.textContent = '<?php echo esc_js(__('Synchronisiere…', 'wp-starter')); ?>';
                status.style.display = 'none';

                var body = new FormData();
                body.append('action', 'member_sync_now');
                body.append('nonce', btn.dataset.nonce);

                fetch(btn.dataset.ajaxUrl, { method: 'POST', body: body })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            btn.textContent = '<?php echo esc_js(__('Fertig — Seite wird neu geladen…', 'wp-starter')); ?>';
                            status.style.color = '#00a32a';
                            status.style.display = 'block';
                            status.textContent = data.data.message;
                            setTimeout(function () { window.location.reload(); }, 1500);
                        } else {
                            btn.disabled = false;
                            btn.textContent = '<?php echo esc_js(__('Jetzt synchronisieren', 'wp-starter')); ?>';
                            status.style.color = '#d63638';
                            status.style.display = 'block';
                            status.textContent = data.data.message || '<?php echo esc_js(__('Fehler beim Synchronisieren.', 'wp-starter')); ?>';
                        }
                    })
                    .catch(function () {
                        btn.disabled = false;
                        btn.textContent = '<?php echo esc_js(__('Jetzt synchronisieren', 'wp-starter')); ?>';
                        status.style.color = '#d63638';
                        status.style.display = 'block';
                        status.textContent = '<?php echo esc_js(__('Verbindungsfehler.', 'wp-starter')); ?>';
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
                    __('Wird im Klartext in der Datenbank gespeichert.', 'wp-starter'),
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
                        'value' => 'member_download',
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
                        'value' => 'member_download',
                    ],
                ],
            ],
            'position' => 'side',
        ]);

        // Sidebar: SFTP import details — only shown on child entries (download_sftp_source is set)
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
                    __('SFTP-Identifier', 'wp-starter'),
                    'download_sftp_identifier',
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
                        'value' => 'member_download',
                    ],
                ],
            ],
            'position' => 'side',
        ]);

        // Remove the SFTP import meta box entirely on parent entries (download_sftp_source is empty)
        add_action('add_meta_boxes', static function (): void {
            global $post;
            if (!$post instanceof \WP_Post || $post->post_type !== 'member_download') {
                return;
            }

            $sftpSource = get_post_meta($post->ID, 'download_sftp_source', true);
            if (empty($sftpSource)) {
                remove_meta_box('acf-group_member_download_sftp_info', 'member_download', 'side');
            }
        }, 99);
    }
}
