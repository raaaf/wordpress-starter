<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use Tests\Support\TestCase;
use WordpressStarter\Helpers\ComponentId;

/**
 * Tests for the ComponentId helper, which replaces per-view `static`
 * counters in Blade components with a request-scoped registry so ids stay
 * unique across multiple renders of the same component within one request.
 */
final class ComponentIdTest extends TestCase
{
    protected function tearDown(): void
    {
        ComponentId::reset();
        parent::tearDown();
    }

    public function testNextReturnsSequentialIdsPerPrefix(): void
    {
        $this->assertSame('foo-1', ComponentId::next('foo'));
        $this->assertSame('foo-2', ComponentId::next('foo'));
        $this->assertSame('foo-3', ComponentId::next('foo'));
    }

    public function testNextKeepsSeparateCountersPerPrefix(): void
    {
        $this->assertSame('foo-1', ComponentId::next('foo'));
        $this->assertSame('bar-1', ComponentId::next('bar'));
        $this->assertSame('foo-2', ComponentId::next('foo'));
    }

    public function testUniqueReturnsIdUnchangedOnFirstUse(): void
    {
        $this->assertSame('section-id', ComponentId::unique('section-id'));
    }

    public function testUniqueAppendsSuffixOnRepeat(): void
    {
        $this->assertSame('section-id', ComponentId::unique('section-id'));
        $this->assertSame('section-id-2', ComponentId::unique('section-id'));
        $this->assertSame('section-id-3', ComponentId::unique('section-id'));
    }

    public function testUniqueTracksDifferentIdsIndependently(): void
    {
        $this->assertSame('a', ComponentId::unique('a'));
        $this->assertSame('b', ComponentId::unique('b'));
        $this->assertSame('a-2', ComponentId::unique('a'));
    }

    public function testAnchorSlugifiesUmlautsSpacesAndUppercase(): void
    {
        // Not 'ueber-uns': the theme runs single-locale (see bootstrap.php's
        // remove_accents() double), so core's German-locale transliteration
        // never applies and ü maps to plain 'u', as core does for every
        // other locale too.
        $this->assertSame('uber-uns', ComponentId::anchor('Über uns'));
    }

    public function testAnchorKeepsUnderscoresConvertsDotsAndPercentEncodesNonAscii(): void
    {
        $this->assertSame('foo_bar', ComponentId::anchor('foo_bar'));
        $this->assertSame('example-com', ComponentId::anchor('example.com'));
        $this->assertSame('%e6%97%a5%e6%9c%ac', ComponentId::anchor('日本'));
    }

    public function testAnchorOfPunctuationOnlyStringIsEmpty(): void
    {
        $this->assertSame('', ComponentId::anchor('!!!'));
    }

    public function testUniqueSkipsASuffixAlreadyTakenByADifferentRawId(): void
    {
        // A raw anchor that happens to equal an earlier collision's suffix
        // ("foo-2") must not collide with the id the NEXT "foo" collision
        // produces.
        $this->assertSame('foo', ComponentId::unique('foo'));
        $this->assertSame('foo-2', ComponentId::unique('foo-2'));
        $this->assertSame('foo-3', ComponentId::unique('foo'));
    }

    public function testNextWithEmptyPrefixFallsBackToField(): void
    {
        $this->assertSame('field-1', ComponentId::next(''));
        $this->assertSame('field-2', ComponentId::next(''));
    }

    public function testResetClearsBothCounterAndSeenState(): void
    {
        ComponentId::next('foo');
        ComponentId::unique('bar');

        ComponentId::reset();

        $this->assertSame('foo-1', ComponentId::next('foo'));
        $this->assertSame('bar', ComponentId::unique('bar'));
    }
}
