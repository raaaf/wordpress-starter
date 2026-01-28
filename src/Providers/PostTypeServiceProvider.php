<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\PostTypes\Team;
use WordpressStarter\PostTypes\Testimonial;

/**
 * Post Type Service Provider
 *
 * Registers all custom post types and taxonomies for the theme.
 * Add new post types here as they are created.
 */
class PostTypeServiceProvider extends ServiceProvider
{
    /**
     * Custom Post Types to register
     *
     * @var array<class-string>
     */
    private array $postTypes = [
        Team::class,
        Testimonial::class,
    ];

    /**
     * Custom Taxonomies to register
     *
     * @var array<class-string>
     */
    private array $taxonomies = [
        // Add taxonomy classes here
    ];

    public function register(): void
    {
        // Nothing to register
    }

    public function boot(): void
    {
        $this->registerPostTypes();
        $this->registerTaxonomies();
        $this->registerAcfFields();
    }

    /**
     * Register all custom post types
     */
    private function registerPostTypes(): void
    {
        foreach ($this->postTypes as $postTypeClass) {
            if (method_exists($postTypeClass, 'register')) {
                $postTypeClass::register();
            }
        }
    }

    /**
     * Register all custom taxonomies
     */
    private function registerTaxonomies(): void
    {
        foreach ($this->taxonomies as $taxonomyClass) {
            if (method_exists($taxonomyClass, 'register')) {
                $taxonomyClass::register();
            }
        }
    }

    /**
     * Register ACF fields for post types
     */
    private function registerAcfFields(): void
    {
        add_action('acf/init', function (): void {
            foreach ($this->postTypes as $postTypeClass) {
                if (method_exists($postTypeClass, 'registerFields')) {
                    $postTypeClass::registerFields();
                }
            }
        });
    }
}
