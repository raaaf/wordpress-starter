<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use Illuminate\Support\Facades\Blade;
use WordpressStarter\Acf\AcfExtended;
use WordpressStarter\Acf\FlexibleContent;
use WordpressStarter\Acf\Options;
use WordpressStarter\Vite;

class AcfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Set up ACF JSON save/load points
        $this->setupAcfJson();

        // Configure ACF Extended for better Flexible Content UX
        AcfExtended::init();

        // Register options pages
        add_action('acf/init', [Options::class, 'register']);

        // Register flexible content fields
        add_action('acf/init', [FlexibleContent::class, 'register']);

        // Initialize cache clearing
        Options::initCacheClearing();

        // Register REST API integration
        $this->registerRestApi();

        // Register field validation hooks
        $this->registerValidationHooks();

        // Auto-generate section anchors on save
        $this->registerSectionAnchorGeneration();
    }

    public function boot(): void
    {
        // Register Blade directives
        $this->registerBladeDirectives();

        // Keep [video]/[audio] shortcode <source> tags in kses'd WYSIWYG output.
        add_filter('wp_kses_allowed_html', [self::class, 'allowMediaSourceTag'], 20, 2);

        // Keep the form controls of form shortcodes in kses'd WYSIWYG output.
        add_filter('wp_kses_allowed_html', [self::class, 'allowFormControlTags'], 20, 2);

        // Add ACF admin styles
        $this->addAdminStyles();

        // Add flexible content title scripts
        $this->addFlexibleTitleScripts();
    }

    private function setupAcfJson(): void
    {
        $jsonPath = get_template_directory() . '/acf-json';

        // Create directory if it doesn't exist
        if (!is_dir($jsonPath)) {
            wp_mkdir_p($jsonPath);
        }

        // Set save point
        add_filter('acf/settings/save_json', function () use ($jsonPath) {
            return $jsonPath;
        });

        // Set load point
        add_filter('acf/settings/load_json', function ($paths) use ($jsonPath) {
            unset($paths[0]);
            $paths[] = $jsonPath;

            return $paths;
        });
    }

    private function registerBladeDirectives(): void
    {
        if (!class_exists('Illuminate\Support\Facades\Blade')) {
            return;
        }

        // @field directive - escaped by default for security (ACF 6.2.5+)
        Blade::directive('field', function ($expression) {
            return "<?php echo esc_html(\\WordpressStarter\\Acf\\Fields::get({$expression})); ?>";
        });

        // @fieldRaw directive - for trusted HTML content (use with caution)
        Blade::directive('fieldRaw', function ($expression) {
            return "<?php echo wp_kses_post(\\WordpressStarter\\Acf\\Fields::get({$expression})); ?>";
        });

        // @option directive - escaped by default for security
        Blade::directive('option', function ($expression) {
            return "<?php echo esc_html(\\WordpressStarter\\Acf\\Fields::option({$expression})); ?>";
        });

        // @optionRaw directive - for trusted HTML content (use with caution)
        Blade::directive('optionRaw', function ($expression) {
            return "<?php echo wp_kses_post(\\WordpressStarter\\Acf\\Fields::option({$expression})); ?>";
        });

        // @hasfield directive
        Blade::directive('hasfield', function ($expression) {
            return "<?php if (\\WordpressStarter\\Acf\\Fields::has({$expression})): ?>";
        });

        // @endhasfield directive
        Blade::directive('endhasfield', function () {
            return '<?php endif; ?>';
        });

        // @repeater directive
        Blade::directive('repeater', function ($expression) {
            return "<?php foreach (\\WordpressStarter\\Acf\\Fields::repeater({$expression}) as \$item): ?>";
        });

        // @endrepeater directive
        Blade::directive('endrepeater', function () {
            return '<?php endforeach; ?>';
        });

        // @flexible directive
        Blade::directive('flexible', function ($expression) {
            return "<?php foreach (\\WordpressStarter\\Acf\\Fields::flexible({$expression}) as \$layout): ?>";
        });

        // @endflexible directive
        Blade::directive('endflexible', function () {
            return '<?php endforeach; ?>';
        });

        // @layout directive for flexible content
        Blade::directive('layout', function ($expression) {
            return "<?php if (\$layout['acf_fc_layout'] === {$expression}): ?>";
        });

        // @endlayout directive
        Blade::directive('endlayout', function () {
            return '<?php endif; ?>';
        });

        // @group directive
        Blade::directive('group', function ($expression) {
            return "<?php \$group = \\WordpressStarter\\Acf\\Fields::group({$expression}); if (\$group): ?>";
        });

        // @endgroup directive
        Blade::directive('endgroup', function () {
            return '<?php endif; ?>';
        });

        // @kses directive - sanitize WYSIWYG content
        //
        // wp_filter_content_tags() is what core runs on the_content() to add
        // srcset, sizes, loading and decoding to content images. ACF sections
        // bypass the_content(), so editor-inserted images were shipping the full
        // size file to phones (measured 432 KiB of overhead on one page).
        // Sanitize first, then add attributes, otherwise kses strips them again.
        // ContentImages sits in between: core needs the wp-image-<id> class to
        // resolve the attachment, and images inserted without a media library
        // reference do not have it.
        Blade::directive('kses', function ($expression) {
            return "<?php echo wp_filter_content_tags(\\WordpressStarter\\Support\\ContentImages::addAttachmentIds(wp_kses_post({$expression}))); ?>";
        });
    }

    /**
     * Allow <source> elements in post-context kses.
     *
     * Core's post allowlist permits <video>/<audio> but not <source>,
     * so wp_kses_post() strips the sources that wp_video_shortcode()
     * and wp_audio_shortcode() emit inside WYSIWYG content.
     *
     * Applies to every post-context kses call site-wide; the allowance is
     * limited to src/type attributes and source cannot execute scripts.
     *
     * @param array<string, array<string, bool>|mixed> $tags Allowed tags.
     * @param string $context Kses context.
     *
     * @return array<string, array<string, bool>|mixed>
     */
    public static function allowMediaSourceTag(array $tags, string $context): array
    {
        if ($context === 'post') {
            $tags['source'] = [
                'src' => true,
                'type' => true,
            ];
        }

        return $tags;
    }

    /**
     * Allow form-control tags in post-context kses.
     *
     * Core's post allowlist permits <label>, <fieldset> and <textarea> but not
     * <form>, <input>, <select> or <option>. A form shortcode placed in a
     * WYSIWYG field therefore renders as a half-built form: the labels and a
     * textarea survive while the <form> element and every input are stripped,
     * with nothing to tell the editor what happened.
     *
     * Applies to every post-context kses call site-wide. Attributes are listed
     * explicitly because tags added through this filter do not inherit core's
     * global attributes, and kses still drops on* handlers and javascript:
     * URLs, so no script can enter this way. A <form> pointing at an arbitrary
     * action does become possible for anyone who may edit content, which is
     * the same trust level already required to place arbitrary links.
     *
     * @param array<string, array<string, bool>|mixed> $tags Allowed tags.
     * @param string $context Kses context.
     *
     * @return array<string, array<string, bool>|mixed>
     */
    public static function allowFormControlTags(array $tags, string $context): array
    {
        if ($context !== 'post') {
            return $tags;
        }

        // Tags added by a filter miss core's global attributes, so the ones
        // form markup relies on are repeated for each tag below.
        $common = [
            'class' => true,
            'id' => true,
            'style' => true,
            'title' => true,
            'role' => true,
            'dir' => true,
            'lang' => true,
            'hidden' => true,
            'tabindex' => true,
            'data-*' => true,
            'aria-describedby' => true,
            'aria-hidden' => true,
            'aria-invalid' => true,
            'aria-label' => true,
            'aria-labelledby' => true,
            'aria-live' => true,
            'aria-required' => true,
        ];

        $tags['form'] = array_merge($common, [
            'action' => true,
            'method' => true,
            'enctype' => true,
            'accept-charset' => true,
            'name' => true,
            'target' => true,
            'novalidate' => true,
        ]);

        $tags['input'] = array_merge($common, [
            'type' => true,
            'name' => true,
            'value' => true,
            'placeholder' => true,
            'size' => true,
            'maxlength' => true,
            'minlength' => true,
            'min' => true,
            'max' => true,
            'step' => true,
            'pattern' => true,
            'accept' => true,
            'autocomplete' => true,
            'autocapitalize' => true,
            'checked' => true,
            'multiple' => true,
            'required' => true,
            'readonly' => true,
            'disabled' => true,
        ]);

        $tags['select'] = array_merge($common, [
            'name' => true,
            'size' => true,
            'multiple' => true,
            'autocomplete' => true,
            'required' => true,
            'disabled' => true,
        ]);

        $tags['option'] = array_merge($common, [
            'value' => true,
            'label' => true,
            'selected' => true,
            'disabled' => true,
        ]);

        $tags['optgroup'] = array_merge($common, [
            'label' => true,
            'disabled' => true,
        ]);

        // Core allows <textarea> but only a handful of attributes, which drops
        // the placeholder and validation hints form plugins emit.
        $tags['textarea'] = array_merge(
            is_array($tags['textarea'] ?? null) ? $tags['textarea'] : [],
            $common,
            [
                'cols' => true,
                'rows' => true,
                'wrap' => true,
                'name' => true,
                'placeholder' => true,
                'maxlength' => true,
                'minlength' => true,
                'required' => true,
                'readonly' => true,
                'disabled' => true,
            ],
        );

        return $tags;
    }

    private function addAdminStyles(): void
    {
        add_action('admin_head', function () {
            ?>
            <style>
                /* ACF Admin Improvements */
                .acf-field .acf-label label {
                    font-weight: 600;
                }

                .acf-flexible-content .layout {
                    border: 1px solid #e0e0e0;
                    border-radius: 4px;
                    margin-bottom: 15px;
                }

                .acf-repeater .acf-row:nth-child(even) {
                    background-color: #f9f9f9;
                }
            </style>
            <?php
        });
    }

    /**
     * Add flexible content layout title scripts
     * Auto-generates layout titles based on content for better UX
     */
    private function addFlexibleTitleScripts(): void
    {
        add_action('admin_enqueue_scripts', function (string $hook) {
            // Only load on post edit screens
            if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
                return;
            }

            // Use dev-mode flag determined once in Vite::init() (runs before Application boot)
            $isDev = Vite::isDev();

            if ($isDev) {
                // Development mode - load from Vite dev server
                $host = config('vite.dev_server.host', 'localhost');
                $port = config('vite.dev_server.port', 5173);
                wp_enqueue_script(
                    'acf-flexible-titles',
                    "http://{$host}:{$port}/resources/js/admin/flexible-titles.ts",
                    ['acf-input'],
                    null,
                    true,
                );
            } else {
                // Production mode - load from manifest
                $scriptUrl = Vite::getAssetUrl('resources/js/admin/flexible-titles.ts');
                if ($scriptUrl) {
                    wp_enqueue_script(
                        'acf-flexible-titles',
                        $scriptUrl,
                        ['acf-input'],
                        null,
                        true,
                    );
                }
            }
        });
    }

    /**
     * Register REST API integration for ACF fields
     * Enables ACF fields in REST API responses with proper security
     */
    private function registerRestApi(): void
    {
        // Enable ACF fields in REST API for posts
        add_filter('acf/rest_api/item_permissions/get', function () {
            return current_user_can('read');
        });

        // Add custom endpoint for theme options (read-only, admin only)
        add_action('rest_api_init', function () {
            register_rest_route('theme/v1', '/options', [
                'methods' => 'GET',
                'callback' => function () {
                    if (!function_exists('get_fields')) {
                        return new \WP_Error('acf_not_active', 'ACF is not active', ['status' => 500]);
                    }

                    $options = get_fields('option');

                    // Filter out sensitive data
                    $safeOptions = array_filter($options ?? [], function ($key) {
                        // Exclude analytics IDs and other sensitive data from public API
                        return !str_starts_with($key, 'analytics_') && !str_starts_with($key, 'api_');
                    }, ARRAY_FILTER_USE_KEY);

                    return rest_ensure_response($safeOptions);
                },
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            ]);
        });
    }

    /**
     * Register field validation hooks
     * Allows custom validation rules for ACF fields
     */
    private function registerValidationHooks(): void
    {
        // Tabelle: Zellen gegen Spaltenzahl pruefen.
        //
        // Kopfzeilen und Zeilen sind zwei unabhaengige Repeater. Wer eine Spalte
        // ergaenzt und die Zeilen vergisst, bekam bisher keinerlei Rueckmeldung.
        // Das Template gleicht die Zahl inzwischen an, aber ueberzaehlige Zellen
        // fallen dabei weg: stiller Datenverlust beim Anschauen, nicht beim
        // Speichern. Deshalb hier, wo es noch zu retten ist.
        //
        // Geprueft wird auf dem gesamten geposteten Baum statt per
        // acf/validate_value, weil eine Zelle ihre Schwesterspalten nicht kennt:
        // beide Repeater liegen unter derselben Flexible-Content-Zeile, und ACF
        // reicht bei der Feldvalidierung keinen Pfad dorthin mit.
        add_action('acf/validate_save_post', [self::class, 'validateTableRows']);

        // Example: Validate URL fields contain valid URLs
        add_filter('acf/validate_value/type=url', function ($valid, $value) {
            if (!$valid || empty($value)) {
                return $valid;
            }

            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return __('Bitte gib eine gültige URL ein.', 'wp-starter');
            }

            return $valid;
        }, 10, 2);

        // Example: Validate email fields
        add_filter('acf/validate_value/type=email', function ($valid, $value) {
            if (!$valid || empty($value)) {
                return $valid;
            }

            if (!is_email($value)) {
                return __('Bitte gib eine gültige E-Mail-Adresse ein.', 'wp-starter');
            }

            return $valid;
        }, 10, 2);

        // Sanitize text fields on save
        add_filter('acf/update_value/type=text', function ($value) {
            return sanitize_text_field($value);
        }, 10, 1);

        // Sanitize textarea fields on save
        add_filter('acf/update_value/type=textarea', function ($value) {
            return sanitize_textarea_field($value);
        }, 10, 1);

        // Add [br] hint to all text and textarea field instructions
        $brHint = function ($field): mixed {
            if (!is_array($field) || empty($field['type'])) {
                return $field;
            }

            // Skip fields that don't benefit from line breaks
            $excludeNames = ['email', 'phone', 'url', 'website', 'section_anchor'];
            if (in_array($field['_name'] ?? '', $excludeNames, true)) {
                return $field;
            }

            $hint = __('Nutze [br] für einen manuellen Zeilenumbruch.', 'wp-starter');
            if (!empty($field['instructions']) && !str_contains($field['instructions'], '[br]')) {
                $field['instructions'] .= ' ' . $hint;
            } elseif (empty($field['instructions'])) {
                $field['instructions'] = $hint;
            }

            return $field;
        };
        add_filter('acf/prepare_field/type=text', $brHint);
        add_filter('acf/prepare_field/type=textarea', $brHint);
    }

    /**
     * Auto-generate section_anchor values on save
     *
     * Fills empty section_anchor fields with a unique ID based on layout name
     * and position. Preserves manually set anchors.
     */
    private function registerSectionAnchorGeneration(): void
    {
        $callback = function ($postId) use (&$callback): void {
            if (!function_exists('have_rows') || wp_is_post_revision($postId)) {
                return;
            }

            $sections = get_field('page_sections', $postId);
            if (!is_array($sections)) {
                return;
            }

            $layoutCounters = [];
            $changed = false;

            foreach ($sections as &$section) {
                $layout = $section['acf_fc_layout'] ?? '';
                if (!$layout) {
                    continue;
                }

                $layoutCounters[$layout] = ( $layoutCounters[$layout] ?? 0 ) + 1;

                if (empty($section['section_anchor'])) {
                    $section['section_anchor'] = str_replace('_', '-', $layout) . '-' . $layoutCounters[$layout];
                    $changed = true;
                }
            }
            unset($section);

            if ($changed) {
                remove_action('acf/save_post', $callback, 20);
                update_field('page_sections', $sections, $postId);
                add_action('acf/save_post', $callback, 20);
            }
        };
        add_action('acf/save_post', $callback, 20);
    }

    /**
     * Zellenzahl jeder Tabellenzeile gegen die Zahl der Spaltenueberschriften.
     *
     * Laeuft ueber den geposteten ACF-Baum, weil Kopfzeilen und Zeilen
     * Geschwister-Repeater sind und eine Feldvalidierung den Weg zum
     * Geschwisterfeld nicht kennt.
     */
    public static function validateTableRows(): void
    {
        // Nonce: ACF prueft sie vor diesem Hook. Sanitisierung: hier wird nichts
        // gespeichert und nichts ausgegeben, es werden ausschliesslich
        // Array-Groessen gezaehlt. Die Werte selbst fasst diese Pruefung nie an.
        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
        $posted = isset($_POST['acf']) && is_array($_POST['acf']) ? wp_unslash($_POST['acf']) : null;
        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput

        if (!is_array($posted)) {
            return;
        }

        foreach (self::findTableGroups($posted) as $gruppe) {
            $spalten = count($gruppe['headers']);

            if ($spalten === 0) {
                continue;
            }

            foreach (array_values( (array) $gruppe['rows']) as $index => $zeile) {
                $zellen = self::countRowCells( (array) $zeile);

                if ($zellen === $spalten) {
                    continue;
                }

                acf_add_validation_error(
                    $gruppe['feld'],
                    sprintf(
                        /* translators: 1: Zeilennummer, 2: Zahl der Zellen, 3: Zahl der Spalten */
                        __('Tabelle: Zeile %1$d hat %2$d Zellen, die Tabelle aber %3$d Spalten. Ergänze oder entferne Zellen in dieser Zeile.', 'wp-starter'),
                        $index + 1,
                        $zellen,
                        $spalten,
                    ),
                );
            }
        }
    }

    /**
     * Zellenzahl einer Tabellenzeile: die Groesse ihres ersten Array-Werts.
     *
     * Der Zellen-Repeater liegt unter einem Schluessel, dessen Name je nach
     * Feldkontext variiert, deshalb wird der erste Array-Wert genommen statt
     * ein Feld beim Namen zu suchen.
     *
     * @param array<mixed> $zeile
     */
    private static function countRowCells(array $zeile): int
    {
        foreach ($zeile as $wert) {
            if (is_array($wert)) {
                return count($wert);
            }
        }

        return 0;
    }

    /**
     * Alle Tabellen-Layouts im geposteten Baum finden.
     *
     * Erkannt wird an den Feldschluesseln: ein Knoten, der sowohl einen
     * Schluessel auf `_headers` als auch einen auf `_rows` traegt, ist eine
     * Tabelle. Das ueberlebt eine Umbenennung des Layouts und findet auch
     * Tabellen in verschachtelten Feldgruppen.
     *
     * @param array<mixed> $baum
     *
     * @return array<int, array{feld: string, headers: array<mixed>, rows: array<mixed>}>
     */
    private static function findTableGroups(array $baum): array
    {
        $gefunden = [];

        foreach ($baum as $wert) {
            if (!is_array($wert)) {
                continue;
            }

            $gefunden = array_merge($gefunden, self::findTableGroups($wert));
        }

        $headerFeld = null;
        $rowFeld = null;

        foreach (array_keys($baum) as $schluessel) {
            if (!is_string($schluessel)) {
                continue;
            }

            if (str_ends_with($schluessel, '_headers')) {
                $headerFeld = $schluessel;
            }

            if (str_ends_with($schluessel, '_rows')) {
                $rowFeld = $schluessel;
            }
        }

        if ($headerFeld !== null && $rowFeld !== null
            && is_array($baum[$headerFeld]) && is_array($baum[$rowFeld])) {
            $gefunden[] = [
                'feld' => $rowFeld,
                'headers' => $baum[$headerFeld],
                'rows' => $baum[$rowFeld],
            ];
        }

        return $gefunden;
    }
}
