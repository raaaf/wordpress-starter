{{--
    Admin UI for the design tokens options page.

    Rendered from DesignTokenServiceProvider::addTokensPageScript() on admin_footer.
    Uses AJAX for uploads and restores to avoid nested form issues with ACF.

    Expected data:
    - string $ajaxUrl AJAX endpoint URL (admin-ajax.php)
    - string $uploadNonce Nonce for the upload AJAX action
    - string $restoreNonce Nonce for the restore AJAX action
    - string $uploadAction AJAX action name for token upload
    - string $restoreAction AJAX action name for backup restore
    - array<array{timestamp: string, date: string, types: string[]}> $backupSets Available backup sets
--}}
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
    <div class="wp-starter-loading-text">{!! esc_html__('Design Tokens werden aktualisiert...', 'wp-starter') !!}</div>
    <div class="wp-starter-loading-subtext">{!! esc_html__('Build läuft, bitte warten', 'wp-starter') !!}</div>
</div>
<script>
    (function() {
        const overlay = document.getElementById('wpStarterLoading');
        const acfForm = document.getElementById('post');
        const ajaxUrl = '{!! esc_url($ajaxUrl) !!}';
        const uploadNonce = '{!! esc_js($uploadNonce) !!}';
        const restoreNonce = '{!! esc_js($restoreNonce) !!}';

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
                    { labelText: '{!! esc_js(__('Primitives (Basis-Farben, Spacing, etc.)', 'wp-starter')) !!}', inputId: 'wp-starter-file-primitives' },
                    { labelText: '{!! esc_js(__('Light Mode (Semantische Tokens)', 'wp-starter')) !!}', inputId: 'wp-starter-file-light' },
                    { labelText: '{!! esc_js(__('Dark Mode (Semantische Tokens)', 'wp-starter')) !!}', inputId: 'wp-starter-file-dark' },
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
                uploadBtn.textContent = '{!! esc_js(__('Tokens hochladen und anwenden', 'wp-starter')) !!}';
                uploadBtnP.appendChild(uploadBtn);
                uploadUi.appendChild(uploadBtnP);

                const descP = document.createElement('p');
                descP.className = 'description';
                descP.textContent = '{!! esc_js(__('Du kannst einzelne Dateien oder alle drei gleichzeitig hochladen. Ein Backup wird automatisch erstellt.', 'wp-starter')) !!}';
                uploadUi.appendChild(descP);

                messageDiv.appendChild(uploadUi);

                // Handle upload button click
                uploadBtn.addEventListener('click', async function() {
                    const primitives = document.getElementById('wp-starter-file-primitives').files[0];
                    const light = document.getElementById('wp-starter-file-light').files[0];
                    const dark = document.getElementById('wp-starter-file-dark').files[0];

                    if (!primitives && !light && !dark) {
                        showNotice('upload', 'error', '{!! esc_js(__('Bitte mindestens eine Datei auswählen.', 'wp-starter')) !!}');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', '{!! esc_js($uploadAction) !!}');
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
                            showNotice('upload', 'success', result.data.message + ' {!! esc_js(__('Seite wird neu geladen...', 'wp-starter')) !!}');
                            // Clear ACF dirty state to prevent "unsaved changes" warning
                            if (typeof acf !== 'undefined' && acf.unload) {
                                acf.unload.reset();
                            }
                            // Reload page after short delay to show updated color pickers
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showNotice('upload', 'error', result.data.message || '{!! esc_js(__('Ein Fehler ist aufgetreten.', 'wp-starter')) !!}');
                        }
                    } catch (error) {
                        showNotice('upload', 'error', '{!! esc_js(__('Netzwerkfehler. Bitte erneut versuchen.', 'wp-starter')) !!}');
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
                const backupSets = {!! wp_json_encode($backupSets) !!};

                if (backupSets.length === 0) {
                    const emptyWrapper = document.createElement('div');
                    emptyWrapper.setAttribute('style', 'background: #f0f0f1; padding: 15px; border-radius: 4px;');
                    const emptyP = document.createElement('p');
                    emptyP.setAttribute('style', 'margin: 0; color: #666;');
                    emptyP.textContent = '{!! esc_js(__('Keine Backups vorhanden. Backups werden automatisch erstellt, wenn Tokens geändert werden.', 'wp-starter')) !!}';
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
                    selectLabel.textContent = '{!! esc_js(__('Backup-Zeitpunkt auswählen', 'wp-starter')) !!}';
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
                    restoreBtn.textContent = '{!! esc_js(__('Wiederherstellen', 'wp-starter')) !!}';
                    btnWrapper.appendChild(restoreBtn);

                    flexRow.appendChild(selectWrapper);
                    flexRow.appendChild(btnWrapper);
                    restoreUi.appendChild(flexRow);

                    const descP = document.createElement('p');
                    descP.className = 'description';
                    descP.setAttribute('style', 'margin-top: 10px;');
                    descP.textContent = '{!! esc_js(__('Stellt alle Token-Dateien vom gewählten Zeitpunkt wieder her.', 'wp-starter')) !!}';
                    restoreUi.appendChild(descP);

                    messageDiv.appendChild(restoreUi);

                    // Handle restore button click
                    restoreBtn.addEventListener('click', async function() {
                        const timestamp = document.getElementById('wp-starter-backup-select').value;

                        if (!confirm('{!! esc_js(__('Backup wirklich wiederherstellen? Die aktuellen Token-Dateien werden überschrieben.', 'wp-starter')) !!}')) {
                            return;
                        }

                        const formData = new FormData();
                        formData.append('action', '{!! esc_js($restoreAction) !!}');
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
                                showNotice('restore', 'success', result.data.message + ' {!! esc_js(__('Seite wird neu geladen...', 'wp-starter')) !!}');
                                // Clear ACF dirty state to prevent "unsaved changes" warning
                                if (typeof acf !== 'undefined' && acf.unload) {
                                    acf.unload.reset();
                                }
                                // Reload page after short delay to show updated color pickers
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                showNotice('restore', 'error', result.data.message || '{!! esc_js(__('Ein Fehler ist aufgetreten.', 'wp-starter')) !!}');
                            }
                        } catch (error) {
                            showNotice('restore', 'error', '{!! esc_js(__('Netzwerkfehler. Bitte erneut versuchen.', 'wp-starter')) !!}');
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
