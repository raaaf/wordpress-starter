<?php

declare(strict_types=1);

namespace WordpressStarter\Mcp;

use WordpressStarter\Acf\FieldDefinitions;
use WordpressStarter\Acf\FlexibleContent;
use WordpressStarter\Helpers\DesignTokenFiles;
use WordpressStarter\Providers\AcfServiceProvider;
use WordpressStarter\Providers\DesignTokenRestProvider;
use WordpressStarter\Providers\ServiceProvider;
use WordpressStarter\Security;
use WordpressStarter\Services\StyleguidePage;

/**
 * MCP contract provider.
 *
 * A separate plugin lets Claude read and write page content over MCP. It
 * knows nothing about this theme's ACF structure; it asks for it through the
 * filter `wp_starter_mcp_contract` and gets this array (version 1):
 *
 * - version         int, additive: new keys may appear, existing ones keep their meaning
 * - page_field      ACF name of the page builder field (page_sections)
 * - page_field_key  its field key (field_page_sections), for update_field()
 * - writable        layout names the plugin may write (explicit allowlist)
 * - copy_meta       post meta keys to copy along when a page is duplicated
 * - layouts         callable(): array, the registered layouts (lazy, see layouts())
 * - is_readable     callable(int $postId): bool
 * - after_write     callable(int $postId): void, run after the plugin stored page_sections
 * - is_allowed_embed_host  callable(string $hostOrUrl): bool
 * - design_tokens   callable(): array, the site's colours and the token file endpoint
 * - validate_sections      callable(array $sections): array of problems, empty when valid
 *
 * Non-invasive by design: the filter only answers when the plugin asks, and
 * the plugin only gets callables, so the theme keeps every decision.
 */
class ContractProvider extends ServiceProvider
{
    /**
     * Literal on purpose, not built from ThemeContext: the plugin looks for
     * exactly this name in every theme forked from the starter, and bin/setup.php
     * only rewrites the quoted text domain 'wp-starter', not this string.
     */
    public const FILTER = 'wp_starter_mcp_contract';

    public const VERSION = 1;

    /**
     * Layouts the plugin may write. Explicit, so a new layout stays read-only
     * until it is added here. Left out: forms and member-area modules
     * (newsletter, contact_form: wired to plugins and consent; member_downloads,
     * download_list: content behind the member login).
     *
     * @var array<int, string>
     */
    public const WRITABLE_LAYOUTS = [
        'hero',
        'one_column',
        'two_columns',
        'one_column_image',
        'two_columns_images',
        'three_columns_images',
        'four_columns_images',
        'three_columns',
        'four_columns',
        'one_third_two_thirds',
        'two_thirds_one_third',
        'accordion',
        'tabs',
        'cta',
        'button',
        'image',
        'video',
        'gallery',
        'before_after',
        'testimonials',
        'cards',
        'stats',
        'timeline',
        'team',
        'events',
        'pricing_table',
        'map',
        'posts',
        'table',
        'embed',
        'quote',
        'alert',
        'jump_menu',
        'divider',
        'logo_slider',
    ];

    /**
     * Meta copied along with a duplicated page: Yoast's SEO fields and the
     * member-area flag (with ACF's field-key reference), so a copy of a
     * member page never turns public by accident.
     *
     * @var array<int, string>
     */
    public const COPY_META = [
        '_yoast_wpseo_title',
        '_yoast_wpseo_metadesc',
        '_yoast_wpseo_focuskw',
        '_yoast_wpseo_meta-robots-noindex',
        '_yoast_wpseo_meta-robots-nofollow',
        '_yoast_wpseo_canonical',
        '_yoast_wpseo_opengraph-title',
        '_yoast_wpseo_opengraph-description',
        'page_is_member_area',
        '_page_is_member_area',
    ];

    /** Page templates that render theme reference pages, never content. */
    private const REFERENCE_TEMPLATES = [
        StyleguidePage::TEMPLATE,
        'page-design-system.blade.php',
    ];

    public function register(): void
    {
        // No container bindings needed.
    }

    public function boot(): void
    {
        add_filter(self::FILTER, [$this, 'contract']);
    }

    /**
     * @param mixed $contract whatever an earlier filter returned (ignored)
     *
     * @return array<string, mixed>
     */
    public function contract(mixed $contract = null): array
    {
        return [
            'version' => self::VERSION,
            'page_field' => 'page_sections',
            'page_field_key' => 'field_page_sections',
            'writable' => self::WRITABLE_LAYOUTS,
            'copy_meta' => self::COPY_META,
            'layouts' => [self::class, 'layouts'],
            'is_readable' => [self::class, 'isReadable'],
            'after_write' => [self::class, 'afterWrite'],
            'is_allowed_embed_host' => [self::class, 'isAllowedEmbedHost'],
            'design_tokens' => [self::class, 'designTokens'],
            'validate_sections' => [self::class, 'validateSections'],
        ];
    }

    /**
     * The registered layouts. A callable instead of a value: the layout list
     * is filterable and cached on first use (FlexibleContent::layouts()), so
     * reading it while the plugin merely asks for the contract would freeze
     * it before themes and plugins have added theirs.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function layouts(): array
    {
        return FlexibleContent::layouts();
    }

    /**
     * Pages the plugin may read: published pages that are neither password
     * protected, nor member-area or protected pages, nor theme reference pages
     * (styleguide, design system).
     */
    public static function isReadable(int $postId): bool
    {
        $post = get_post($postId);
        if (!$post || $post->post_type !== 'page' || $post->post_status !== 'publish') {
            return false;
        }

        if ($post->post_password !== '') {
            return false;
        }

        if (function_exists('get_field')
            && ( get_field('page_is_member_area', $postId) || get_field('page_is_protected', $postId) )) {
            return false;
        }

        $template = (string) get_post_meta($postId, '_wp_page_template', true);
        if (in_array($template, self::REFERENCE_TEMPLATES, true)) {
            return false;
        }

        return StyleguidePage::find() !== $postId;
    }

    /** Same follow-up as saving the page in the backend. */
    public static function afterWrite(int $postId): void
    {
        AcfServiceProvider::ensureSectionAnchors($postId);
    }

    /** Takes a bare host ("player.vimeo.com") or an https URL. */
    public static function isAllowedEmbedHost(string $hostOrUrl): bool
    {
        $value = trim($hostOrUrl);
        if ($value === '') {
            return false;
        }

        return Security::isAllowedEmbedHost(str_contains($value, '://') ? $value : 'https://' . $value . '/');
    }

    /**
     * @return array{colors: array<string, string>, endpoint: string, files: array<int, string>}
     */
    public static function designTokens(): array
    {
        return [
            'colors' => DesignTokenFiles::baseColors(),
            'endpoint' => DesignTokenRestProvider::endpoint(),
            'files' => DesignTokenFiles::FILES,
        ];
    }

    /**
     * Problems in sections the plugin wants to write, in plain words.
     * Checks the layout allowlist and the table shape (same rule as the
     * backend form, AcfServiceProvider::findTableRowMismatches()).
     *
     * @param array<mixed> $sections
     *
     * @return array<int, string>
     */
    public static function validateSections(array $sections): array
    {
        $problems = [];

        foreach (array_values($sections) as $index => $section) {
            $layout = is_array($section) ? ( $section['acf_fc_layout'] ?? null ) : null;
            if (!is_string($layout) || !in_array($layout, self::WRITABLE_LAYOUTS, true)) {
                $problems[] = sprintf('Section %d: layout "%s" is not writable.', $index + 1, is_string($layout) ? $layout : '');
            }
        }

        foreach (AcfServiceProvider::findTableRowMismatches(self::withTableSuffixes($sections)) as $mismatch) {
            $problems[] = sprintf(
                'Table %s, row %d: %d cells for %d columns.',
                $mismatch['input'],
                $mismatch['row'],
                $mismatch['cells'],
                $mismatch['columns'],
            );
        }

        return $problems;
    }

    /**
     * Sections may use field keys (field_..._headers) or field names
     * (headers, rows). findTableRowMismatches() recognises tables by the key
     * suffix, so bare names get one.
     *
     * @param array<mixed> $tree
     *
     * @return array<mixed>
     */
    private static function withTableSuffixes(array $tree): array
    {
        $result = [];
        foreach ($tree as $key => $value) {
            if ($key === FieldDefinitions::TABLE_HEADERS_NAME || $key === FieldDefinitions::TABLE_ROWS_NAME) {
                $key = '_' . $key;
            }
            $result[$key] = is_array($value) ? self::withTableSuffixes($value) : $value;
        }

        return $result;
    }
}
