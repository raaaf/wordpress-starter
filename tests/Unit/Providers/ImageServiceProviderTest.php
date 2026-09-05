<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use ReflectionMethod;
use Tests\Support\TestCase;
use WordpressStarter\Providers\ImageServiceProvider;

/**
 * Tests for the img-tag attribute extraction used when rebuilding responsive images.
 */
final class ImageServiceProviderTest extends TestCase
{
    private function extractAttr(string $tag, string $attr): string
    {
        $reflection = new ReflectionMethod(ImageServiceProvider::class, 'extractAttr');
        $reflection->setAccessible(true);

        return $reflection->invoke(null, $tag, $attr);
    }

    public function testExtractsTheNamedAttribute(): void
    {
        $tag = '<img src="a.webp" alt="A cat" />';

        $this->assertSame('A cat', $this->extractAttr($tag, 'alt'));
    }

    public function testDoesNotMatchAnAttributeNameThatIsOnlyASubstring(): void
    {
        // A "data-alt" attribute must not satisfy a lookup for "alt".
        $tag = '<img src="a.webp" data-alt="wrong value" />';

        $this->assertSame('', $this->extractAttr($tag, 'alt'));
    }

    public function testMatchesTheAttributeRegardlessOfQuoteStyle(): void
    {
        $tag = "<img src='a.webp' alt='A cat' />";

        $this->assertSame('A cat', $this->extractAttr($tag, 'alt'));
    }

    public function testMatchesAnAttributeValueContainingANewline(): void
    {
        // Editor markup can wrap attribute values across a line break.
        $tag = "<img src=\"a.webp\" alt=\"A cat\nsleeping\" />";

        $this->assertSame("A cat\nsleeping", $this->extractAttr($tag, 'alt'));
    }

    private function extractClass(string $tag): string
    {
        $reflection = new ReflectionMethod(ImageServiceProvider::class, 'extractClass');
        $reflection->setAccessible(true);

        return $reflection->invoke(null, $tag);
    }

    public function testExtractClassDoesNotMatchAnAttributeNameThatIsOnlyASubstring(): void
    {
        // A "data-class" attribute must not satisfy a lookup for "class".
        $tag = '<img src="a.webp" data-class="wp-image-1 alignleft" />';

        $this->assertSame('', $this->extractClass($tag));
    }

    public function testExtractClassMatchesRegardlessOfQuoteStyle(): void
    {
        $tag = "<img src='a.webp' class='foo wp-image-1 alignleft' />";

        $this->assertSame('foo', $this->extractClass($tag));
    }
}
