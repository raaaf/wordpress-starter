<?php

declare(strict_types=1);

namespace WordpressStarter\PostTypes;

class MemberDownload extends AbstractPostType
{
    protected static string $postType        = 'member_download';
    protected static string $singular        = 'Dokument';
    protected static string $plural          = 'Dokumente';
    protected static string $menuIcon        = 'dashicons-download';
    protected static int $menuPosition    = 30;
    protected static bool $public          = false;
    protected static bool $showInRest      = false;
    protected static bool $hasArchive      = false;
    protected static array|false $rewrite    = false;
    protected static array $taxonomies      = ['download_category'];

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
        $isSynced   = get_post_meta($postId, '_sftp_synced', true) === '1';

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
            'high'
        );
    }

    public static function renderSyncNoticeMetaBox(\WP_Post $post): void
    {
        $isSynced = get_post_meta($post->ID, '_sftp_synced', true) === '1';
        $host     = get_post_meta($post->ID, 'download_sftp_host', true) ?: '—';
        $path     = get_post_meta($post->ID, 'download_sftp_path', true) ?: '—';

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

        acf_add_local_field_group([
            'key'    => 'group_member_download_fields',
            'title'  => __('Dokument', 'wp-starter'),
            'fields' => [
                [
                    'key'           => 'field_mdl_source_type',
                    'label'         => __('Quelle', 'wp-starter'),
                    'name'          => 'download_source_type',
                    'type'          => 'radio',
                    'choices'       => [
                        'upload'   => __('Hochgeladene Datei', 'wp-starter'),
                        'external' => __('Externe URL', 'wp-starter'),
                        'sftp'     => __('SFTP-Ordner', 'wp-starter'),
                    ],
                    'default_value' => 'upload',
                    'layout'        => 'horizontal',
                ],
                [
                    'key'   => 'field_mdl_description',
                    'label' => __('Beschreibung', 'wp-starter'),
                    'name'  => 'download_description',
                    'type'  => 'textarea',
                    'rows'  => 2,
                ],
                // upload: WP media file
                [
                    'key'               => 'field_mdl_file',
                    'label'             => __('Datei', 'wp-starter'),
                    'name'              => 'download_file',
                    'type'              => 'file',
                    'return_format'     => 'array',
                    'library'           => 'all',
                    'mime_types'        => 'pdf,doc,docx,xls,xlsx,zip',
                    'instructions'      => __('Erlaubt: PDF, Word, Excel, ZIP', 'wp-starter'),
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_mdl_source_type',
                                'operator' => '==',
                                'value'    => 'upload',
                            ],
                        ],
                    ],
                ],
                // external: direct URL
                [
                    'key'               => 'field_mdl_external_url',
                    'label'             => __('Externe URL', 'wp-starter'),
                    'name'              => 'download_external_url',
                    'type'              => 'text',
                    'instructions'      => __('Direkte URL zur Datei (muss https:// verwenden).', 'wp-starter'),
                    'placeholder'       => 'https://example.com/dokument.pdf',
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_mdl_source_type',
                                'operator' => '==',
                                'value'    => 'external',
                            ],
                        ],
                    ],
                ],
                // sftp: SFTP directory listing
                [
                    'key'               => 'field_mdl_sftp_host',
                    'label'             => __('SFTP-Host', 'wp-starter'),
                    'name'              => 'download_sftp_host',
                    'type'              => 'text',
                    'instructions'      => __('Hostname des SFTP-Servers, z.B. sftp.example.com oder storagebox.hetzner.de', 'wp-starter'),
                    'placeholder'       => 'sftp.example.com',
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_mdl_source_type',
                                'operator' => '==',
                                'value'    => 'sftp',
                            ],
                        ],
                    ],
                ],
                [
                    'key'               => 'field_mdl_sftp_port',
                    'label'             => __('SFTP-Port', 'wp-starter'),
                    'name'              => 'download_sftp_port',
                    'type'              => 'number',
                    'instructions'      => __('Standard: 22', 'wp-starter'),
                    'default_value'     => 22,
                    'min'               => 1,
                    'max'               => 65535,
                    'wrapper'           => ['width' => '25'],
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_mdl_source_type',
                                'operator' => '==',
                                'value'    => 'sftp',
                            ],
                        ],
                    ],
                ],
                [
                    'key'               => 'field_mdl_sftp_username',
                    'label'             => __('SFTP-Benutzername', 'wp-starter'),
                    'name'              => 'download_sftp_username',
                    'type'              => 'text',
                    'instructions'      => __('Bei Hetzner Storage Box: dein Hauptbenutzer, z.B. u123456, oder ein Unterbenutzer.', 'wp-starter'),
                    'placeholder'       => 'u123456',
                    'wrapper'           => ['width' => '37'],
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_mdl_source_type',
                                'operator' => '==',
                                'value'    => 'sftp',
                            ],
                        ],
                    ],
                ],
                [
                    'key'               => 'field_mdl_sftp_password',
                    'label'             => __('SFTP-Passwort', 'wp-starter'),
                    'name'              => 'download_sftp_password',
                    'type'              => 'password',
                    'instructions'      => __('Wird im Klartext in der Datenbank gespeichert.', 'wp-starter'),
                    'wrapper'           => ['width' => '38'],
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_mdl_source_type',
                                'operator' => '==',
                                'value'    => 'sftp',
                            ],
                        ],
                    ],
                ],
                [
                    'key'               => 'field_mdl_sftp_path',
                    'label'             => __('Remote-Pfad', 'wp-starter'),
                    'name'              => 'download_sftp_path',
                    'type'              => 'text',
                    'instructions'      => __('Absoluter Pfad auf dem Server, z.B. /dokumente/', 'wp-starter'),
                    'placeholder'       => '/dokumente/',
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_mdl_source_type',
                                'operator' => '==',
                                'value'    => 'sftp',
                            ],
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'member_download',
                    ],
                ],
            ],
        ]);

        // Sidebar: status fields shown on all member_download entries
        acf_add_local_field_group([
            'key'      => 'group_member_download_status',
            'title'    => __('Status', 'wp-starter'),
            'fields'   => [
                [
                    'key'           => 'field_mdl_available',
                    'label'         => __('Verfügbar', 'wp-starter'),
                    'name'          => 'download_available',
                    'type'          => 'true_false',
                    'default_value' => 1,
                    'ui'            => 1,
                ],
                [
                    'key'      => 'field_mdl_last_modified',
                    'label'    => __('Zuletzt geändert', 'wp-starter'),
                    'name'     => 'download_last_modified',
                    'type'     => 'text',
                    'readonly' => 1,
                ],
            ],
            'location' => [
                [
                    [
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'member_download',
                    ],
                ],
            ],
            'position' => 'side',
        ]);

        // Sidebar: SFTP import details — only shown on child entries (download_sftp_source is set)
        acf_add_local_field_group([
            'key'      => 'group_member_download_sftp_info',
            'title'    => __('SFTP-Import', 'wp-starter'),
            'fields'   => [
                [
                    'key'      => 'field_mdl_sftp_source',
                    'label'    => __('SFTP-Quelle', 'wp-starter'),
                    'name'     => 'download_sftp_source',
                    'type'     => 'text',
                    'readonly' => 1,
                ],
                [
                    'key'      => 'field_mdl_sftp_remote_file',
                    'label'    => __('Remote-Datei', 'wp-starter'),
                    'name'     => 'download_sftp_remote_file',
                    'type'     => 'text',
                    'readonly' => 1,
                ],
                [
                    'key'      => 'field_mdl_sftp_identifier',
                    'label'    => __('SFTP-Identifier', 'wp-starter'),
                    'name'     => 'download_sftp_identifier',
                    'type'     => 'text',
                    'readonly' => 1,
                ],
            ],
            'location' => [
                [
                    [
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'member_download',
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
