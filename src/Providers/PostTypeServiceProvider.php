<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\PostTypes\MemberDownload;
use WordpressStarter\PostTypes\Team;
use WordpressStarter\PostTypes\Testimonial;
use WordpressStarter\Taxonomies\DownloadCategory;

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
        MemberDownload::class,
        Team::class,
        Testimonial::class,
    ];

    /**
     * Custom Taxonomies to register
     *
     * @var array<class-string>
     */
    private array $taxonomies = [
        DownloadCategory::class,
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
        $this->registerAdminHooks();
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
     * Register admin UI hooks (columns, meta boxes) for post types
     */
    private function registerAdminHooks(): void
    {
        if (!is_admin()) {
            return;
        }

        foreach ($this->postTypes as $postTypeClass) {
            if (method_exists($postTypeClass, 'registerAdminHooks')) {
                $postTypeClass::registerAdminHooks();
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
