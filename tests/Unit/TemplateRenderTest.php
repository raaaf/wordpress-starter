<?php

declare(strict_types=1);

namespace WordpressStarter\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use Throwable;
use WordpressStarter\Application;

/**
 * Render smoke test for all Blade templates.
 *
 * Renders every templates/**\/*.blade.php with the WordPress mocks from the
 * test bootstrap. Catches the two failure classes that broke production on
 * 2026-06-11 and that no other check covers:
 *
 * 1. References to classes that do not exist in this theme (e.g. an
 *    unconverted namespace from another theme in the fork family).
 * 2. Templates corrupted by mass edits (e.g. a stripped Blade directive
 *    leaving a bare ($var) echoed as literal text).
 *
 * Missing WordPress core functions/classes in the mock environment are
 * tolerated; everything else that throws fails the test.
 */
final class TemplateRenderTest extends TestCase
{
    /** Namespaces of the other themes in the fork family — must never leak in. */
    private const FOREIGN_NAMESPACES = [
        'GoldeneStrategie\\',
        'StiftungsNavigator\\',
        'moenius\\',
        'Siera\\',
        'FIMVertrieb\\',
    ];

    /** Default view data so standalone renders of partials do not fatal on required params. */
    private const VIEW_DATA = [
        'layoutCounters' => [],
        'items' => [],
        'idPrefix' => 'test',
        'pagination' => '',
        'ariaLabel' => 'test',
        'navClass' => '',
        'title' => 'test',
        'text' => 'test',
        'svgPath' => '',
    ];

    public function testAllTemplatesRenderWithoutRealErrors(): void
    {
        $app = Application::getInstance();
        $app->boot();

        $factory = blade();
        $factory->getFinder()->addLocation($this->templatesDir());

        $failures = [];
        $rendered = 0;
        $tolerated = 0;
        $viewData = self::VIEW_DATA + ['slot' => new \Illuminate\View\ComponentSlot()];

        foreach ($this->templateFiles() as $path) {
            $view = $this->viewName($path);

            // Standalone component renders produce undefined-variable warnings;
            // only Throwables are relevant here.
            $level = error_reporting(E_ERROR | E_PARSE);

            try {
                $factory->make($view, $viewData)->render();
                $rendered++;
            } catch (Throwable $e) {
                $root = $e;
                while ($root->getPrevious() !== null) {
                    $root = $root->getPrevious();
                }

                if ($this->isMockGap($root->getMessage())) {
                    $tolerated++;
                } else {
                    $failures[] = $path . ' — ' . $root->getMessage();
                }
            } finally {
                error_reporting($level);
                $factory->flushState();
            }
        }

        $this->assertGreaterThan(0, $rendered, 'No template rendered at all — harness broken?');
        $this->assertSame(
            [],
            $failures,
            "Templates failed to render with real errors:\n" . implode("\n", $failures),
        );
    }

    /**
     * Templates that emit part of their sub-field content as raw, unescaped
     * HTML (via {!! !!}) instead of the default Blade escaping. Each of these
     * sanitizes the raw-echoed value itself before output (Text::lineBreaks()
     * for titles/copy, wp_kses_post()/@kses for body content) rather than
     * relying on Blade's automatic escaping, so they are rendered separately
     * below via assertRawOutputTemplateSanitizes() instead of the plain
     * "escaped output" check the other templates get.
     */
    private const RAW_OUTPUT_TEMPLATES = [
        'flexible.cta',
        'flexible.hero',
        'flexible.contact-form',
        'flexible.newsletter',
        'flexible.two-columns-images',
        'flexible.three-columns-images',
        'flexible.four-columns-images',
    ];

    private const HOSTILE_PAYLOAD = '<script>alert(1)</script>"&\'"><img src=x onerror=alert(1)> javascript:alert(1)';

    /** Proves a template actually escaped the payload rather than merely not crashing. */
    private const ESCAPED_MARKER = '&lt;script&gt;';

    /** Minimum templates that must render successfully with the hostile payload seeded. */
    private const MIN_RENDERED = 58;

    /**
     * Minimum templates whose output must contain the escaped payload
     * marker. Measured baseline: 3 templates proved escaping from VIEW_DATA
     * alone (title/text passed directly to partials); seeding the ACF
     * get_field()/get_sub_field() mocks with $acfKeys below raises that to 6,
     * since most templates use field names outside the fixed seed list or
     * pull content via repeaters (have_rows()), which this seed does not
     * cover.
     */
    private const MIN_ESCAPED = 6;

    /**
     * Templates confirmed (by reading their source, not by assumption) to
     * throw during the hostile-payload render pass because of a mock gap in
     * tests/bootstrap.php, not a template defect. Each entry documents the
     * gap so a future regression in one of these templates is not silently
     * re-added to the list without a matching reason.
     */
    private const ALLOWED_THROWING_TEMPLATES = [
        '404', // wp_date() not mocked
        'admin.design-tokens-page', // esc_js() not mocked
        'admin.setup-page', // esc_html_e() not mocked
        'archive', // is_category() not mocked
        'flexible.contact-form', // shortcode_exists() not mocked
        'flexible.posts', // WP_Query class not mocked
        'home', // have_posts() not mocked
        'index', // have_posts() not mocked
        'layouts.app', // wp_date() not mocked
        'member-area.login-page', // wp_date() not mocked
        'page-member-area', // WordpressStarter\MemberArea\is_user_logged_in() not mocked
        'page-styleguide', // sanitize_key() not mocked
        'page', // have_posts() not mocked
        'partials.footer-menu', // wp_date() not mocked
        'partials.footer', // wp_date() not mocked
        'partials.styleguide-views', // add_query_arg() not mocked
        'partials.the_loop', // have_posts() not mocked
        'search', // get_search_query() not mocked
        'single-testimonial', // wp_date() not mocked
        'single', // have_posts() not mocked
    ];

    /**
     * Same render pass as testAllTemplatesRenderWithoutRealErrors(), but with
     * a hostile payload in every string VIEW_DATA field and in the ACF
     * get_field()/get_sub_field() mocks instead of a benign placeholder: a
     * benign placeholder never proves that a field is actually escaped on
     * output, and most templates read their content from ACF sub-fields
     * rather than from VIEW_DATA.
     */
    public function testTemplatesEscapeHostilePayloadInViewData(): void
    {
        $app = Application::getInstance();
        $app->boot();

        $factory = blade();
        $factory->getFinder()->addLocation($this->templatesDir());

        // Blade's compiled-cache staleness check only compares the source
        // view's mtime against the compiled file, not whether a directive
        // like @kses changed meaning since it was compiled. A stale compiled
        // view (e.g. from before @kses was registered) silently keeps
        // emitting the un-sanitized literal directive text, which would mask
        // exactly the escaping defect this test exists to catch. Force a
        // fresh compile for this pass so the assertions below reflect the
        // current template source, not a stale cache.
        foreach (glob(get_template_directory() . '/compiled/*.php') ?: [] as $compiledView) {
            @unlink($compiledView);
        }

        $hostile = self::HOSTILE_PAYLOAD;
        $viewData = [
            'layoutCounters' => [],
            'items' => [],
            'idPrefix' => $hostile,
            'pagination' => '',
            'ariaLabel' => $hostile,
            'navClass' => $hostile,
            'title' => $hostile,
            'text' => $hostile,
            'svgPath' => '',
            'slot' => new \Illuminate\View\ComponentSlot(),
        ];

        // Seed the ACF mocks so templates pulling content via get_sub_field()/
        // get_field() (the majority) also receive the hostile payload. The
        // bootstrap mocks look up fields by exact name with no wildcard, so
        // the most common field names across templates/flexible/*.blade.php
        // are seeded explicitly.
        $acfKeys = [
            'title', 'headline', 'subline', 'text', 'content', 'description',
            'label', 'button_text', 'link', 'cta_text', 'eyebrow', 'caption',
            'quote', 'name', 'role',
            // column_N feeds the raw-echoed body text of the *-columns-images
            // layouts (see RAW_OUTPUT_TEMPLATES); without it those templates
            // never receive the payload at all and the sanitize check below
            // would be untestable rather than proven.
            'column_1', 'column_2', 'column_3', 'column_4',
        ];
        $GLOBALS['wp_mock_sub_fields'] = array_fill_keys($acfKeys, $hostile);
        $GLOBALS['wp_mock_fields'] = array_fill_keys($acfKeys, $hostile);

        // flexible.newsletter gates its whole raw-output block behind
        // ($isHttps || current_user_can('edit_posts')); its own action_url
        // sub-field is not in $acfKeys, so without this the gate stays
        // closed and the hostile payload never reaches its sink at all.
        $GLOBALS['wp_mock_current_user_can']['edit_posts'] = true;

        $offenders = [];
        $thrown = [];
        $rendered = 0;
        $escaped = 0;

        foreach ($this->templateFiles() as $path) {
            $view = $this->viewName($path);

            if (in_array($view, self::RAW_OUTPUT_TEMPLATES, true)) {
                $this->assertRawOutputTemplateSanitizes($factory, $view, $viewData, $offenders, $thrown);

                continue;
            }

            $level = error_reporting(E_ERROR | E_PARSE);

            try {
                $renderedOutput = $factory->make($view, $viewData)->render();
                $rendered++;

                if (str_contains($renderedOutput, '<script>alert(1)</script>')) {
                    $offenders[] = $path;
                }

                if (str_contains($renderedOutput, '<img src=x onerror=alert(1)>')) {
                    $offenders[] = $path . ' (unescaped onerror handler)';
                }

                if (str_contains(strtolower($renderedOutput), 'href="javascript:')) {
                    $offenders[] = $path . ' (unescaped javascript: scheme)';
                }

                if (str_contains($renderedOutput, self::ESCAPED_MARKER)) {
                    $escaped++;
                }
            } catch (Throwable) {
                $thrown[] = $view;
            } finally {
                error_reporting($level);
                $factory->flushState();
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Templates rendered the hostile payload unescaped:\n" . implode("\n", $offenders),
        );

        $unexpectedThrows = array_diff($thrown, self::ALLOWED_THROWING_TEMPLATES);
        $this->assertSame(
            [],
            $unexpectedThrows,
            "Templates threw during the hostile-payload pass and are not in ALLOWED_THROWING_TEMPLATES:\n"
                . implode("\n", $unexpectedThrows),
        );

        $this->assertGreaterThanOrEqual(
            self::MIN_RENDERED,
            $rendered,
            'Fewer templates rendered successfully than expected, a mock gap may be silently swallowing real failures.',
        );
        $this->assertGreaterThanOrEqual(
            self::MIN_ESCAPED,
            $escaped,
            'Fewer templates than expected proved actual escaping of the hostile payload, the payload may no longer be reaching output sinks.',
        );
    }

    /**
     * Renders one of the RAW_OUTPUT_TEMPLATES (which intentionally echo part
     * of their content via {!! !!}) and asserts that the value each of them
     * sanitizes before that raw echo (Text::lineBreaks(), wp_kses_post(),
     * @kses) actually strips the hostile payload's script tag and any event
     * handler, while the benign remainder of the payload text still renders.
     * A template that emits the raw payload unescaped is a real XSS, reported
     * as a test failure via $offenders, not skipped.
     *
     * @param array<string, mixed> $viewData
     * @param list<string> $offenders
     * @param list<string> $thrown
     */
    private function assertRawOutputTemplateSanitizes(
        \Illuminate\View\Factory $factory,
        string $view,
        array $viewData,
        array &$offenders,
        array &$thrown,
    ): void {
        $level = error_reporting(E_ERROR | E_PARSE);

        try {
            $renderedOutput = $factory->make($view, $viewData)->render();
        } catch (Throwable) {
            $thrown[] = $view;

            return;
        } finally {
            error_reporting($level);
            $factory->flushState();
        }

        if (
            str_contains($renderedOutput, '<script')
            || str_contains(strtolower($renderedOutput), 'onerror=alert(1)')
            || str_contains(strtolower($renderedOutput), 'href="javascript:')
        ) {
            $offenders[] = $view . ' (raw output template did not sanitize the hostile payload)';

            return;
        }

        if (!str_contains($renderedOutput, 'alert(1)')) {
            $offenders[] = $view . ' (raw output template stripped the benign remainder of the payload too, not just the tag)';
        }
    }

    public function testNoForeignThemeNamespaceReferences(): void
    {
        $offenders = [];

        foreach ($this->templateFiles() as $path) {
            $content = file_get_contents($path);
            if ($content === false) {
                continue;
            }

            foreach (self::FOREIGN_NAMESPACES as $namespace) {
                if (str_contains($content, $namespace)) {
                    $offenders[] = $path . ' — references ' . rtrim($namespace, '\\');
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Templates reference a foreign theme namespace (unconverted propagation):\n" . implode("\n", $offenders),
        );
    }

    public function testIsMockGapFailsForTypoedThemeFunction(): void
    {
        // A theme-specific function name matches none of the tolerated
        // WordPress/ACF prefixes, so it must NOT be treated as a mock gap.
        // Otherwise it would mask a typo'd theme function forever.
        $this->assertFalse(
            $this->invokeIsMockGap('Call to undefined function theme_typo_function()'),
        );
    }

    public function testIsMockGapFailsForAlreadyMockedFunction(): void
    {
        // esc_url is already mocked by the test bootstrap. If it is reported
        // as undefined, that is a real bug (broken bootstrap require order),
        // never a legitimate mock gap.
        $this->assertFalse(
            $this->invokeIsMockGap('Call to undefined function esc_url()'),
        );
    }

    public function testIsMockGapTolerantForUnmockedCoreFunction(): void
    {
        // Not mocked by the bootstrap, but matches a tolerated WordPress
        // core prefix.
        $this->assertTrue(
            $this->invokeIsMockGap('Call to undefined function wp_some_unmocked_core_function()'),
        );
    }

    private function invokeIsMockGap(string $message): bool
    {
        $method = new ReflectionMethod($this, 'isMockGap');
        $method->setAccessible(true);

        return $method->invoke($this, $message);
    }

    public function testNoBareOutputExpressions(): void
    {
        // A bare ($var) in output position is the residue of a stripped Blade
        // directive (e.g. @kses($var) mangled by a faulty mass edit).
        $pattern = '/(^\s*|>)\(\$\w+(\[.{1,3}\w+.{1,3}\])?\)(<|\s*$)/m';
        $offenders = [];

        foreach ($this->templateFiles() as $path) {
            $content = file_get_contents($path);
            if ($content === false) {
                continue;
            }

            if (preg_match($pattern, $content, $match)) {
                $offenders[] = $path . ' — bare output expression: ' . trim($match[0]);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Templates contain bare output expressions (stripped directive?):\n" . implode("\n", $offenders),
        );
    }

    /**
     * WordPress core functions/classes are not fully mocked in the test
     * bootstrap; their absence is not a theme bug. A function the bootstrap
     * DOES mock but that still reports as undefined is never a mock gap.
     * That indicates a real bug (e.g. a broken require order), not a
     * missing mock. Everything else is tolerated only if it shares a
     * WordPress/ACF naming family (e.g. "esc_", "get_", "have_") with a
     * function the bootstrap already mocks — narrow enough that a typo'd
     * theme function (e.g. "theme_typo_function") does not match and still
     * fails the test.
     */
    private function isMockGap(string $message): bool
    {
        if (preg_match('/Call to undefined function (?:[A-Za-z_][A-Za-z0-9_]*\\\\)*([A-Za-z_][A-Za-z0-9_]*)\(\)/', $message, $match)) {
            $function = $match[1];

            if (in_array($function, $this->mockedFunctionNames(), true)) {
                return false;
            }

            foreach ($this->mockedFunctionPrefixes() as $prefix) {
                if (str_starts_with($function, $prefix)) {
                    return true;
                }
            }

            return false;
        }

        if (preg_match('/Class "(WP_[A-Za-z_]+|WP\\\\[A-Za-z_\\\\]+)" not found/', $message)) {
            return true;
        }

        return false;
    }

    /**
     * Function names the test bootstrap mocks, parsed straight out of
     * tests/bootstrap.php so this list can never drift from the actual mocks.
     *
     * @return list<string>
     */
    private function mockedFunctionNames(): array
    {
        static $names = null;

        if ($names === null) {
            $bootstrap = file_get_contents(dirname(__DIR__) . '/bootstrap.php');
            preg_match_all('/function_exists\(\'([A-Za-z_][A-Za-z0-9_]*)\'\)/', (string) $bootstrap, $matches);
            $names = $matches[1];
        }

        return $names;
    }

    /**
     * First naming segment (e.g. "esc_", "have_", "sanitize_") of every
     * mocked function, derived from mockedFunctionNames(). A function that
     * shares one of these prefixes belongs to a WordPress/ACF naming family
     * the bootstrap already partially covers, so an unmocked sibling
     * (esc_js() next to the mocked esc_url()) is a legitimate mock gap
     * rather than a theme bug.
     *
     * @return list<string>
     */
    private function mockedFunctionPrefixes(): array
    {
        static $prefixes = null;

        if ($prefixes === null) {
            $prefixes = [];
            foreach ($this->mockedFunctionNames() as $name) {
                if (preg_match('/^([a-z]+_)/', $name, $match)) {
                    $prefixes[$match[1]] = true;
                }
            }
            $prefixes = array_keys($prefixes);
        }

        return $prefixes;
    }

    /** @return list<string> */
    private function templateFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->templatesDir(), RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            $path = $file->getPathname();
            if (str_ends_with($path, '.blade.php')) {
                $files[] = $path;
            }
        }

        sort($files);

        return $files;
    }

    private function viewName(string $path): string
    {
        $relative = substr($path, strlen($this->templatesDir()) + 1);

        return str_replace(['/', '.blade.php'], ['.', ''], $relative);
    }

    private function templatesDir(): string
    {
        return dirname(__DIR__, 2) . '/templates';
    }
}
