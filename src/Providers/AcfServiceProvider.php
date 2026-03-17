<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\Acf\AcfExtended;
use WordpressStarter\Acf\Options;
use WordpressStarter\Acf\FlexibleContent;
use WordpressStarter\Vite;
use Illuminate\Support\Facades\Blade;

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
        Blade::directive('kses', function ($expression) {
            return "<?php echo wp_kses_post({$expression}); ?>";
        });
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

            // Check if Vite dev server is running
            $isDev = defined('WP_DEBUG') && WP_DEBUG && \WordpressStarter\Vite::isDevServerRunning();

            if ($isDev) {
                // Development mode - load from Vite dev server
                $host = config('vite.dev_server.host', 'localhost');
                $port = config('vite.dev_server.port', 5173);
                wp_enqueue_script(
                    'acf-flexible-titles',
                    "http://{$host}:{$port}/resources/js/admin/flexible-titles.ts",
                    ['acf-input'],
                    null,
                    true
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
                        true
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
        // Example: Validate URL fields contain valid URLs
        add_filter('acf/validate_value/type=url', function ($valid, $value) {
            if (!$valid || empty($value)) {
                return $valid;
            }

            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return __('Bitte geben Sie eine gültige URL ein.', 'wp-starter');
            }

            return $valid;
        }, 10, 2);

        // Example: Validate email fields
        add_filter('acf/validate_value/type=email', function ($valid, $value) {
            if (!$valid || empty($value)) {
                return $valid;
            }

            if (!is_email($value)) {
                return __('Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'wp-starter');
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

        // Add [br] hint to title field instructions
        add_filter('acf/prepare_field/name=title', function ($field): mixed {
            if ($field['type'] === 'text' && !empty($field['instructions'])) {
                $field['instructions'] .= ' ' . __('Nutze [br] für einen manuellen Zeilenumbruch.', 'wp-starter');
            }
            return $field;
        });
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
}
