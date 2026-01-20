<?php

declare(strict_types=1);

namespace WordpressStarter\Acf;

class Fields
{
    /**
     * Get ACF field value with type safety and default value
     */
    public static function get(string $field, mixed $postId = null, mixed $default = null): mixed
    {
        if (!function_exists('get_field')) {
            return $default;
        }

        $value = get_field($field, $postId);
        
        return $value !== null && $value !== false ? $value : $default;
    }

    /**
     * Get ACF option field value with caching
     */
    public static function option(string $field, mixed $default = null): mixed
    {
        if (!function_exists('get_field')) {
            return $default;
        }

        $cacheKey = 'acf_option_' . $field;
        $cached = wp_cache_get($cacheKey, 'theme');
        
        if ($cached !== false) {
            return $cached;
        }

        $value = get_field($field, 'option');
        $result = $value !== null && $value !== false ? $value : $default;
        
        wp_cache_set($cacheKey, $result, 'theme', HOUR_IN_SECONDS);
        
        return $result;
    }

    /**
     * Get repeater field as collection
     *
     * @return array<int, array<string, mixed>>
     */
    public static function repeater(string $field, mixed $postId = null): array
    {
        $value = self::get($field, $postId, []);

        return is_array($value) ? $value : [];
    }

    /**
     * Get flexible content field
     *
     * @return array<int, array<string, mixed>>
     */
    public static function flexible(string $field, mixed $postId = null): array
    {
        return self::repeater($field, $postId);
    }

    /**
     * Get group field
     *
     * @return array<string, mixed>
     */
    public static function group(string $field, mixed $postId = null): array
    {
        $value = self::get($field, $postId, []);

        return is_array($value) ? $value : [];
    }

    /**
     * Check if field has value
     */
    public static function has(string $field, mixed $postId = null): bool
    {
        if (!function_exists('get_field')) {
            return false;
        }

        $value = get_field($field, $postId);
        
        if (is_array($value)) {
            return !empty($value);
        }
        
        return $value !== null && $value !== false && $value !== '';
    }

    /**
     * Get image field with size
     *
     * @return array{url: string, width: int, height: int, alt: string}|null
     */
    public static function image(string $field, string $size = 'full', mixed $postId = null): ?array
    {
        $imageId = self::get($field, $postId);
        
        if (!$imageId) {
            return null;
        }

        if (is_array($imageId)) {
            return $imageId;
        }

        $image = wp_get_attachment_image_src($imageId, $size);
        
        if (!$image) {
            return null;
        }

        return [
            'url' => $image[0],
            'width' => $image[1],
            'height' => $image[2],
            'alt' => get_post_meta($imageId, '_wp_attachment_image_alt', true),
        ];
    }

    /**
     * Get responsive image HTML with automatic width/height and srcset
     *
     * @param array<string, string> $attr
     */
    public static function responsiveImage(string $field, string $size = 'full', array $attr = [], mixed $postId = null): string
    {
        $imageId = self::get($field, $postId);

        if (!$imageId || is_array($imageId)) {
            return '';
        }

        // Ensure loading="lazy" is set by default for CLS optimization
        $attr = array_merge(['loading' => 'lazy', 'decoding' => 'async'], $attr);

        return wp_get_attachment_image($imageId, $size, false, $attr);
    }

    /**
     * Get picture element with WebP support
     * Falls back to original format if WebP not available
     *
     * @param array<string, string> $attr
     */
    public static function pictureWebP(string $field, string $size = 'full', array $attr = [], mixed $postId = null): string
    {
        $imageId = self::get($field, $postId);

        if (!$imageId || is_array($imageId)) {
            return '';
        }

        $image = wp_get_attachment_image_src($imageId, $size);
        if (!$image) {
            return '';
        }

        $alt = get_post_meta($imageId, '_wp_attachment_image_alt', true);
        $srcset = wp_get_attachment_image_srcset($imageId, $size);
        $sizes = wp_get_attachment_image_sizes($imageId, $size);

        // Build attributes string
        $attrString = '';
        $defaultAttr = [
            'loading' => 'lazy',
            'decoding' => 'async',
            'width' => $image[1],
            'height' => $image[2],
            'alt' => esc_attr($alt ?: ''),
        ];

        foreach (array_merge($defaultAttr, $attr) as $key => $value) {
            $attrString .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }

        // Check if WebP version exists (WordPress 5.8+ generates WebP)
        $webpSrcset = self::getWebPSrcset($imageId, $size);

        $html = '<picture>';

        if ($webpSrcset) {
            $html .= sprintf(
                '<source type="image/webp" srcset="%s"%s>',
                esc_attr($webpSrcset),
                $sizes ? sprintf(' sizes="%s"', esc_attr($sizes)) : ''
            );
        }

        $html .= sprintf(
            '<img src="%s"%s%s%s>',
            esc_url($image[0]),
            $srcset ? sprintf(' srcset="%s"', esc_attr($srcset)) : '',
            $sizes ? sprintf(' sizes="%s"', esc_attr($sizes)) : '',
            $attrString
        );

        $html .= '</picture>';

        return $html;
    }

    /**
     * Get WebP srcset if available
     */
    private static function getWebPSrcset(int $imageId, string $size): string
    {
        $metadata = wp_get_attachment_metadata($imageId);

        if (!$metadata || empty($metadata['sizes'])) {
            return '';
        }

        // Check if WordPress has generated WebP versions (5.8+)
        $uploadDir = wp_get_upload_dir();
        $basePath = trailingslashit($uploadDir['basedir']) . dirname($metadata['file']) . '/';
        $baseUrl = trailingslashit($uploadDir['baseurl']) . dirname($metadata['file']) . '/';

        $webpSrcset = [];

        foreach ($metadata['sizes'] as $sizeName => $sizeData) {
            $webpFile = preg_replace('/\.(jpe?g|png)$/i', '.webp', $sizeData['file']);

            if (file_exists($basePath . $webpFile)) {
                $webpSrcset[] = $baseUrl . $webpFile . ' ' . $sizeData['width'] . 'w';
            }
        }

        return implode(', ', $webpSrcset);
    }

    /**
     * Get link field
     *
     * @return array{url: string, title: string, target: string}|null
     */
    public static function link(string $field, mixed $postId = null): ?array
    {
        $link = self::get($field, $postId);
        
        if (!is_array($link) || empty($link['url'])) {
            return null;
        }

        return [
            'url' => $link['url'] ?? '',
            'title' => $link['title'] ?? '',
            'target' => $link['target'] ?? '_self',
        ];
    }

    /**
     * Output link HTML
     */
    public static function linkHtml(string $field, string $class = '', mixed $postId = null): string
    {
        $link = self::link($field, $postId);
        
        if (!$link) {
            return '';
        }

        $attributes = [
            'href' => esc_url($link['url']),
            'target' => esc_attr($link['target']),
        ];
        
        if ($class) {
            $attributes['class'] = esc_attr($class);
        }
        
        if ($link['target'] === '_blank') {
            $attributes['rel'] = 'noopener noreferrer';
        }

        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= " {$key}=\"{$value}\"";
        }

        return sprintf('<a%s>%s</a>', $attrString, esc_html($link['title']));
    }
}
