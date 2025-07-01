<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\Acf\Blocks;
use WordpressStarter\Acf\Options;
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
        
        // Initialize cache clearing
        Options::initCacheClearing();
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

        // @field directive
        Blade::directive('field', function ($expression) {
            return "<?php echo \\WordpressStarter\\Acf\\Fields::get({$expression}); ?>";
        });

        // @option directive
        Blade::directive('option', function ($expression) {
            return "<?php echo \\WordpressStarter\\Acf\\Fields::option({$expression}); ?>";
        });

        // @hasfield directive
        Blade::directive('hasfield', function ($expression) {
            return "<?php if (\\WordpressStarter\\Acf\\Fields::has({$expression})): ?>";
        });

        // @endhasfield directive
        Blade::directive('endhasfield', function () {
            return "<?php endif; ?>";
        });

        // @repeater directive
        Blade::directive('repeater', function ($expression) {
            return "<?php foreach (\\WordpressStarter\\Acf\\Fields::repeater({$expression}) as \$item): ?>";
        });

        // @endrepeater directive
        Blade::directive('endrepeater', function () {
            return "<?php endforeach; ?>";
        });

        // @flexible directive
        Blade::directive('flexible', function ($expression) {
            return "<?php foreach (\\WordpressStarter\\Acf\\Fields::flexible({$expression}) as \$layout): ?>";
        });

        // @endflexible directive
        Blade::directive('endflexible', function () {
            return "<?php endforeach; ?>";
        });

        // @layout directive for flexible content
        Blade::directive('layout', function ($expression) {
            return "<?php if (\$layout['acf_fc_layout'] === {$expression}): ?>";
        });

        // @endlayout directive
        Blade::directive('endlayout', function () {
            return "<?php endif; ?>";
        });

        // @group directive
        Blade::directive('group', function ($expression) {
            return "<?php \$group = \\WordpressStarter\\Acf\\Fields::group({$expression}); if (\$group): ?>";
        });

        // @endgroup directive
        Blade::directive('endgroup', function () {
            return "<?php endif; ?>";
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
}