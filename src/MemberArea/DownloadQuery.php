<?php

declare(strict_types=1);

namespace WordpressStarter\MemberArea;

use WordpressStarter\RateLimiter;

class DownloadQuery
{
    private const ALLOWED_PER_PAGE = [20, 50, 100];
    private const DEFAULT_PER_PAGE = 20;

    private const EXT_VARIANTS = [
        'pdf'  => 'error',
        'xls'  => 'success',
        'xlsx' => 'success',
        'doc'  => 'brand',
        'docx' => 'brand',
        'zip'  => 'warning',
    ];

    public static function handle(): void
    {
        if (!Auth::isAuthenticated()) {
            wp_send_json_error(['message' => __('Nicht authentifiziert.', 'wp-starter')], 401);
        }

        $nonce = sanitize_text_field(wp_unslash($_GET['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'member_downloads_query')) {
            wp_send_json_error(['message' => __('Ungültige Anfrage.', 'wp-starter')], 403);
        }

        RateLimiter::enforce('member_downloads_query', 60, 60);

        // Return facets (available filters with counts) when requested
        if (isset($_GET['facets'])) {
            wp_send_json_success(self::facets());
        }

        $search   = sanitize_text_field(wp_unslash($_GET['search']   ?? ''));
        $category = sanitize_key(wp_unslash($_GET['category']        ?? ''));
        $ext      = sanitize_key(wp_unslash($_GET['ext']             ?? ''));
        $page     = max(1, absint($_GET['page']     ?? 1));
        $perPage  = absint($_GET['per_page'] ?? self::DEFAULT_PER_PAGE);

        if (!in_array($perPage, self::ALLOWED_PER_PAGE, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $result = self::query($search, $category, $ext, $page, $perPage);

        wp_send_json_success($result);
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, pages: int, current_page: int, per_page: int}
     */
    private static function query(
        string $search,
        string $category,
        string $ext,
        int $page,
        int $perPage
    ): array {
        // Base meta_query: exclude SFTP parent folder entries
        $metaQuery = [
            'relation' => 'OR',
            ['key' => 'download_source_type', 'value' => 'sftp', 'compare' => '!='],
            [
                'relation' => 'AND',
                ['key' => 'download_source_type', 'value' => 'sftp', 'compare' => '='],
                ['key' => 'download_sftp_source', 'value' => '', 'compare' => '!='],
            ],
        ];

        $args = [
            'post_type'      => 'member_download',
            'post_status'    => 'publish',
            'posts_per_page' => $ext ? -1 : $perPage,
            'paged'          => $ext ? 1 : $page,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => (bool) $ext,
            'meta_query'     => $metaQuery, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        ];

        if (!empty($search)) {
            $args['s'] = $search;
        }

        if (!empty($category)) {
            $args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                [
                    'taxonomy' => 'download_category',
                    'field'    => 'slug',
                    'terms'    => $category,
                ],
            ];
        }

        // Extend search to also match download_description meta field
        $extendSearch = null;
        if (!empty($search)) {
            $extendSearch = static function (string $searchSql, \WP_Query $wpQuery) use ($search): string {
                global $wpdb;
                if (!$wpQuery->get('s')) {
                    return $searchSql;
                }
                $term = '%' . $wpdb->esc_like($search) . '%';
                $searchSql .= $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    " OR ({$wpdb->postmeta}.meta_key = 'download_description' AND {$wpdb->postmeta}.meta_value LIKE %s)",
                    $term
                );
                return $searchSql;
            };
            add_filter('posts_search', $extendSearch, 10, 2);
        }

        $wpQuery = new \WP_Query($args);

        if ($extendSearch !== null) {
            remove_filter('posts_search', $extendSearch, 10);
        }

        $posts = $wpQuery->posts;

        // Pre-warm the object cache so subsequent get_field() calls inside the loop
        // hit cache instead of issuing individual DB queries (avoids N+1).
        update_meta_cache('post', wp_list_pluck($posts, 'ID'));

        // PHP-level extension filter (avoids complex SQL JOIN on attachments table)
        if (!empty($ext)) {
            $posts = array_filter($posts, static function (\WP_Post $post) use ($ext): bool {
                return self::getPostExt($post->ID) === strtolower($ext);
            });
            $posts = array_values($posts);

            $total = count($posts);
            $pages = max(1, (int) ceil($total / $perPage));
            $page  = min($page, $pages);
            $posts = array_slice($posts, ( $page - 1 ) * $perPage, $perPage);
        } else {
            $total = $wpQuery->found_posts;
            $pages = max(1, (int) ceil($total / $perPage));
        }

        // Build term label cache
        $terms       = get_terms(['taxonomy' => 'download_category', 'hide_empty' => false]);
        $termLabels  = [];
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $termLabels[$term->slug] = $term->name;
            }
        }

        $dateFormat = get_option('date_format');
        $items = [];
        foreach ($posts as $post) {
            $postId       = $post->ID;
            $postTerms    = get_the_terms($postId, 'download_category');
            $termSlug     = ( !is_wp_error($postTerms) && !empty($postTerms) ) ? $postTerms[0]->slug : '';
            $lastModified = get_field('download_last_modified', $postId) ?: '';
            $available    = (bool) ( get_field('download_available', $postId) ?? true );

            $fileExt      = self::getPostExt($postId);
            $extVariant   = self::EXT_VARIANTS[$fileExt] ?? 'gray';

            $lastModifiedLabel = '';
            $isUpdated         = false;
            if ($lastModified) {
                $timestamp = strtotime($lastModified);
                if ($timestamp !== false) {
                    $lastModifiedLabel = date_i18n($dateFormat, $timestamp);
                    $isUpdated         = $timestamp > ( time() - 7 * DAY_IN_SECONDS );
                }
            }

            $downloadNonce = wp_create_nonce('member_download_' . $postId);
            $downloadUrl   = admin_url('admin-ajax.php')
                . '?action=member_download&download_id=' . $postId
                . '&nonce=' . $downloadNonce;

            $items[] = [
                'id'             => $postId,
                'title'          => $post->post_title,
                'ext'            => strtoupper($fileExt),
                'ext_variant'    => $extVariant,
                'category_label' => $termSlug ? ( $termLabels[$termSlug] ?? $termSlug ) : '',
                'last_modified'  => $lastModifiedLabel,
                'is_updated'     => $isUpdated,
                'available'      => $available,
                'download_url'   => $downloadUrl,
            ];
        }

        return [
            'items'        => $items,
            'total'        => $total,
            'pages'        => $pages,
            'current_page' => $page,
            'per_page'     => $perPage,
        ];
    }

    private static function getPostExt(int $postId): string
    {
        $sourceType = get_field('download_source_type', $postId) ?: 'upload';

        if ($sourceType === 'upload') {
            $file     = get_field('download_file', $postId);
            $fileName = is_array($file) ? ( $file['filename'] ?? '' ) : '';
            return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        }

        if ($sourceType === 'sftp') {
            $remotePath = get_field('download_sftp_remote_file', $postId) ?: '';
            return strtolower(pathinfo($remotePath, PATHINFO_EXTENSION));
        }

        // external
        $url = get_field('download_external_url', $postId) ?: '';
        return strtolower(pathinfo( (string) wp_parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    }

    /**
     * Return available filter options with counts (only items that actually exist).
     *
     * @return array{categories: list<array{slug: string, label: string, count: int}>, extensions: list<array{value: string, label: string, count: int}>}
     */
    private static function facets(): array
    {
        // Fetch all published, non-parent downloads
        $posts = get_posts([
            'post_type'      => 'member_download',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
            'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                'relation' => 'OR',
                ['key' => 'download_source_type', 'value' => 'sftp', 'compare' => '!='],
                [
                    'relation' => 'AND',
                    ['key' => 'download_source_type', 'value' => 'sftp', 'compare' => '='],
                    ['key' => 'download_sftp_source', 'value' => '', 'compare' => '!='],
                ],
            ],
            'fields' => 'ids',
        ]);

        // Pre-warm the object cache for all post IDs so getPostExt() calls inside
        // the loop hit cache instead of issuing individual DB queries (avoids N+1).
        update_meta_cache('post', $posts);

        // Count by category
        $categoryCounts = [];
        // Count by extension
        $extCounts = [];

        foreach ($posts as $postId) {
            // Category
            $terms = get_the_terms($postId, 'download_category');
            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $term) {
                    $categoryCounts[$term->slug] = $categoryCounts[$term->slug] ?? ['label' => $term->name, 'count' => 0];
                    ++$categoryCounts[$term->slug]['count'];
                }
            }

            // Extension
            $ext = self::getPostExt($postId);
            if (!empty($ext)) {
                $extCounts[$ext] = ( $extCounts[$ext] ?? 0 ) + 1;
            }
        }

        // Build categories list sorted by label
        $categories = [];
        foreach ($categoryCounts as $slug => $data) {
            $categories[] = [
                'slug'  => $slug,
                'label' => $data['label'],
                'count' => $data['count'],
            ];
        }
        usort($categories, static fn ($a, $b) => strcmp($a['label'], $b['label']));

        // Build extensions list in a defined order
        $extOrder = ['pdf', 'docx', 'doc', 'xlsx', 'xls', 'zip'];
        $extensions = [];
        foreach ($extOrder as $ext) {
            if (isset($extCounts[$ext])) {
                $extensions[] = [
                    'value' => $ext,
                    'label' => strtoupper($ext),
                    'count' => $extCounts[$ext],
                ];
            }
        }
        // Append any unexpected extensions not in the defined order
        foreach ($extCounts as $ext => $count) {
            if (!in_array($ext, $extOrder, true)) {
                $extensions[] = [
                    'value' => $ext,
                    'label' => strtoupper($ext),
                    'count' => $count,
                ];
            }
        }

        return [
            'categories' => $categories,
            'extensions' => $extensions,
        ];
    }
}
