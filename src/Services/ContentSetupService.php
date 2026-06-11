<?php

declare(strict_types=1);

namespace WordpressStarter\Services;

use WordpressStarter\ThemeContext;
use WP_Query;

/**
 * Content setup service.
 *
 * Handles first-run content scaffolding: pages, menus, sample posts,
 * contact data defaults, and permalink structure. Extracted from
 * PluginServiceProvider to separate content concerns from plugin-install UI.
 */
class ContentSetupService
{
    /**
     * Default options used when no config file is present (rerun path).
     *
     * @var array<string, mixed>
     */
    private const DEFAULT_SETUP_OPTIONS = [
        'create_pages' => true,
        'pages' => [
            'home' => ['title' => 'Startseite', 'template' => 'page-home'],
            'about' => ['title' => 'Über uns', 'template' => ''],
            'services' => ['title' => 'Leistungen', 'template' => ''],
            'contact' => ['title' => 'Kontakt', 'template' => ''],
            'privacy' => ['title' => 'Datenschutz', 'template' => ''],
            'imprint' => ['title' => 'Impressum', 'template' => ''],
        ],
        'menu_assignments' => [
            'header-menu' => ['about', 'services', 'contact'],
            'legal-menu' => ['privacy', 'imprint'],
            'footer-menu' => ['about', 'services', 'contact'],
        ],
    ];

    /**
     * Run content setup once on first activation.
     *
     * Checks and sets the completion option; skips when already done.
     *
     * @param array<string, mixed> $setupOptions
     */
    public function runOnce(array $setupOptions): void
    {
        if (get_option(ThemeContext::optionKey('content_setup_complete'))) {
            return;
        }

        if (empty($setupOptions)) {
            return;
        }

        $this->execute($setupOptions);

        update_option(ThemeContext::optionKey('content_setup_complete'), true);
    }

    /**
     * Re-run content setup (manual rerun from Tools page).
     *
     * Resets the completion flag, clears cached styleguide images, then
     * runs the same execution path as the first-run setup.
     *
     * @param array<string, mixed> $setupOptions Options from config file; falls back to defaults when empty.
     */
    public function rerun(array $setupOptions): void
    {
        if (empty($setupOptions)) {
            $setupOptions = self::DEFAULT_SETUP_OPTIONS;
        }

        delete_option(ThemeContext::optionKey('content_setup_complete'));
        delete_option(ThemeContext::optionKey('styleguide_images'));

        $this->execute($setupOptions);

        update_option(ThemeContext::optionKey('content_setup_complete'), true);
    }

    /**
     * Core execution path shared by first-run and rerun.
     *
     * @param array<string, mixed> $setupOptions
     */
    private function execute(array $setupOptions): void
    {
        if (!empty($setupOptions['delete_default_content'])) {
            $this->deleteDefaultContent();
        }

        if (!empty($setupOptions['set_permalink_structure'])) {
            $this->setPermalinkStructure();
        }

        $createdPageIds = [];
        if (!empty($setupOptions['pages'])) {
            $createdPageIds = $this->createDefaultPages($setupOptions['pages']);
        }

        if (!empty($createdPageIds) && !empty($setupOptions['menu_assignments'])) {
            $this->createDefaultMenus($createdPageIds, $setupOptions['menu_assignments']);
        }

        if (!empty($setupOptions['create_posts']) && !empty($setupOptions['posts'])) {
            $this->createDefaultPosts($setupOptions['posts']);
        }

        $this->setDefaultContactData();
    }

    /**
     * Delete default WordPress content (Hello World post, sample page, default comment).
     */
    private function deleteDefaultContent(): void
    {
        $default_post = get_post(1);
        if ($default_post && $default_post->post_type === 'post') {
            wp_delete_post(1, true);
        }

        $sample_page = get_post(2);
        if ($sample_page && $sample_page->post_type === 'page') {
            wp_delete_post(2, true);
        }

        $comment = get_comment(1);
        if ($comment) {
            wp_delete_comment(1, true);
        }
    }

    /**
     * Set permalink structure to /%postname%/.
     */
    private function setPermalinkStructure(): void
    {
        global $wp_rewrite;

        $wp_rewrite->set_permalink_structure('/%postname%/');
        $wp_rewrite->flush_rules();
    }

    /**
     * Create default pages from the pages config.
     *
     * @param array<string, array{title: string, template: string, status?: string}> $pages
     *
     * @return array<string, int> Map of page slug to page ID
     */
    private function createDefaultPages(array $pages): array
    {
        $homePageId = null;
        $createdPages = [];

        foreach ($pages as $slug => $pageData) {
            $existing = get_page_by_path($slug);
            if ($existing) {
                $createdPages[$slug] = $existing->ID;

                if ($slug === 'styleguide') {
                    update_option(ThemeContext::optionKey('styleguide_page_id'), $existing->ID);
                }
                continue;
            }

            $postStatus = $pageData['status'] ?? 'publish';

            $pageId = wp_insert_post([
                'post_title' => $pageData['title'],
                'post_name' => $slug,
                'post_status' => $postStatus,
                'post_type' => 'page',
                'post_content' => '',
            ]);

            if ($pageId && !is_wp_error($pageId)) {
                $createdPages[$slug] = $pageId;

                if (!empty($pageData['template'])) {
                    update_post_meta($pageId, '_wp_page_template', $pageData['template'] . '.blade.php');
                }

                if ($slug === 'home') {
                    $homePageId = $pageId;
                }

                if (!empty($pageData['protected']) && function_exists('update_field')) {
                    update_field('field_page_is_protected', true, $pageId);
                }

                if ($slug !== 'styleguide' && $slug !== 'member-area' && function_exists('update_field')) {
                    $heroLayout = [
                        [
                            'acf_fc_layout' => 'hero',
                            'title' => $pageData['title'],
                        ],
                    ];
                    update_field('page_sections', $heroLayout, $pageId);
                }

                if ($slug === 'styleguide') {
                    update_option(ThemeContext::optionKey('styleguide_page_id'), $pageId);
                    update_option(ThemeContext::optionKey('welcome_dismissed'), true);
                }

                if (function_exists('update_field')) {
                    if ($slug === 'privacy') {
                        update_field('datenschutz_seite', $pageId, 'option');
                        update_option('wp_page_for_privacy_policy', $pageId);
                    } elseif ($slug === 'imprint') {
                        update_field('impressum_seite', $pageId, 'option');
                    }
                }
            }
        }

        if ($homePageId) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $homePageId);
        }

        return $createdPages;
    }

    /**
     * Create default menus and populate them with pages.
     *
     * @param array<string, int> $pageIds Map of page slug to page ID
     * @param array<string, string[]> $menuAssignments Map of menu location to page slugs
     */
    private function createDefaultMenus(array $pageIds, array $menuAssignments): void
    {
        $locations = get_registered_nav_menus();
        $menuLocations = get_nav_menu_locations();

        foreach ($menuAssignments as $location => $pageSlugs) {
            if (!isset($locations[$location])) {
                continue;
            }

            $menuName = $locations[$location];
            $existingMenu = wp_get_nav_menu_object($menuName);

            if ($existingMenu) {
                $menuId = $existingMenu->term_id;
            } else {
                $menuId = wp_create_nav_menu($menuName);

                if (is_wp_error($menuId)) {
                    continue;
                }
            }

            $menuLocations[$location] = $menuId;

            $position = 0;
            foreach ($pageSlugs as $pageSlug) {
                if (!isset($pageIds[$pageSlug])) {
                    continue;
                }

                $pageId = $pageIds[$pageSlug];
                $page = get_post($pageId);

                if (!$page) {
                    continue;
                }

                $existingItems = wp_get_nav_menu_items($menuId);
                $alreadyInMenu = false;

                if ($existingItems) {
                    foreach ($existingItems as $item) {
                        if ( (int) $item->object_id === $pageId && $item->object === 'page') {
                            $alreadyInMenu = true;
                            break;
                        }
                    }
                }

                if ($alreadyInMenu) {
                    continue;
                }

                $position += 10;

                wp_update_nav_menu_item($menuId, 0, [
                    'menu-item-object-id' => $pageId,
                    'menu-item-object' => 'page',
                    'menu-item-type' => 'post_type',
                    'menu-item-status' => 'publish',
                    'menu-item-title' => $page->post_title,
                    'menu-item-position' => $position,
                ]);
            }
        }

        set_theme_mod('nav_menu_locations', $menuLocations);
    }

    /**
     * Create sample blog posts.
     *
     * @param array<int, array{title: string, content: string, excerpt: string}> $posts
     */
    private function createDefaultPosts(array $posts): void
    {
        $baseTime = time();
        $dayOffset = 0;

        foreach ($posts as $postData) {
            $existingQuery = new WP_Query([
                'post_type' => 'post',
                'title' => $postData['title'],
                'posts_per_page' => 1,
                'fields' => 'ids',
            ]);
            if ($existingQuery->have_posts()) {
                continue;
            }

            $postDate = gmdate('Y-m-d H:i:s', $baseTime - ( $dayOffset * DAY_IN_SECONDS ));
            $dayOffset += rand(3, 5);

            $postId = wp_insert_post([
                'post_title' => $postData['title'],
                'post_content' => $postData['content'],
                'post_excerpt' => $postData['excerpt'],
                'post_status' => 'publish',
                'post_type' => 'post',
                'post_date' => $postDate,
                'post_date_gmt' => $postDate,
            ]);

            if (is_wp_error($postId)) {
                continue;
            }
        }
    }

    /**
     * Set default contact data and footer options in theme options.
     */
    private function setDefaultContactData(): void
    {
        if (!function_exists('update_field')) {
            return;
        }

        if (!get_field('company_name', 'option')) {
            update_field('company_name', 'Musterfirma GmbH', 'option');
        }
        if (!get_field('address', 'option')) {
            update_field('address', "Musterstraße 123\n12345 Musterstadt", 'option');
        }
        if (!get_field('phone', 'option')) {
            update_field('phone', '+49 123 456789', 'option');
        }
        if (!get_field('email', 'option')) {
            update_field('email', 'info@example.com', 'option');
        }
        if (!get_field('maps_link', 'option')) {
            update_field('maps_link', 'https://goo.gl/maps/', 'option');
        }

        if (get_field('footer_show_logo', 'option') === null) {
            update_field('footer_show_logo', true, 'option');
        }
        if (get_field('footer_show_company', 'option') === null) {
            update_field('footer_show_company', true, 'option');
        }
        if (get_field('footer_show_nav', 'option') === null) {
            update_field('footer_show_nav', true, 'option');
        }
        if (!get_field('footer_nav_title', 'option')) {
            update_field('footer_nav_title', 'Navigation', 'option');
        }
        if (!get_field('footer_nav_menu', 'option')) {
            update_field('footer_nav_menu', 'footer-menu', 'option');
        }
        if (get_field('footer_show_contact', 'option') === null) {
            update_field('footer_show_contact', true, 'option');
        }
        if (get_field('footer_show_social', 'option') === null) {
            update_field('footer_show_social', true, 'option');
        }
        if (get_field('footer_show_legal', 'option') === null) {
            update_field('footer_show_legal', true, 'option');
        }
    }
}
