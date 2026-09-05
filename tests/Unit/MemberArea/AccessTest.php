<?php

declare(strict_types=1);

namespace Tests\Unit\MemberArea;

use ReflectionClass;
use Tests\Support\TestCase;
use WordpressStarter\MemberArea\Access;
use WordpressStarter\MemberArea\Auth;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Tests for WordpressStarter\MemberArea\Access: isProtectedForCurrentVisitor()
 * (the shared trust decision consumers outside checkAccess() rely on),
 * excludeFromSitemap() (the meta_query merge for the core XML sitemap),
 * restrictRestPage() (blanking protected content in the REST API),
 * excludeFromRestSearch() and excludeFromSearch() (the meta_query merge for
 * the REST search endpoint and the front-end main search query).
 */
final class AccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Auth caches auth mode in a static property that resetAllMocks()
        // does not touch (see AuthTest); reset it so one test's field values
        // cannot leak into the next through Auth::isAuthenticated().
        $reflection = new ReflectionClass(Auth::class);
        foreach (['cachedAuthMode', 'cachedCookieTtl', 'cachedSharedPassword'] as $property) {
            $prop = $reflection->getProperty($property);
            $prop->setAccessible(true);
            $prop->setValue(null, false);
        }
    }

    // -- isProtectedForCurrentVisitor() ----------------------------------

    public function testPasswordProtectedPostIsProtectedRegardlessOfMemberAreaFlags(): void
    {
        $GLOBALS['wp_mock_password_required'] = true;

        $this->assertTrue(Access::isProtectedForCurrentVisitor(5));
    }

    public function testProtectedPageIsProtectedForAnonymousVisitor(): void
    {
        $this->setMockField('page_is_protected', true, 42);
        $this->setMockField('page_is_member_area', false, 42);
        // No current user id set: is_user_logged_in() is false, so
        // Auth::isAuthenticated() falls through to the password-mode cookie
        // check, finds no cookie, and returns false.

        $this->assertTrue(Access::isProtectedForCurrentVisitor(42));
    }

    public function testProtectedPageIsNotProtectedForAllowedVisitor(): void
    {
        $this->setMockField('page_is_protected', true, 42);
        $this->setMockField('page_is_member_area', false, 42);
        $GLOBALS['wp_mock_current_user_id'] = 1;
        $GLOBALS['wp_mock_current_user_can']['manage_options'] = true;

        $this->assertFalse(Access::isProtectedForCurrentVisitor(42));
    }

    public function testUnprotectedPageIsNotProtectedForAnonymousVisitor(): void
    {
        $this->setMockField('page_is_protected', false, 7);
        $this->setMockField('page_is_member_area', false, 7);

        $this->assertFalse(Access::isProtectedForCurrentVisitor(7));
    }

    // -- excludeFromSitemap() ---------------------------------------------

    public function testExcludeFromSitemapLeavesNonPageArgsUntouched(): void
    {
        $args = ['post_type' => 'post', 'foo' => 'bar'];

        $result = Access::excludeFromSitemap($args, 'post');

        $this->assertSame($args, $result);
    }

    public function testExcludeFromSitemapAddsProtectionMetaQueryWhenNoneExists(): void
    {
        $args = ['post_type' => 'page'];

        $result = Access::excludeFromSitemap($args, 'page');

        $this->assertSame(
            [
                'relation' => 'AND',
                [
                    'relation' => 'OR',
                    ['key' => 'page_is_protected', 'compare' => 'NOT EXISTS'],
                    ['key' => 'page_is_protected', 'value' => '1', 'compare' => '!='],
                ],
                [
                    'relation' => 'OR',
                    ['key' => 'page_is_member_area', 'compare' => 'NOT EXISTS'],
                    ['key' => 'page_is_member_area', 'value' => '1', 'compare' => '!='],
                ],
            ],
            $result['meta_query'],
        );
    }

    public function testExcludeFromSitemapPreservesExistingMetaQueryUnderAnd(): void
    {
        $existingMetaQuery = ['relation' => 'OR', ['key' => 'featured', 'value' => '1']];
        $args = ['post_type' => 'page', 'meta_query' => $existingMetaQuery]; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query

        $result = Access::excludeFromSitemap($args, 'page');

        $this->assertSame('AND', $result['meta_query']['relation']);
        $this->assertSame($existingMetaQuery, $result['meta_query'][0]);
        $this->assertSame(
            [
                'relation' => 'AND',
                [
                    'relation' => 'OR',
                    ['key' => 'page_is_protected', 'compare' => 'NOT EXISTS'],
                    ['key' => 'page_is_protected', 'value' => '1', 'compare' => '!='],
                ],
                [
                    'relation' => 'OR',
                    ['key' => 'page_is_member_area', 'compare' => 'NOT EXISTS'],
                    ['key' => 'page_is_member_area', 'value' => '1', 'compare' => '!='],
                ],
            ],
            $result['meta_query'][1],
        );
    }

    // -- restrictRestPage() -------------------------------------------------

    public function testRestrictRestPageBlanksContentForDisallowedVisitor(): void
    {
        $this->setMockField('page_is_protected', true, 42);
        $this->setMockField('page_is_member_area', false, 42);

        $post = new WP_Post(['ID' => 42]);
        $response = new WP_REST_Response([
            'title' => ['rendered' => 'Secret', 'raw' => 'Secret'],
            'content' => ['rendered' => '<p>Secret</p>', 'raw' => '<p>Secret</p>', 'protected' => false],
            'excerpt' => ['rendered' => 'Secret excerpt', 'raw' => 'Secret excerpt', 'protected' => false],
        ]);

        $result = Access::restrictRestPage($response, $post);

        $data = $result->get_data();
        $this->assertSame('', $data['content']['rendered']);
        $this->assertSame('', $data['content']['raw']);
        $this->assertTrue($data['content']['protected']);
        $this->assertSame('', $data['excerpt']['rendered']);
        $this->assertSame('', $data['excerpt']['raw']);
        $this->assertTrue($data['excerpt']['protected']);
    }

    public function testRestrictRestPageKeepsContentForAllowedVisitor(): void
    {
        $this->setMockField('page_is_protected', true, 42);
        $this->setMockField('page_is_member_area', false, 42);
        $GLOBALS['wp_mock_current_user_id'] = 1;
        $GLOBALS['wp_mock_current_user_can']['manage_options'] = true;

        $post = new WP_Post(['ID' => 42]);
        $response = new WP_REST_Response([
            'content' => ['rendered' => '<p>Visible</p>', 'raw' => '<p>Visible</p>'],
            'excerpt' => ['rendered' => 'Visible excerpt', 'raw' => 'Visible excerpt'],
        ]);

        $result = Access::restrictRestPage($response, $post);

        $data = $result->get_data();
        $this->assertSame('<p>Visible</p>', $data['content']['rendered']);
        $this->assertSame('Visible excerpt', $data['excerpt']['rendered']);
    }

    // -- excludeFromRestSearch() --------------------------------------------

    public function testExcludeFromRestSearchAddsProtectionMetaQueryForDisallowedVisitor(): void
    {
        $request = new WP_REST_Request('GET', '/wp/v2/search');

        $result = Access::excludeFromRestSearch(['post_type' => 'page'], $request);

        $this->assertSame(
            [
                'relation' => 'AND',
                [
                    'relation' => 'OR',
                    ['key' => 'page_is_protected', 'compare' => 'NOT EXISTS'],
                    ['key' => 'page_is_protected', 'value' => '1', 'compare' => '!='],
                ],
                [
                    'relation' => 'OR',
                    ['key' => 'page_is_member_area', 'compare' => 'NOT EXISTS'],
                    ['key' => 'page_is_member_area', 'value' => '1', 'compare' => '!='],
                ],
            ],
            $result['meta_query'],
        );
    }

    public function testExcludeFromRestSearchLeavesArgsUntouchedForAllowedVisitor(): void
    {
        $GLOBALS['wp_mock_current_user_id'] = 1;
        $GLOBALS['wp_mock_current_user_can']['manage_options'] = true;
        $request = new WP_REST_Request('GET', '/wp/v2/search');

        $args = ['post_type' => 'page'];
        $result = Access::excludeFromRestSearch($args, $request);

        $this->assertSame($args, $result);
    }

    // -- excludeFromSearch() -------------------------------------------------

    public function testExcludeFromSearchAddsProtectionMetaQueryOnMainFrontEndSearchQuery(): void
    {
        $query = new WP_Query();
        $query->is_search = true;
        $query->is_main_query = true;

        Access::excludeFromSearch($query);

        $this->assertSame(
            [
                'relation' => 'AND',
                [
                    'relation' => 'OR',
                    ['key' => 'page_is_protected', 'compare' => 'NOT EXISTS'],
                    ['key' => 'page_is_protected', 'value' => '1', 'compare' => '!='],
                ],
                [
                    'relation' => 'OR',
                    ['key' => 'page_is_member_area', 'compare' => 'NOT EXISTS'],
                    ['key' => 'page_is_member_area', 'value' => '1', 'compare' => '!='],
                ],
            ],
            $query->get('meta_query'),
        );
    }

    public function testExcludeFromSearchLeavesNonMainQueryUntouched(): void
    {
        $query = new WP_Query();
        $query->is_search = true;
        $query->is_main_query = false;

        Access::excludeFromSearch($query);

        $this->assertSame('', $query->get('meta_query'));
    }

    public function testExcludeFromSearchLeavesNonSearchQueryUntouched(): void
    {
        $query = new WP_Query();
        $query->is_search = false;
        $query->is_main_query = true;

        Access::excludeFromSearch($query);

        $this->assertSame('', $query->get('meta_query'));
    }

    public function testExcludeFromSearchLeavesAdminQueryUntouched(): void
    {
        $this->setIsAdmin(true);
        $query = new WP_Query();
        $query->is_search = true;
        $query->is_main_query = true;

        Access::excludeFromSearch($query);

        $this->assertSame('', $query->get('meta_query'));
    }

    public function testExcludeFromSearchLeavesQueryUntouchedForAllowedVisitor(): void
    {
        $GLOBALS['wp_mock_current_user_id'] = 1;
        $GLOBALS['wp_mock_current_user_can']['manage_options'] = true;
        $query = new WP_Query();
        $query->is_search = true;
        $query->is_main_query = true;

        Access::excludeFromSearch($query);

        $this->assertSame('', $query->get('meta_query'));
    }
}
