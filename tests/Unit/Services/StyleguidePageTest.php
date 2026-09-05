<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use ArrayAccess;
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
        $GLOBALS['wp_mock_current_user_can'] = ['edit_pages' => true];
        $GLOBALS['wp_mock_doing_ajax'] = false;
        ThemeContext::reset();
    }

    private function candidate(int $id, string $status = 'private', int $sections = 40, bool $claimed = false): array
    {
        return ['id' => $id, 'status' => $status, 'sections' => $sections, 'claimedByOtherTheme' => $claimed];
    }

    /**
     * Drop-in for a $GLOBALS mock-storage slot that counts writes made
     * through it, so a test can assert "did not write again" rather than
     * only "value still matches" (a redundant write would satisfy that too).
     * The mock get_option()/get_post_meta()/update_option()/update_post_meta()
     * functions in tests/bootstrap.php read and write plain array offsets,
     * and PHP dispatches that offset syntax through ArrayAccess when the
     * underlying value is an object implementing it — no bootstrap change
     * needed.
     *
     * @return ArrayAccess<string, mixed>&object{writes: int}
     */
    private function countingStorageSpy(): ArrayAccess
    {
        return new class() implements ArrayAccess {
            public int $writes = 0;

            /** @var array<string, mixed> */
            private array $data = [];

            public function offsetExists(mixed $offset): bool
            {
                return isset($this->data[$offset]);
            }

            public function offsetGet(mixed $offset): mixed
            {
                return $this->data[$offset] ?? null;
            }

            public function offsetSet(mixed $offset, mixed $value): void
            {
                ++$this->writes;
                $this->data[$offset] = $value;
            }

            public function offsetUnset(mixed $offset): void
            {
                unset($this->data[$offset]);
            }
        };
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

    /**
     * A user without edit_pages must not have the marker/option written on
     * their behalf — this is the gate find() relies on when it opportunistically
     * re-adopts from a read-only admin_notices render.
     */
    public function testGateBlocksWriteForAUserWithoutEditPages(): void
    {
        $GLOBALS['wp_mock_current_user_can'] = ['edit_pages' => false];

        StyleguidePage::adopt(632);

        $this->assertEmpty(get_post_meta(632, StyleguidePage::markerKey(), true));
        $this->assertFalse(get_option(StyleguidePage::optionKey(), false));
    }

    /**
     * The other half of the same gate: even a user with edit_pages must not
     * have the marker/option written on their behalf from an AJAX request.
     */
    public function testGateBlocksWriteDuringAjaxEvenWithEditPages(): void
    {
        $GLOBALS['wp_mock_doing_ajax'] = true;

        StyleguidePage::adopt(632);

        $this->assertEmpty(get_post_meta(632, StyleguidePage::markerKey(), true));
        $this->assertFalse(get_option(StyleguidePage::optionKey(), false));
    }

    /**
     * The content-setup path (after_switch_theme / WP-CLI theme activation /
     * the manage_options Tools rerun) runs with no logged-in user, so the
     * ordinary gate would silently skip the write and leave the page unmarked.
     * force: true must write regardless of capability or ajax context.
     */
    public function testForceWritesRegardlessOfTheGate(): void
    {
        $GLOBALS['wp_mock_current_user_can'] = ['edit_pages' => false];
        $GLOBALS['wp_mock_doing_ajax'] = true;

        StyleguidePage::adopt(632, force: true);

        $this->assertSame('1', get_post_meta(632, StyleguidePage::markerKey(), true));
        $this->assertSame(632, get_option(StyleguidePage::optionKey()));
    }

    /**
     * A repeat adopt() of an already-adopted page (as find() does on every
     * opportunistic re-resolve) must not write again — otherwise every
     * admin_notices render on a resolved page would hit postmeta/options
     * unconditionally. The spy objects below stand in for the option/marker
     * storage so a no-op is observable as zero further writes, not merely as
     * "value still matches" (a redundant write would satisfy that too).
     */
    public function testIdempotentSecondCallDoesNotWriteAgain(): void
    {
        $optionSpy = $this->countingStorageSpy();
        $markerSpy = $this->countingStorageSpy();
        $GLOBALS['wp_mock_options'] = $optionSpy;
        $GLOBALS['wp_mock_post_meta'][632] = $markerSpy;

        StyleguidePage::adopt(632);
        $this->assertSame(1, $optionSpy->writes);
        $this->assertSame(1, $markerSpy->writes);

        StyleguidePage::adopt(632);

        $this->assertSame(1, $optionSpy->writes);
        $this->assertSame(1, $markerSpy->writes);
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
