<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\Support\TestCase;
use WordpressStarter\Services\StyleguidePage;
use WordpressStarter\ThemeContext;

/**
 * The decision logic guarded here is the one that can overwrite somebody's page,
 * so the cases below are deliberately about refusing rather than about finding.
 */
final class StyleguidePageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_mock_template'] = 'wordpress-starter-theme';
        $GLOBALS['wp_mock_options'] = [];
        $GLOBALS['wp_mock_post_meta'] = [];
        $GLOBALS['wp_mock_posts_by_id'] = [];
        ThemeContext::reset();
    }

    private function candidate(int $id, string $status = 'private', int $sections = 40, bool $claimed = false): array
    {
        return ['id' => $id, 'status' => $status, 'sections' => $sections, 'claimedByOtherTheme' => $claimed];
    }

    public function testMarkerKeyIsHiddenAndThemePrefixed(): void
    {
        $this->assertSame('_wordpress_starter_theme_styleguide', StyleguidePage::markerKey());
    }

    public function testPicksTheSingleUsableCandidate(): void
    {
        $this->assertSame(632, StyleguidePage::chooseCandidate([$this->candidate(632)]));
    }

    public function testReturnsNothingWhenThereAreNoCandidates(): void
    {
        $this->assertSame(0, StyleguidePage::chooseCandidate([]));
    }

    public function testIgnoresTrashedPages(): void
    {
        $result = StyleguidePage::chooseCandidate([
            $this->candidate(580, 'trash', 43),
            $this->candidate(632),
        ]);

        $this->assertSame(632, $result);
    }

    public function testIgnoresPagesClaimedByAnotherTheme(): void
    {
        $result = StyleguidePage::chooseCandidate([
            $this->candidate(500, 'private', 40, true),
            $this->candidate(632),
        ]);

        $this->assertSame(632, $result);
    }

    public function testIgnoresPagesWithoutLayouts(): void
    {
        $result = StyleguidePage::chooseCandidate([
            $this->candidate(700, 'private', 0),
            $this->candidate(632),
        ]);

        $this->assertSame(632, $result);
    }

    /**
     * The important one. Two plausible pages must never resolve to a guess.
     */
    public function testReportsAmbiguityInsteadOfGuessing(): void
    {
        $result = StyleguidePage::chooseCandidate([
            $this->candidate(632),
            $this->candidate(640),
        ]);

        $this->assertSame(StyleguidePage::AMBIGUOUS, $result);
    }

    public function testValidateRejectsATrashedPage(): void
    {
        $GLOBALS['wp_mock_posts_by_id'][580] = ['post_type' => 'page', 'post_status' => 'trash'];

        $this->assertSame(0, StyleguidePage::validate(580));
    }

    public function testValidateRejectsAMissingPage(): void
    {
        $this->assertSame(0, StyleguidePage::validate(455));
    }

    public function testValidateRejectsANonPage(): void
    {
        $GLOBALS['wp_mock_posts_by_id'][12] = ['post_type' => 'post', 'post_status' => 'publish'];

        $this->assertSame(0, StyleguidePage::validate(12));
    }

    public function testValidateAcceptsAPrivatePage(): void
    {
        $GLOBALS['wp_mock_posts_by_id'][632] = ['post_type' => 'page', 'post_status' => 'private'];

        $this->assertSame(632, StyleguidePage::validate(632));
    }

    public function testAdoptWritesMarkerAndOption(): void
    {
        StyleguidePage::adopt(632);

        $this->assertSame('1', get_post_meta(632, StyleguidePage::markerKey(), true));
        $this->assertSame(632, get_option(StyleguidePage::optionKey()));
    }

    public function testNeedsMigrationWhenTemplateIsNotTheNewOne(): void
    {
        $GLOBALS['wp_mock_post_meta'][632]['_wp_page_template'] = StyleguidePage::LEGACY_TEMPLATE;

        $this->assertTrue(StyleguidePage::needsMigration(632));
    }

    public function testDoesNotNeedMigrationOnceSwitched(): void
    {
        $GLOBALS['wp_mock_post_meta'][632]['_wp_page_template'] = StyleguidePage::TEMPLATE;

        $this->assertFalse(StyleguidePage::needsMigration(632));
    }

    public function testForgetClearsBothOptionAndMarker(): void
    {
        StyleguidePage::adopt(632);
        $GLOBALS['wp_mock_posts']['page'] = [632];

        StyleguidePage::forget();

        $this->assertEmpty(get_post_meta(632, StyleguidePage::markerKey(), true));
        $this->assertFalse(get_option(StyleguidePage::optionKey(), false));
    }

    /**
     * The AMBIGUOUS deadlock: two pages carry the marker while the option points
     * at neither, which is the only way find() reaches that state at all. Clearing
     * by option alone would delete nothing and leave find() answering AMBIGUOUS
     * forever, with no way out through the Tools panel.
     */
    public function testForgetClearsMarkersOnEveryMarkedPageNotJustTheOptionOne(): void
    {
        $marker = StyleguidePage::markerKey();
        $GLOBALS['wp_mock_post_meta'][701][$marker] = '1';
        $GLOBALS['wp_mock_post_meta'][702][$marker] = '1';
        $GLOBALS['wp_mock_posts']['page'] = [701, 702];

        StyleguidePage::forget();

        $this->assertEmpty(get_post_meta(701, $marker, true));
        $this->assertEmpty(get_post_meta(702, $marker, true));
    }
}
