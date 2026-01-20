<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\Acf\Blocks;
use WordpressStarter\Acf\BlockFields;
use WordpressStarter\Acf\Options;
use WordpressStarter\Acf\FlexibleContent;
use Illuminate\Support\Facades\Blade;

class AcfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Set up ACF JSON save/load points
        $this->setupAcfJson();

        // Register ACF blocks
        add_action('acf/init', [Blocks::class, 'register']);

        // Register options pages
        add_action('acf/init', [Options::class, 'register']);

        // Register flexible content fields
        add_action('acf/init', [FlexibleContent::class, 'register']);

        // Register block field groups
        add_action('acf/init', [BlockFields::class, 'register']);

        // Initialize cache clearing
        Options::initCacheClearing();

        // Register REST API integration
        $this->registerRestApi();

        // Register field validation hooks
        $this->registerValidationHooks();

        // Register Block Bindings API (WordPress 6.5+)
        $this->registerBlockBindings();
    }

    public function boot(): void
    {
        // Register Blade directives
        $this->registerBladeDirectives();
        
        // Add ACF admin styles
        $this->addAdminStyles();
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

        // @innerblocks directive - render InnerBlocks for nested blocks
        Blade::directive('innerblocks', function ($expression) {
            if (empty($expression)) {
                return '<?php echo \\WordpressStarter\\Acf\\Blocks::renderInnerBlocks(); ?>';
            }
            return "<?php echo \\WordpressStarter\\Acf\\Blocks::renderInnerBlocks({$expression}); ?>";
        });

        // @blockwrapper directive - output block wrapper attributes
        Blade::directive('blockwrapper', function ($expression) {
            return "<?php echo \\WordpressStarter\\Acf\\Blocks::getBlockWrapperAttributes({$expression}); ?>";
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
     * Register REST API integration for ACF fields
     * Enables ACF fields in REST API responses with proper security
     */
    private function registerRestApi(): void
    {
        // Enable ACF fields in REST API for posts
        add_filter('acf/rest_api/item_permissions/get', function ($permission) {
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
        add_filter('acf/validate_value/type=url', function ($valid, $value, $field) {
            if (!$valid || empty($value)) {
                return $valid;
            }

            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return __('Bitte geben Sie eine gültige URL ein.', 'wp-starter');
            }

            return $valid;
        }, 10, 3);

        // Example: Validate email fields
        add_filter('acf/validate_value/type=email', function ($valid, $value, $field) {
            if (!$valid || empty($value)) {
                return $valid;
            }

            if (!is_email($value)) {
                return __('Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'wp-starter');
            }

            return $valid;
        }, 10, 3);

        // Sanitize text fields on save
        add_filter('acf/update_value/type=text', function ($value, $postId, $field) {
            return sanitize_text_field($value);
        }, 10, 3);

        // Sanitize textarea fields on save
        add_filter('acf/update_value/type=textarea', function ($value, $postId, $field) {
            return sanitize_textarea_field($value);
        }, 10, 3);
    }

    /**
     * Register Block Bindings API support (WordPress 6.5+)
     * Allows ACF fields to be used as dynamic block attributes
     *
     * @see https://www.advancedcustomfields.com/blog/acf-6-2-8
     */
    private function registerBlockBindings(): void
    {
        // Only register if WordPress 6.5+ and ACF supports it
        if (!function_exists('register_block_bindings_source')) {
            return;
        }

        // ACF already registers 'acf/field' source in ACF 6.2.8+
        // This filter allows customizing which fields are accessible
        add_filter('acf/rest_api/field_settings/show_in_rest', function ($showInRest, $field) {
            // Allow specific field types in REST API for block bindings
            $allowedTypes = ['text', 'textarea', 'number', 'email', 'url', 'image', 'file'];

            if (in_array($field['type'], $allowedTypes, true)) {
                return true;
            }

            return $showInRest;
        }, 10, 2);
    }
}
