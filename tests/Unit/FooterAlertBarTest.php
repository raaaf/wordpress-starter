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
        $this->assertStringContainsString('<p><strong><strong>Wichtiger Hinweis:</strong></strong></p>', $result);
    }

    public function testDemotesEveryHeadingLevelAndKeepsAttributes(): void
    {
        foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
            $result = FooterAlertBar::demoteHeadings("<{$tag} class=\"x\">Titel</{$tag}>");

            $this->assertSame('<p><strong>Titel</strong></p>', $result, "Tag {$tag}");
        }
    }

    public function testLeavesTextWithoutHeadingsUntouched(): void
    {
        $html = '<p>Nur ein <strong>Absatz</strong> mit <a href="/x">Link</a>.</p>';

        $this->assertSame($html, FooterAlertBar::demoteHeadings($html));
    }
}
