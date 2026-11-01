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
     * The lineBreaks() method passes an empty attribute allowlist for <br> to wp_kses(), so an
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

    public function testPlainReplacesBreaksWithSpaceAndStripsTags(): void
    {
        $this->assertSame('Häufige Fragen zu uns', Text::plain('Häufige<br>Fragen <br /> zu <strong>uns</strong>'));
        $this->assertSame('', Text::plain(null));
    }

    public function testNumberAndUnitAreGlued(): void
    {
        $nbsp = "\u{00A0}";
        $this->assertSame("25{$nbsp}Jahren und 10{$nbsp}Jahre", Text::typography('25 Jahren und 10 Jahre'));
        $this->assertSame("5{$nbsp}kWh, 3{$nbsp}MWp, 2{$nbsp}kg, 9{$nbsp}€, 4{$nbsp}EUR", Text::typography('5 kWh, 3 MWp, 2 kg, 9 €, 4 EUR'));
    }

    public function testNumberBeforeOtherWordsStaysSeparate(): void
    {
        $this->assertSame('25 Jahrhunderte', Text::typography('25 Jahrhunderte'));
        $this->assertSame('Jahre 25', Text::typography('Jahre 25'));
    }

    public function testAbbreviationsAreGlued(): void
    {
        $nbsp = "\u{00A0}";
        $this->assertSame("p.{$nbsp}a. und z.{$nbsp}B. so", Text::typography('p. a. und z. B. so'));
    }

    public function testPercentTakesNarrowNoBreakSpace(): void
    {
        $this->assertSame("7\u{00A0}% und 3\u{00A0}‰", Text::typography('7 % und 3 ‰'));
    }

    public function testSpacedHyphenBecomesEnDash(): void
    {
        $this->assertSame('Solar – Wind', Text::typography('Solar - Wind'));
        $this->assertSame('E-Mail und Mit-Arbeiter', Text::typography('E-Mail und Mit-Arbeiter'));
    }

    public function testTagsAndAttributesStayUntouched(): void
    {
        $html = '<a href="/a - b?x=5 kg" class="z. B. p. a.">5 kg - gut</a>';
        $this->assertSame(
            "<a href=\"/a - b?x=5 kg\" class=\"z. B. p. a.\">5\u{00A0}kg – gut</a>",
            Text::typography($html)
        );
    }

    public function testTypographyReturnsNonStringsUnchanged(): void
    {
        $this->assertNull(Text::typography(null));
        $this->assertFalse(Text::typography(false));
    }

    public function testTypographySkipsRawElementContents(): void
    {
        $this->assertSame('<script>a - b</script> x – y', Text::typography('<script>a - b</script> x - y'));
        $this->assertSame('<CODE>a - b</CODE> x – y', Text::typography('<CODE>a - b</CODE> x - y'));
        $this->assertSame('<pre>a - b <code>c - d</code> e - f</pre> x – y', Text::typography('<pre>a - b <code>c - d</code> e - f</pre> x - y'));
    }

    public function testLineBreaksAppliesTypography(): void
    {
        $this->assertSame("10\u{00A0}Jahre<br>Solar – Wind", Text::lineBreaks('10 Jahre[br]Solar - Wind'));
    }

    public function testFileTypeReturnsTheUppercaseKnownExtension(): void
    {
        $this->assertSame('PDF', Text::fileType('https://example.test/a/form.pdf'));
        $this->assertSame('DOCX', Text::fileType('https://example.test/a/form.DOCX'));
    }

    public function testFileTypeIgnoresTheQueryString(): void
    {
        $this->assertSame('PDF', Text::fileType('https://example.test/form.pdf?v=3'));
        $this->assertSame('', Text::fileType('https://example.test/download?file=form.pdf'));
    }

    public function testFileTypeIsEmptyForUnknownOrMissingExtensions(): void
    {
        $this->assertSame('', Text::fileType('https://example.test/page.html'));
        $this->assertSame('', Text::fileType('https://example.test/page'));
    }

    public function testFileTypeIsEmptyForMailto(): void
    {
        $this->assertSame('', Text::fileType('mailto:info@example.test'));
    }
}
