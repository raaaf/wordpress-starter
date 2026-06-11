<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

/**
 * Theme Service Provider
 *
 * Core theme bootstrap: registers theme supports, caches the current theme
 * version, and wires up the Blade template routing layer. All other concerns
 * (media, asset optimisation, editor integration, branding) live in their
 * own dedicated providers.
 */
class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->setupThemeVersion();
    }

    public function boot(): void
    {
        $this->addThemeSupport();
        $this->addTemplateFilter();
        $this->registerBladePageTemplates();
    }

    private function setupThemeVersion(): void
    {
        $transientKey = \WordpressStarter\ThemeContext::prefix() . '_theme_version';
        if (!get_transient($transientKey)) {
            $theme_version = wp_get_theme()->get('Version');
            set_transient($transientKey, $theme_version, DAY_IN_SECONDS);
        }
    }

    private function addThemeSupport(): void
    {
        add_action('after_setup_theme', function (): void {
            add_theme_support('title-tag');
            add_theme_support('custom-logo');
            add_theme_support('html5', [
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
            ]);
            add_theme_support('post-thumbnails');
            add_theme_support('align-wide');
        });
    }

    private function addTemplateFilter(): void
    {
        add_filter('template_include', function (string $template): string {
            $blade = $GLOBALS['blade'] ?? null;

            if (!$blade) {
                return $template;
            }

            // Try to determine the best template based on WordPress context
            $templateName = $this->getBladeTemplateName($template);

            if ($blade->exists($templateName)) {
                $GLOBALS['template_name'] = $templateName;

                return get_template_directory() . '/config/index.php';
            }

            return $template;
        });
    }

    /**
     * Register Blade page templates with WordPress
     *
     * Scans templates directory for .blade.php files with "Template Name:" comment
     * and registers them so they appear in the page template dropdown.
     * Uses transient caching to avoid filesystem operations on every request.
     */
    private function registerBladePageTemplates(): void
    {
        add_filter('theme_page_templates', function (array $templates): array {
            // Try to get cached template list first
            $cacheKey = 'blade_page_templates_' . wp_get_theme()->get('Version');
            $cachedTemplates = get_transient($cacheKey);

            if ($cachedTemplates !== false && is_array($cachedTemplates)) {
                return array_merge($templates, $cachedTemplates);
            }

            $templatesDir = get_template_directory() . '/templates';

            if (!is_dir($templatesDir)) {
                return $templates;
            }

            $bladeFiles = glob($templatesDir . '/page-*.blade.php');
            if (!$bladeFiles) {
                return $templates;
            }

            $foundTemplates = [];
            foreach ($bladeFiles as $file) {
                $filename = basename($file);
                // Skip if already registered
                if (isset($templates[$filename])) {
                    continue;
                }

                // Read file and extract Template Name from comment
                $content = file_get_contents($file);
                if ($content && preg_match('/Template Name:\s*(.+)/i', $content, $matches)) {
                    $foundTemplates[$filename] = trim($matches[1]);
                }
            }

            // Cache for 7 days (invalidated by theme version in cache key)
            set_transient($cacheKey, $foundTemplates, WEEK_IN_SECONDS);

            return array_merge($templates, $foundTemplates);
        });
    }

    /**
     * Determine the Blade template name based on WordPress context
     */
    private function getBladeTemplateName(string $wpTemplate): string
    {
        $blade = $GLOBALS['blade'] ?? null;

        if (!$blade) {
            return wp_basename($wpTemplate, '.php');
        }

        // Check for page templates (custom template, page-{slug}, page-{id}, page)
        if (is_page()) {
            $pageId = get_queried_object_id();
            $pageSlug = get_queried_object()->post_name ?? '';

            // Check for custom page template (set via _wp_page_template meta)
            $customTemplate = get_page_template_slug($pageId);
            if ($customTemplate) {
                // Remove .blade.php or .php extension to get template name
                $templateName = wp_basename($customTemplate, '.blade.php');
                $templateName = wp_basename($templateName, '.php');
                if ($blade->exists($templateName)) {
                    return $templateName;
                }
            }

            // Check for page-{slug}.blade.php
            if ($pageSlug && $blade->exists("page-{$pageSlug}")) {
                return "page-{$pageSlug}";
            }
            // Check for page-{id}.blade.php
            if ($pageId && $blade->exists("page-{$pageId}")) {
                return "page-{$pageId}";
            }
            // Check for page.blade.php
            if ($blade->exists('page')) {
                return 'page';
            }
        }

        // Check for single post templates (single-{post_type}, single)
        if (is_single()) {
            $postType = get_post_type();

            // Check for single-{post_type}.blade.php
            if ($postType && $blade->exists("single-{$postType}")) {
                return "single-{$postType}";
            }
            // Check for single.blade.php
            if ($blade->exists('single')) {
                return 'single';
            }
        }

        // Check for archive templates
        if (is_archive()) {
            if (is_category() && $blade->exists('category')) {
                return 'category';
            }
            if (is_tag() && $blade->exists('tag')) {
                return 'tag';
            }
            if (is_author() && $blade->exists('author')) {
                return 'author';
            }
            if ($blade->exists('archive')) {
                return 'archive';
            }
        }

        // Check for search template
        if (is_search() && $blade->exists('search')) {
            return 'search';
        }

        // Check for 404 template
        if (is_404() && $blade->exists('404')) {
            return '404';
        }

        // Check for front page template
        if (is_front_page() && $blade->exists('front-page')) {
            return 'front-page';
        }

        // Check for home (blog) template
        if (is_home() && $blade->exists('home')) {
            return 'home';
        }

        // Fall back to extracting name from WordPress template
        $templateName = wp_basename($wpTemplate, '.php');
        $templateName = wp_basename($templateName, '.blade');

        return $templateName;
    }
}
