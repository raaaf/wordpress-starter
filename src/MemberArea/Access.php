<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

class Access
{
    public static function register(): void
    {
        add_filter('template_include', [self::class, 'checkAccess'], 11);
        add_filter('wp_robots', [self::class, 'noindexMemberPages']);
        add_filter('rest_prepare_page', [self::class, 'restrictRestPage'], 10, 2);
        add_filter('wp_sitemaps_posts_query_args', [self::class, 'excludeFromSitemap'], 10, 2);
        add_action('pre_get_posts', [self::class, 'excludeFromSearch']);
        add_filter('rest_post_search_query', [self::class, 'excludeFromRestSearch'], 10, 2);
    }

    /**
     * Read page_is_member_area and page_is_protected once per post ID and cache the result.
     *
     * @return array{is_member_area: mixed, is_protected: mixed}
     */
    private static function getPageFlags(int $postId): array
    {
        static $cache = [];

        if (!isset($cache[$postId])) {
            $cache[$postId] = [
                'is_member_area' => get_field('page_is_member_area', $postId),
                'is_protected'   => get_field('page_is_protected', $postId),
            ];
        }

        return $cache[$postId];
    }

    /**
     * Meta query that matches posts NOT flagged as protected/member-area,
     * including posts that never had the ACF fields saved at all (no meta row).
     * Using '!=' alone renders as an INNER JOIN requiring the meta row to
     * exist, which drops every post without a saved flag from the results.
     *
     * @return array<int|string, mixed>
     */
    private static function unprotectedMetaQuery(): array
    {
        return [
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
        ];
    }

    /**
     * Merge the protection meta_query clause into an existing meta_query so the
     * protection clause is always AND-ed regardless of the relation the existing
     * meta_query uses. Appending the clause directly into an existing meta_query
     * that sets 'relation' => 'OR' would make the protection clause optional
     * instead of mandatory.
     *
     * @param array<int|string, mixed> $existingMetaQuery
     * @param array<int|string, mixed> $protectionClause
     * @return array<int|string, mixed>
     */
    private static function mergeProtectionMetaQuery(array $existingMetaQuery, array $protectionClause): array
    {
        if (empty($existingMetaQuery)) {
            return $protectionClause;
        }

        return [
            'relation' => 'AND',
            $existingMetaQuery,
            $protectionClause,
        ];
    }

    /**
     * Whether the member area / page protection feature is active at all
     * (the "Interner Bereich aktiv" backend toggle).
     */
    private static function isProtectionActive(): bool
    {
        if (!function_exists('get_field')) {
            return true;
        }

        $active = get_field('member_area_active', 'option');

        return $active === null || (bool) $active;
    }

    /**
     * Whether the current visitor is allowed to see a protected/member-area page,
     * reusing the same decision checkAccess() makes for the front-end request.
     */
    private static function isVisitorAllowed(int $postId): bool
    {
        if (!self::isProtectionActive()) {
            return true;
        }

        $flags = self::getPageFlags($postId);
        if (!$flags['is_member_area'] && !$flags['is_protected']) {
            return true;
        }

        return Auth::isAuthenticated();
    }

    /**
     * Add noindex to member area and protected pages so they are excluded from search engines.
     *
     * @param array<string, bool|string> $robots
     * @return array<string, bool|string>
     */
    public static function noindexMemberPages(array $robots): array
    {
        if (!is_page()) {
            return $robots;
        }

        $flags = self::getPageFlags(get_queried_object_id());
        if ($flags['is_member_area'] || $flags['is_protected']) {
            $robots['noindex'] = true;
            $robots['nofollow'] = true;
        }

        return $robots;
    }

    public static function checkAccess(string $template): string
    {
        // Check backend toggle (ACF is loaded at this point)
        if (!self::isProtectionActive()) {
            return $template;
        }

        if (!is_page()) {
            return $template;
        }

        $flags = self::getPageFlags(get_queried_object_id());

        // Member area dashboard page
        $isMemberArea = $flags['is_member_area'];
        if ($isMemberArea) {
            $blade = $GLOBALS['blade'] ?? null;
            if (!$blade) {
                return $template;
            }

            if (!Auth::isAuthenticated()) {
                $GLOBALS['template_name'] = 'member-area.login-page';
            } else {
                $GLOBALS['template_name'] = 'page-member-area';
            }

            return get_template_directory() . '/config/index.php';
        }

        // Protected page — redirect to login if not authenticated
        $isProtected = $flags['is_protected'];
        if (!$isProtected) {
            return $template;
        }

        if (Auth::isAuthenticated()) {
            return $template;
        }

        $blade = $GLOBALS['blade'] ?? null;
        if (!$blade) {
            return $template;
        }

        $GLOBALS['template_name'] = 'member-area.login-page';
        return get_template_directory() . '/config/index.php';
    }

    /**
     * Blank out protected/member-area page content in the REST API for
     * visitors who are not allowed to see it (mirrors core's password-protected
     * posts). Must stay a WP_REST_Response, never a WP_Error: in collection
     * requests (/wp/v2/pages) core's prepare_response_for_collection() passes
     * non-response values through unchanged, so a WP_Error would be
     * serialised as a plain item inside the list instead of being blocked.
     */
    public static function restrictRestPage(\WP_REST_Response $response, \WP_Post $post): \WP_REST_Response
    {
        if (self::isVisitorAllowed($post->ID)) {
            return $response;
        }

        $data = $response->get_data();

        if (isset($data['title']['rendered'])) {
            $data['title']['rendered'] = __('Geschützt', 'wp-starter');
        }
        if (isset($data['title']['raw'])) {
            $data['title']['raw'] = __('Geschützt', 'wp-starter');
        }
        if (isset($data['content']['rendered'])) {
            $data['content']['rendered'] = '';
            $data['content']['protected'] = true;
        }
        if (isset($data['content']['raw'])) {
            $data['content']['raw'] = '';
        }
        if (isset($data['excerpt']['rendered'])) {
            $data['excerpt']['rendered'] = '';
            $data['excerpt']['protected'] = true;
        }
        if (isset($data['excerpt']['raw'])) {
            $data['excerpt']['raw'] = '';
        }

        $response->set_data($data);

        return $response;
    }

    /**
     * Exclude protected/member-area pages from the core XML sitemaps.
     *
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public static function excludeFromSitemap(array $args, string $postType): array
    {
        if ('page' !== $postType) {
            return $args;
        }

        $existingMetaQuery = $args['meta_query'] ?? [];
        if (!is_array($existingMetaQuery)) {
            $existingMetaQuery = [];
        }

        $args['meta_query'] = self::mergeProtectionMetaQuery($existingMetaQuery, self::unprotectedMetaQuery()); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query

        return $args;
    }

    /**
     * Exclude protected/member-area pages from the front-end main search
     * query for visitors who are not allowed to see them.
     */
    public static function excludeFromSearch(\WP_Query $query): void
    {
        if (is_admin() || !$query->is_search() || !$query->is_main_query()) {
            return;
        }

        if (!self::isProtectionActive()) {
            return;
        }

        if (Auth::isAuthenticated()) {
            return;
        }

        $metaQuery = $query->get('meta_query');
        if (!is_array($metaQuery)) {
            $metaQuery = [];
        }

        $metaQuery = self::mergeProtectionMetaQuery($metaQuery, self::unprotectedMetaQuery());

        $query->set('meta_query', $metaQuery); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
    }

    /**
     * Exclude protected/member-area pages from the REST search endpoint
     * (/wp/v2/search) for visitors who are not allowed to see them. Core's
     * WP_REST_Post_Search_Handler has no per-item visibility filter, so the
     * item must never be returned by the underlying WP_Query in the first
     * place, hooked via rest_post_search_query.
     *
     * @param array<string, mixed> $queryArgs
     * @return array<string, mixed>
     */
    public static function excludeFromRestSearch(array $queryArgs, \WP_REST_Request $request): array
    {
        if (!self::isProtectionActive() || Auth::isAuthenticated()) {
            return $queryArgs;
        }

        $metaQuery = $queryArgs['meta_query'] ?? [];
        if (!is_array($metaQuery)) {
            $metaQuery = [];
        }

        $metaQuery = self::mergeProtectionMetaQuery($metaQuery, self::unprotectedMetaQuery());

        $queryArgs['meta_query'] = $metaQuery; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query

        return $queryArgs;
    }
}
