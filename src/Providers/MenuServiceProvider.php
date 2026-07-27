<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

class MenuServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No registration needed for menus
    }

    public function boot(): void
    {
        $this->registerMenus();
        $this->addMenuFilters();
    }

    private function registerMenus(): void
    {
        add_action('init', function (): void {
            register_nav_menus([
                'header-menu' => __('Header Menu', 'wp-starter'),
                'legal-menu'  => __('Legal Menu', 'wp-starter'),
                'footer-menu' => __('Footer Menu', 'wp-starter'),
            ]);
        });
    }

    private function addMenuFilters(): void
    {
        // Add custom classes to menu items
        add_filter('nav_menu_css_class', [$this, 'addMenuItemClasses'], 10, 4);
        add_filter('nav_menu_submenu_css_class', [$this, 'addSubmenuClasses'], 10, 3);
        add_filter('nav_menu_link_attributes', [$this, 'addAriaCurrent'], 10, 4);
    }

    /**
     * Expose the active menu item to assistive technology.
     *
     * WordPress only marks it with a CSS class, which screen readers ignore.
     *
     * @param array<string, string> $atts
     * @return array<string, string>
     */
    public function addAriaCurrent(array $atts, \WP_Post $item, \stdClass $args, int $depth): array
    {
        $classes = $item->classes ?? [];

        if (in_array('current-menu-item', (array) $classes, true) || in_array('current_page_item', (array) $classes, true)) {
            $atts['aria-current'] = 'page';
        }

        return $atts;
    }

    /**
     * @param array<int, string> $classes
     * @return array<int, string>
     */
    public function addMenuItemClasses(array $classes, \WP_Post $item, \stdClass $args, int $depth): array
    {
        if (isset($args->li_class)) {
            $classes[] = $args->li_class;
        }
        if (isset($args->{"li_class_$depth"})) {
            $classes[] = $args->{"li_class_$depth"};
        }
        return $classes;
    }

    /**
     * @param array<int, string> $classes
     * @return array<int, string>
     */
    public function addSubmenuClasses(array $classes, \stdClass $args, int $depth): array
    {
        if (isset($args->submenu_class)) {
            $classes[] = $args->submenu_class;
        }
        if (isset($args->{"submenu_class_$depth"})) {
            $classes[] = $args->{"submenu_class_$depth"};
        }
        return $classes;
    }
}
