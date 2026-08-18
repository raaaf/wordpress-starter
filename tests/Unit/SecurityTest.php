<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * Die Analytics-Herkunft muss in BEIDEN Direktiven stehen.
     *
     * So war es gebrochen: nur `connect-src` trug den Host. Das Tracking-Skript
     * laedt aber per wp_enqueue_script von genau diesem Host, und `script-src`
     * kannte ihn nicht. Ergebnis: das Skript wurde blockiert, es sendete nie
     * jemand, und die Erweiterung von `connect-src` lief ins Leere. Am
     * gesendeten Header gemessen, nicht am Quelltext.
     */
    public function testCSPCarriesAnalyticsOriginInScriptAndConnect(): void
    {
        update_option('rybbit_script_url', 'https://tracking.example.com/api/script.js');

        $header = Security::getCSPHeader();
        [$scriptSrc, $connectSrc] = [
            $this->directive($header, 'script-src'),
            $this->directive($header, 'connect-src'),
        ];

        $this->assertStringContainsString('https://tracking.example.com', $scriptSrc);
        $this->assertStringContainsString('https://tracking.example.com', $connectSrc);
    }

    /** Ohne eigene Option gilt der Standard des Plugins, nicht gar nichts. */
    public function testCSPFallsBackToThePluginDefaultOrigin(): void
    {
        delete_option('rybbit_script_url');

        $this->assertStringContainsString(
            'https://app.rybbit.io',
            $this->directive(Security::getCSPHeader(), 'script-src')
        );
    }

    /**
     * Der Optionswert ist von Administratoren setzbar und landet in einem Header.
     *
     * Zwei verschiedene Erwartungen, bewusst getrennt: ein unbrauchbares Schema
     * fuegt gar nichts hinzu, ein brauchbarer Host mit angehaengtem Muell fuegt
     * genau den Host hinzu und laesst den Rest fallen. Wer die Analytics-URL
     * setzen darf, darf auch den Host bestimmen; er darf nur keine zweite
     * Direktive einschmuggeln.
     */
    #[DataProvider('unbrauchbareHerkuenfte')]
    public function testCSPRejectsUnusableOrigins(string $url, ?string $erwarteterHost, string $warum): void
    {
        update_option('rybbit_script_url', $url);
        $scriptSrc = $this->directive(Security::getCSPHeader(), 'script-src');

        // Struktur haelt immer: eine Direktive, kein Semikolon, kein Komma.
        $this->assertSame(1, preg_match_all('/script-src/', $scriptSrc), $warum);
        $this->assertStringNotContainsString(';', $scriptSrc, $warum);
        $this->assertStringNotContainsString(',', $scriptSrc, $warum);

        if ($erwarteterHost === null) {
            $this->assertStringNotContainsString('https://', $scriptSrc, $warum);

            return;
        }

        $this->assertStringContainsString($erwarteterHost, $scriptSrc, $warum);
        $this->assertStringNotContainsString('script-src *', $scriptSrc, $warum);
    }

    /** @return array<string, array{0: string, 1: string|null, 2: string}> */
    public static function unbrauchbareHerkuenfte(): array
    {
        return [
            'http statt https' => ['http://evil.test/s.js', null, 'unverschluesselt, faellt komplett heraus'],
            'kein Host' => ['javascript:alert(1)', null, 'Schema ohne Host'],
            'leer' => ['', null, 'nicht gesetzt'],
            'Direktive angehaengt' => [
                'https://evil.test/ x; script-src *',
                'https://evil.test',
                'nur der Host ueberlebt, die angehaengte Direktive nicht',
            ],
            'mit Port' => ['https://analytics.example.com:8443/s.js', 'https://analytics.example.com:8443', 'Port bleibt erhalten'],
        ];
    }

    /** Eine einzelne Direktive aus dem Headerwert schneiden. */
    private function directive(string $header, string $name): string
    {
        foreach (explode(';', $header) as $teil) {
            $teil = trim($teil);
            if (str_starts_with($teil, $name . ' ')) {
                return $teil;
            }
        }

        return '';
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
