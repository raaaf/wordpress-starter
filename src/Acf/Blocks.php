<?php

declare(strict_types=1);

namespace WordpressStarter\Acf;

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

            // Merge with defaults
            $block = array_merge([
                'name' => $blockName,
                'title' => ucfirst(str_replace('-', ' ', $blockName)),
                'render_callback' => [self::class, 'renderBlock'],
                'category' => 'theme',
                'icon' => 'block-default',
                'keywords' => [],
                'supports' => [
                    'align' => true,
                    'mode' => true,
                    'jsx' => true,
                ],
            ], $blockData);

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

            if ($req['type'] === 'function') {
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
        $blade = getBladeViewFactory();
        
        if (!$blade) {
            echo '<!-- Blade not initialized -->';
            return;
        }

        // Block data
        $blockName = str_replace('acf/', '', $block['name']);
        $templatePath = "blocks.{$blockName}.template";
        
        // Check if template exists
        if (!$blade->exists($templatePath)) {
            echo "<!-- Block template not found: {$templatePath} -->";
            return;
        }

        // Prepare data for template
        $data = [
            'block' => $block,
            'fields' => get_fields() ?: [],
            'is_preview' => $isPreview,
            'post_id' => $postId,
            'classes' => self::getBlockClasses($block),
            'anchor' => $block['anchor'] ?? '',
        ];

        // Render template
        echo $blade->make($templatePath, $data)->render();
    }

    /**
     * Get block classes
     *
     * @param array{name: string, align?: string, className?: string} $block
     */
    private static function getBlockClasses(array $block): string
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
     * Register custom block categories
     *
     * @param array<int, array{slug: string, title: string, icon?: string}> $categories
     * @return array<int, array{slug: string, title: string, icon?: string}>
     */
    public static function registerCategories(array $categories, \WP_Block_Editor_Context $context): array
    {
        return array_merge([
            [
                'slug' => 'theme',
                'title' => __('Theme Blocks', 'wp-starter'),
                'icon' => 'layout',
            ],
        ], $categories);
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
            'supports' => [
                'align' => true,
                'mode' => true,
                'jsx' => true,
            ],
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
        <p>{{ __('Block preview', 'wp-starter') }}</p>
    @else
        {{-- Block content here --}}
    @endif
</div>
BLADE;
        
        file_put_contents($blockDir . '/template.blade.php', $template);
        
        return true;
    }
}