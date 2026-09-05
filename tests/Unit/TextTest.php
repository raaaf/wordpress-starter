<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;
use WordpressStarter\Helpers\Text;

/**
 * Tests for the Text helper.
 */
final class TextTest extends TestCase
{
    /**
     * The German trunk prefix "(0)" must not survive into the tel: href,
     * otherwise the resulting number cannot be dialled.
     */
    public function testTelHrefDropsTheTrunkPrefix(): void
    {
        $this->assertSame('+499131916660', Text::telHref('+49 (0)9131 91666 0'));
        $this->assertSame('+49911123456', Text::telHref('+49 (0) 911 123456'));
    }

    public function testTelHrefStripsFormattingCharacters(): void
    {
        $this->assertSame('+49911123456', Text::telHref('+49 911 1234-56'));
        $this->assertSame('+4991112345', Text::telHref('+49 911 / 12345'));
    }

    public function testTelHrefKeepsNationalNumbersIntact(): void
    {
        $this->assertSame('09131916660', Text::telHref('09131 916660'));
    }

    public function testTelHrefHandlesEmptyInput(): void
    {
        $this->assertSame('', Text::telHref(null));
        $this->assertSame('', Text::telHref(''));
    }

    /**
     * lineBreaks() passes an empty attribute allowlist for <br> to wp_kses(), so an
     * injected attribute must not survive - only the bare tag is allowed.
     */
    public function testLineBreaksStripsAttributesFromBr(): void
    {
        $this->assertSame('a<br>b', Text::lineBreaks('a<br onclick="x">b'));
    }

    /**
     * Only <br> is allowlisted, so any other tag must be stripped while its
     * inner text content is kept (wp_kses()'s documented behaviour for a
     * disallowed tag, not just script/style).
     */
    public function testLineBreaksStripsDisallowedTags(): void
    {
        $this->assertSame('text', Text::lineBreaks('<em>text</em>'));
    }

    /**
     * The [br] placeholder is the documented way callers request a line
     * break; it must convert to a real <br> tag before the allowlist runs.
     */
    public function testLineBreaksConvertsBracketPlaceholderToBrTag(): void
    {
        $this->assertSame('line1<br>line2', Text::lineBreaks('line1[br]line2'));
    }

    /**
     * A bare, unattributed <br> is the one tag the allowlist keeps.
     */
    public function testLineBreaksKeepsBareBrTag(): void
    {
        $this->assertSame('a<br>b', Text::lineBreaks('a<br>b'));
    }
}
