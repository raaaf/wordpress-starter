<?php

declare(strict_types=1);

namespace StiftungsNavigatorGmbH\Acf;

class Blocks
{
    /**
     * Plugin requirements mapping
     * Maps requirement keys to their check functions/classes
     *
     * @var array<string, array{type: string, check: string, name: string}>
     */
    private const PLUGIN_REQUIREMENTS = [
        'contact-form-7' => [
            'type' => 'class',
            'check' => 'WPCF7',
            'name' => 'Contact Form 7',
        ],
        'woocommerce' => [
            'type' => 'class',
            'check' => 'WooCommerce',
            'name' => 'WooCommerce',
        ],
        'acf-pro' => [
            'type' => 'function',
            'check' => 'acf_add_local_field_group',
            'name' => 'Advanced Custom Fields PRO',
        ],
    ];

    /**
     * Register ACF Gutenberg blocks
     */
    public static function register(): void
    {
        if (!function_exists('acf_register_block_type')) {
            return;
        }

        // Register block categories
        add_filter('block_categories_all', [self::class, 'registerCategories'], 10, 2);

        // Force edit mode for all ACF blocks via filter (most reliable method)
        add_filter('acf/register_block_type_args', [self::class, 'forceEditMode']);

        // ACF Extended: Dynamic block titles based on field values
        if (class_exists('ACFE')) {
            add_filter('acfe/block_title', [self::class, 'getBlockTitle'], 10, 3);
        }

        // Auto-discover and register blocks
        $blocksDir = get_template_directory() . '/blocks';

        if (!is_dir($blocksDir)) {
            return;
        }

        $blocks = glob($blocksDir . '/*/block.json');

        foreach ($blocks as $blockConfig) {
            $blockData = json_decode(file_get_contents($blockConfig), true);

            if (!$blockData) {
                continue;
            }

            $blockDir = dirname($blockConfig);
            $blockName = basename($blockDir);

            // Check plugin requirements
            if (!self::checkRequirements($blockData)) {
                continue;
            }

            // Determine the best category for this block
            $autoCategory = self::getBlockCategory($blockName);

            // Merge with defaults - auto-assign category if not specified in block.json
            $block = array_merge([
                'name' => $blockName,
                'title' => ucfirst(str_replace('-', ' ', $blockName)),
                'render_callback' => [self::class, 'renderBlock'],
                'category' => $autoCategory,
                'icon' => self::getBlockIcon($blockName),
                'keywords' => self::getBlockKeywords($blockName),
            ], $blockData);

            // Force edit mode for all blocks to avoid CSS conflicts in Gutenberg iframe
            // This MUST be set after merge to override any block.json settings
            $block['mode'] = 'edit';

            // Merge supports, keeping block.json values but forcing mode off
            $block['supports'] = array_merge(
                ['align' => true, 'jsx' => true],
                $block['supports'] ?? [],
                ['mode' => false] // Force disable mode switching - must be last
            );

            // Only override category if not explicitly set in block.json
            if (empty($blockData['category']) || $blockData['category'] === 'theme') {
                $block['category'] = $autoCategory;
            }

            acf_register_block_type($block);
        }
    }

    /**
     * Check if block requirements are met
     *
     * @param array<string, mixed> $blockData Block configuration data
     * @return bool True if all requirements are met
     */
    private static function checkRequirements(array $blockData): bool
    {
        // No requirements specified - always register
        if (empty($blockData['requires'])) {
            return true;
        }

        $requires = (array) $blockData['requires'];

        foreach ($requires as $requirement) {
            if (!self::isRequirementMet($requirement)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a single requirement is met
     *
     * @param string $requirement Requirement key from PLUGIN_REQUIREMENTS
     * @return bool True if requirement is met
     */
    private static function isRequirementMet(string $requirement): bool
    {
        // Check predefined requirements
        if (isset(self::PLUGIN_REQUIREMENTS[$requirement])) {
            $req = self::PLUGIN_REQUIREMENTS[$requirement];

            if ($req['type'] === 'class') {
                return class_exists($req['check']);
            }

            // @phpstan-ignore-next-line (dynamic requirement checking - const structure is intentionally extensible)
            if ($req['type'] === 'function') {
                // @phpstan-ignore-next-line
                return function_exists($req['check']);
            }
        }

        // Allow custom class checks (class:ClassName)
        if (str_starts_with($requirement, 'class:')) {
            return class_exists(substr($requirement, 6));
        }

        // Allow custom function checks (function:function_name)
        if (str_starts_with($requirement, 'function:')) {
            return function_exists(substr($requirement, 9));
        }

        // Unknown requirement type - skip block to be safe
        return false;
    }

    /**
     * Get list of missing requirements for a block
     *
     * @param array<string, mixed> $blockData Block configuration data
     * @return array<string> List of missing plugin names
     */
    public static function getMissingRequirements(array $blockData): array
    {
        $missing = [];

        if (empty($blockData['requires'])) {
            return $missing;
        }

        $requires = (array) $blockData['requires'];

        foreach ($requires as $requirement) {
            if (!self::isRequirementMet($requirement)) {
                $missing[] = self::PLUGIN_REQUIREMENTS[$requirement]['name'] ?? $requirement;
            }
        }

        return $missing;
    }

    /**
     * Render block using Blade template
     *
     * @param array{name: string, align?: string, className?: string, anchor?: string, mode?: string, id?: string} $block
     */
    public static function renderBlock(array $block, string $content = '', bool $isPreview = false, int $postId = 0): void
    {
        try {
            $blade = getBladeViewFactory();

            if (!$blade) {
                self::renderError('Blade not initialized', $isPreview);
                return;
            }

            // Block data
            $blockName = str_replace('acf/', '', $block['name']);
            $templatePath = "{$blockName}.template";

            // Check if template exists
            if (!$blade->exists($templatePath)) {
                self::renderError("Template not found: {$templatePath}", $isPreview);
                return;
            }

            // Prepare data for template
            // Use get_fields() from postmeta, fallback to block data attribute (for programmatic blocks)
            $fields = get_fields();
            if (empty($fields) && !empty($block['data'])) {
                $fields = $block['data'];
            }

            $data = [
                'block' => $block,
                'fields' => $fields ?: [],
                'is_preview' => $isPreview,
                'post_id' => $postId,
                'classes' => self::getBlockClasses($block),
                'anchor' => $block['anchor'] ?? '',
                'content' => $content, // InnerBlocks content
                'wrapper_attributes' => self::getBlockWrapperAttributes($block),
            ];

            // Render template - Blade handles escaping via {{ }} syntax
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Blade template handles escaping
            echo $blade->make($templatePath, $data)->render();
        } catch (\Throwable $e) {
            self::renderError($e->getMessage(), $isPreview);

            // Log error in debug mode
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('WP-Starter Block Error [' . ( $block['name'] ?? 'unknown' ) . ']: ' . $e->getMessage());
            }
        }
    }

    /**
     * Render an error message for block rendering failures.
     *
     * @param string $message Error message to display
     * @param bool $isPreview Whether the block is being rendered in preview mode
     */
    private static function renderError(string $message, bool $isPreview): void
    {
        // Always output HTML comment for debugging
        echo '<!-- Block Error: ' . esc_html($message) . ' -->';

        // Show visible error in preview mode or when debug is enabled
        if ($isPreview || ( defined('WP_DEBUG') && WP_DEBUG )) {
            echo '<div class="acf-block-error" style="padding: 1rem; background: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; border-radius: 4px; margin: 0.5rem 0;">';
            echo '<strong>Block Error:</strong> ' . esc_html($message);
            echo '</div>';
        }
    }

    /**
     * Get block wrapper attributes for Gutenberg integration
     * Uses acf_block_wrapper_attributes() if available (ACF 6.0+)
     *
     * @param array{name: string, align?: string, className?: string, anchor?: string, id?: string, style?: array<string, mixed>, backgroundColor?: string, textColor?: string} $block
     * @return string HTML attributes string
     */
    public static function getBlockWrapperAttributes(array $block): string
    {
        // Use ACF's native function if available (ACF 6.0+)
        if (function_exists('acf_block_wrapper_attributes')) {
            return acf_block_wrapper_attributes([
                'class' => self::getBlockClasses($block),
            ]);
        }

        // Fallback for older ACF versions
        $attributes = [];

        // ID/Anchor
        if (!empty($block['anchor'])) {
            $attributes['id'] = esc_attr($block['anchor']);
        }

        // Classes
        $classes = self::getBlockClasses($block);

        // Background color (Gutenberg color palette)
        if (!empty($block['backgroundColor'])) {
            $classes .= ' has-' . esc_attr($block['backgroundColor']) . '-background-color has-background';
        }

        // Text color (Gutenberg color palette)
        if (!empty($block['textColor'])) {
            $classes .= ' has-' . esc_attr($block['textColor']) . '-color has-text-color';
        }

        $attributes['class'] = $classes;

        // Inline styles from Gutenberg
        if (!empty($block['style'])) {
            $styles = self::parseBlockStyles($block['style']);
            if ($styles) {
                $attributes['style'] = $styles;
            }
        }

        // Build attributes string
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }

        return trim($attrString);
    }

    /**
     * Parse Gutenberg block styles to CSS string
     *
     * @param array<string, mixed> $styles
     */
    private static function parseBlockStyles(array $styles): string
    {
        $css = [];

        // Spacing (padding/margin)
        if (!empty($styles['spacing'])) {
            foreach (['padding', 'margin'] as $property) {
                if (!empty($styles['spacing'][$property])) {
                    foreach ($styles['spacing'][$property] as $side => $value) {
                        if ($value) {
                            $css[] = "{$property}-{$side}: " . self::getCssValue($value);
                        }
                    }
                }
            }
        }

        // Typography
        if (!empty($styles['typography'])) {
            if (!empty($styles['typography']['fontSize'])) {
                $css[] = 'font-size: ' . self::getCssValue($styles['typography']['fontSize']);
            }
            if (!empty($styles['typography']['lineHeight'])) {
                $css[] = 'line-height: ' . $styles['typography']['lineHeight'];
            }
        }

        // Color
        if (!empty($styles['color'])) {
            if (!empty($styles['color']['background'])) {
                $css[] = 'background-color: ' . $styles['color']['background'];
            }
            if (!empty($styles['color']['text'])) {
                $css[] = 'color: ' . $styles['color']['text'];
            }
        }

        return implode('; ', $css);
    }

    /**
     * Convert CSS preset values to actual CSS values
     */
    private static function getCssValue(string $value): string
    {
        // Handle CSS preset values like "var:preset|spacing|50"
        if (str_starts_with($value, 'var:')) {
            $parts = explode('|', substr($value, 4));
            if (count($parts) === 3) {
                return "var(--wp--preset--{$parts[0]}--{$parts[2]})";
            }
        }

        return $value;
    }

    /**
     * Render InnerBlocks placeholder for use in templates
     * Use this in block templates that support nested blocks
     *
     * @param array<string, mixed> $args InnerBlocks arguments
     * @return string InnerBlocks markup
     */
    public static function renderInnerBlocks(array $args = []): string
    {
        $defaults = [
            'allowedBlocks' => null,
            'template' => null,
            'templateLock' => false,
            'renderAppender' => true,
        ];

        $args = array_merge($defaults, $args);

        // Build InnerBlocks tag for JSX mode
        $attributes = [];

        if ($args['allowedBlocks'] !== null) {
            $attributes['allowedBlocks'] = wp_json_encode($args['allowedBlocks']);
        }

        if ($args['template'] !== null) {
            $attributes['template'] = wp_json_encode($args['template']);
        }

        if ($args['templateLock']) {
            $attributes['templateLock'] = esc_attr($args['templateLock']);
        }

        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= sprintf(' %s=\'%s\'', $key, $value);
        }

        return sprintf('<InnerBlocks%s />', $attrString);
    }

    /**
     * Check if current block supports InnerBlocks
     *
     * @param array<string, mixed> $block
     */
    public static function supportsInnerBlocks(array $block): bool
    {
        return !empty($block['supports']['jsx']);
    }

    /**
     * Get block classes
     *
     * @param array{name: string, align?: string, className?: string} $block
     */
    public static function getBlockClasses(array $block): string
    {
        $classes = ['acf-block'];

        // Block name
        $blockName = str_replace('acf/', '', $block['name']);
        $classes[] = "block-{$blockName}";

        // Alignment
        if (!empty($block['align'])) {
            $classes[] = "align{$block['align']}";
        }

        // Custom classes
        if (!empty($block['className'])) {
            $classes[] = $block['className'];
        }

        return implode(' ', $classes);
    }

    /**
     * Force edit mode for all ACF blocks
     *
     * This filter runs on ALL ACF block registrations and ensures
     * edit mode is enforced regardless of block.json settings.
     *
     * @param array<string, mixed> $args Block registration arguments
     * @return array<string, mixed>
     */
    public static function forceEditMode(array $args): array
    {
        // Force edit mode - no preview rendering
        $args['mode'] = 'edit';

        // Disable mode switching in supports
        if (!isset($args['supports'])) {
            $args['supports'] = [];
        }
        $args['supports']['mode'] = false;

        return $args;
    }

    /**
     * Generate dynamic block title from field values (ACF Extended)
     *
     * This makes blocks easier to identify in the editor by showing
     * the actual content (like heading text) instead of just "Hero".
     *
     * @param string $title The default block title
     * @param array<string, mixed> $block Block data including 'data' with field values
     * @param int $postId The post ID
     * @return string The dynamic block title
     */
    public static function getBlockTitle(string $title, array $block, int $postId): string
    {
        // Block data contains field values in the 'data' key
        $data = $block['data'] ?? [];
        if (empty($data)) {
            return $title;
        }

        // Priority list of fields to use as title
        $titleFields = [
            'title',
            'heading',
            'headline',
            'name',
            'label',
            'text',
            'question', // for accordion items
        ];

        foreach ($titleFields as $fieldName) {
            if (!empty($data[$fieldName]) && is_string($data[$fieldName])) {
                $fieldTitle = wp_strip_all_tags($data[$fieldName]);
                // Truncate long titles
                if (mb_strlen($fieldTitle) > 50) {
                    $fieldTitle = mb_substr($fieldTitle, 0, 47) . '...';
                }
                return $title . ': ' . $fieldTitle;
            }
        }

        // Check for nested title in first repeater item (e.g., accordion items)
        foreach ($data as $key => $value) {
            // Skip ACF internal keys
            if (str_starts_with($key, '_') || $key === 'field_') {
                continue;
            }
            if (is_array($value) && !empty($value[0]) && is_array($value[0])) {
                foreach ($titleFields as $nestedField) {
                    if (!empty($value[0][$nestedField]) && is_string($value[0][$nestedField])) {
                        $fieldTitle = wp_strip_all_tags($value[0][$nestedField]);
                        if (mb_strlen($fieldTitle) > 50) {
                            $fieldTitle = mb_substr($fieldTitle, 0, 47) . '...';
                        }
                        return $title . ': ' . $fieldTitle;
                    }
                }
            }
        }

        return $title;
    }

    /**
     * Register custom block categories
     *
     * Places theme block categories at the TOP of the inserter for maximum visibility.
     * Categories are ordered by frequency of use.
     *
     * @param array<int, array{slug: string, title: string, icon?: string}> $categories
     * @return array<int, array{slug: string, title: string, icon?: string}>
     */
    public static function registerCategories(array $categories, \WP_Block_Editor_Context $context): array
    {
        // Theme categories - placed FIRST in the inserter
        $themeCategories = [
            [
                'slug' => 'theme-layout',
                'title' => '📐 ' . __('Layout', 'stiftungs-navigator-gmbh'),
                'icon' => 'columns',
            ],
            [
                'slug' => 'theme-content',
                'title' => '📝 ' . __('Inhalte', 'stiftungs-navigator-gmbh'),
                'icon' => 'editor-alignleft',
            ],
            [
                'slug' => 'theme-media',
                'title' => '🖼️ ' . __('Medien', 'stiftungs-navigator-gmbh'),
                'icon' => 'format-gallery',
            ],
            [
                'slug' => 'theme-interactive',
                'title' => '✨ ' . __('Interaktiv', 'stiftungs-navigator-gmbh'),
                'icon' => 'star-filled',
            ],
            [
                'slug' => 'theme',
                'title' => '🎨 ' . __('Theme Blocks', 'stiftungs-navigator-gmbh'),
                'icon' => 'layout',
            ],
        ];

        return array_merge($themeCategories, $categories);
    }

    /**
     * Get the appropriate category for a block based on its type
     *
     * @param string $blockName The block name (without acf/ prefix)
     * @return string The category slug
     */
    public static function getBlockCategory(string $blockName): string
    {
        // Layout blocks
        $layoutBlocks = [
            'one-column', 'two-columns', 'three-columns', 'four-columns',
            'one-third-two-thirds', 'two-thirds-one-third', 'two-columns-images',
            'divider',
        ];

        // Content blocks
        $contentBlocks = [
            'hero', 'cta', 'accordion', 'cards', 'testimonials', 'team',
            'pricing-table', 'stats', 'timeline', 'table', 'posts',
        ];

        // Media blocks
        $mediaBlocks = [
            'image', 'video', 'gallery', 'logo-slider', 'before-after',
        ];

        // Interactive blocks
        $interactiveBlocks = [
            'tabs', 'contact-form', 'map',
        ];

        if (in_array($blockName, $layoutBlocks, true)) {
            return 'theme-layout';
        }

        if (in_array($blockName, $contentBlocks, true)) {
            return 'theme-content';
        }

        if (in_array($blockName, $mediaBlocks, true)) {
            return 'theme-media';
        }

        if (in_array($blockName, $interactiveBlocks, true)) {
            return 'theme-interactive';
        }

        return 'theme';
    }

    /**
     * Get an appropriate icon for a block based on its type
     *
     * @param string $blockName The block name (without acf/ prefix)
     * @return string Dashicon name
     */
    public static function getBlockIcon(string $blockName): string
    {
        $icons = [
            // Layout
            'one-column' => 'align-center',
            'two-columns' => 'columns',
            'three-columns' => 'grid-view',
            'four-columns' => 'screenoptions',
            'one-third-two-thirds' => 'align-pull-left',
            'two-thirds-one-third' => 'align-pull-right',
            'two-columns-images' => 'format-gallery',
            'divider' => 'minus',

            // Content
            'hero' => 'superhero-alt',
            'cta' => 'megaphone',
            'accordion' => 'list-view',
            'cards' => 'grid-view',
            'testimonials' => 'format-quote',
            'team' => 'groups',
            'pricing-table' => 'money-alt',
            'stats' => 'chart-bar',
            'timeline' => 'backup',
            'table' => 'editor-table',
            'posts' => 'admin-post',

            // Media
            'image' => 'format-image',
            'video' => 'video-alt3',
            'gallery' => 'images-alt2',
            'logo-slider' => 'slides',
            'before-after' => 'image-flip-horizontal',

            // Interactive
            'tabs' => 'category',
            'contact-form' => 'email-alt',
            'map' => 'location-alt',
        ];

        return $icons[$blockName] ?? 'block-default';
    }

    /**
     * Get relevant keywords for a block to improve search
     *
     * @param string $blockName The block name (without acf/ prefix)
     * @return array<string> Keywords in German
     */
    public static function getBlockKeywords(string $blockName): array
    {
        $keywords = [
            // Layout
            'one-column' => ['spalte', 'layout', 'zentral', 'column'],
            'two-columns' => ['spalten', 'layout', '2', 'zweispaltig', 'columns'],
            'three-columns' => ['spalten', 'layout', '3', 'dreispaltig', 'columns'],
            'four-columns' => ['spalten', 'layout', '4', 'vierspaltig', 'columns'],
            'one-third-two-thirds' => ['spalten', 'asymmetrisch', 'sidebar'],
            'two-thirds-one-third' => ['spalten', 'asymmetrisch', 'sidebar'],
            'two-columns-images' => ['bild', 'text', 'spalten', 'media'],
            'divider' => ['trenner', 'abstand', 'linie', 'spacer'],

            // Content
            'hero' => ['header', 'banner', 'kopf', 'teaser', 'intro'],
            'cta' => ['button', 'aktion', 'call to action', 'aufforderung'],
            'accordion' => ['faq', 'aufklappen', 'fragen', 'antworten'],
            'cards' => ['karten', 'features', 'boxen', 'icons'],
            'testimonials' => ['referenzen', 'zitate', 'kunden', 'bewertungen'],
            'team' => ['mitarbeiter', 'personen', 'über uns', 'kontakt'],
            'pricing-table' => ['preise', 'tarife', 'pakete', 'kosten'],
            'stats' => ['zahlen', 'statistik', 'counter', 'daten'],
            'timeline' => ['zeitstrahl', 'historie', 'verlauf', 'chronik'],
            'table' => ['tabelle', 'daten', 'liste', 'übersicht'],
            'posts' => ['blog', 'artikel', 'beiträge', 'news'],

            // Media
            'image' => ['bild', 'foto', 'grafik', 'picture'],
            'video' => ['film', 'youtube', 'vimeo', 'media'],
            'gallery' => ['galerie', 'bilder', 'fotos', 'lightbox'],
            'logo-slider' => ['partner', 'kunden', 'logos', 'carousel'],
            'before-after' => ['vergleich', 'vorher', 'nachher', 'slider'],

            // Interactive
            'tabs' => ['reiter', 'register', 'navigation', 'wechseln'],
            'contact-form' => ['kontakt', 'formular', 'email', 'nachricht'],
            'map' => ['karte', 'standort', 'google', 'anfahrt'],
        ];

        return $keywords[$blockName] ?? [$blockName];
    }

    /**
     * Create block template structure
     */
    public static function createBlockScaffold(string $blockName): bool
    {
        $blocksDir = get_template_directory() . '/blocks';
        $blockDir = $blocksDir . '/' . $blockName;
        
        if (is_dir($blockDir)) {
            return false;
        }

        // Create directory
        wp_mkdir_p($blockDir);
        
        // Create block.json
        $blockJson = [
            'name' => $blockName,
            'title' => ucfirst(str_replace('-', ' ', $blockName)),
            'description' => '',
            'category' => 'theme',
            'icon' => 'block-default',
            'keywords' => [],
        ];
        
        file_put_contents($blockDir . '/block.json', json_encode($blockJson, JSON_PRETTY_PRINT));
        
        // Create template
        $template = <<<'BLADE'
@php
    $classes = $classes ?? '';
    $anchor = $anchor ?? '';
@endphp

<div class="{{ $classes }}" @if($anchor) id="{{ $anchor }}" @endif>
    @if($is_preview)
        <p>{{ __('Block preview', 'stiftungs-navigator-gmbh') }}</p>
    @else
        {{-- Block content here --}}
    @endif
</div>
BLADE;
        
        file_put_contents($blockDir . '/template.blade.php', $template);
        
        return true;
    }
}
