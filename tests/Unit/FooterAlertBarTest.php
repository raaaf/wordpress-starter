<?php

declare(strict_types=1);

namespace Tests\Unit;

use WordpressStarter\Support\FooterAlertBar;
use Tests\Support\TestCase;

/**
 * Tests for heading demotion in the footer alert bar.
 */
final class FooterAlertBarTest extends TestCase
{
    public function testDemotesPastedHeadingToBoldParagraph(): void
    {
        // Real payload pasted from Outlook into the alert WYSIWYG.
        $html = '<h3><strong>Wichtiger Hinweis:</strong></h3><p>Angeboten werden Nachrangdarlehen.</p>';

        $result = FooterAlertBar::demoteHeadings($html);

        $this->assertStringNotContainsString('<h3', $result);
        $this->assertStringNotContainsString('</h3>', $result);
        $this->assertStringContainsString('<p><strong>Wichtiger Hinweis:</strong></p>', $result);
    }

    public function testDoesNotDoubleWrapAlreadyBoldHeadingContent(): void
    {
        $html = '<h3><strong>Wichtiger Hinweis:</strong></h3>';

        $this->assertSame('<p><strong>Wichtiger Hinweis:</strong></p>', FooterAlertBar::demoteHeadings($html));
    }

    public function testWrapsPlainHeadingContentInStrong(): void
    {
        $html = '<h3>Wichtiger Hinweis:</h3>';

        $this->assertSame('<p><strong>Wichtiger Hinweis:</strong></p>', FooterAlertBar::demoteHeadings($html));
    }

    public function testTreatsBTagAsPlainTextNotAsExistingBold(): void
    {
        // <b> is left as-is, not recognised as an existing bold wrapper: the heading
        // content still gets wrapped in <strong>, producing <strong><b>...</b></strong>.
        $html = '<h3><b>Wichtiger Hinweis:</b></h3>';

        $this->assertSame('<p><strong><b>Wichtiger Hinweis:</b></strong></p>', FooterAlertBar::demoteHeadings($html));
    }

    public function testDemotesEveryHeadingLevelAndKeepsAttributes(): void
    {
        foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
            $result = FooterAlertBar::demoteHeadings("<{$tag} class=\"x\">Titel</{$tag}>");

            $this->assertSame('<p><strong>Titel</strong></p>', $result, "Tag {$tag}");
        }
    }

    public function testDemotesUnclosedHeadingViaTheFallbackPass(): void
    {
        // Content pasted in from Outlook/Word can carry an unclosed heading tag.
        // The pair-aware first pass never matches it (no closing </h*> to pair
        // with), so the fallback pass demotes only the opening tag, leaving an
        // unmatched <p><strong>; force_balance_tags() then closes both.
        $html = '<h2>Titel';

        $this->assertSame('<p><strong>Titel</strong></p>', FooterAlertBar::demoteHeadings($html));
    }

    public function testDemotesUnclosedHeadingWhoseClosingTagIsMissingBeforeNextTag(): void
    {
        // Same fallback case, but followed by unrelated markup instead of end
        // of string. There is no </h3> anywhere in the string, so the
        // pair-aware pass still cannot match it; force_balance_tags() still
        // closes every tag left open by the fallback pass.
        $html = '<h3>Titel<p>Text</p>';

        $result = FooterAlertBar::demoteHeadings($html);

        $this->assertStringNotContainsString('<h3', $result);
        // Balanced: no dangling opening tag without a matching close.
        $this->assertSame(
            substr_count($result, '<p>') + substr_count($result, '<strong>'),
            substr_count($result, '</p>') + substr_count($result, '</strong>')
        );
    }

    public function testUnclosedHeadingProducesBalancedMarkup(): void
    {
        // Regression test for R2-S6: an unbalanced pasted <h2> must not leave
        // an opening <strong>/<p> without its closing tag, since
        // wp_kses_post() does not balance tags on its own.
        $html = '<h2>Titel<p>Weiterer Text ohne schliessendes Tag';

        $result = FooterAlertBar::demoteHeadings($html);

        $this->assertStringEndsWith('</strong></p>', $result);
        $this->assertSame(
            substr_count($result, '<p>') + substr_count($result, '<strong>'),
            substr_count($result, '</p>') + substr_count($result, '</strong>')
        );
    }

    public function testGreaterThanInsideAQuotedHeadingAttributeDoesNotTruncateTheMatch(): void
    {
        // Regression test: the heading regex is quote-aware, so a ">" inside
        // a quoted attribute value does not end the <h*> tag match early and
        // leak the attribute tail into the rendered text.
        $html = '<h2 title="a > b">Text</h2>';

        $this->assertSame('<p><strong>Text</strong></p>', FooterAlertBar::demoteHeadings($html));
    }

    public function testPartiallyBoldHeadingContentIsNotDoubleWrapped(): void
    {
        // Regression test: a heading that is only partially wrapped in
        // <strong> (not "already bold" as a whole) must still end up with
        // exactly one <strong> around the whole text, never a nested
        // <strong><strong>...
        $html = '<h2><strong>Part</strong> rest</h2>';

        $this->assertSame('<p><strong>Part rest</strong></p>', FooterAlertBar::demoteHeadings($html));
    }

    public function testLeavesTextWithoutHeadingsUntouched(): void
    {
        $html = '<p>Nur ein <strong>Absatz</strong> mit <a href="/x">Link</a>.</p>';

        $this->assertSame($html, FooterAlertBar::demoteHeadings($html));
    }

    public function testAlertVisibleOnAllPagesByDefault(): void
    {
        $GLOBALS['wp_mock_queried_object_id'] = 42;
        $this->setMockField('footer_alerts', [
            [
                'active' => true,
                'text' => 'Hinweis',
                'visibility' => 'all',
                'pages' => [],
                'dismissible' => false,
            ],
        ], 'option');

        $result = FooterAlertBar::getVisibleAlerts();

        $this->assertCount(1, $result);
        $this->assertSame('Hinweis', $result[0]['text']);
    }

    public function testOnlyVisibilityShowsAlertWhenCurrentPageInList(): void
    {
        $GLOBALS['wp_mock_queried_object_id'] = 5;
        $this->setMockField('footer_alerts', [
            [
                'active' => true,
                'text' => 'Nur hier',
                'visibility' => 'only',
                'pages' => [5, 9],
                'dismissible' => false,
            ],
        ], 'option');

        $result = FooterAlertBar::getVisibleAlerts();

        $this->assertCount(1, $result);
        $this->assertSame('Nur hier', $result[0]['text']);
    }

    public function testOnlyVisibilityHidesAlertWhenCurrentPageNotInList(): void
    {
        $GLOBALS['wp_mock_queried_object_id'] = 3;
        $this->setMockField('footer_alerts', [
            [
                'active' => true,
                'text' => 'Nur hier',
                'visibility' => 'only',
                'pages' => [5, 9],
                'dismissible' => false,
            ],
        ], 'option');

        $result = FooterAlertBar::getVisibleAlerts();

        $this->assertSame([], $result);
    }

    public function testExceptVisibilityHidesAlertWhenCurrentPageInList(): void
    {
        $GLOBALS['wp_mock_queried_object_id'] = 5;
        $this->setMockField('footer_alerts', [
            [
                'active' => true,
                'text' => 'Ueberall ausser hier',
                'visibility' => 'except',
                'pages' => [5, 9],
                'dismissible' => false,
            ],
        ], 'option');

        $result = FooterAlertBar::getVisibleAlerts();

        $this->assertSame([], $result);
    }

    public function testExceptVisibilityShowsAlertWhenCurrentPageNotInList(): void
    {
        $GLOBALS['wp_mock_queried_object_id'] = 3;
        $this->setMockField('footer_alerts', [
            [
                'active' => true,
                'text' => 'Ueberall ausser hier',
                'visibility' => 'except',
                'pages' => [5, 9],
                'dismissible' => false,
            ],
        ], 'option');

        $result = FooterAlertBar::getVisibleAlerts();

        $this->assertCount(1, $result);
        $this->assertSame('Ueberall ausser hier', $result[0]['text']);
    }

    public function testEmptyPagesListWithOnlyVisibilityHidesAlert(): void
    {
        $GLOBALS['wp_mock_queried_object_id'] = 5;
        $this->setMockField('footer_alerts', [
            [
                'active' => true,
                'text' => 'Nur hier',
                'visibility' => 'only',
                'pages' => [],
                'dismissible' => false,
            ],
        ], 'option');

        $result = FooterAlertBar::getVisibleAlerts();

        $this->assertSame([], $result);
    }

    public function testEmptyPagesListWithExceptVisibilityShowsAlert(): void
    {
        $GLOBALS['wp_mock_queried_object_id'] = 5;
        $this->setMockField('footer_alerts', [
            [
                'active' => true,
                'text' => 'Ueberall',
                'visibility' => 'except',
                'pages' => [],
                'dismissible' => false,
            ],
        ], 'option');

        $result = FooterAlertBar::getVisibleAlerts();

        $this->assertCount(1, $result);
    }

    public function testInactiveAlertIsHidden(): void
    {
        $GLOBALS['wp_mock_queried_object_id'] = 5;
        $this->setMockField('footer_alerts', [
            [
                'active' => false,
                'text' => 'Versteckter Hinweis',
                'visibility' => 'all',
                'pages' => [],
                'dismissible' => false,
            ],
        ], 'option');

        $result = FooterAlertBar::getVisibleAlerts();

        $this->assertSame([], $result);
    }

    public function testEmptyTextIsSkipped(): void
    {
        $GLOBALS['wp_mock_queried_object_id'] = 5;
        $this->setMockField('footer_alerts', [
            [
                'active' => true,
                'text' => '',
                'visibility' => 'all',
                'pages' => [],
                'dismissible' => false,
            ],
        ], 'option');

        $result = FooterAlertBar::getVisibleAlerts();

        $this->assertSame([], $result);
    }

    public function testWhitespaceOnlyTextIsSkipped(): void
    {
        $GLOBALS['wp_mock_queried_object_id'] = 5;
        $this->setMockField('footer_alerts', [
            [
                'active' => true,
                'text' => "   \n\t  ",
                'visibility' => 'all',
                'pages' => [],
                'dismissible' => false,
            ],
        ], 'option');

        $result = FooterAlertBar::getVisibleAlerts();

        $this->assertSame([], $result);
    }

    public function testMultipleAlertsAreFilteredIndependently(): void
    {
        $GLOBALS['wp_mock_queried_object_id'] = 5;
        $this->setMockField('footer_alerts', [
            [
                'active' => true,
                'text' => 'Immer sichtbar',
                'visibility' => 'all',
                'pages' => [],
                'dismissible' => false,
            ],
            [
                'active' => true,
                'text' => 'Nur auf Seite 9',
                'visibility' => 'only',
                'pages' => [9],
                'dismissible' => false,
            ],
            [
                'active' => true,
                'text' => 'Ausser auf Seite 5',
                'visibility' => 'except',
                'pages' => [5],
                'dismissible' => false,
            ],
        ], 'option');

        $result = FooterAlertBar::getVisibleAlerts();

        $texts = array_column($result, 'text');
        $this->assertSame(['Immer sichtbar'], $texts);
    }
}
