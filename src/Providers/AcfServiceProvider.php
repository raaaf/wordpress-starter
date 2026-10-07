<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use Illuminate\Support\Facades\Blade;
use WordpressStarter\Acf\AcfExtended;
use WordpressStarter\Acf\FieldDefinitions;
use WordpressStarter\Acf\FlexibleContent;
use WordpressStarter\Acf\Options;
use WordpressStarter\Acf\PageSettings;
use WordpressStarter\Services\TabsContentMigration;
use WordpressStarter\Vite;

class AcfServiceProvider extends ServiceProvider
{
    /**
     * Field key suffixes of the table fields, derived from the names FieldDefinitions::tableFields
     * builds its keys from ("field_{$prefix}_<name>").
     */
    private const TABLE_CELL_CONTENT_SUFFIX = '_' . FieldDefinitions::TABLE_CELL_CONTENT_NAME;
    private const TABLE_HEADERS_SUFFIX = '_' . FieldDefinitions::TABLE_HEADERS_NAME;
    private const TABLE_ROWS_SUFFIX = '_' . FieldDefinitions::TABLE_ROWS_NAME;

    public function register(): void
    {
        // Set up ACF JSON save/load points
        $this->setupAcfJson();

        // Configure ACF Extended for better Flexible Content UX
        AcfExtended::init();

        // Register options pages
        add_action('acf/init', [Options::class, 'register']);

        // Register page settings (e.g. landing page mode)
        add_action('acf/init', [PageSettings::class, 'register']);

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

        // One-time move of the removed tab text into nested one_column modules
        add_action('init', [TabsContentMigration::class, 'maybeRun'], 99);
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
     * URLs, so no script can enter this way.
     *
     * Saving: in a save context (is_admin(), wp_doing_ajax(), a REST request or an XML-RPC request)
     * a user without the unfiltered_html capability gets $tags back unchanged,
     * so wp_kses_post() strips form controls for them. This filter alone does
     * not cover ACF WYSIWYG values, because ACF stores them without kses;
     * sanitizeWysiwyg() runs wp_kses_post() on them at save time for users
     * without unfiltered_html, which makes the strip effective there. Rendering
     * on the frontend (visitors, no capabilities) keeps the additions, so forms
     * a user with unfiltered_html saved still render. There is no CSP
     * form-action any more, so this capability check together with
     * sanitizeWysiwyg() is the control that stops a contributor from posting
     * forms to a foreign host.
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

        $isSaveContext = is_admin()
            || wp_doing_ajax()
            || ( defined('REST_REQUEST') && REST_REQUEST )
            || ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST );

        if ($isSaveContext && !current_user_can('unfiltered_html')) {
            return $tags;
        }

        foreach (self::formControlTags() as $tag => $attributes) {
            // Core allows <textarea> but only a handful of attributes, which drops
            // the placeholder and validation hints form plugins emit.
            $existing = $tag === 'textarea' && is_array($tags['textarea'] ?? null) ? $tags['textarea'] : [];
            $tags[$tag] = array_merge($existing, $attributes);
        }

        return $tags;
    }

    /**
     * Tag names removed from table cells: every form-control tag plus <button>,
     * which core's post allowlist already permits and formControlTags() omits.
     *
     * @return list<string>
     */
    private static function formTagsToStrip(): array
    {
        return [...array_keys(self::formControlTags()), 'button'];
    }

    /**
     * Form-control tags with their allowed attributes, the list behind
     * allowFormControlTags(). The table-cell strip in sanitizeTextarea() reads
     * formTagsToStrip(), which adds the tags core already allows.
     *
     * @return array<string, array<string, bool>>
     */
    private static function formControlTags(): array
    {
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

        return [
            'form' => array_merge($common, [
                'action' => true,
                'method' => true,
                'enctype' => true,
                'accept-charset' => true,
                'name' => true,
                'target' => true,
                'novalidate' => true,
            ]),

            'input' => array_merge($common, [
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
            ]),

            'select' => array_merge($common, [
                'name' => true,
                'size' => true,
                'multiple' => true,
                'autocomplete' => true,
                'required' => true,
                'disabled' => true,
            ]),

            'option' => array_merge($common, [
                'value' => true,
                'label' => true,
                'selected' => true,
                'disabled' => true,
            ]),

            'optgroup' => array_merge($common, [
                'label' => true,
                'disabled' => true,
            ]),

            'textarea' => array_merge($common, [
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
            ]),
        ];
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
                $port = config('vite.dev_server.port', 5180);
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
        // No ACF read-permission filter is registered here: 'acf/rest_api/item_permissions/get'
        // does not exist in the installed ACF plugins, so ACF fields in REST
        // responses follow core's defaults for the post type.

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
                        // member_ covers the member-area shared password hash, whose
                        // load_value blanking only runs in wp-admin, not in REST.
                        return !str_starts_with($key, 'analytics_')
                            && !str_starts_with($key, 'api_')
                            && !str_starts_with($key, 'member_');
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

        // Validate every url field: absolute and protocol-relative URLs, IDN hosts included.
        add_filter('acf/validate_value/type=url', [self::class, 'validateUrl'], 10, 2);

        // Newsletter-Adresse: leer oder eine https-Adresse.
        add_filter('acf/validate_value/key=' . FieldDefinitions::fieldKey('flex_newsletter', 'action_url'), [self::class, 'validateNewsletterActionUrl'], 10, 2);

        // Newsletter-Feldname: nur Zeichen, die das Template behaelt.
        add_filter('acf/validate_value/key=' . FieldDefinitions::fieldKey('flex_newsletter', 'email_field'), [self::class, 'validateNewsletterEmailField'], 10, 2);

        // Contact-Form-7-ID: Ziffern oder Hash (CF7 5.8+), nichts Weiteres.
        add_filter('acf/validate_value/key=' . FieldDefinitions::fieldKey('flex_contact_form', 'form_id'), [self::class, 'validateContactFormId'], 10, 2);

        // Validate every email field.
        add_filter('acf/validate_value/type=email', [self::class, 'validateEmail'], 10, 2);

        // date_picker/time_picker: ACF's format_value throws on garbage (e.g.
        // "300000000000"), which would 500 every page reading the field.
        add_filter('acf/validate_value/type=date_picker', [self::class, 'validateDatePicker'], 10, 2);
        add_filter('acf/validate_value/type=time_picker', [self::class, 'validateTimePicker'], 10, 2);

        // Sanitize text fields on save
        add_filter('acf/update_value/type=text', function ($value) {
            return sanitize_text_field($value);
        }, 10, 1);

        // Sanitize textarea fields on save
        add_filter('acf/update_value/type=textarea', [self::class, 'sanitizeTextarea'], 10, 3);

        // ACF stores WYSIWYG values without kses: strip disallowed markup on save
        add_filter('acf/update_value/type=wysiwyg', [self::class, 'sanitizeWysiwyg'], 10, 3);

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

            // Tabellenzellen erlauben HTML, [br] wird dort nicht umgewandelt
            if (str_ends_with( (string) ( $field['key'] ?? '' ), '_cell_content')) {
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
     * Validate url fields: absolute and protocol-relative URLs, IDN hosts included.
     *
     * @param bool|string $valid
     */
    public static function validateUrl(mixed $valid, mixed $value): mixed
    {
        if ($valid !== true || empty($value)) {
            return $valid;
        }

        $candidate = (string) $value;

        // filter_var() rejects a missing scheme, which core ACF url fields allow.
        if (str_starts_with($candidate, '//')) {
            $candidate = 'https:' . $candidate;
        }

        // filter_var() also rejects non-ASCII hosts, so test their punycode form.
        $host = parse_url($candidate, PHP_URL_HOST);
        if (is_string($host) && !preg_match('/^[\x20-\x7e]+$/', $host) && function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($host);
            if ($ascii !== false) {
                $candidate = str_replace($host, $ascii, $candidate);
            }
        }

        if (!filter_var($candidate, FILTER_VALIDATE_URL)) {
            return __('Bitte gib eine gültige URL ein.', 'wp-starter');
        }

        return $valid;
    }

    /**
     * Newsletter form action: empty or an https URL.
     *
     * @param bool|string $valid
     */
    public static function validateNewsletterActionUrl(mixed $valid, mixed $value): mixed
    {
        if ($valid !== true || empty($value)) {
            return $valid;
        }

        if (strtolower(strval(wp_parse_url(strval($value), PHP_URL_SCHEME))) !== 'https') {
            return __('Bitte gib die https-Adresse aus dem Einbettungscode deines Newsletter-Anbieters an.', 'wp-starter');
        }

        return $valid;
    }

    /**
     * Newsletter email field name: empty or only characters the template keeps.
     *
     * @param bool|string $valid
     */
    public static function validateNewsletterEmailField(mixed $valid, mixed $value): mixed
    {
        // Keep an earlier error string (or false) from another validator.
        if ($valid !== true || empty($value)) {
            return $valid;
        }

        if (!preg_match('/^[' . FieldDefinitions::NEWSLETTER_EMAIL_FIELD_CHARS . ']+$/D', strval($value))) {
            return __('Bitte nutze nur Buchstaben, Ziffern, Unterstrich, Bindestrich und eckige Klammern.', 'wp-starter');
        }

        return $valid;
    }

    /**
     * Validate the Contact Form 7 ID (digits or hash, as in the shortcode).
     *
     * @param bool|string $valid
     */
    public static function validateContactFormId(mixed $valid, mixed $value): mixed
    {
        // Keep an earlier error string (or false) from another validator.
        if ($valid !== true || empty($value)) {
            return $valid;
        }

        if (!preg_match('/^[' . FieldDefinitions::CONTACT_FORM_ID_CHARS . ']+$/D', strval($value))) {
            return __('Bitte trage nur die ID aus dem Shortcode ein (Buchstaben, Ziffern, Bindestrich).', 'wp-starter');
        }

        return $valid;
    }

    /**
     * Validate email fields.
     *
     * @param bool|string $valid
     */
    public static function validateEmail(mixed $valid, mixed $value): mixed
    {
        if ($valid !== true || empty($value)) {
            return $valid;
        }

        if (!is_email($value)) {
            return __('Bitte gib eine gültige E-Mail-Adresse ein.', 'wp-starter');
        }

        return $valid;
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

            $sections = get_field('page_sections', $postId, false);
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
     * Accept only empty or a real calendar date stored as Ymd.
     *
     * @param true|string $valid
     */
    public static function validateDatePicker(mixed $valid, mixed $value): mixed
    {
        if ($valid !== true || $value === '' || $value === null) {
            return $valid;
        }

        if (!is_string($value) || !self::matchesFormat('!Ymd', $value)) {
            return __('Bitte wähle ein gültiges Datum.', 'wp-starter');
        }

        return $valid;
    }

    /**
     * Accept only empty or a real time of day as H:i:s or H:i.
     *
     * @param true|string $valid
     */
    public static function validateTimePicker(mixed $valid, mixed $value): mixed
    {
        if ($valid !== true || $value === '' || $value === null) {
            return $valid;
        }

        $isTime = is_string($value)
            && ( self::matchesFormat('!H:i:s', $value) || self::matchesFormat('!H:i', $value) );
        if (!$isTime) {
            return __('Bitte wähle eine gültige Uhrzeit.', 'wp-starter');
        }

        return $valid;
    }

    /**
     * Strict round-trip check: the value must parse and re-format identically.
     */
    private static function matchesFormat(string $format, string $value): bool
    {
        return self::parseStrict($format, $value) !== null;
    }

    /**
     * Strict parse of a stored value; null unless it round-trips unchanged
     * (rejects non-strings, overflowing dates like 20261332 and huge numbers).
     * Shared by the validators above and the Event reader.
     */
    public static function parseStrict(string $format, mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value)) {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat($format, $value);

        return $parsed !== false && $parsed->format(ltrim($format, '!')) === $value ? $parsed : null;
    }

    /**
     * Textarea-Felder beim Speichern von Tags befreien.
     *
     * Ausnahme Tabellenzelle: Die Feldanweisung verspricht "HTML erlaubt" und die
     * Ausgabe laeuft durch wp_kses_post, daher bleibt erlaubtes HTML erhalten.
     *
     * Auch in der Tabellenzelle bleiben Formularelemente draussen, auch fuer
     * Nutzer mit unfiltered_html: die Ausgabe erlaubt sie wegen
     * allowFormControlTags, aber eine Zelle braucht sie nicht, und ohne
     * form-action in der CSP koennte sonst ein Formular an einen fremden Host
     * gespeichert werden.
     *
     * @param array<string, mixed> $field
     */
    public static function sanitizeTextarea(mixed $value, mixed $postId, array $field): mixed
    {
        $key = $field['key'] ?? '';

        if (str_ends_with( (string) $key, self::TABLE_CELL_CONTENT_SUFFIX)) {
            // Erst die Post-Allowlist, dann Formular-Tags samt Attributen entfernen
            // (Anführungszeichen-bewusst, damit ein ">" im Attributwert nicht abschneidet),
            // dann zur Sicherheit noch einmal durch die Allowlist.
            $cleaned = wp_kses_post( (string) $value);
            $formTags = implode('|', self::formTagsToStrip());
            $cleaned = preg_replace(
                '~</?(?:' . $formTags . ')\b(?:"[^"]*"|\'[^\']*\'|[^>"\'])*>~i',
                '',
                $cleaned
            ) ?? '';

            return wp_kses_post($cleaned);
        }

        return sanitize_textarea_field($value);
    }

    /**
     * Sanitize WYSIWYG values on save for users without unfiltered_html.
     *
     * ACF stores WYSIWYG values without kses. In this save context
     * allowFormControlTags() withholds the form-control additions, so
     * wp_kses_post() strips <form>, <input> and the like. Users with
     * unfiltered_html keep their markup unchanged.
     *
     * @param array<string, mixed> $field
     */
    public static function sanitizeWysiwyg(mixed $value, mixed $postId, array $field): mixed
    {
        if (!is_string($value) || current_user_can('unfiltered_html')) {
            return $value;
        }

        return wp_kses_post($value);
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

        foreach (self::findTableRowMismatches($posted) as $mismatch) {
            acf_add_validation_error(
                $mismatch['input'],
                sprintf(
                    /* translators: 1: Zeilennummer, 2: Zahl der Zellen, 3: Zahl der Spalten */
                    __('Tabelle: Zeile %1$d passt nicht zur Spaltenzahl (Zellen in der Zeile: %2$d, Spalten in der Tabelle: %3$d). Ergänze oder entferne Zellen in dieser Zeile.', 'wp-starter'),
                    $mismatch['row'],
                    $mismatch['cells'],
                    $mismatch['columns'],
                ),
            );
        }
    }

    /**
     * Zeilen, deren Zellenzahl nicht zur Zahl der Spaltenueberschriften passt.
     *
     * Reine Funktion ueber den geposteten Baum, ohne $_POST und ohne ACF-Aufruf.
     * Eine Tabelle ohne Spaltenueberschriften wird uebersprungen.
     *
     * @param array<mixed> $posted
     *
     * @return array<int, array{input: string, row: int, cells: int, columns: int}>
     */
    public static function findTableRowMismatches(array $posted): array
    {
        $mismatches = [];

        foreach (self::findTableGroups($posted) as $group) {
            $columns = count($group['headers']);

            if ($columns === 0) {
                continue;
            }

            foreach (array_values( (array) $group['rows']) as $index => $row) {
                $cells = self::countRowCells( (array) $row);

                if ($cells === $columns) {
                    continue;
                }

                $mismatches[] = [
                    'input' => $group['input'],
                    'row' => $index + 1,
                    'cells' => $cells,
                    'columns' => $columns,
                ];
            }
        }

        return $mismatches;
    }

    /**
     * Zellenzahl einer Tabellenzeile: die Groesse ihres ersten Array-Werts.
     *
     * Der Zellen-Repeater liegt unter einem Schluessel, dessen Name je nach
     * Feldkontext variiert, deshalb wird der erste Array-Wert genommen statt
     * ein Feld beim Namen zu suchen.
     *
     * @param array<mixed> $row
     */
    private static function countRowCells(array $row): int
    {
        foreach ($row as $value) {
            if (is_array($value)) {
                return count($value);
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
     * `input` ist der volle Eingabename des Zeilen-Repeaters (acf[...][...]),
     * an den acf_add_validation_error die Meldung hängt.
     *
     * @param array<mixed> $tree
     * @param array<int, int|string> $path Schluessel von der Wurzel bis zu $tree
     *
     * @return array<int, array{input: string, headers: array<mixed>, rows: array<mixed>}>
     */
    private static function findTableGroups(array $tree, array $path = []): array
    {
        $found = [];

        foreach ($tree as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            $found = array_merge($found, self::findTableGroups($value, [...$path, $key]));
        }

        $headerField = null;
        $rowField = null;

        foreach (array_keys($tree) as $key) {
            if (!is_string($key)) {
                continue;
            }

            if (str_ends_with($key, self::TABLE_HEADERS_SUFFIX)) {
                $headerField = $key;
            }

            if (str_ends_with($key, self::TABLE_ROWS_SUFFIX)) {
                $rowField = $key;
            }
        }

        if ($headerField !== null && $rowField !== null
            && is_array($tree[$headerField]) && is_array($tree[$rowField])) {
            $found[] = [
                'input' => 'acf[' . implode('][', [...$path, $rowField]) . ']',
                'headers' => $tree[$headerField],
                'rows' => $tree[$rowField],
            ];
        }

        return $found;
    }
}
