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
            $level = error_reporting(E_ERROR | E_PARSE);  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting,WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- deliberately silences undefined-variable noise while probing mock-gap templates

            try {
                $factory->make($view, $viewData)->render();
                ++$rendered;
            } catch (Throwable $e) {
                $root = $e;
                while ($root->getPrevious() !== null) {
                    $root = $root->getPrevious();
                }

                if ($this->isMockGap($root->getMessage())) {
                    ++$tolerated;
                } else {
                    $failures[] = $path . ' — ' . $root->getMessage();
                }
            } finally {
                error_reporting($level);  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting,WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- restores prior error_reporting level after the probe
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
     * Catches a failure class testAllTemplatesRenderWithoutRealErrors() cannot
     * see: a Blade component tag (<x-...>) whose attribute list contains a
     * directive (e.g. @if(...) attr=... @endif) never gets recognised as a
     * component tag by the compiler, so it is emitted verbatim as literal
     * text instead of throwing. No exception, no bare-output pattern — the
     * output just silently contains the uncompiled source. Any of these
     * markers surviving into rendered HTML means a component tag or
     * directive failed to compile (real incident: templates/partials/
     * consent-gate.blade.php and templates/flexible/cta.blade.php,
     * templates/flexible/button.blade.php, 2026-09-05).
     */
    public function testNoUncompiledBladeArtifactsInOutput(): void
    {
        $app = Application::getInstance();
        $app->boot();

        $factory = blade();
        $factory->getFinder()->addLocation($this->templatesDir());

        $viewData = self::VIEW_DATA + ['slot' => new \Illuminate\View\ComponentSlot()];
        $offenders = [];

        foreach ($this->templateFiles() as $path) {
            $view = $this->viewName($path);

            // Templates that throw here are already reported by
            // testAllTemplatesRenderWithoutRealErrors(); this test only
            // inspects output that actually rendered.
            $level = error_reporting(E_ERROR | E_PARSE);  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting,WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- deliberately silences undefined-variable noise while probing mock-gap templates

            try {
                $renderedOutput = $factory->make($view, $viewData)->render();
            } catch (Throwable) {
                continue;
            } finally {
                error_reporting($level);  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting,WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- restores prior error_reporting level after the probe
                $factory->flushState();
            }

            foreach (['<x-', '@if(', '@endif', '{{'] as $artifact) {
                if (str_contains($renderedOutput, $artifact)) {
                    $offenders[] = $view . ' — uncompiled Blade artifact in output: ' . $artifact;
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Templates emitted uncompiled Blade syntax (component tag or directive failed to compile):\n" . implode("\n", $offenders),
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

    /**
     * Templates whose iframe only appears inside an Alpine `<template
     * x-if>` consent gate (partials/consent-gate.blade.php): no network
     * request or third-party cookie happens until the visitor clicks the
     * button, because a `<template>` element's content is inert markup to
     * the browser until Alpine clones it into the live DOM. Verified below
     * by asserting the literal `<iframe` tag is nested directly inside the
     * `<template x-if="...">` wrapper rather than emitted bare. Maps view
     * name to the expected consent button label.
     *
     * @var array<string, string>
     */
    private const CONSENT_GATED_IFRAME_TEMPLATES = [
        'flexible.map' => 'Karte laden',
        'flexible.embed' => 'Inhalt laden',
        'flexible.video' => 'Video laden',
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
        'archive', // is_category() not mocked
        'flexible.contact-form', // shortcode_exists() not mocked
        'home', // have_posts() not mocked
        'index', // have_posts() not mocked
        'layouts.app', // wp_date() not mocked
        'member-area.login-page', // wp_date() not mocked
        'page-member-area', // wp_date() not mocked (partials.footer-menu)
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

        // flexible.map, flexible.embed and flexible.video gate their iframe
        // behind Security::isAllowedEmbedHost(): a hostile-payload string is
        // not a valid https URL on an allowed host, so it would fail that
        // check before ever reaching the consent-gate/iframe markup the
        // CONSENT_GATED_IFRAME_TEMPLATES assertions below rely on, and the
        // gate would never actually be exercised. Seed each field with a
        // real allowed-host URL instead (www.google.com and
        // www.youtube-nocookie.com are both in
        // Security::HARDCODED_FRAME_SRC_HOSTS).
        $GLOBALS['wp_mock_sub_fields']['embed_url'] = 'https://www.google.com/maps/embed?pb=x';
        $GLOBALS['wp_mock_sub_fields']['url'] = 'https://www.youtube-nocookie.com/embed/x?a=1&b=2';
        // flexible.video only reaches its consent-gated iframe branch for
        // source=external with a video_url matching the YouTube/Vimeo regex
        // in the template itself; 'source' is also read by
        // flexible.team/flexible.testimonials, where it merely selects
        // their unrelated 'manual' repeater path (anything other than
        // 'cpt'), so this does not change their behaviour.
        $GLOBALS['wp_mock_sub_fields']['source'] = 'external';
        $GLOBALS['wp_mock_sub_fields']['video_url'] = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

        // flexible.newsletter gates its whole raw-output block behind
        // ($isHttps || current_user_can('edit_posts')); its own action_url
        // sub-field is not in $acfKeys, so without this the gate stays
        // closed and the hostile payload never reaches its sink at all.
        $GLOBALS['wp_mock_current_user_can']['edit_posts'] = true;
        // $isHttps also gates the privacy-policy-link branch (line 83-96);
        // without a real https action_url that branch, and the
        // get_privacy_policy_url() link inside it, never render at all.
        $GLOBALS['wp_mock_sub_fields']['action_url'] = 'https://newsletter.example.test/subscribe';
        $GLOBALS['wp_mock_privacy_policy_url'] = 'https://example.test/datenschutz';

        // flexible.logo-slider's esc_js()-escaped pause-button labels only
        // render once $logoData is non-empty, which needs a resolvable
        // attachment image URL, not just a repeater row.
        $GLOBALS['wp_mock_sub_fields']['logos'] = [
            ['logo' => 501, 'link' => $hostile, 'name' => $hostile],
        ];
        $GLOBALS['wp_mock_attachments'][501]['logo'] = ['https://example.test/logo.png', 100, 50];

        // flexible.pricing-table's @js()-escaped yearly/monthly price swap
        // only renders with billing_toggle on and a plan whose yearly price
        // differs from its monthly one.
        $GLOBALS['wp_mock_sub_fields']['billing_toggle'] = true;
        $GLOBALS['wp_mock_sub_fields']['plans'] = [
            [
                'name' => $hostile,
                'price' => $hostile,
                'period' => $hostile,
                'price_yearly' => $hostile,
                'features' => $hostile,
            ],
        ];

        $offenders = [];
        $thrown = [];
        $rendered = 0;
        $escaped = 0;
        $capturedOutputs = [];

        foreach ($this->templateFiles() as $path) {
            $view = $this->viewName($path);

            if (in_array($view, self::RAW_OUTPUT_TEMPLATES, true)) {
                $this->assertRawOutputTemplateSanitizes($factory, $view, $viewData, $offenders, $thrown);

                continue;
            }

            $level = error_reporting(E_ERROR | E_PARSE);  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting,WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- deliberately silences undefined-variable noise while probing mock-gap templates

            try {
                $renderedOutput = $factory->make($view, $viewData)->render();
                ++$rendered;

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
                    ++$escaped;
                }

                if (in_array($view, ['flexible.logo-slider', 'flexible.pricing-table'], true)) {
                    $capturedOutputs[$view] = $renderedOutput;
                }

                if (array_key_exists($view, self::CONSENT_GATED_IFRAME_TEMPLATES)) {
                    $buttonLabel = self::CONSENT_GATED_IFRAME_TEMPLATES[$view];

                    if (!str_contains($renderedOutput, $buttonLabel)) {
                        $offenders[] = $path . " (consent button \"{$buttonLabel}\" missing)";
                    }

                    if (!preg_match('/<template\s+x-if="[^"]*"\s*>\s*<iframe\b/s', $renderedOutput)) {
                        $offenders[] = $path . ' (iframe not gated inside <template x-if>)';
                    }
                }
            } catch (Throwable) {
                $thrown[] = $view;
            } finally {
                error_reporting($level);  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting,WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- restores prior error_reporting level after the probe
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

        $this->assertStringContainsString(
            self::ESCAPED_MARKER,
            $capturedOutputs['flexible.logo-slider'] ?? '',
            'flexible.logo-slider did not exercise its esc_js()-escaped code path (empty $logoData?).',
        );
        $this->assertStringContainsString(
            self::ESCAPED_MARKER,
            $capturedOutputs['flexible.pricing-table'] ?? '',
            'flexible.pricing-table did not exercise its @js()-escaped code path (empty $plans or switchesPrice false?).',
        );
    }

    /**
     * Renders one of the RAW_OUTPUT_TEMPLATES (which intentionally echo part
     * of their content via {!! !!}) and asserts that the value each of them
     * sanitizes before that raw echo (Text::lineBreaks(), wp_kses_post(),
     *
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
        $level = error_reporting(E_ERROR | E_PARSE);  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting,WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- deliberately silences undefined-variable noise while probing mock-gap templates

        try {
            $renderedOutput = $factory->make($view, $viewData)->render();
        } catch (Throwable) {
            $thrown[] = $view;

            return;
        } finally {
            error_reporting($level);  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting,WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- restores prior error_reporting level after the probe
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

    /**
     * Flexible.member-downloads gates its whole download table behind
     * Auth::isAuthenticated() (templates/flexible/member-downloads.blade.php:17),
     * which was never exercised by the other render passes above (they never
     * seed a logged-in user), so the gate itself was untested.
     */
    public function testMemberDownloadsGatesOnAuthentication(): void
    {
        $app = Application::getInstance();
        $app->boot();

        $factory = blade();
        $factory->getFinder()->addLocation($this->templatesDir());

        $viewData = self::VIEW_DATA + ['slot' => new \Illuminate\View\ComponentSlot(), 'sectionAnchor' => null];

        $GLOBALS['wp_mock_fields']['page_is_member_area'] = true;
        $GLOBALS['wp_mock_current_user_id'] = 0;
        $GLOBALS['wp_mock_current_user_can'] = [];

        try {
            $loggedOutOutput = $factory->make('flexible.member-downloads', $viewData)->render();
            $factory->flushState();

            $this->assertStringNotContainsString(
                'x-data="downloadTable"',
                $loggedOutOutput,
                'Download table rendered for a logged-out visitor.',
            );

            // Auth::isAuthenticated() grants access unconditionally to a
            // logged-in administrator (manage_options), regardless of the
            // configured member-area auth mode - the simplest logged-in path.
            $GLOBALS['wp_mock_current_user_id'] = 1;
            $GLOBALS['wp_mock_current_user_can']['manage_options'] = true;

            $loggedInOutput = $factory->make('flexible.member-downloads', $viewData)->render();
            $factory->flushState();

            $this->assertStringContainsString(
                'x-data="downloadTable"',
                $loggedInOutput,
                'Download table did not render for an authenticated administrator.',
            );
        } finally {
            unset(
                $GLOBALS['wp_mock_fields']['page_is_member_area'],
                $GLOBALS['wp_mock_current_user_id'],
                $GLOBALS['wp_mock_current_user_can'],
            );
        }
    }

    /**
     * Flexible.video (templates/flexible/video.blade.php) derives the
     * <source type="..."> attribute from the uploaded file's extension and
     * the external-video branch from a host-anchored URL parse. Covers the
     * two regressions fixed alongside R2-S3/R2-S1: an .ogg upload getting a
     * <source> without a type (wp_check_filetype() reports audio/ogg for
     * .ogg), and youtu.be/an unrelated host bypassing the old
     * youtube.com|youtu.be-only pattern.
     */
    public function testVideoLayoutSourceTypesAndFileExtensions(): void
    {
        $app = Application::getInstance();
        $app->boot();

        $factory = blade();
        $factory->getFinder()->addLocation($this->templatesDir());

        $viewData = self::VIEW_DATA + ['slot' => new \Illuminate\View\ComponentSlot(), 'sectionAnchor' => null];

        try {
            // Self-hosted upload, .webm.
            $GLOBALS['wp_mock_sub_fields'] = [
                'source' => 'wordpress',
                'video' => 'https://example.test/movie.webm',
                'poster' => 601,
                'captions' => 'https://example.test/captions.vtt',
            ];
            $GLOBALS['wp_mock_attachments'][601]['hero-background'] = ['https://example.test/poster.jpg', 1200, 675];

            $webmOutput = $factory->make('flexible.video', $viewData)->render();
            $factory->flushState();

            $this->assertStringContainsString(
                '<source src="https://example.test/movie.webm" type="video/webm">',
                $webmOutput,
                'Self-hosted .webm upload did not get a type="video/webm" <source> tag.',
            );

            // Self-hosted upload, .ogg — the regression: wp_check_filetype()
            // reports audio/ogg for this extension, so the old code either
            // skipped the type or mistyped it.
            $GLOBALS['wp_mock_sub_fields']['video'] = 'https://example.test/movie.ogg';

            $oggOutput = $factory->make('flexible.video', $viewData)->render();
            $factory->flushState();

            $this->assertStringContainsString(
                '<source src="https://example.test/movie.ogg" type="video/ogg">',
                $oggOutput,
                '.ogg upload did not resolve to type="video/ogg".',
            );

            // External youtu.be short link — the old pattern already matched
            // this form, but only via string search, not a host-anchored
            // parse; keep it green as the anchored replacement's baseline.
            $GLOBALS['wp_mock_sub_fields'] = [
                'source' => 'external',
                'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            ];

            $youtuBeOutput = $factory->make('flexible.video', $viewData)->render();
            $factory->flushState();

            $this->assertStringContainsString(
                'youtube-nocookie.com/embed/dQw4w9WgXcQ',
                $youtuBeOutput,
                'youtu.be short link did not resolve to a YouTube-nocookie embed.',
            );

            // Unrelated host must never be detected as a video provider —
            // the template falls back to $video_type = 'self', which for
            // source=external with no matching branch renders no iframe.
            $GLOBALS['wp_mock_sub_fields']['video_url'] = 'https://example.test/youtube.com/watch?v=dQw4w9WgXcQ';

            $unrelatedHostOutput = $factory->make('flexible.video', $viewData)->render();
            $factory->flushState();

            $this->assertStringNotContainsString(
                '<iframe',
                $unrelatedHostOutput,
                'An unrelated host produced a video iframe.',
            );
        } finally {
            unset(
                $GLOBALS['wp_mock_sub_fields'],
                $GLOBALS['wp_mock_attachments'][601],
            );
        }
    }

    /**
     * Flexible.posts (templates/flexible/posts.blade.php:15-19) checks the
     * backend-supplied post_type sub-field against get_post_types(['public'
     * => true]) and silently falls back to 'post' for anything not in that
     * allowlist. Never exercised before: WP_Query was unmocked, so the
     * template threw before reaching WP_Query::__construct(), which is where
     * the resolved $postType lands in the 'post_type' arg.
     */
    public function testPostsLayoutFiltersPostTypeAgainstAllowlist(): void
    {
        $app = Application::getInstance();
        $app->boot();

        $factory = blade();
        $factory->getFinder()->addLocation($this->templatesDir());

        $viewData = self::VIEW_DATA + ['slot' => new \Illuminate\View\ComponentSlot(), 'sectionAnchor' => null];

        $GLOBALS['wp_mock_post_types'] = ['page' => ['public' => true]];

        try {
            // Not in the public-post-types allowlist (and not the hardcoded
            // 'post' addition in the template) -> falls back to 'post'.
            $GLOBALS['wp_mock_sub_fields']['post_type'] = 'member_download';
            $factory->make('flexible.posts', $viewData)->render();
            $factory->flushState();
            $this->assertSame(
                'post',
                $GLOBALS['wp_mock_last_query_args']['post_type'] ?? null,
                'A non-public post type should fall back to "post".',
            );

            $GLOBALS['wp_mock_sub_fields']['post_type'] = 'attachment';
            $factory->make('flexible.posts', $viewData)->render();
            $factory->flushState();
            $this->assertSame(
                'post',
                $GLOBALS['wp_mock_last_query_args']['post_type'] ?? null,
                '"attachment" is explicitly unset from the allowlist and must fall back to "post".',
            );

            // A public post type in the allowlist passes through unchanged.
            $GLOBALS['wp_mock_sub_fields']['post_type'] = 'page';
            $factory->make('flexible.posts', $viewData)->render();
            $factory->flushState();
            $this->assertSame(
                'page',
                $GLOBALS['wp_mock_last_query_args']['post_type'] ?? null,
                'A public post type in the allowlist should pass through unchanged.',
            );
        } finally {
            unset(
                $GLOBALS['wp_mock_post_types'],
                $GLOBALS['wp_mock_sub_fields']['post_type'],
                $GLOBALS['wp_mock_last_query_args'],
            );
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
     * First naming segment (e.g. "have_", "sanitize_") of every mocked
     * function, derived from mockedFunctionNames(). A function that shares
     * one of these prefixes belongs to a WordPress/ACF naming family the
     * bootstrap already partially covers, so an unmocked sibling is a
     * legitimate mock gap rather than a theme bug.
     *
     * "esc_" is excluded on purpose: every esc_* function is a small,
     * self-contained WordPress escaping helper the bootstrap can always
     * mock faithfully, never a "genuinely unmockable" core internal like
     * wp_date() or have_posts(). Blanket-tolerating the whole "esc_"
     * family let esc_js() and esc_html_e() go unmocked for months while
     * every template calling them silently counted as passing (real
     * incident, 2026-09-05): an unmocked esc_* function is now a real
     * failure here, exactly like an unmocked non-WordPress function, and
     * must be added to the bootstrap by exact name instead.
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
            unset($prefixes['esc_']);
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
