{{--
    Admin UI for the plugin setup page.

    Rendered from PluginServiceProvider::renderSetupPage().

    Expected data:
    - array  $categories             Plugin categories (keyed by category name)
    - array  $selectedPlugins        Plugins selected via Composer config
    - array  $missingSelectedPlugins Plugins from selected list that are not yet active
    - bool   $hasConfig              Whether a setup config file exists
    - string $nonce                  wp_create_nonce() value for plugin install
    - string $ajaxActionInstallPlugin   AJAX action name for single install
    - string $ajaxActionInstallAll      AJAX action name for bulk install
--}}
<div class="wrap">
    <h1><?php esc_html_e('WP-Starter Theme Setup', 'wp-starter'); ?></h1>

    <div class="wp-starter-setup-header" style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
        <h2 style="margin-top: 0;"><?php esc_html_e('Willkommen beim WP-Starter Theme!', 'wp-starter'); ?></h2>
        <p><?php esc_html_e('Plugins werden über Composer verwaltet. Führen Sie "composer install" aus, um die konfigurierten Plugins zu installieren.', 'wp-starter'); ?></p>
        <p style="color: #50575e; font-size: 13px;">
            <span class="dashicons dashicons-info-outline"></span>
            <?php esc_html_e('ACF PRO muss manuell installiert werden (Premium-Plugin).', 'wp-starter'); ?>
        </p>

        <?php if (!empty($missingSelectedPlugins)) : ?>
            <p>
                <button type="button" id="wp-starter-install-all" class="button button-primary button-hero">
                    <?php
                    printf(
                        // translators: %d is the number of plugins to activate/install
                        esc_html__('Plugins aktivieren (%d)', 'wp-starter'),
                        count($missingSelectedPlugins),
                    );
            ?>
                </button>
            </p>
        <?php else : ?>
            <p style="color: #00a32a; font-weight: 600;">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('Alle Plugins sind aktiv!', 'wp-starter'); ?>
            </p>
        <?php endif; ?>
    </div>

    <div id="wp-starter-install-progress" style="display: none; background: #fff; padding: 20px; margin: 20px 0; box-shadow: 0 1px 1px rgba(0,0,0,.04); border-left: 4px solid #2271b1;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0;"><?php esc_html_e('Installation läuft...', 'wp-starter'); ?></h3>
            <div style="text-align: right;">
                <span class="wp-starter-progress-counter" style="font-size: 14px; color: #50575e;"></span>
                <span class="wp-starter-elapsed-time" style="font-size: 12px; color: #787c82; display: block;"></span>
            </div>
        </div>
        <div class="wp-starter-progress-bar" style="background: #ddd; height: 24px; border-radius: 4px; overflow: hidden; position: relative;">
            <div class="wp-starter-progress-fill" style="background: linear-gradient(90deg, #2271b1 0%, #135e96 100%); height: 100%; width: 0%; transition: width 0.3s;"></div>
            <span class="wp-starter-progress-percent" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px; font-weight: 600; color: #1d2327;"></span>
        </div>
        <div class="wp-starter-current-plugin" style="margin-top: 15px; padding: 12px; background: #f0f6fc; border-radius: 4px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="wp-starter-spinner" style="display: inline-block; width: 20px; height: 20px; border: 2px solid #2271b1; border-top-color: transparent; border-radius: 50%; animation: wp-starter-spin 1s linear infinite;"></span>
                <div>
                    <strong class="wp-starter-plugin-name" style="display: block; color: #1d2327;"></strong>
                    <span class="wp-starter-plugin-step" style="font-size: 12px; color: #50575e;"></span>
                </div>
            </div>
        </div>
        <div class="wp-starter-install-log" style="max-height: 200px; overflow-y: auto; margin-top: 15px; padding: 12px; background: #f6f7f7; border-radius: 4px; font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace; font-size: 12px; line-height: 1.6;"></div>
    </div>

    <style>
        @keyframes wp-starter-spin {
            to { transform: rotate(360deg); }
        }
    </style>

    <?php foreach ($categories as $categoryName => $categoryPlugins) : ?>
        <div class="wp-starter-plugin-category" style="background: #fff; padding: 20px; margin: 20px 0; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2><?php echo esc_html($categoryName); ?></h2>
            <table class="wp-list-table widefat plugins">
                <tbody>
                    <?php foreach ($categoryPlugins as $key => $plugin) : ?>
                        <?php
                $isActive = ($plugin['check'])();
                        $isExternal = !empty($plugin['external']);
                        $isInstalled = !$isExternal && WordpressStarter\PluginInstaller::isInstalled($plugin['slug']);
                        ?>
                        <tr class="<?php echo $isActive ? 'active' : 'inactive'; ?>" data-slug="<?php echo esc_attr($plugin['slug']); ?>">
                            <td class="plugin-title column-primary" style="padding: 15px;">
                                <strong><?php echo esc_html($plugin['name']); ?></strong>
                                <?php if ($isExternal) : ?>
                                    <span style="background: #d63638; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-left: 5px;">Premium</span>
                                <?php endif; ?>
                                <?php if ($plugin['required']) : ?>
                                    <span style="background: #dba617; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-left: 5px;">Erforderlich</span>
                                <?php endif; ?>
                                <p class="description" style="margin: 5px 0 0;"><?php echo esc_html($plugin['description']); ?></p>
                            </td>
                            <td class="column-status" style="padding: 15px; text-align: right; white-space: nowrap;">
                                <?php if ($isActive) : ?>
                                    <span style="color: #00a32a;"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Aktiv', 'wp-starter'); ?></span>
                                <?php elseif ($isExternal) : ?>
                                    <a href="<?php echo esc_url($plugin['external']); ?>" class="button" target="_blank" rel="noopener">
                                        <?php esc_html_e('Website besuchen', 'wp-starter'); ?>
                                        <span class="dashicons dashicons-external" style="line-height: 1.4;"></span>
                                    </a>
                                <?php elseif ($isInstalled) : ?>
                                    <button type="button" class="button button-primary wp-starter-activate-plugin" data-slug="<?php echo esc_attr($plugin['slug']); ?>">
                                        <?php esc_html_e('Aktivieren', 'wp-starter'); ?>
                                    </button>
                                <?php else : ?>
                                    <button type="button" class="button button-primary wp-starter-install-plugin" data-slug="<?php echo esc_attr($plugin['slug']); ?>">
                                        <?php esc_html_e('Installieren & Aktivieren', 'wp-starter'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

    <div style="margin-top: 30px; text-align: center;">
        <a href="<?php echo esc_url(admin_url()); ?>" class="button button-secondary button-hero">
            <?php esc_html_e('Zum Dashboard', 'wp-starter'); ?>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var nonce = '<?php echo esc_attr($nonce); ?>';
    var ajaxActionInstallPlugin = '<?php echo esc_js($ajaxActionInstallPlugin); ?>';

    function doAjax(data, onSuccess, onError) {
        var controller = new AbortController();
        var timeoutId = setTimeout(function() { controller.abort(); }, 120000);

        fetch(ajaxurl, {
            method: 'POST',
            signal: controller.signal,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data).toString()
        })
        .then(function(res) { return res.json(); })
        .then(function(response) {
            clearTimeout(timeoutId);
            onSuccess(response);
        })
        .catch(function(err) {
            clearTimeout(timeoutId);
            onError(err && err.name === 'AbortError' ? 'timeout' : 'error', err);
        });
    }

    // Single plugin install
    document.querySelectorAll('.wp-starter-install-plugin, .wp-starter-activate-plugin').forEach(function(button) {
        button.addEventListener('click', function() {
            var slug = button.getAttribute('data-slug');
            var row = button.closest('tr');

            button.disabled = true;
            button.textContent = '<?php esc_html_e('Wird installiert...', 'wp-starter'); ?>';

            doAjax(
                { action: ajaxActionInstallPlugin, slug: slug, nonce: nonce },
                function(response) {
                    if (response.success) {
                        if (row) { row.classList.remove('inactive'); row.classList.add('active'); }
                        var span = document.createElement('span');
                        span.style.color = '#00a32a';
                        span.innerHTML = '<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Aktiv', 'wp-starter'); ?>';
                        button.replaceWith(span);
                    } else {
                        button.disabled = false;
                        button.textContent = '<?php esc_html_e('Fehler - Erneut versuchen', 'wp-starter'); ?>';
                        var errorMsg = response.data && response.data.message ? response.data.message : '<?php esc_html_e('Installation fehlgeschlagen.', 'wp-starter'); ?>';
                        console.error('Plugin install error:', errorMsg);
                        alert(errorMsg);
                    }
                },
                function(status, err) {
                    button.disabled = false;
                    button.textContent = '<?php esc_html_e('Fehler - Erneut versuchen', 'wp-starter'); ?>';
                    console.error('Plugin install fetch error:', status, err);
                    if (status === 'timeout') {
                        alert('<?php esc_html_e('Die Installation hat zu lange gedauert. Bitte versuchen Sie es erneut.', 'wp-starter'); ?>');
                    } else {
                        alert('<?php esc_html_e('Netzwerkfehler bei der Installation. Bitte versuchen Sie es erneut.', 'wp-starter'); ?>');
                    }
                }
            );
        });
    });

    // Install all plugins
    var installAllBtn = document.getElementById('wp-starter-install-all');
    if (installAllBtn) {
        installAllBtn.addEventListener('click', function() {
            var progressEl = document.getElementById('wp-starter-install-progress');
            var progressBar = progressEl.querySelector('.wp-starter-progress-fill');
            var progressPercent = progressEl.querySelector('.wp-starter-progress-percent');
            var progressCounter = progressEl.querySelector('.wp-starter-progress-counter');
            var elapsedTime = progressEl.querySelector('.wp-starter-elapsed-time');
            var pluginName = progressEl.querySelector('.wp-starter-plugin-name');
            var pluginStep = progressEl.querySelector('.wp-starter-plugin-step');
            var currentPlugin = progressEl.querySelector('.wp-starter-current-plugin');
            var log = progressEl.querySelector('.wp-starter-install-log');

            var plugins = [];
            var pluginNames = {};
            document.querySelectorAll('.wp-starter-install-plugin').forEach(function(btn) {
                var slug = btn.getAttribute('data-slug');
                var nameEl = btn.closest('tr') && btn.closest('tr').querySelector('strong');
                plugins.push(slug);
                pluginNames[slug] = nameEl ? nameEl.textContent.trim() : slug;
            });

            if (plugins.length === 0) {
                alert('<?php esc_html_e('Alle Plugins sind bereits installiert!', 'wp-starter'); ?>');
                return;
            }

            installAllBtn.disabled = true;
            progressEl.style.display = '';
            log.innerHTML = '';

            var total = plugins.length;
            var completed = 0;
            var startTime = Date.now();

            var timerInterval = setInterval(function() {
                var elapsed = Math.floor((Date.now() - startTime) / 1000);
                var minutes = Math.floor(elapsed / 60);
                var seconds = elapsed % 60;
                elapsedTime.textContent = '<?php esc_html_e('Verstrichene Zeit:', 'wp-starter'); ?> ' +
                    (minutes > 0 ? minutes + ' min ' : '') + seconds + ' s';
            }, 1000);

            function updateProgress(current, tot, percent) {
                progressBar.style.width = percent + '%';
                progressPercent.textContent = percent + '%';
                progressCounter.textContent = 'Plugin ' + current + ' / ' + tot;
            }

            function logMessage(type, slug, message, details) {
                var name = pluginNames[slug] || slug;
                var timestamp = new Date().toLocaleTimeString('de-DE');
                var icon = type === 'success' ? '✓' : (type === 'error' ? '✗' : '○');
                var color = type === 'success' ? '#00a32a' : (type === 'error' ? '#d63638' : '#50575e');
                var div = document.createElement('div');
                div.style.cssText = 'color:' + color + ';padding:4px 0;border-bottom:1px solid #e0e0e0;';
                var tsSpan = document.createElement('span');
                tsSpan.style.cssText = 'color:#787c82;margin-right:8px;';
                tsSpan.textContent = '[' + timestamp + ']';
                var strong = document.createElement('strong');
                strong.textContent = icon + ' ' + name;
                div.appendChild(tsSpan);
                div.appendChild(strong);
                if (message) {
                    var msgSpan = document.createElement('span');
                    msgSpan.style.cssText = 'color:#50575e;';
                    msgSpan.textContent = ' — ' + message;
                    div.appendChild(msgSpan);
                }
                if (details) {
                    var detailDiv = document.createElement('div');
                    detailDiv.style.cssText = 'font-size:11px;color:#787c82;margin-left:20px;';
                    detailDiv.textContent = details;
                    div.appendChild(detailDiv);
                }
                log.appendChild(div);
                log.scrollTop = log.scrollHeight;
            }

            function installNext() {
                if (plugins.length === 0) {
                    clearInterval(timerInterval);
                    currentPlugin.innerHTML = '<div style="display:flex;align-items:center;gap:10px;color:#00a32a;">' +
                        '<span class="dashicons dashicons-yes-alt" style="font-size:24px;"></span>' +
                        '<strong><?php esc_html_e('Alle Plugins wurden erfolgreich installiert!', 'wp-starter'); ?></strong></div>';
                    progressEl.querySelector('h3').textContent = '<?php esc_html_e('Installation abgeschlossen', 'wp-starter'); ?>';
                    progressEl.style.borderLeftColor = '#00a32a';
                    installAllBtn.style.display = 'none';
                    logMessage('success', '', '<?php esc_html_e('Installation abgeschlossen', 'wp-starter'); ?>',
                        '<?php esc_html_e('Seite wird neu geladen...', 'wp-starter'); ?>');
                    setTimeout(function() { location.reload(); }, 2000);
                    return;
                }

                var slug = plugins.shift();
                var currentNum = completed + 1;
                var percent = Math.round((completed / total) * 100);

                updateProgress(currentNum, total, percent);
                pluginName.textContent = pluginNames[slug];
                pluginStep.textContent = '<?php esc_html_e('Lade Plugin von WordPress.org herunter...', 'wp-starter'); ?>';
                logMessage('info', slug, '<?php esc_html_e('Installation gestartet', 'wp-starter'); ?>');

                var stepTimeout = setTimeout(function() {
                    pluginStep.textContent = '<?php esc_html_e('Entpacke und installiere...', 'wp-starter'); ?>';
                }, 2000);
                var stepTimeout2 = setTimeout(function() {
                    pluginStep.textContent = '<?php esc_html_e('Aktiviere Plugin...', 'wp-starter'); ?>';
                }, 4000);

                doAjax(
                    { action: ajaxActionInstallPlugin, slug: slug, nonce: nonce },
                    function(response) {
                        clearTimeout(stepTimeout);
                        clearTimeout(stepTimeout2);
                        completed++;
                        updateProgress(completed, total, Math.round((completed / total) * 100));

                        if (response.success) {
                            var details = response.data && response.data.installed ?
                                '<?php esc_html_e('Neu installiert und aktiviert', 'wp-starter'); ?>' :
                                '<?php esc_html_e('Aktiviert', 'wp-starter'); ?>';
                            logMessage('success', slug, '<?php esc_html_e('Erfolgreich', 'wp-starter'); ?>', details);
                        } else {
                            var errorMsg = response.data && response.data.message ? response.data.message : '<?php esc_html_e('Unbekannter Fehler', 'wp-starter'); ?>';
                            logMessage('error', slug, '<?php esc_html_e('Fehlgeschlagen', 'wp-starter'); ?>', errorMsg);
                        }
                        installNext();
                    },
                    function(status, err) {
                        clearTimeout(stepTimeout);
                        clearTimeout(stepTimeout2);
                        completed++;
                        updateProgress(completed, total, Math.round((completed / total) * 100));
                        var errorDetail = status === 'timeout' ?
                            '<?php esc_html_e('Zeitüberschreitung', 'wp-starter'); ?>' :
                            (err && err.message ? err.message : status);
                        logMessage('error', slug, '<?php esc_html_e('Netzwerkfehler', 'wp-starter'); ?>', errorDetail);
                        installNext();
                    }
                );
            }

            installNext();
        });
    }
});
</script>
