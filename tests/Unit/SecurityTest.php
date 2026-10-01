<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;
use WordpressStarter\Config;
use WordpressStarter\Security;

/**
 * Tests for Security class.
 */
final class SecurityTest extends TestCase
{
    private bool $enableCspBefore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSecurityState();
        $this->enableCspBefore = (bool) Config::get('security.enable_csp');
    }

    protected function tearDown(): void
    {
        $this->resetSecurityState();
        Config::set('security.enable_csp', $this->enableCspBefore);
        parent::tearDown();
    }

    private function resetSecurityState(): void
    {
        $this->resetStaticProperties(Security::class, ['nonce' => null, 'headerEmitter' => null]);
    }

    public function testGetNonceGeneratesBase64StringOfTheGeneratedByteLength(): void
    {
        $nonce = Security::getNonce();

        // Security::getNonce() generates random_bytes(16), base64-encoded.
        $decoded = base64_decode($nonce, true); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decodes the generated nonce to assert its byte length

        $this->assertNotFalse($decoded, 'nonce must be valid base64');
        $this->assertSame(16, strlen($decoded));
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

    /** Pins the exact CSP: only the three non-breaking directives, nothing restricts scripts, styles, frames or form targets. */
    public function testGetCSPHeaderIsExactlyTheThreeNonBreakingDirectives(): void
    {
        $this->assertSame("frame-ancestors 'self'; base-uri 'self'; object-src 'none'", Security::getCSPHeader());
    }

    /**
     * The isAllowedEmbedHost() method is the gate templates use before
     * rendering an iframe: https only, no explicit non-443 port, never the
     * site's own host or its www alias.
     */
    #[DataProvider('embedHostAllowance')]
    public function testIsAllowedEmbedHost(string $url, bool $expected, string $warum): void
    {
        $this->setMockField('embed_allowed_hosts', 'calendly.com', 'option');

        $this->assertSame($expected, Security::isAllowedEmbedHost($url), $warum);
    }

    /** @return array<string, array{0: string, 1: bool, 2: string}> */
    public static function embedHostAllowance(): array
    {
        return [
            'hartkodierter Host erlaubt' => ['https://www.youtube-nocookie.com/embed/x', true, 'in HARDCODED_FRAME_SRC_HOSTS'],
            'Options-Host erlaubt' => ['https://calendly.com/meeting', true, 'aus der embed_allowed_hosts Option'],
            'Subdomain des Options-Hosts abgelehnt' => ['https://sub.calendly.com/meeting', false, 'heutige Semantik: nur exakte Eintraege, keine Subdomains'],
            'eigener Host abgelehnt' => ['https://' . (string) wp_parse_url(home_url(), PHP_URL_HOST) . '/x', false, 'allow-same-origin darf den Sandkasten nicht aushebeln'],
            'www-Alias des eigenen Hosts abgelehnt' => ['https://www.' . (string) wp_parse_url(home_url(), PHP_URL_HOST) . '/x', false, 'www-Alias ist derselbe same-origin Sandkasten-Bruch'],
            'Userinfo-Trick abgelehnt' => ['https://calendly.com@evil.test/x', false, 'wp_parse_url liest den echten Host (evil.test), nicht das Userinfo-Feld'],
            'Grossschreibung im Schema akzeptiert' => ['HTTPS://calendly.com/meeting', true, 'Schema-Vergleich ist case-insensitiv'],
            'http abgelehnt' => ['http://calendly.com/meeting', false, 'nur https ist zulaessig'],
            'expliziter Port 8443 abgelehnt' => ['https://calendly.com:8443/meeting', false, 'nur der https-Standardport ist erlaubt'],
            'expliziter Standardport 443 erlaubt' => ['https://calendly.com:443/meeting', true, 'Port 443 entspricht dem impliziten https-Standardport'],
        ];
    }

    public function testScriptLoaderTagAddsNonceToSingleTag(): void
    {
        Security::init();
        $nonce = Security::getNonce();

        $tag = "<script src='foo.js'></script>\n"; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- test fixture string, never rendered
        $result = apply_filters('script_loader_tag', $tag, 'foo');

        $this->assertSame('<script nonce="' . $nonce . '" src=\'foo.js\'></script>' . "\n", $result);
    }

    /** Core can concatenate several script tags into one string; every one of them gets its own nonce. */
    public function testScriptLoaderTagNoncesEveryTagInAConcatenatedString(): void
    {
        Security::init();
        $nonce = Security::getNonce();

        $tag = "<script id='a-before'>var a=1;</script>\n<script src='a.js' id='a'></script>\n<script id='a-after'>var b=2;</script>\n"; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- test fixture string, never rendered
        $result = apply_filters('script_loader_tag', $tag, 'a');

        $needle = '<script nonce="' . $nonce . '"';
        $this->assertSame(3, substr_count( (string) $result, $needle));
    }

    public function testScriptLoaderTagLeavesExistingNonceUnchanged(): void
    {
        Security::init();

        $tag = "<script nonce=\"existing-nonce\" src='foo.js'></script>\n"; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- test fixture string, never rendered
        $result = apply_filters('script_loader_tag', $tag, 'foo');

        $this->assertSame($tag, $result);
    }

    /** A "nonce=" substring inside the src query string must not be mistaken for an existing nonce attribute. */
    public function testScriptLoaderTagAddsNonceWhenSrcHasNonceQueryParam(): void
    {
        Security::init();
        $nonce = Security::getNonce();

        $tag = "<script src='foo.js?nonce=1'></script>\n"; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- test fixture string, never rendered
        $result = apply_filters('script_loader_tag', $tag, 'foo');

        $expected = '<script nonce="' . $nonce . '" src=\'foo.js?nonce=1\'></script>' . "\n";
        $this->assertSame($expected, $result);
    }

    public function testScriptLoaderTagAddsNonceToTagWithoutAttributes(): void
    {
        Security::init();
        $nonce = Security::getNonce();

        $tag = '<script></script>';
        $result = apply_filters('script_loader_tag', $tag, 'foo');

        $this->assertSame('<script nonce="' . $nonce . '"></script>', $result);
    }

    /** The getHardeningHeaders() method pins the exact header names and values sent by addHardeningHeaders(). */
    public function testGetHardeningHeadersReturnsThePinnedMap(): void
    {
        $this->assertSame(
            [
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
                'Permissions-Policy' => 'geolocation=(), camera=(), microphone=(), payment=()',
            ],
            Security::getHardeningHeaders(),
        );
    }

    /** The addHardeningHeaders() callback emits exactly the pinned header lines through the test seam, never a real header() call. */
    public function testAddHardeningHeadersEmitsThePinnedHeaderLinesThroughTheSeam(): void
    {
        $emitted = [];
        Security::setHeaderEmitter(function (string $headerLine) use (&$emitted): void {
            $emitted[] = $headerLine;
        });

        Security::init();
        $callback = $GLOBALS['wp_mock_hooks']['actions']['send_headers'][0]['callback'];
        $callback();

        $this->assertSame(
            [
                'X-Content-Type-Options: nosniff',
                'X-Frame-Options: SAMEORIGIN',
                'Referrer-Policy: strict-origin-when-cross-origin',
                'Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=()',
            ],
            $emitted,
        );
    }

    /** The addHardeningHeaders() callback must not emit anything on admin or AJAX requests, even through the seam. */
    public function testAddHardeningHeadersSkipsAdminAndAjaxRequestsThroughTheSeam(): void
    {
        $emitted = [];
        Security::setHeaderEmitter(function (string $headerLine) use (&$emitted): void {
            $emitted[] = $headerLine;
        });

        Security::init();
        $callback = $GLOBALS['wp_mock_hooks']['actions']['send_headers'][0]['callback'];

        $GLOBALS['wp_mock_is_admin'] = true;
        $callback();
        $this->assertSame([], $emitted);

        $GLOBALS['wp_mock_is_admin'] = false;
        $GLOBALS['wp_mock_doing_ajax'] = true;
        $callback();
        $this->assertSame([], $emitted);
    }

    /** The init() method registers the CSP send_headers hook and makes the nonce available globally. */
    public function testInitRegistersCspHookAndExposesNonceGlobal(): void
    {
        Security::init();

        $this->assertArrayHasKey('send_headers', $GLOBALS['wp_mock_hooks']['actions']);
        $this->assertGreaterThanOrEqual(2, count($GLOBALS['wp_mock_hooks']['actions']['send_headers']), 'hardening headers and CSP header are both hooked on send_headers');
        $this->assertArrayHasKey('script_loader_tag', $GLOBALS['wp_mock_hooks']['filters']);
        $this->assertArrayHasKey('wp_inline_script_attributes', $GLOBALS['wp_mock_hooks']['filters']);
        $this->assertArrayHasKey('wp_script_attributes', $GLOBALS['wp_mock_hooks']['filters']);
        $this->assertSame(Security::getNonce(), $GLOBALS['csp_nonce']);
    }

    /** Disabling the CSP in config must not register a CSP header hook. */
    public function testInitSkipsCspHookWhenDisabledInConfig(): void
    {
        Config::set('security.enable_csp', false);

        Security::init();

        // Only addHardeningHeaders() registers on send_headers when CSP is off.
        $this->assertCount(1, $GLOBALS['wp_mock_hooks']['actions']['send_headers'] ?? []);
    }

    /** The script_loader_tag and wp_script_attributes filter callbacks inject the current nonce. */
    public function testScriptAttributeFilterCallbacksInjectNonce(): void
    {
        Security::init();
        $nonce = Security::getNonce();

        $inlineCallback = $GLOBALS['wp_mock_hooks']['filters']['wp_inline_script_attributes'][0]['callback'];
        $this->assertSame(['nonce' => $nonce], $inlineCallback([]));
        $this->assertSame(['nonce' => 'existing'], $inlineCallback(['nonce' => 'existing']), 'must not overwrite an already-present nonce');

        $scriptCallback = $GLOBALS['wp_mock_hooks']['filters']['wp_script_attributes'][0]['callback'];
        $this->assertSame(['nonce' => $nonce], $scriptCallback([]));
        $this->assertSame(['nonce' => 'existing'], $scriptCallback(['nonce' => 'existing']), 'must not overwrite an already-present nonce');
    }
}
