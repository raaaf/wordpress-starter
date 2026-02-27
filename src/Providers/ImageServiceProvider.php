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

        /*
         * Fix oversized or improperly-sized images inserted via Classic Editor WYSIWYG fields.
         * Case 1: Image already has srcset but a wrong/missing sizes attribute (e.g. "2560px").
         * Case 2: Image has no srcset at all (e.g. inserted as "Full Size" or "Medium") — we
         *         rebuild it using the content size so the browser gets a proper responsive image.
         */
        add_filter('wp_content_img_tag', function (string $tag, string $context, int $attachmentId): string {
            if ($attachmentId <= 0) {
                return $tag;
            }

            // Case 1: has srcset but wrong/missing sizes — fix sizes attribute only.
            if (str_contains($tag, 'srcset=')) {
                $hasSizes        = str_contains($tag, 'sizes=');
                $hasDefaultSizes = str_contains($tag, '2560px');

                if (!$hasSizes || $hasDefaultSizes) {
                    $tag = preg_replace('/\ssizes="[^"]*"/', '', $tag) ?? $tag;
                    $tag = preg_replace('/(\s*\/?>)$/', ' sizes="(max-width: 896px) 100vw, 896px"$1', $tag) ?? $tag;
                }

                return $tag;
            }

            // Case 2: no srcset — rebuild as content-sized responsive image.
            // The editor inserted the full-size or an intermediate size without srcset.
            // Replace the whole img tag with a properly-sized responsive version.
            $rebuilt = wp_get_attachment_image($attachmentId, 'content', false, [
                'class'   => implode(' ', array_filter([
                    self::extractClass($tag),
                    'size-content',
                ])),
                'alt'     => self::extractAttr($tag, 'alt'),
                'loading' => 'lazy',
                'sizes'   => '(max-width: 896px) 100vw, 896px',
            ]);

            return $rebuilt ?: $tag;
        }, 10, 3);
    }

    /**
     * Disable WordPress default sizes we don't use to save storage
     */
    private function disableUnusedDefaultSizes(): void
    {
        add_filter('intermediate_image_sizes_advanced', function (array $sizes): array {
            // Keep: thumbnail (WP admin), medium (300px — used in srcset for small screens),
            // medium_large (768px — srcset fallback), large (1024px — srcset fallback).
            // Remove: 1536x1536, 2048x2048 (not needed).
            unset($sizes['1536x1536']);
            unset($sizes['2048x2048']);
            return $sizes;
        });
    }

    /**
     * Extract the value of a named HTML attribute from an img tag string.
     */
    private static function extractAttr(string $tag, string $attr): string
    {
        preg_match('/' . preg_quote($attr, '/') . '="([^"]*)"/', $tag, $m);
        return $m[1] ?? '';
    }

    /**
     * Extract CSS classes from an img tag, stripping WordPress size/alignment classes
     * that will be re-added by wp_get_attachment_image().
     */
    private static function extractClass(string $tag): string
    {
        preg_match('/class="([^"]*)"/', $tag, $m);
        $classes = preg_split('/\s+/', $m[1] ?? '', -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // Remove WP-generated size/alignment classes; wp_get_attachment_image adds its own.
        $strip = ['alignnone', 'alignleft', 'alignright', 'aligncenter', 'size-full', 'size-medium', 'size-large'];
        $classes = array_filter($classes, fn (string $c): bool => !in_array($c, $strip, true) && !str_starts_with($c, 'wp-image-'));

        return implode(' ', $classes);
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
