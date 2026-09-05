<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use ReflectionMethod;
use Tests\Support\TestCase;
use WordpressStarter\Providers\MediaServiceProvider;

/**
 * Tests for the SVG-detection and sanitization helpers used to gate SVG
 * uploads. These must key off the actual file (extension / content), never
 * the browser-supplied Content-Type, and must fail closed when sanitization
 * cannot confirm the content is safe.
 */
final class MediaServiceProviderTest extends TestCase
{
    private MediaServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new MediaServiceProvider();
    }

    private function looksLikeSvgContent(string $path): bool
    {
        $reflection = new ReflectionMethod(MediaServiceProvider::class, 'looksLikeSvgContent');
        $reflection->setAccessible(true);

        return $reflection->invoke($this->provider, $path);
    }

    private function isSvgUpload(string $filename): bool
    {
        $reflection = new ReflectionMethod(MediaServiceProvider::class, 'isSvgUpload');
        $reflection->setAccessible(true);

        return $reflection->invoke($this->provider, $filename);
    }

    /**
     * @return string|false
     */
    private function sanitizeSvg(string $content)
    {
        $reflection = new ReflectionMethod(MediaServiceProvider::class, 'sanitizeSvg');
        $reflection->setAccessible(true);

        return $reflection->invoke($this->provider, $content);
    }

    private function writeTempFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'media-svg-test-');
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return $path;
    }

    /** @var string[] */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }
        $this->tempFiles = [];
        parent::tearDown();
    }

    public function testLooksLikeSvgContentDetectsSvgRootTag(): void
    {
        $path = $this->writeTempFile('<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $this->assertTrue($this->looksLikeSvgContent($path));
    }

    public function testLooksLikeSvgContentDetectsSvgAfterXmlDeclaration(): void
    {
        $path = $this->writeTempFile("<?xml version=\"1.0\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\"></svg>");

        $this->assertTrue($this->looksLikeSvgContent($path));
    }

    public function testLooksLikeSvgContentRejectsUnrelatedContent(): void
    {
        $path = $this->writeTempFile("\x89PNG\r\n\x1a\nnot really svg content here");

        $this->assertFalse($this->looksLikeSvgContent($path));
    }

    public function testLooksLikeSvgContentRejectsUnreadablePath(): void
    {
        $this->assertFalse($this->looksLikeSvgContent('/nonexistent/path/for/test.svg'));
    }

    public function testIsSvgUploadIsTrueForSvgExtensionRegardlessOfContent(): void
    {
        // Declared extension is svg even though the content sniff would fail
        // on its own; the extension signal alone must be enough.
        $this->assertTrue($this->isSvgUpload('logo.svg'));
    }

    public function testIsSvgUploadIsFalseForSvgContentWithMismatchedExtension(): void
    {
        // Content sniffing alone must not decide: upload_mimes() only
        // permits the "svg" extension in the first place, so a non-.svg
        // file must never be routed into the SVG upload path, even if its
        // content looks like SVG (e.g. a JPEG with an XMP packet, or a
        // .txt file). Content sniffing is used only to confirm a .svg file
        // in wp_check_filetype_and_ext, never to widen this gate.
        $this->assertFalse($this->isSvgUpload('image.png'));
    }

    public function testIsSvgUploadIsTrueForUppercaseSvgExtension(): void
    {
        $this->assertTrue($this->isSvgUpload('logo.SVG'));
    }

    public function testIsSvgUploadIsFalseForNeitherExtensionNorContent(): void
    {
        $this->assertFalse($this->isSvgUpload('notes.txt'));
    }

    public function testSanitizeSvgRemovesScriptElement(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

        $sanitized = $this->sanitizeSvg($svg);

        $this->assertIsString($sanitized);
        $this->assertStringNotContainsString('<script', $sanitized);
    }

    public function testSanitizeSvgReturnsFalseForUnparsableContent(): void
    {
        $sanitized = $this->sanitizeSvg('this is not xml at all <<<');

        $this->assertFalse($sanitized);
    }
}
