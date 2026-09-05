<?php

declare(strict_types=1);

namespace Tests\Unit\PostTypes;

use Tests\Support\PlainTaxonomyFixture;
use Tests\Support\TestCase;
use WordpressStarter\PostTypes\MemberDownload;
use WordpressStarter\PostTypes\Team;
use WordpressStarter\Taxonomies\DownloadCategory;

/**
 * Regression tests for the map_meta_cap poisoning bug: a post type or
 * taxonomy that sets $requiredCapability must map every primitive
 * capability to it, and must never leak a meta-cap key (edit_post,
 * read_post, delete_post) into the 'capabilities' array, since WordPress
 * resolves those against the primitive map instead of the raw capability
 * and would otherwise fall back to do_not_allow.
 */
final class PostTypeCapabilitiesTest extends TestCase
{
    public function testMemberDownloadMapsMetaCap(): void
    {
        MemberDownload::registerPostType();

        $args = $GLOBALS['wp_mock_post_types']['member_download'];

        $this->assertTrue($args['map_meta_cap']);
    }

    public function testMemberDownloadMapsEveryPrimitiveToManageOptions(): void
    {
        MemberDownload::registerPostType();

        $args = $GLOBALS['wp_mock_post_types']['member_download'];
        $capabilities = $args['capabilities'];

        $primitives = [
            'edit_posts',
            'edit_others_posts',
            'edit_published_posts',
            'edit_private_posts',
            'publish_posts',
            'delete_posts',
            'delete_others_posts',
            'delete_published_posts',
            'delete_private_posts',
            'read_private_posts',
            'create_posts',
        ];

        foreach ($primitives as $primitive) {
            $this->assertArrayHasKey($primitive, $capabilities, "Missing primitive '{$primitive}'");
            $this->assertSame('manage_options', $capabilities[$primitive], "Primitive '{$primitive}' does not map to manage_options");
        }
    }

    public function testMemberDownloadDoesNotEmitMetaCapKeys(): void
    {
        MemberDownload::registerPostType();

        $args = $GLOBALS['wp_mock_post_types']['member_download'];
        $capabilities = $args['capabilities'];

        // These are meta caps: WordPress resolves them via map_meta_cap()
        // against the primitives above, not as literal capability names.
        // Present here, they would poison current_user_can('manage_options')
        // into resolving to do_not_allow.
        foreach (['edit_post', 'read_post', 'delete_post'] as $metaCap) {
            $this->assertArrayNotHasKey($metaCap, $capabilities, "Meta-cap key '{$metaCap}' must not be emitted");
        }
    }

    public function testPostTypeWithoutRequiredCapabilityEmitsNoCapabilitiesKey(): void
    {
        Team::registerPostType();

        $args = $GLOBALS['wp_mock_post_types']['team_member'];

        $this->assertArrayNotHasKey('capabilities', $args);
    }

    public function testDownloadCategoryMapsAllTermCapsToManageOptions(): void
    {
        DownloadCategory::registerTaxonomy();

        $args = $GLOBALS['wp_mock_taxonomies']['download_category'];
        $capabilities = $args['capabilities'];

        foreach (['manage_terms', 'edit_terms', 'delete_terms', 'assign_terms'] as $termCap) {
            $this->assertSame('manage_options', $capabilities[$termCap], "Term cap '{$termCap}' does not map to manage_options");
        }
    }

    public function testTaxonomyWithoutRequiredCapabilityEmitsNoCapabilitiesKey(): void
    {
        PlainTaxonomyFixture::registerTaxonomy();

        $args = $GLOBALS['wp_mock_taxonomies']['plain_taxonomy_fixture'];

        $this->assertArrayNotHasKey('capabilities', $args);
    }
}
