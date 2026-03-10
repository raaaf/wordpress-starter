<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\ColorPaletteGenerator;
use WordpressStarter\DesignTokenValidator;
use WordpressStarter\RateLimiter;

/**
 * Design Token Service Provider
 *
 * Handles design token management in the WordPress admin:
 * - Download token files
 * - Upload and validate token files
 * - Sync color picker values to token JSON
 * - Trigger CSS regeneration via Node.js
 * - Backup management
 */
class DesignTokenServiceProvider extends ServiceProvider
{
    private const TOKENS_DIR = 'config/design-tokens';
    private const BACKUP_DIR = 'config/design-tokens/backups';
    private const NONCE_DOWNLOAD = 'wp-starter-download-tokens';
    private const NONCE_UPLOAD = 'wp-starter-upload-tokens';
    private const NONCE_REGENERATE = 'wp-starter-regenerate-tokens';
    private const NONCE_APPLY_COLORS = 'wp-starter-apply-colors';
    private const NONCE_RESTORE = 'wp-starter-restore-tokens';

    private const TOKEN_TYPES = ['primitives', 'light', 'dark'];

    /**
     * Color definitions for palette colors (generate full 50-900 shades)
     */
    private const PALETTE_COLORS = [
        'accent' => 'field_tokens_accent_color',
        'primary' => 'field_tokens_primary_color',
        'secondary' => 'field_tokens_secondary_color',
        'gray' => 'field_tokens_gray_color',
    ];

    /**
     * Color definitions for status colors (single color with light/dark variants)
     */
    private const STATUS_COLORS = [
        'success' => 'field_tokens_success_color',
        'warning' => 'field_tokens_warning_color',
        'error' => 'field_tokens_error_color',
    ];

    public function register(): void
    {
        // Handle non-AJAX token actions (downloads, regenerate)
        add_action('admin_init', [$this, 'handleTokenActions'], 1);

        // AJAX handlers for token upload and restore
        add_action('wp_ajax_wp_starter_upload_tokens', [$this, 'ajaxUploadTokens']);
        add_action('wp_ajax_wp_starter_restore_backup', [$this, 'ajaxRestoreBackup']);
    }

    public function boot(): void
    {
        // Add hook to sync colors when ACF options are saved
        add_action('acf/save_post', [$this, 'maybeSyncColorsOnSave'], 20);

        // Prefill color pickers with current token values (palette colors)
        foreach (self::PALETTE_COLORS as $colorName => $fieldKey) {
            add_filter("acf/load_field/key={$fieldKey}", function (array $field) use ($colorName) {
                return $this->prefillPaletteColor($field, $colorName);
            });
        }

        // Prefill color pickers with current token values (status colors)
        foreach (self::STATUS_COLORS as $colorName => $fieldKey) {
            add_filter("acf/load_field/key={$fieldKey}", function (array $field) use ($colorName) {
                return $this->prefillStatusColor($field, $colorName);
            });
        }

        // Add scripts and styles on tokens page
        add_action('admin_footer', [$this, 'addTokensPageScript']);

        // Display stored token notices
        add_action('admin_notices', [$this, 'displayStoredNotices']);
    }

    /**
     * Display stored admin notices from transients
     */
    public function displayStoredNotices(): void
    {
        $notice = get_transient('wp_starter_token_notice');

        if (!$notice || !is_array($notice)) {
            return;
        }

        // Delete transient immediately to prevent duplicate display
        delete_transient('wp_starter_token_notice');

        $type = isset($notice['type']) ? sanitize_key($notice['type']) : 'info';
        $message = isset($notice['message']) ? $notice['message'] : '';

        if (empty($message)) {
            return;
        }

        printf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            esc_attr($type),
            wp_kses_post(nl2br($message))
        );
    }

    /**
     * Add scripts and styles for tokens options page
     *
     * Uses AJAX for uploads and restores to avoid nested form issues with ACF.
     */
    public function addTokensPageScript(): void
    {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'theme-options-tokens') === false) {
            return;
        }

        // Get backup sets for restore dropdown
        $backupSets = self::getAvailableBackupSets();

        ?>
        <style>
            .wp-starter-loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.8);
                z-index: 999999;
                display: none;
                align-items: center;
                justify-content: center;
                flex-direction: column;
                gap: 15px;
            }
            .wp-starter-loading-overlay.active {
                display: flex;
            }
            .wp-starter-spinner {
                width: 40px;
                height: 40px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #2271b1;
                border-radius: 50%;
                animation: wp-starter-spin 1s linear infinite;
            }
            @keyframes wp-starter-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .wp-starter-loading-text {
                font-size: 14px;
                color: #1d2327;
                font-weight: 500;
            }
            .wp-starter-loading-subtext {
                font-size: 12px;
                color: #646970;
            }
            .wp-starter-ajax-notice {
                padding: 10px 15px;
                margin: 10px 0;
                border-left: 4px solid;
                background: #fff;
            }
            .wp-starter-ajax-notice.success {
                border-color: #00a32a;
                background: #edfaef;
            }
            .wp-starter-ajax-notice.error {
                border-color: #d63638;
                background: #fcf0f1;
            }
        </style>
        <div class="wp-starter-loading-overlay" id="wpStarterLoading">
            <div class="wp-starter-spinner"></div>
            <div class="wp-starter-loading-text"><?php esc_html_e('Design Tokens werden aktualisiert...', 'wp-starter'); ?></div>
            <div class="wp-starter-loading-subtext"><?php esc_html_e('Build läuft, bitte warten', 'wp-starter'); ?></div>
        </div>
        <script>
            (function() {
                const overlay = document.getElementById('wpStarterLoading');
                const acfForm = document.getElementById('post');
                const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
                const uploadNonce = '<?php echo esc_js(wp_create_nonce(self::NONCE_UPLOAD)); ?>';
                const restoreNonce = '<?php echo esc_js(wp_create_nonce(self::NONCE_RESTORE)); ?>';

                // Show loading overlay for ACF form submissions
                if (acfForm && overlay) {
                    acfForm.addEventListener('submit', function() {
                        overlay.classList.add('active');
                    });
                }

                // Inject AJAX-based upload UI into upload section
                const uploadSection = document.querySelector('[data-key="field_tokens_upload_section"]');
                if (uploadSection) {
                    const messageDiv = uploadSection.querySelector('.acf-input');
                    if (messageDiv) {
                        const uploadUi = document.createElement('div');
                        uploadUi.id = 'wp-starter-upload-ui';
                        uploadUi.setAttribute('style', 'background: #f9f9f9; padding: 15px; border-radius: 4px;');

                        const uploadNotice = document.createElement('div');
                        uploadNotice.id = 'wp-starter-upload-notice';
                        uploadUi.appendChild(uploadNotice);

                        const grid = document.createElement('div');
                        grid.setAttribute('style', 'display: grid; gap: 15px; max-width: 500px;');

                        [
                            { labelText: '<?php echo esc_js(__('Primitives (Basis-Farben, Spacing, etc.)', 'wp-starter')); ?>', inputId: 'wp-starter-file-primitives' },
                            { labelText: '<?php echo esc_js(__('Light Mode (Semantische Tokens)', 'wp-starter')); ?>', inputId: 'wp-starter-file-light' },
                            { labelText: '<?php echo esc_js(__('Dark Mode (Semantische Tokens)', 'wp-starter')); ?>', inputId: 'wp-starter-file-dark' },
                        ].forEach(function(item) {
                            const row = document.createElement('div');
                            const lbl = document.createElement('label');
                            lbl.setAttribute('style', 'display: block; margin-bottom: 5px; font-weight: 500;');
                            lbl.textContent = item.labelText;
                            const inp = document.createElement('input');
                            inp.type = 'file';
                            inp.id = item.inputId;
                            inp.accept = '.json,application/json';
                            row.appendChild(lbl);
                            row.appendChild(inp);
                            grid.appendChild(row);
                        });
                        uploadUi.appendChild(grid);

                        const uploadBtnP = document.createElement('p');
                        uploadBtnP.setAttribute('style', 'margin-top: 15px;');
                        const uploadBtn = document.createElement('button');
                        uploadBtn.type = 'button';
                        uploadBtn.id = 'wp-starter-upload-btn';
                        uploadBtn.className = 'button button-primary';
                        uploadBtn.textContent = '<?php echo esc_js(__('Tokens hochladen und anwenden', 'wp-starter')); ?>';
                        uploadBtnP.appendChild(uploadBtn);
                        uploadUi.appendChild(uploadBtnP);

                        const descP = document.createElement('p');
                        descP.className = 'description';
                        descP.textContent = '<?php echo esc_js(__('Du kannst einzelne Dateien oder alle drei gleichzeitig hochladen. Ein Backup wird automatisch erstellt.', 'wp-starter')); ?>';
                        uploadUi.appendChild(descP);

                        messageDiv.appendChild(uploadUi);

                        // Handle upload button click
                        uploadBtn.addEventListener('click', async function() {
                            const primitives = document.getElementById('wp-starter-file-primitives').files[0];
                            const light = document.getElementById('wp-starter-file-light').files[0];
                            const dark = document.getElementById('wp-starter-file-dark').files[0];

                            if (!primitives && !light && !dark) {
                                showNotice('upload', 'error', '<?php echo esc_js(__('Bitte mindestens eine Datei auswählen.', 'wp-starter')); ?>');
                                return;
                            }

                            const formData = new FormData();
                            formData.append('action', 'wp_starter_upload_tokens');
                            formData.append('nonce', uploadNonce);
                            if (primitives) formData.append('token_primitives', primitives);
                            if (light) formData.append('token_light', light);
                            if (dark) formData.append('token_dark', dark);

                            overlay.classList.add('active');

                            try {
                                const response = await fetch(ajaxUrl, {
                                    method: 'POST',
                                    body: formData
                                });
                                const result = await response.json();

                                if (result.success) {
                                    showNotice('upload', 'success', result.data.message + ' <?php echo esc_js(__('Seite wird neu geladen...', 'wp-starter')); ?>');
                                    // Clear ACF dirty state to prevent "unsaved changes" warning
                                    if (typeof acf !== 'undefined' && acf.unload) {
                                        acf.unload.reset();
                                    }
                                    // Reload page after short delay to show updated color pickers
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1500);
                                } else {
                                    showNotice('upload', 'error', result.data.message || '<?php echo esc_js(__('Ein Fehler ist aufgetreten.', 'wp-starter')); ?>');
                                }
                            } catch (error) {
                                showNotice('upload', 'error', '<?php echo esc_js(__('Netzwerkfehler. Bitte erneut versuchen.', 'wp-starter')); ?>');
                            } finally {
                                overlay.classList.remove('active');
                            }
                        });
                    }
                }

                // Inject AJAX-based restore UI into restore section
                const restoreSection = document.querySelector('[data-key="field_tokens_restore_section"]');
                if (restoreSection) {
                    const messageDiv = restoreSection.querySelector('.acf-input');
                    if (messageDiv) {
                        const backupSets = <?php echo wp_json_encode($backupSets); ?>;

                        if (backupSets.length === 0) {
                            const emptyWrapper = document.createElement('div');
                            emptyWrapper.setAttribute('style', 'background: #f0f0f1; padding: 15px; border-radius: 4px;');
                            const emptyP = document.createElement('p');
                            emptyP.setAttribute('style', 'margin: 0; color: #666;');
                            emptyP.textContent = '<?php echo esc_js(__('Keine Backups vorhanden. Backups werden automatisch erstellt, wenn Tokens geändert werden.', 'wp-starter')); ?>';
                            emptyWrapper.appendChild(emptyP);
                            messageDiv.appendChild(emptyWrapper);
                        } else {
                            const restoreUi = document.createElement('div');
                            restoreUi.id = 'wp-starter-restore-ui';
                            restoreUi.setAttribute('style', 'background: #f9f9f9; padding: 15px; border-radius: 4px;');

                            const noticeContainer = document.createElement('div');
                            noticeContainer.id = 'wp-starter-restore-notice';
                            restoreUi.appendChild(noticeContainer);

                            const flexRow = document.createElement('div');
                            flexRow.setAttribute('style', 'display: flex; gap: 10px; align-items: end; flex-wrap: wrap;');

                            const selectWrapper = document.createElement('div');
                            selectWrapper.setAttribute('style', 'flex: 1; min-width: 250px;');
                            const selectLabel = document.createElement('label');
                            selectLabel.setAttribute('style', 'display: block; margin-bottom: 5px; font-weight: 500;');
                            selectLabel.textContent = '<?php echo esc_js(__('Backup-Zeitpunkt auswählen', 'wp-starter')); ?>';
                            const selectEl = document.createElement('select');
                            selectEl.id = 'wp-starter-backup-select';
                            selectEl.setAttribute('style', 'width: 100%;');
                            backupSets.forEach(function(b) {
                                const opt = document.createElement('option');
                                opt.value = b.timestamp;
                                opt.textContent = b.date + ' (' + b.types.join(', ') + ')';
                                selectEl.appendChild(opt);
                            });
                            selectWrapper.appendChild(selectLabel);
                            selectWrapper.appendChild(selectEl);

                            const btnWrapper = document.createElement('div');
                            const restoreBtn = document.createElement('button');
                            restoreBtn.type = 'button';
                            restoreBtn.id = 'wp-starter-restore-btn';
                            restoreBtn.className = 'button';
                            restoreBtn.textContent = '<?php echo esc_js(__('Wiederherstellen', 'wp-starter')); ?>';
                            btnWrapper.appendChild(restoreBtn);

                            flexRow.appendChild(selectWrapper);
                            flexRow.appendChild(btnWrapper);
                            restoreUi.appendChild(flexRow);

                            const descP = document.createElement('p');
                            descP.className = 'description';
                            descP.setAttribute('style', 'margin-top: 10px;');
                            descP.textContent = '<?php echo esc_js(__('Stellt alle Token-Dateien vom gewählten Zeitpunkt wieder her.', 'wp-starter')); ?>';
                            restoreUi.appendChild(descP);

                            messageDiv.appendChild(restoreUi);

                            // Handle restore button click
                            restoreBtn.addEventListener('click', async function() {
                                const timestamp = document.getElementById('wp-starter-backup-select').value;

                                if (!confirm('<?php echo esc_js(__('Backup wirklich wiederherstellen? Die aktuellen Token-Dateien werden überschrieben.', 'wp-starter')); ?>')) {
                                    return;
                                }

                                const formData = new FormData();
                                formData.append('action', 'wp_starter_restore_backup');
                                formData.append('nonce', restoreNonce);
                                formData.append('timestamp', timestamp);

                                overlay.classList.add('active');

                                try {
                                    const response = await fetch(ajaxUrl, {
                                        method: 'POST',
                                        body: formData
                                    });
                                    const result = await response.json();

                                    if (result.success) {
                                        showNotice('restore', 'success', result.data.message + ' <?php echo esc_js(__('Seite wird neu geladen...', 'wp-starter')); ?>');
                                        // Clear ACF dirty state to prevent "unsaved changes" warning
                                        if (typeof acf !== 'undefined' && acf.unload) {
                                            acf.unload.reset();
                                        }
                                        // Reload page after short delay to show updated color pickers
                                        setTimeout(() => {
                                            window.location.reload();
                                        }, 1500);
                                    } else {
                                        showNotice('restore', 'error', result.data.message || '<?php echo esc_js(__('Ein Fehler ist aufgetreten.', 'wp-starter')); ?>');
                                    }
                                } catch (error) {
                                    showNotice('restore', 'error', '<?php echo esc_js(__('Netzwerkfehler. Bitte erneut versuchen.', 'wp-starter')); ?>');
                                } finally {
                                    overlay.classList.remove('active');
                                }
                            });
                        }
                    }
                }

                function showNotice(section, type, message) {
                    const noticeDiv = document.getElementById(`wp-starter-${section}-notice`);
                    if (noticeDiv) {
                        noticeDiv.textContent = '';
                        const msgEl = document.createElement('div');
                        msgEl.className = 'wp-starter-ajax-notice ' + type;
                        msgEl.textContent = message;
                        noticeDiv.appendChild(msgEl);
                    }
                }

                async function updateBackupDropdown() {
                    // Reload page to update backup list (simpler than fetching via AJAX)
                    // The success message is already shown, user will see updated list after reload
                }
            })();
        </script>
        <?php
    }

    /**
     * Prefill palette color picker with current token value
     *
     * @param array<string, mixed> $field ACF field configuration
     * @param string $colorName Color name (accent, primary, secondary, gray)
     * @return array<string, mixed>
     */
    private function prefillPaletteColor(array $field, string $colorName): array
    {
        // Get option name from field key
        $optionName = 'options_token_' . $colorName . '_color';

        // Use get_option directly to avoid recursion (get_field triggers load_field filter)
        $savedValue = get_option($optionName);
        if (!$savedValue) {
            $currentColor = self::getCurrentTokenColor($colorName, '500');
            if ($currentColor) {
                $field['default_value'] = $currentColor;
            }
        }
        return $field;
    }

    /**
     * Prefill status color picker with current token value
     *
     * @param array<string, mixed> $field ACF field configuration
     * @param string $colorName Color name (success, warning, error)
     * @return array<string, mixed>
     */
    private function prefillStatusColor(array $field, string $colorName): array
    {
        // Get option name from field key
        $optionName = 'options_token_' . $colorName . '_color';

        // Use get_option directly to avoid recursion (get_field triggers load_field filter)
        $savedValue = get_option($optionName);
        if (!$savedValue) {
            $currentColor = self::getCurrentStatusColor($colorName);
            if ($currentColor) {
                $field['default_value'] = $currentColor;
            }
        }
        return $field;
    }

    /**
     * Handle all token-related admin actions
     */
    public function handleTokenActions(): void
    {
        $this->handleDownloadTokens();
        $this->handleUploadTokens();
        $this->handleRegenerateTokens();
        $this->handleApplyColors();
        $this->handleRestoreBackup();
    }

    /**
     * Handle token file download
     */
    private function handleDownloadTokens(): void
    {
        if (!isset($_GET['wp-starter-download-token'])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, self::NONCE_DOWNLOAD)) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'wp-starter'));
        }

        $type = isset($_GET['type']) ? sanitize_file_name(wp_unslash($_GET['type'])) : 'primitives';

        if (!in_array($type, self::TOKEN_TYPES, true)) {
            wp_die(esc_html__('Ungültiger Token-Typ.', 'wp-starter'));
        }

        $file = get_template_directory() . '/' . self::TOKENS_DIR . "/{$type}.tokens.json";

        if (!file_exists($file)) {
            wp_die(esc_html__('Datei nicht gefunden.', 'wp-starter'));
        }

        // Send file
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $type . '.tokens.json"');
        header('Content-Length: ' . filesize($file));
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
        readfile($file);
        exit;
    }

    /**
     * Handle token file upload
     */
    private function handleUploadTokens(): void
    {
        if (!isset($_POST['wp-starter-upload-tokens'])) {
            return;
        }

        // Set flag to prevent ACF color sync from overwriting uploaded files
        set_transient('wp_starter_tokens_just_uploaded', true, 60);

        $nonce = isset($_POST['wp-starter-upload-tokens-nonce']) ? sanitize_text_field(wp_unslash($_POST['wp-starter-upload-tokens-nonce'])) : '';

        if (!wp_verify_nonce($nonce, self::NONCE_UPLOAD)) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'wp-starter'));
        }

        // Rate limiting
        if (!RateLimiter::check('token_upload', 5, 60)) {
            $this->addAdminNotice('error', __('Zu viele Anfragen. Bitte warte eine Minute.', 'wp-starter'));
            return;
        }

        $validator = new DesignTokenValidator();
        $errors = [];
        $uploaded = [];

        // Process each token type
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File uploads handled via WordPress file functions
        $files = $_FILES;

        foreach (self::TOKEN_TYPES as $type) {
            $fileKey = "token_{$type}";

            // Check if file was uploaded
            if (!isset($files[$fileKey]['error']) || !isset($files[$fileKey]['tmp_name'])) {
                continue;
            }

            $fileError = (int) $files[$fileKey]['error'];
            $tmpName = $files[$fileKey]['tmp_name'];

            if ($fileError === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($fileError !== UPLOAD_ERR_OK) {
                $errors[] = sprintf(
                    /* translators: 1: token type, 2: error code */
                    __('Upload-Fehler für %1$s: Code %2$s', 'wp-starter'),
                    $type,
                    (string) $fileError
                );
                continue;
            }

            // Validate tmp_name is a valid uploaded file
            if (!is_uploaded_file($tmpName)) {
                continue;
            }

            // Check MIME type
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpName);

            if (!in_array($mimeType, ['application/json', 'text/plain'], true)) {
                $errors[] = sprintf(
                    /* translators: %s: token type */
                    __('Ungültiger Dateityp für %s', 'wp-starter'),
                    $type
                );
                continue;
            }

            // Read and validate JSON
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            $content = file_get_contents($tmpName);
            if ($content === false) {
                $errors[] = sprintf(
                    /* translators: %s: token type */
                    __('Konnte Datei %s nicht lesen', 'wp-starter'),
                    $type
                );
                continue;
            }

            $validation = $validator->validate($content, $type);

            if (!$validation['valid']) {
                foreach ($validation['errors'] as $error) {
                    $errors[] = "{$type}: {$error}";
                }
                continue;
            }

            $uploaded[$type] = $tmpName;
        }

        if (!empty($errors)) {
            $this->addAdminNotice(
                'error',
                __('Token-Upload fehlgeschlagen:', 'wp-starter') . '<ul><li>' . implode('</li><li>', array_map('esc_html', $errors)) . '</li></ul>'
            );
            return;
        }

        if (empty($uploaded)) {
            $this->addAdminNotice('warning', __('Keine Dateien zum Upload ausgewählt.', 'wp-starter'));
            return;
        }

        // Create backup before overwriting
        $this->createBackup();

        // Copy files to tokens directory
        $tokensDir = get_template_directory() . '/' . self::TOKENS_DIR;
        $copyErrors = [];

        foreach ($uploaded as $type => $tmpFile) {
            $targetFile = $tokensDir . "/{$type}.tokens.json";
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
            if (!copy($tmpFile, $targetFile)) {
                $copyErrors[] = sprintf(
                    /* translators: %s: token type */
                    __('Konnte %s nicht speichern', 'wp-starter'),
                    $type
                );
            }
        }

        if (!empty($copyErrors)) {
            $this->addAdminNotice('error', implode('<br>', array_map('esc_html', $copyErrors)));
            return;
        }

        // Run transform script
        $result = $this->runTokenTransform();

        // Sync ACF color picker values from uploaded tokens
        if ($result['success']) {
            $this->syncColorPickersFromTokens();
        }

        // Store notice as transient (survives redirect)
        $message = $result['message'];
        if (!$result['success'] && isset($result['details'])) {
            $message .= "\n" . $result['details'];
        }

        set_transient('wp_starter_token_notice', [
            'type' => $result['success'] ? 'success' : 'error',
            'message' => $message,
        ], 30);

        // Redirect to prevent form resubmission
        wp_safe_redirect(add_query_arg('tokens-updated', '1', sanitize_url(wp_get_referer()) ?: admin_url()));
        exit;
    }

    /**
     * Handle manual token regeneration
     */
    private function handleRegenerateTokens(): void
    {
        if (!isset($_GET['wp-starter-regenerate-tokens'])) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, self::NONCE_REGENERATE)) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'wp-starter'));
        }

        $result = $this->runTokenTransform();

        set_transient('wp_starter_token_notice', [
            'type' => $result['success'] ? 'success' : 'error',
            'message' => $result['message'],
        ], 30);

        wp_safe_redirect(sanitize_url(wp_get_referer()) ?: admin_url('admin.php?page=theme-options-tokens'));
        exit;
    }

    /**
     * Handle color picker apply action
     */
    private function handleApplyColors(): void
    {
        if (!isset($_POST['wp-starter-apply-colors'])) {
            return;
        }

        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, self::NONCE_APPLY_COLORS)) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'wp-starter'));
        }

        $result = $this->syncColorsToTokens();

        if ($result !== null) {
            set_transient('wp_starter_token_notice', [
                'type' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
            ], 30);
        }

        wp_safe_redirect(add_query_arg('colors-applied', '1', sanitize_url(wp_get_referer()) ?: admin_url()));
        exit;
    }

    /**
     * Handle backup restore action
     */
    private function handleRestoreBackup(): void
    {
        if (!isset($_POST['wp-starter-restore-backup'])) {
            return;
        }

        // Set flag to prevent ACF color sync from overwriting restored files
        set_transient('wp_starter_tokens_just_uploaded', true, 60);

        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, self::NONCE_RESTORE)) {
            wp_die(esc_html__('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'wp-starter'));
        }

        $timestamp = isset($_POST['backup_timestamp']) ? sanitize_text_field(wp_unslash($_POST['backup_timestamp'])) : '';

        if (empty($timestamp)) {
            $this->addAdminNotice('error', __('Kein Backup-Zeitpunkt ausgewählt.', 'wp-starter'));
            return;
        }

        // Validate timestamp format (YYYY-MM-DD_HH-MM-SS)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $timestamp)) {
            $this->addAdminNotice('error', __('Ungültiger Zeitstempel.', 'wp-starter'));
            return;
        }

        $backupDir = get_template_directory() . '/' . self::BACKUP_DIR;
        $tokensDir = get_template_directory() . '/' . self::TOKENS_DIR;
        $restored = [];
        $errors = [];

        foreach (self::TOKEN_TYPES as $type) {
            $backupFile = $backupDir . "/{$type}_{$timestamp}.tokens.json";
            $targetFile = $tokensDir . "/{$type}.tokens.json";

            if (!file_exists($backupFile)) {
                continue;
            }

            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
            if (copy($backupFile, $targetFile)) {
                $restored[] = $type;
            } else {
                $errors[] = sprintf(
                    /* translators: %s: token type */
                    __('Konnte %s nicht wiederherstellen', 'wp-starter'),
                    $type
                );
            }
        }

        if (empty($restored)) {
            $this->addAdminNotice('error', __('Keine Backup-Dateien für diesen Zeitpunkt gefunden.', 'wp-starter'));
            return;
        }

        // Regenerate CSS
        $result = $this->runTokenTransform();

        // Sync ACF color picker values from restored tokens
        if ($result['success']) {
            $this->syncColorPickersFromTokens();
        }

        // Build notice message
        $noticeMessage = '';
        $noticeType = 'success';

        if (!empty($errors)) {
            $noticeMessage = implode('<br>', array_map('esc_html', $errors)) . '<br><br>';
            $noticeType = 'warning';
        }

        if ($result['success']) {
            $noticeMessage .= sprintf(
                /* translators: %s: list of restored token types */
                __('Backup erfolgreich wiederhergestellt: %s', 'wp-starter'),
                implode(', ', $restored)
            );
        } else {
            $noticeMessage .= $result['message'];
            $noticeType = 'error';
        }

        set_transient('wp_starter_token_notice', [
            'type' => $noticeType,
            'message' => $noticeMessage,
        ], 30);

        wp_safe_redirect(add_query_arg('backup-restored', '1', sanitize_url(wp_get_referer()) ?: admin_url()));
        exit;
    }

    /**
     * AJAX handler for token upload
     *
     * Handles file uploads via AJAX to avoid nested form issues with ACF.
     */
    public function ajaxUploadTokens(): void
    {
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, self::NONCE_UPLOAD)) {
            wp_send_json_error(['message' => __('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter')]);
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'wp-starter')]);
        }

        // Rate limiting
        if (!RateLimiter::check('token_upload', 5, 60)) {
            wp_send_json_error(['message' => __('Zu viele Anfragen. Bitte warte eine Minute.', 'wp-starter')]);
        }

        $validator = new DesignTokenValidator();
        $errors = [];
        $uploaded = [];

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File uploads handled via WordPress file functions
        $files = $_FILES;

        foreach (self::TOKEN_TYPES as $type) {
            $fileKey = "token_{$type}";

            if (!isset($files[$fileKey]['error']) || !isset($files[$fileKey]['tmp_name'])) {
                continue;
            }

            $fileError = (int) $files[$fileKey]['error'];
            $tmpName = $files[$fileKey]['tmp_name'];

            if ($fileError === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($fileError !== UPLOAD_ERR_OK) {
                $errors[] = sprintf(
                    /* translators: 1: token type, 2: error code */
                    __('Upload-Fehler für %1$s: Code %2$s', 'wp-starter'),
                    $type,
                    (string) $fileError
                );
                continue;
            }

            if (!is_uploaded_file($tmpName)) {
                continue;
            }

            // Check MIME type
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpName);

            if (!in_array($mimeType, ['application/json', 'text/plain'], true)) {
                $errors[] = sprintf(
                    /* translators: %s: token type */
                    __('Ungültiger Dateityp für %s', 'wp-starter'),
                    $type
                );
                continue;
            }

            // Read and validate JSON
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            $content = file_get_contents($tmpName);
            if ($content === false) {
                $errors[] = sprintf(
                    /* translators: %s: token type */
                    __('Konnte Datei %s nicht lesen', 'wp-starter'),
                    $type
                );
                continue;
            }

            $validation = $validator->validate($content, $type);

            if (!$validation['valid']) {
                foreach ($validation['errors'] as $error) {
                    $errors[] = "{$type}: {$error}";
                }
                continue;
            }

            $uploaded[$type] = $tmpName;
        }

        if (!empty($errors)) {
            wp_send_json_error([
                'message' => __('Token-Upload fehlgeschlagen:', 'wp-starter') . ' ' . implode(', ', $errors),
            ]);
        }

        if (empty($uploaded)) {
            wp_send_json_error(['message' => __('Keine Dateien zum Upload ausgewählt.', 'wp-starter')]);
        }

        // Create backup before overwriting
        $this->createBackup();

        // Copy files to tokens directory
        $tokensDir = get_template_directory() . '/' . self::TOKENS_DIR;
        $copyErrors = [];

        foreach ($uploaded as $type => $tmpFile) {
            $targetFile = $tokensDir . "/{$type}.tokens.json";
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
            if (!copy($tmpFile, $targetFile)) {
                $copyErrors[] = sprintf(
                    /* translators: %s: token type */
                    __('Konnte %s nicht speichern', 'wp-starter'),
                    $type
                );
            }
        }

        if (!empty($copyErrors)) {
            wp_send_json_error(['message' => implode(', ', $copyErrors)]);
        }

        // Run transform script
        $result = $this->runTokenTransform();

        // Sync ACF color picker values from uploaded tokens
        if ($result['success']) {
            $this->syncColorPickersFromTokens();
        }

        if ($result['success']) {
            wp_send_json_success(['message' => $result['message']]);
        } else {
            $message = $result['message'];
            if (!empty($result['details'])) {
                $message .= ' ' . $result['details'];
            }
            wp_send_json_error(['message' => $message]);
        }
    }

    /**
     * AJAX handler for backup restore
     *
     * Handles restore via AJAX to avoid nested form issues with ACF.
     */
    public function ajaxRestoreBackup(): void
    {
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, self::NONCE_RESTORE)) {
            wp_send_json_error(['message' => __('Sicherheitsüberprüfung fehlgeschlagen.', 'wp-starter')]);
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'wp-starter')]);
        }

        $timestamp = isset($_POST['timestamp']) ? sanitize_text_field(wp_unslash($_POST['timestamp'])) : '';

        if (empty($timestamp)) {
            wp_send_json_error(['message' => __('Kein Backup-Zeitpunkt ausgewählt.', 'wp-starter')]);
        }

        // Validate timestamp format (YYYY-MM-DD_HH-MM-SS)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $timestamp)) {
            wp_send_json_error(['message' => __('Ungültiger Zeitstempel.', 'wp-starter')]);
        }

        $backupDir = get_template_directory() . '/' . self::BACKUP_DIR;
        $tokensDir = get_template_directory() . '/' . self::TOKENS_DIR;
        $restored = [];
        $errors = [];

        foreach (self::TOKEN_TYPES as $type) {
            $backupFile = $backupDir . "/{$type}_{$timestamp}.tokens.json";
            $targetFile = $tokensDir . "/{$type}.tokens.json";

            if (!file_exists($backupFile)) {
                continue;
            }

            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
            if (copy($backupFile, $targetFile)) {
                $restored[] = $type;
            } else {
                $errors[] = sprintf(
                    /* translators: %s: token type */
                    __('Konnte %s nicht wiederherstellen', 'wp-starter'),
                    $type
                );
            }
        }

        if (empty($restored)) {
            wp_send_json_error(['message' => __('Keine Backup-Dateien für diesen Zeitpunkt gefunden.', 'wp-starter')]);
        }

        // Regenerate CSS
        $result = $this->runTokenTransform();

        // Sync ACF color picker values from restored tokens
        if ($result['success']) {
            $this->syncColorPickersFromTokens();
        }

        if (!empty($errors)) {
            wp_send_json_error(['message' => implode(', ', $errors)]);
        }

        if ($result['success']) {
            wp_send_json_success([
                'message' => sprintf(
                    /* translators: %s: list of restored token types */
                    __('Backup erfolgreich wiederhergestellt: %s', 'wp-starter'),
                    implode(', ', $restored)
                ),
            ]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    /**
     * Maybe sync colors when ACF options page is saved
     *
     * @param int|string $postId The post ID being saved
     */
    public function maybeSyncColorsOnSave($postId): void
    {
        // Only trigger on options page save
        if ($postId !== 'options') {
            return;
        }

        // Skip if this is a token upload request (direct check)
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Just checking for presence
        if (isset($_POST['wp-starter-upload-tokens'])) {
            return;
        }

        // Skip if this is a backup restore request (direct check)
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Just checking for presence
        if (isset($_POST['wp-starter-restore-backup'])) {
            return;
        }

        // Skip if tokens were just uploaded - the upload form is nested inside the ACF form,
        // so ACF's save_post fires with old color values that would overwrite the uploaded files
        if (get_transient('wp_starter_tokens_just_uploaded')) {
            delete_transient('wp_starter_tokens_just_uploaded');
            return;
        }

        // Check if color fields were submitted (more reliable than checking screen)
        // ACF handles nonce verification and we only check array keys, values are handled by ACF
        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $acfFields = isset( $_POST['acf'] ) && is_array( wp_unslash( $_POST['acf'] ) ) ? wp_unslash( $_POST['acf'] ) : [];
        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        // Check if any of our color field keys are in the submitted data
        $hasColorField = false;

        foreach (self::PALETTE_COLORS as $fieldKey) {
            if (isset($acfFields[$fieldKey])) {
                $hasColorField = true;
                break;
            }
        }

        if (!$hasColorField) {
            foreach (self::STATUS_COLORS as $fieldKey) {
                if (isset($acfFields[$fieldKey])) {
                    $hasColorField = true;
                    break;
                }
            }
        }

        if (!$hasColorField) {
            return;
        }

        // Sync colors from ACF to token files and capture result
        $result = $this->syncColorsToTokens();

        // Store result as transient for display after redirect
        if ($result !== null) {
            set_transient('wp_starter_token_notice', [
                'type' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'] . ( ! empty( $result['details'] ) ? "\n" . $result['details'] : '' ),
            ], 30);
        }
    }

    /**
     * Sync color picker values to token JSON files
     *
     * @return array{success: bool, message: string, details?: string}|null Result or null if no colors to sync
     */
    private function syncColorsToTokens(): ?array
    {
        // Collect all palette colors from ACF
        $paletteColors = [];
        foreach (self::PALETTE_COLORS as $colorName => $fieldKey) {
            $optionName = 'token_' . $colorName . '_color';
            $color = get_field($optionName, 'option');
            if ($color) {
                $paletteColors[$colorName] = $color;
            }
        }

        // Collect all status colors from ACF
        $statusColors = [];
        foreach (self::STATUS_COLORS as $colorName => $fieldKey) {
            $optionName = 'token_' . $colorName . '_color';
            $color = get_field($optionName, 'option');
            if ($color) {
                $statusColors[$colorName] = $color;
            }
        }

        if (empty($paletteColors) && empty($statusColors)) {
            return null;
        }

        // Create backup
        $this->createBackup();

        $tokensDir = get_template_directory() . '/' . self::TOKENS_DIR;
        $primitivesFile = $tokensDir . '/primitives.tokens.json';

        if (!file_exists($primitivesFile)) {
            return [
                'success' => false,
                'message' => __('Token-Datei nicht gefunden.', 'wp-starter'),
            ];
        }

        // Read current primitives
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $content = file_get_contents($primitivesFile);
        if ($content === false) {
            return [
                'success' => false,
                'message' => __('Konnte Token-Datei nicht lesen.', 'wp-starter'),
            ];
        }

        $primitives = json_decode($content, true);
        if (!is_array($primitives)) {
            return [
                'success' => false,
                'message' => __('Ungültiges JSON in Token-Datei.', 'wp-starter'),
            ];
        }

        $generator = new ColorPaletteGenerator();

        // Update palette colors (full shade range 50-900)
        foreach ($paletteColors as $colorName => $color) {
            $palette = $generator->generate($color);
            $tokens = $generator->toFigmaTokenFormat($palette, $colorName);

            if (!isset($primitives['color'][$colorName])) {
                $primitives['color'][$colorName] = [];
            }

            foreach ($tokens as $shade => $token) {
                $primitives['color'][$colorName][$shade] = $token;
            }
        }

        // Update status colors (single color with light/dark variants)
        foreach ($statusColors as $colorName => $color) {
            $this->updateStatusColorInPrimitives($primitives, $colorName, $color, $generator);
        }

        // Save updated primitives
        $json = wp_json_encode($primitives, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($primitivesFile, $json);

        // Update semantic tokens (light.tokens.json and dark.tokens.json)
        // These files contain resolved values that need to match the updated primitives
        $this->updateSemanticTokens($tokensDir, $primitives, $generator, $paletteColors, $statusColors);

        // Run transform and build
        return $this->runTokenTransform();
    }

    /**
     * Update semantic tokens (light/dark) to match updated primitives
     *
     * @param string $tokensDir Path to tokens directory
     * @param array<string, mixed> $primitives Updated primitives
     * @param ColorPaletteGenerator $generator Color palette generator
     * @param array<string, string> $paletteColors Changed palette colors
     * @param array<string, string> $statusColors Changed status colors
     */
    private function updateSemanticTokens(
        string $tokensDir,
        array $primitives,
        ColorPaletteGenerator $generator,
        array $paletteColors,
        array $statusColors
    ): void {
        $modes = ['light', 'dark'];

        foreach ($modes as $mode) {
            $filePath = $tokensDir . "/{$mode}.tokens.json";
            if (!file_exists($filePath)) {
                continue;
            }

            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            $content = file_get_contents($filePath);
            if ($content === false) {
                continue;
            }

            $tokens = json_decode($content, true);
            if (!is_array($tokens)) {
                continue;
            }

            // Update tokens that reference changed primitives
            $updated = $this->updateSemanticReferences($tokens, $primitives, $paletteColors);
            $updated = $this->updateSemanticStatusColors($tokens, $primitives, $statusColors) || $updated;

            if ($updated) {
                $json = wp_json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
                file_put_contents($filePath, $json);
            }
        }
    }

    /**
     * Update semantic tokens that reference changed primitive palette colors
     *
     * @param array<string, mixed> &$tokens Semantic tokens (modified by reference)
     * @param array<string, mixed> $primitives Updated primitives
     * @param array<string, string> $paletteColors Changed palette colors
     * @return bool Whether any tokens were updated
     */
    private function updateSemanticReferences(array &$tokens, array $primitives, array $paletteColors): bool
    {
        $updated = false;

        // Recursively search for tokens with aliasData that references our changed colors
        $this->walkTokensWithAliases($tokens, function (array &$token) use ($primitives, $paletteColors, &$updated) {
            if (!isset($token['$extensions']['com.figma.aliasData']['targetVariableName'])) {
                return;
            }

            $targetName = $token['$extensions']['com.figma.aliasData']['targetVariableName'];

            // Extract color name and shade from the target variable name.
            if (preg_match('/^color\/(\w+)\/(\d+)$/', $targetName, $matches)) {
                $colorName = $matches[1];
                $shade = $matches[2];

                // Check if this color was changed
                if (!isset($paletteColors[$colorName])) {
                    return;
                }

                // Get the new value from updated primitives
                if (isset($primitives['color'][$colorName][$shade]['$value'])) {
                    $token['$value'] = $primitives['color'][$colorName][$shade]['$value'];
                    $updated = true;
                }
            }
        });

        return $updated;
    }

    /**
     * Update semantic tokens for status colors (success, warning, error)
     *
     * @param array<string, mixed> &$tokens Semantic tokens (modified by reference)
     * @param array<string, mixed> $primitives Updated primitives
     * @param array<string, string> $statusColors Changed status colors
     * @return bool Whether any tokens were updated
     */
    private function updateSemanticStatusColors(array &$tokens, array $primitives, array $statusColors): bool
    {
        $updated = false;

        // Map semantic token paths to primitive paths for status colors
        $statusMappings = [
            'success' => [
                'bg.success' => 'success-light',
                'bg.success-strong' => 'success',
                'text.success' => 'success-dark',
                'border.success' => 'success',
            ],
            'warning' => [
                'bg.warning' => 'warning-light',
                'bg.warning-strong' => 'warning',
                'text.warning' => 'warning-dark',
                'border.warning' => 'warning',
            ],
            'error' => [
                'bg.error' => 'error-light',
                'bg.error-strong' => 'error',
                'text.error' => 'error-dark',
                'border.error' => 'error',
            ],
        ];

        foreach ($statusColors as $colorName => $color) {
            if (!isset($statusMappings[$colorName])) {
                continue;
            }

            foreach ($statusMappings[$colorName] as $semanticPath => $primitiveName) {
                $parts = explode('.', $semanticPath);
                $category = $parts[0]; // bg, text, border
                $tokenName = $parts[1]; // success, warning, error, etc.

                if (!isset($tokens[$category][$tokenName]['$value'])) {
                    continue;
                }

                // Get value from primitives
                if (isset($primitives['color'][$primitiveName]['$value'])) {
                    $tokens[$category][$tokenName]['$value'] = $primitives['color'][$primitiveName]['$value'];
                    $updated = true;
                }
            }
        }

        return $updated;
    }

    /**
     * Walk through tokens recursively and call callback for each token with alias data
     *
     * @param array<string, mixed> &$tokens Tokens to walk
     * @param callable $callback Callback to call for each token
     */
    private function walkTokensWithAliases(array &$tokens, callable $callback): void
    {
        foreach ($tokens as $key => &$value) {
            if ($key === '$extensions' || $key === '$type' || $key === '$value') {
                continue;
            }

            if (is_array($value)) {
                if (isset($value['$type']) && isset($value['$value'])) {
                    // This is a token node
                    $callback($value);
                } else {
                    // Recurse into nested structure
                    $this->walkTokensWithAliases($value, $callback);
                }
            }
        }
    }

    /**
     * Update status color in primitives (success, warning, error with light/dark variants)
     *
     * @param array<string, mixed> &$primitives Primitives array (modified by reference)
     * @param string $colorName Color name (success, warning, error)
     * @param string $color Hex color value
     * @param ColorPaletteGenerator $generator Color palette generator
     */
    private function updateStatusColorInPrimitives(array &$primitives, string $colorName, string $color, ColorPaletteGenerator $generator): void
    {
        // Generate palette to get light (shade 100) and dark (shade 700) variants
        $palette = $generator->generate($color);

        // Main color
        $primitives['color'][$colorName] = $this->createColorToken($color);

        // Light variant (shade 100)
        if (isset($palette['100'])) {
            $primitives['color'][$colorName . '-light'] = $this->createColorToken($palette['100']);
        }

        // Dark variant (shade 700)
        if (isset($palette['700'])) {
            $primitives['color'][$colorName . '-dark'] = $this->createColorToken($palette['700']);
        }
    }

    /**
     * Create a color token in Figma format
     *
     * @param string $hex Hex color value
     * @return array<string, mixed> Token array
     */
    private function createColorToken(string $hex): array
    {
        // Normalize hex
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        // Convert to RGB components (0-1 range)
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        return [
            '$type' => 'color',
            '$value' => [
                'colorSpace' => 'srgb',
                'components' => [$r, $g, $b],
                'alpha' => 1,
                'hex' => '#' . strtoupper($hex),
            ],
        ];
    }

    /**
     * Create a backup of current token files
     */
    private function createBackup(): void
    {
        $tokensDir = get_template_directory() . '/' . self::TOKENS_DIR;
        $backupDir = get_template_directory() . '/' . self::BACKUP_DIR;

        // Create backup directory if needed
        if (!is_dir($backupDir)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
            mkdir($backupDir, 0755, true);
        }

        $timestamp = gmdate('Y-m-d_H-i-s');

        foreach (self::TOKEN_TYPES as $type) {
            $sourceFile = $tokensDir . "/{$type}.tokens.json";
            if (file_exists($sourceFile)) {
                $backupFile = $backupDir . "/{$type}_{$timestamp}.tokens.json";
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
                copy($sourceFile, $backupFile);
            }
        }

        // Clean old backups (keep last 3)
        $this->cleanOldBackups();
    }

    /**
     * Clean old backup files, keeping only the most recent
     */
    private function cleanOldBackups(): void
    {
        $backupDir = get_template_directory() . '/' . self::BACKUP_DIR;

        if (!is_dir($backupDir)) {
            return;
        }

        foreach (self::TOKEN_TYPES as $type) {
            $pattern = $backupDir . "/{$type}_*.tokens.json";
            $files = glob($pattern);

            if ($files && count($files) > 3) {
                // Sort by modification time (oldest first)
                usort($files, function ($a, $b) {
                    return filemtime($a) - filemtime($b);
                });

                // Delete oldest files
                $toDelete = array_slice($files, 0, count($files) - 3);
                foreach ($toDelete as $file) {
                    // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
                    unlink($file);
                }
            }
        }
    }

    /**
     * Run the Node.js token transform script and Vite build
     *
     * @return array{success: bool, message: string, details?: string, duration?: float}
     */
    private function runTokenTransform(): array
    {
        $startTime = microtime(true);
        $themeDir = get_template_directory();
        $nodeScript = $themeDir . '/scripts/transform-tokens.js';

        if (!file_exists($nodeScript)) {
            return [
                'success' => false,
                'message' => __('Transform-Script nicht gefunden.', 'wp-starter'),
            ];
        }

        $nodePath = $this->findNodePath();
        $npmPath = $this->findNpmPath();

        if (!$nodePath) {
            return [
                'success' => false,
                'message' => __('Node.js nicht gefunden. Bitte führe "npm run tokens && npm run build" manuell aus.', 'wp-starter'),
            ];
        }

        // Build PATH env array for subprocesses (Local by Flywheel may have limited PATH)
        $env = array_merge(getenv() ?: [], [
            'PATH' => implode(':', array_unique(array_filter([
                dirname($nodePath),
                $npmPath ? dirname($npmPath) : null,
                '/usr/local/bin',
                '/opt/homebrew/bin',
                '/usr/bin',
                '/bin',
            ]))),
        ]);

        // Step 1: Run token transform
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open
        $process = proc_open(
            [$nodePath, $nodeScript],
            $descriptors,
            $pipes,
            $themeDir,
            $env
        );

        $output = [];
        $returnVar = 1;
        if (is_resource($process)) {
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $returnVar = proc_close($process);
            if ($stdout) {
                $output = explode("\n", trim($stdout));
            }
        }

        if ($returnVar !== 0) {
            return [
                'success' => false,
                'message' => __('Fehler bei der Token-Transformation.', 'wp-starter'),
                'details' => implode("\n", $output),
            ];
        }

        // Step 2: Run Vite build
        if ($npmPath) {
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open
            $buildProcess = proc_open(
                [$npmPath, 'run', 'build'],
                $descriptors,
                $buildPipes,
                $themeDir,
                $env
            );

            $buildOutput = [];
            $buildReturnVar = 1;
            if (is_resource($buildProcess)) {
                fclose($buildPipes[0]);
                $buildStdout = stream_get_contents($buildPipes[1]);
                fclose($buildPipes[1]);
                fclose($buildPipes[2]);
                $buildReturnVar = proc_close($buildProcess);
                if ($buildStdout) {
                    $buildOutput = explode("\n", trim($buildStdout));
                }
            }

            if ($buildReturnVar !== 0) {
                return [
                    'success' => false,
                    'message' => __('Token-Transformation erfolgreich, aber Build fehlgeschlagen.', 'wp-starter'),
                    'details' => implode("\n", $buildOutput),
                ];
            }
        }

        // Clear caches
        $this->clearTokenCache();

        $duration = round(microtime(true) - $startTime, 1);

        return [
            'success' => true,
            'message' => sprintf(
                /* translators: %s: duration in seconds */
                __('Design Tokens aktualisiert und Build abgeschlossen (%ss)', 'wp-starter'),
                $duration
            ),
            'duration' => $duration,
        ];
    }

    /**
     * Sync ACF color picker values from uploaded token files
     *
     * Updates ACF options to match the colors in primitives.tokens.json
     * so the color pickers on the "Extended" tab show current values.
     */
    private function syncColorPickersFromTokens(): void
    {
        // Update palette colors (accent, primary, secondary, gray)
        foreach (self::PALETTE_COLORS as $colorName => $fieldKey) {
            $currentColor = self::getCurrentTokenColor($colorName, '500');
            if ($currentColor) {
                $fieldName = 'token_' . $colorName . '_color';
                update_field($fieldName, $currentColor, 'option');
            }
        }

        // Update status colors (success, warning, error)
        foreach (self::STATUS_COLORS as $colorName => $fieldKey) {
            $currentColor = self::getCurrentStatusColor($colorName);
            if ($currentColor) {
                $fieldName = 'token_' . $colorName . '_color';
                update_field($fieldName, $currentColor, 'option');
            }
        }
    }

    /**
     * Find npm executable path
     *
     * @return string|null Path to npm or null if not found
     */
    private function findNpmPath(): ?string
    {
        // Common paths
        $paths = [
            '/usr/local/bin/npm',
            '/usr/bin/npm',
            '/opt/homebrew/bin/npm',
        ];

        foreach ($paths as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        // Try 'which npm'
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
        $which = shell_exec('which npm 2>/dev/null');
        if ($which) {
            $path = trim($which);
            if (is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Find Node.js executable path
     *
     * @return string|null Path to Node.js or null if not found
     */
    private function findNodePath(): ?string
    {
        // Common paths
        $paths = [
            '/usr/local/bin/node',
            '/usr/bin/node',
            '/opt/homebrew/bin/node',
        ];

        foreach ($paths as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        // Try 'which node'
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
        $which = shell_exec('which node 2>/dev/null');
        if ($which) {
            $path = trim($which);
            if (is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Clear token-related caches
     */
    private function clearTokenCache(): void
    {
        // Clear Vite manifest cache
        delete_transient('vite_manifest_cache');

        // Clear Blade compiled files
        $compiledDir = get_template_directory() . '/compiled';
        if (is_dir($compiledDir)) {
            $files = glob($compiledDir . '/*.php');
            if ($files) {
                foreach ($files as $file) {
                    // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
                    @unlink($file);
                }
            }
        }

        // Clear object cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        // Fire action for cache plugins
        do_action('wp_starter_tokens_updated');
    }

    /**
     * Add an admin notice to be displayed
     *
     * @param string $type Notice type (success, error, warning, info)
     * @param string $message Notice message
     */
    private function addAdminNotice(string $type, string $message): void
    {
        add_action('admin_notices', function () use ($type, $message) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($type),
                wp_kses_post($message)
            );
        });
    }

    /**
     * Generate restore form HTML
     *
     * This is generated in PHP to avoid ACF's HTML escaping in message fields.
     *
     * @return string Form HTML
     * @phpstan-ignore method.unused (Reserved for future restore UI integration)
     */
    private function generateRestoreFormHtml(): string
    {
        $backupSets = self::getAvailableBackupSets();

        if (empty($backupSets)) {
            return sprintf(
                '<div style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <h3 style="margin: 0 0 10px 0; font-size: 14px;">%s</h3>
                    <p style="margin: 0; color: #666;">%s</p>
                </div>',
                esc_html__('Backup wiederherstellen', 'wp-starter'),
                esc_html__('Keine Backups vorhanden. Backups werden automatisch erstellt, wenn Tokens geändert werden.', 'wp-starter')
            );
        }

        // Build select options
        $options = '';
        foreach ($backupSets as $backup) {
            $typesLabel = implode(', ', $backup['types']);
            $label = sprintf(
                '%s (%s)',
                $backup['date'],
                $typesLabel
            );
            $options .= sprintf(
                '<option value="%s">%s</option>',
                esc_attr($backup['timestamp']),
                esc_html($label)
            );
        }

        return sprintf(
            '<form method="post" style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                %s
                <input type="hidden" name="wp-starter-restore-backup" value="1">
                <h3 style="margin: 0 0 15px 0; font-size: 14px;">%s</h3>
                <div style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 250px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">%s</label>
                        <select name="backup_timestamp" style="width: 100%%;">
                            %s
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="button" onclick="return confirm(\'%s\');">%s</button>
                    </div>
                </div>
                <p class="description" style="margin-top: 10px;">%s</p>
            </form>',
            wp_nonce_field(self::NONCE_RESTORE, '_wpnonce', true, false),
            esc_html__('Backup wiederherstellen', 'wp-starter'),
            esc_html__('Backup-Zeitpunkt auswählen', 'wp-starter'),
            $options,
            esc_js(__('Backup wirklich wiederherstellen? Die aktuellen Token-Dateien werden überschrieben.', 'wp-starter')),
            esc_html__('Wiederherstellen', 'wp-starter'),
            esc_html__('Stellt alle Token-Dateien vom gewählten Zeitpunkt wieder her. Die aktuellen Dateien werden überschrieben.', 'wp-starter')
        );
    }

    /**
     * Get download URL for a token file
     *
     * @param string $type Token type (primitives, light, dark)
     * @return string Download URL
     */
    public static function getDownloadUrl(string $type): string
    {
        return wp_nonce_url(
            add_query_arg([
                'wp-starter-download-token' => '1',
                'type' => $type,
            ], admin_url()),
            self::NONCE_DOWNLOAD
        );
    }

    /**
     * Get regenerate URL
     *
     * @return string Regenerate URL
     */
    public static function getRegenerateUrl(): string
    {
        return wp_nonce_url(
            add_query_arg('wp-starter-regenerate-tokens', '1', admin_url()),
            self::NONCE_REGENERATE
        );
    }

    /**
     * Get upload form nonce field
     *
     * @return string Nonce field HTML
     */
    public static function getUploadNonce(): string
    {
        return wp_nonce_field(self::NONCE_UPLOAD, '_wpnonce', true, false);
    }

    /**
     * Get apply colors form nonce field
     *
     * @return string Nonce field HTML
     */
    public static function getApplyColorsNonce(): string
    {
        return wp_nonce_field(self::NONCE_APPLY_COLORS, '_wpnonce', true, false);
    }

    /**
     * Get restore form nonce field
     *
     * @return string Nonce field HTML
     */
    public static function getRestoreNonce(): string
    {
        return wp_nonce_field(self::NONCE_RESTORE, '_wpnonce', true, false);
    }

    /**
     * Get available backup sets grouped by timestamp
     *
     * Returns an array of backup sets, each containing:
     * - timestamp: The raw timestamp string (YYYY-MM-DD_HH-MM-SS)
     * - date: Formatted date string for display
     * - types: Array of token types available in this backup
     *
     * @return array<array{timestamp: string, date: string, types: string[]}>
     */
    public static function getAvailableBackupSets(): array
    {
        $backupDir = get_template_directory() . '/' . self::BACKUP_DIR;

        if (!is_dir($backupDir)) {
            return [];
        }

        $files = glob($backupDir . '/*.tokens.json');
        if (!$files) {
            return [];
        }

        // Group files by timestamp
        $backupsByTimestamp = [];

        foreach ($files as $file) {
            $filename = basename($file);

            // Extract type and timestamp from filename: {type}_{timestamp}.tokens.json
            if (preg_match('/^(primitives|light|dark)_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.tokens\.json$/', $filename, $matches)) {
                $type = $matches[1];
                $timestamp = $matches[2];

                if (!isset($backupsByTimestamp[$timestamp])) {
                    $backupsByTimestamp[$timestamp] = [];
                }
                $backupsByTimestamp[$timestamp][] = $type;
            }
        }

        if (empty($backupsByTimestamp)) {
            return [];
        }

        // Sort by timestamp descending (newest first)
        krsort($backupsByTimestamp);

        // Format for output
        $result = [];
        foreach ($backupsByTimestamp as $timestamp => $types) {
            // Convert timestamp to DateTime: 2026-01-28_14-30-00 -> 2026-01-28 14:30:00
            $dateStr = str_replace('_', ' ', $timestamp);
            $dateStr = preg_replace('/(\d{2})-(\d{2})-(\d{2})$/', '$1:$2:$3', $dateStr);

            $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $dateStr ?? '');

            $formattedDate = $dateTime
                ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), $dateTime->getTimestamp())
                : $timestamp;

            $result[] = [
                'timestamp' => $timestamp,
                'date' => $formattedDate ?: $timestamp,
                'types' => $types,
            ];
        }

        return $result;
    }

    /**
     * Get current color value from token file
     *
     * @param string $colorName Color name (e.g., 'accent', 'secondary')
     * @param string $shade Shade level (e.g., '500')
     * @return string|null Hex color value or null if not found
     */
    public static function getCurrentTokenColor(string $colorName, string $shade = '500'): ?string
    {
        $tokensFile = get_template_directory() . '/' . self::TOKENS_DIR . '/primitives.tokens.json';

        if (!file_exists($tokensFile)) {
            return null;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $content = file_get_contents($tokensFile);
        if ($content === false) {
            return null;
        }

        $tokens = json_decode($content, true);
        if (!is_array($tokens)) {
            return null;
        }

        // Navigate to color.[colorName].[shade].$value.hex
        if (
            isset($tokens['color'][$colorName][$shade]['$value']['hex']) &&
            is_string($tokens['color'][$colorName][$shade]['$value']['hex'])
        ) {
            return $tokens['color'][$colorName][$shade]['$value']['hex'];
        }

        return null;
    }

    /**
     * Get current status color value from token file
     *
     * @param string $colorName Color name (success, warning, error)
     * @return string|null Hex color value or null if not found
     */
    public static function getCurrentStatusColor(string $colorName): ?string
    {
        $tokensFile = get_template_directory() . '/' . self::TOKENS_DIR . '/primitives.tokens.json';

        if (!file_exists($tokensFile)) {
            return null;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $content = file_get_contents($tokensFile);
        if ($content === false) {
            return null;
        }

        $tokens = json_decode($content, true);
        if (!is_array($tokens)) {
            return null;
        }

        // Navigate to color.[colorName].$value.hex (status colors are flat, not nested in shades)
        if (
            isset($tokens['color'][$colorName]['$value']['hex']) &&
            is_string($tokens['color'][$colorName]['$value']['hex'])
        ) {
            return $tokens['color'][$colorName]['$value']['hex'];
        }

        return null;
    }

    /**
     * Get last backup timestamp
     *
     * @return string|null Formatted timestamp or null if no backups
     */
    public static function getLastBackupTime(): ?string
    {
        $backupDir = get_template_directory() . '/' . self::BACKUP_DIR;

        if (!is_dir($backupDir)) {
            return null;
        }

        $files = glob($backupDir . '/*.tokens.json');
        if (!$files) {
            return null;
        }

        // Get most recent file
        $latestTime = 0;
        foreach ($files as $file) {
            $mtime = filemtime($file);
            if ($mtime > $latestTime) {
                $latestTime = $mtime;
            }
        }

        if ($latestTime === 0) {
            return null;
        }

        return wp_date(get_option('date_format') . ' ' . get_option('time_format'), $latestTime);
    }
}
