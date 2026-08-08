<?php

declare(strict_types=1);

namespace WordpressStarter\Services;

use WordpressStarter\ThemeContext;

/**
 * Single owner of the question "which page is this theme's styleguide".
 *
 * Why this exists: the answer used to live only in a per-theme option, written by
 * two independent creation paths that did not know about each other. On a site
 * where several of these themes have been activated over time — every dev and
 * staging box — that leaves a spread of stale pointers. Measured on one such site:
 *
 *   siera_theme_styleguide_page_id      = 580   -> a page in the trash
 *   wp_starter_styleguide_page_id       = 455   -> a page that no longer exists
 *   wordpress_starter_theme_..._page_id = unset -> while the page was sitting there
 *
 * The page itself carried no evidence of what it was, so nothing could recover.
 * A marker in postmeta fixes that: it lives on the page, survives theme switches
 * and option-prefix renames, and is what any migration can safely key on.
 *
 * The one rule this class never breaks: when the evidence is ambiguous it reports
 * ambiguity instead of picking. A migration that overwrites the wrong page is far
 * worse than one that asks.
 */
final class StyleguidePage
{
    /** Page template introduced with the component-rendered styleguide. */
    public const TEMPLATE = 'page-styleguide.blade.php';

    /**
     * Template the seeder used to assign. It never existed as a file, so WordPress
     * silently fell back to page.blade.php — which is also why it is a reliable
     * fingerprint of a pre-migration styleguide page.
     */
    public const LEGACY_TEMPLATE = 'page-flexible.blade.php';

    /** Returned by find() when several pages could be the styleguide. */
    public const AMBIGUOUS = -1;

    private function __construct()
    {
    }

    /**
     * Postmeta key holding the marker. Underscore-prefixed so it stays out of the
     * custom-fields box; theme-prefixed so two of these themes on one site do not
     * claim each other's page.
     */
    public static function markerKey(): string
    {
        return '_' . ThemeContext::prefix() . '_styleguide';
    }

    public static function optionKey(): string
    {
        return ThemeContext::optionKey('styleguide_page_id');
    }

    /**
     * Resolve the styleguide page.
     *
     * Not a pure query: when it resolves a page via the option, the marker, or a
     * legacy candidate, it re-adopts that page (writes marker + option) to
     * opportunistically keep both in sync. That write also happens from the
     * admin_notices render path — deliberately: it is how a marker-only or
     * option-only pointer heals itself back into agreement without a separate
     * migration step. Memoized per request, keyed by blog ID (see $cachedResults
     * below): find() runs on every admin page load and can cost up to three
     * WP_Query calls plus per-candidate lookups, so repeat calls in one request
     * reuse the first result. get_option() is already blog-scoped, so a plain
     * request-wide cache would keep serving the previous blog's page ID after a
     * switch_to_blog() (core, network admin, and plugins all do this).
     * adopt() and forget() invalidate the cache for the current blog.
     *
     * @return int Page ID, 0 when there is none, self::AMBIGUOUS when several
     *             pages are plausible and none of them is marked.
     */
    /**
     * Per-request memoization of find(), keyed by blog ID.
     *
     * @var array<int, int>
     */
    private static array $cachedResults = [];

    public static function find(): int
    {
        $blogId = get_current_blog_id();

        if (isset(self::$cachedResults[$blogId])) {
            return self::$cachedResults[$blogId];
        }

        $optionValue = get_option(self::optionKey());
        $fromOption = self::validate( (int) $optionValue );
        if ($fromOption > 0) {
            self::adopt($fromOption);
            self::$cachedResults[$blogId] = $fromOption;

            return self::$cachedResults[$blogId];
        }

        $marked = self::queryByMarker();
        if (count($marked) === 1) {
            self::adopt($marked[0]);
            self::$cachedResults[$blogId] = $marked[0];

            return self::$cachedResults[$blogId];
        }
        if (count($marked) > 1) {
            self::$cachedResults[$blogId] = self::AMBIGUOUS;

            return self::$cachedResults[$blogId];
        }

        $chosen = self::chooseCandidate(self::queryLegacyCandidates());
        if ($chosen > 0) {
            self::adopt($chosen);
        }

        self::$cachedResults[$blogId] = $chosen;

        return self::$cachedResults[$blogId];
    }

    /**
     * Record a page as this theme's styleguide, in both places.
     *
     * Invalidates the find() cache: adopting a different page changes the answer.
     */
    public static function adopt(int $pageId): void
    {
        if ($pageId <= 0) {
            return;
        }

        update_post_meta($pageId, self::markerKey(), '1');
        update_option(self::optionKey(), $pageId);
        unset(self::$cachedResults[get_current_blog_id()]);
    }

    /**
     * Clear this theme's claim on the styleguide page: option and every marker.
     *
     * The two must go together — clearing only the option leaves the marker on
     * the old page behind, which the next find() would happily pick back up via
     * queryByMarker().
     *
     * Markers are cleared from EVERY page carrying them, not just the one the
     * option names, and that is what makes an AMBIGUOUS state recoverable. The
     * only way find() reaches AMBIGUOUS is via queryByMarker(), which it only
     * consults when the option is already invalid — so at that moment the option
     * names no page at all. Clearing markers by option alone would delete
     * nothing, leave both marked pages marked, and hand the next find() the same
     * AMBIGUOUS answer forever, with no route out through the Tools panel.
     */
    public static function forget(): void
    {
        $pageId = (int) get_option(self::optionKey());
        if ($pageId > 0) {
            delete_post_meta($pageId, self::markerKey());
        }

        foreach (self::queryByMarker() as $markedId) {
            delete_post_meta($markedId, self::markerKey());
        }

        delete_option(self::optionKey());
        unset(self::$cachedResults[get_current_blog_id()]);
    }

    /**
     * True when the page still renders the old HTML replica rather than the
     * component-backed template.
     */
    public static function needsMigration(int $pageId): bool
    {
        if ($pageId <= 0) {
            return false;
        }

        return (string) get_post_meta($pageId, '_wp_page_template', true) !== self::TEMPLATE;
    }

    /**
     * Confirm an ID really points at a usable page.
     *
     * The post_status check matters and used to be skipped: get_post() happily
     * returns a trashed page, which is what one of the stale options above pointed at.
     */
    public static function validate(int $pageId): int
    {
        if ($pageId <= 0) {
            return 0;
        }

        $post = get_post($pageId);

        if (!$post || $post->post_type !== 'page') {
            return 0;
        }

        if (in_array($post->post_status, ['trash', 'auto-draft'], true)) {
            return 0;
        }

        return $pageId;
    }

    /**
     * Decide which of several legacy candidates is the styleguide.
     *
     * Pure on purpose — this is the part that can get someone's page overwritten,
     * so it is unit-tested without WordPress in the way.
     *
     * @param array<int, array{id:int, status:string, sections:int, claimedByOtherTheme:bool}> $candidates
     *
     * @return int Page ID, 0 when nothing qualifies, self::AMBIGUOUS when several do.
     */
    public static function chooseCandidate(array $candidates): int
    {
        $usable = array_values(array_filter($candidates, static function (array $c): bool {
            if (in_array($c['status'], ['trash', 'auto-draft'], true)) {
                return false;
            }

            // Another one of these themes already claimed it. Not ours to touch.
            if ($c['claimedByOtherTheme']) {
                return false;
            }

            // A styleguide without layouts is an empty shell, not the page we mean.
            return $c['sections'] > 0;
        }));

        if ($usable === []) {
            return 0;
        }

        if (count($usable) > 1) {
            return self::AMBIGUOUS;
        }

        return $usable[0]['id'];
    }

    /**
     * @return array<int, int> IDs of pages carrying this theme's marker.
     */
    private static function queryByMarker(): array
    {
        $ids = get_posts([
            'post_type' => 'page',
            'post_status' => ['publish', 'private', 'draft', 'pending'],
            'numberposts' => 5,
            'fields' => 'ids',
            'meta_key' => self::markerKey(),
            'no_found_rows' => true,
            'suppress_filters' => false,
        ]);

        return array_map('intval', is_array($ids) ? $ids : []);
    }

    /**
     * Pages that look like a pre-marker styleguide: the seeder's dead template
     * assignment, or the slug it would have produced.
     *
     * @return array<int, array{id:int, status:string, sections:int, claimedByOtherTheme:bool}>
     */
    private static function queryLegacyCandidates(): array
    {
        $byTemplate = get_posts([
            'post_type' => 'page',
            'post_status' => ['publish', 'private', 'draft', 'pending'],
            'numberposts' => 20,
            'fields' => 'ids',
            'meta_key' => '_wp_page_template',
            'meta_value' => self::LEGACY_TEMPLATE,
            'no_found_rows' => true,
        ]);

        $byName = get_posts([
            'post_type' => 'page',
            'post_status' => ['publish', 'private', 'draft', 'pending'],
            'numberposts' => 20,
            'fields' => 'ids',
            'name' => 'styleguide',
            'no_found_rows' => true,
        ]);

        $ids = array_unique(array_map('intval', array_merge(
            is_array($byTemplate) ? $byTemplate : [],
            is_array($byName) ? $byName : [],
        )));

        $ownMarker = self::markerKey();
        $candidates = [];

        foreach ($ids as $id) {
            $post = get_post($id);
            if (!$post) {
                continue;
            }

            $sections = get_post_meta($id, 'page_sections', true);

            $candidates[] = [
                'id' => $id,
                'status' => (string) $post->post_status,
                'sections' => is_array($sections) ? count($sections) : (int) $sections,
                'claimedByOtherTheme' => self::claimedByOtherTheme($id, $ownMarker),
            ];
        }

        return $candidates;
    }

    /**
     * True when a sibling theme's marker is already on this page.
     *
     * Exposed for callers outside this class that must not adopt a page
     * another one of these themes already claimed (e.g. the content seeder).
     */
    public static function isClaimedByOtherTheme(int $pageId): bool
    {
        return self::claimedByOtherTheme($pageId, self::markerKey());
    }

    /**
     * A sibling theme's marker on the same page means hands off.
     */
    private static function claimedByOtherTheme(int $pageId, string $ownMarker): bool
    {
        $all = get_post_meta($pageId);

        if (!is_array($all)) {
            return false;
        }

        foreach (array_keys($all) as $key) {
            $key = (string) $key;
            if ($key === $ownMarker) {
                continue;
            }
            if (str_starts_with($key, '_') && str_ends_with($key, '_styleguide')) {
                return true;
            }
        }

        return false;
    }
}
