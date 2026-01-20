<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;
use WordpressStarter\Security;

/**
 * Tests for Security class.
 */
final class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSecurityState();
    }

    protected function tearDown(): void
    {
        $this->resetSecurityState();
        parent::tearDown();
    }

    private function resetSecurityState(): void
    {
        $reflection = new \ReflectionClass(Security::class);
        $nonceProperty = $reflection->getProperty('nonce');
        $nonceProperty->setAccessible(true);
        $nonceProperty->setValue(null, null);
    }

    public function testGetNonceGeneratesBase64String(): void
    {
        $nonce = Security::getNonce();

        // Base64 encoded 16 bytes should be 24 chars with padding, or 22 without padding
        // PHP's base64_encode includes padding, so it's typically 24 chars for 16 bytes
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $nonce);
    }

    public function testGetNonceReturnsSameValueOnMultipleCalls(): void
    {
        $firstNonce = Security::getNonce();
        $secondNonce = Security::getNonce();
        $thirdNonce = Security::getNonce();

        $this->assertSame($firstNonce, $secondNonce);
        $this->assertSame($secondNonce, $thirdNonce);
    }

    public function testGetNonceGeneratesCorrectLength(): void
    {
        $nonce = Security::getNonce();

        // 16 bytes base64 encoded = 24 characters (with padding)
        $this->assertSame(24, strlen($nonce));
    }

    public function testGetNonceGeneratesUniqueValues(): void
    {
        $nonce1 = Security::getNonce();
        $this->resetSecurityState();
        $nonce2 = Security::getNonce();

        // With proper randomness, two nonces should be different
        $this->assertNotSame($nonce1, $nonce2);
    }

    public function testGetCSPHeaderContainsRequiredDirectives(): void
    {
        $header = Security::getCSPHeader();

        $this->assertStringContainsString("default-src 'self'", $header);
        $this->assertStringContainsString("script-src 'self'", $header);
        $this->assertStringContainsString("style-src 'self'", $header);
        $this->assertStringContainsString("font-src 'self'", $header);
        $this->assertStringContainsString("img-src 'self'", $header);
        $this->assertStringContainsString("connect-src 'self'", $header);
    }

    public function testGetCSPHeaderIncludesNonce(): void
    {
        $nonce = Security::getNonce();
        $header = Security::getCSPHeader();

        $this->assertStringContainsString("'nonce-{$nonce}'", $header);
    }

    public function testGetCSPHeaderIncludesYouTubeInFrameSrc(): void
    {
        $header = Security::getCSPHeader();

        $this->assertStringContainsString('frame-src', $header);
        $this->assertStringContainsString('https://www.youtube-nocookie.com', $header);
        $this->assertStringContainsString('https://www.youtube.com', $header);
    }

    public function testGetCSPHeaderIncludesVimeoInFrameSrc(): void
    {
        $header = Security::getCSPHeader();

        $this->assertStringContainsString('https://player.vimeo.com', $header);
    }

    public function testGetCSPHeaderIncludesGoogleFonts(): void
    {
        $header = Security::getCSPHeader();

        $this->assertStringContainsString('https://fonts.gstatic.com', $header);
        $this->assertStringContainsString('https://fonts.googleapis.com', $header);
    }

    public function testGetCSPHeaderIncludesUnsafeInlineForStyles(): void
    {
        $header = Security::getCSPHeader();

        // Extract the style-src directive
        preg_match('/style-src ([^;]+)/', $header, $matches);
        $styleSrc = $matches[1] ?? '';

        $this->assertStringContainsString("'unsafe-inline'", $styleSrc);
    }

    public function testGetCSPHeaderDirectivesAreSemicolonSeparated(): void
    {
        $header = Security::getCSPHeader();

        // All directives should be separated by semicolons
        $this->assertGreaterThan(5, substr_count($header, ';'));
    }
}
