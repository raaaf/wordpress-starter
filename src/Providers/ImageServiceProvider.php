<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

/**
 * Image Service Provider
 *
 * Registers custom image sizes optimized for the theme layouts
 * and disables unused WordPress default sizes to save storage.
 */
class ImageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No registrations needed
    }

    public function boot(): void
    {
        $this->registerImageSizes();
        $this->disableUnusedDefaultSizes();
        $this->addImageSizesToDropdown();
    }

    /**
     * Register custom image sizes optimized for theme layouts
     *
     * All sizes are 2x for retina display support.
     */
    private function registerImageSizes(): void
    {
        add_action('after_setup_theme', function (): void {
            // Content images (max-w-4xl = 896px, 2x retina)
            add_image_size('content', 1792, 0, false);

            // Hero split / two-column (50% of max-w-7xl = 640px, 2x retina)
            add_image_size('hero-split', 1280, 0, false);

            // Hero background - full-width background image (2x retina for 1440px screens)
            add_image_size('hero-background', 2880, 0, false);

            // Card thumbnail - 16:9 for posts/video cards (2x retina)
            add_image_size('card-video', 768, 432, true);

            // Gallery thumbnail - square grid display (2x retina)
            add_image_size('gallery-thumb', 800, 800, true);

            // Team portrait - square (2x retina)
            add_image_size('team-portrait', 768, 768, true);

            // Avatar - small square for testimonials (2x retina)
            add_image_size('avatar', 96, 96, true);

            // Logo - constrained height for logo slider (2x retina)
            add_image_size('logo', 256, 128, false);

            // Set default featured image size to card-video (16:9)
            set_post_thumbnail_size(768, 432, true);
        });

        // Limit upload originals to 2400px — prevents storing unnecessarily large source files
        // while preserving enough quality for all generated sizes (max registered: 2880px)
        add_filter('big_image_size_threshold', fn (): int => 2400);

        // Convert generated thumbnails to WebP for ~30% smaller files
        // Requires Imagick or GD with WebP support; original JPEG/PNG is preserved as fallback
        add_filter('image_editor_output_format', function (array $formats): array {
            $formats['image/jpeg'] = 'image/webp';
            $formats['image/png']  = 'image/webp';
            return $formats;
        });

        // Reduce JPEG quality slightly for better compression (default: 82)
        add_filter('jpeg_quality', fn (): int => 80);
        add_filter('wp_editor_set_quality', fn (): int => 80);

        // Fix oversized images inserted via Classic Editor Wysiwyg fields.
        // WordPress does not add a useful sizes attribute to editor-inserted images.
        // This filter replaces missing or oversized default sizes with a content-width hint
        // so the browser picks the right srcset entry instead of downloading the full-size image.
        add_filter('wp_content_img_tag', function (string $tag): string {
            if (!str_contains($tag, 'srcset=')) {
                return $tag;
            }

            $hasSizes     = str_contains($tag, 'sizes=');
            $hasDefaultSizes = str_contains($tag, '2560px');

            if (!$hasSizes || $hasDefaultSizes) {
                // Remove existing sizes attribute if present
                $tag = preg_replace('/\ssizes="[^"]*"/', '', $tag) ?? $tag;
                // Insert before the closing > or />
                $tag = preg_replace('/(\s*\/?>)$/', ' sizes="(max-width: 896px) 100vw, 896px"$1', $tag) ?? $tag;
            }

            return $tag;
        });
    }

    /**
     * Disable WordPress default sizes we don't use to save storage
     */
    private function disableUnusedDefaultSizes(): void
    {
        add_filter('intermediate_image_sizes_advanced', function (array $sizes): array {
            // Keep: thumbnail (WordPress admin), medium_large (fallback), large (fallback)
            // Remove: medium (replaced by team-portrait), 1536x1536, 2048x2048 (not needed)
            unset($sizes['medium']);
            unset($sizes['1536x1536']);
            unset($sizes['2048x2048']);
            return $sizes;
        });
    }

    /**
     * Add custom sizes to media library dropdown for manual selection
     */
    private function addImageSizesToDropdown(): void
    {
        add_filter('image_size_names_choose', function (array $sizes): array {
            return array_merge($sizes, [
                'content' => __('Inhaltsbereich (groß)', 'wp-starter'),
                'hero-split' => __('Hero / Zweispaltig', 'wp-starter'),
                'hero-background' => __('Hero Hintergrund (Vollbild)', 'wp-starter'),
                'card-video' => __('Karte (16:9)', 'wp-starter'),
                'gallery-thumb' => __('Galerie (Quadrat)', 'wp-starter'),
                'team-portrait' => __('Team Portrait', 'wp-starter'),
                'avatar' => __('Avatar', 'wp-starter'),
                'logo' => __('Logo', 'wp-starter'),
            ]);
        });
    }
}
