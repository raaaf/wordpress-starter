<?php

declare(strict_types=1);

namespace WordpressStarter\Helpers;

/**
 * Request-scoped id generation shared across Blade form/section components.
 *
 * A `static $counter` variable declared inside a compiled Blade view is
 * scoped to that view's compiled function, not to the request: PHPUnit tests
 * that render the same component markup through a fresh, uniquely-named
 * probe view each time never reuse that function, so the counter restarts at
 * zero on every render instead of staying unique across the whole request.
 * Centralizing the counters here, as class-level statics on a single class
 * that every component render shares, fixes that without touching how each
 * component is invoked.
 */
class ComponentId
{
    /** @var array<string, int> */
    private static array $counters = [];

    /** @var array<string, int> */
    private static array $seen = [];

    /** @var array<string, true> */
    private static array $used = [];

    /**
     * Returns a sequential id for the given prefix: "{prefix}-1", "{prefix}-2", ...
     * An empty prefix (e.g. a $name prop that sanitizes to '') falls back to
     * "field" so the result is never a bare "-1".
     */
    public static function next(string $prefix): string
    {
        if ($prefix === '') {
            $prefix = 'field';
        }

        self::$counters[$prefix] = ( self::$counters[$prefix] ?? 0 ) + 1;

        return $prefix . '-' . self::$counters[$prefix];
    }

    /**
     * Returns $id unchanged the first time it is seen in this request; every
     * repeat gets "-2", "-3", ... appended. Every id this method ever
     * returns, suffixed or not, is registered so a later raw id that
     * happens to collide with an earlier suffix (e.g. "foo", "foo-2", "foo")
     * never produces the same id twice: the loop keeps incrementing until it
     * finds a suffix nothing has claimed yet.
     */
    public static function unique(string $id): string
    {
        if (!isset(self::$used[$id])) {
            self::$used[$id] = true;

            return $id;
        }

        $suffix = self::$seen[$id] ?? 1;

        do {
            ++$suffix;
            $candidate = $id . '-' . $suffix;
        } while (isset(self::$used[$candidate]));

        self::$seen[$id] = $suffix;
        self::$used[$candidate] = true;

        return $candidate;
    }

    /**
     * Slugifies a user-supplied anchor (e.g. the `section_anchor` ACF field)
     * the same way every consumer of that field must: sanitize_title()
     * transliterates umlauts via remove_accents() first, unlike
     * sanitize_html_class(), which strips accented characters outright. Any
     * template that builds an `href="#..."` link from the same raw field
     * (e.g. templates/partials/styleguide-nav.blade.php) MUST slugify through
     * this method too, or the link target silently stops matching the id
     * section.blade.php actually renders.
     */
    public static function anchor(string $raw): string
    {
        return sanitize_title($raw);
    }

    /**
     * Resets all counters. Test-only, called from
     * Tests\Support\WordPressMocks::resetAllMocks().
     *
     * Request-scope caveat: these counters are class-level statics, so they
     * live for the lifetime of the PHP process, not strictly one HTTP
     * request. On the standard PHP-FPM/mod_php setup this theme runs on,
     * that is the same thing -- one process serves exactly one request, so
     * counters start fresh every time regardless. It stops being the same
     * thing only under a long-running SAPI (Swoole, RoadRunner, PHP-PM) that
     * reuses one process across requests; only there would a per-request
     * reset (e.g. hooked to WordPress' `wp` action) become necessary.
     *
     * Do NOT hook reset() into Application::boot(): boot() itself can run
     * more than once within a single process on the CURRENT setup too (see
     * the comment on BladeServiceProvider::setupContainer()), and several
     * component tests deliberately render the same `<x-input>`/`<x-radio>`
     * markup across two separate boot() + render cycles to assert the
     * SECOND render gets a distinct id from the first
     * (FormComponentsTest::testInputWithoutIdGetsUniqueIdMatchingLabelFor).
     * Resetting on every boot() would collapse that back to duplicate ids.
     */
    public static function reset(): void
    {
        self::$counters = [];
        self::$seen = [];
        self::$used = [];
    }
}
