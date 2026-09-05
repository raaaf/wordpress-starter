<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
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
        $this->resetStaticProperties(Security::class, ['nonce' => null]);
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

    public function testGetCSPHeaderContainsRequiredDirectives(): void
    {
        $header = Security::getCSPHeader();

        $this->assertStringContainsString("default-src 'self'", $header);
        $this->assertStringContainsString("script-src 'self'", $header);
        $this->assertStringContainsString("style-src 'self'", $header);
        $this->assertStringContainsString("font-src 'self'", $header);
        $this->assertStringContainsString("img-src 'self'", $header);
        $this->assertStringContainsString("connect-src 'self'", $header);
        $this->assertStringContainsString("base-uri 'self'", $header);
        $this->assertStringContainsString("frame-ancestors 'self'", $header);
        $this->assertStringContainsString("worker-src 'self'", $header);
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

        // Checked against the FULL header, not only the sliced directives
        // above: a hostile value that escaped its host-only sanitization
        // could otherwise land in an unrelated directive that the slice
        // above never looks at, and still pass the two checks above.
        $this->assertSame(2, substr_count($header, 'https://tracking.example.com'), 'origin must appear exactly once per directive, twice total');
        $this->assertStringEndsWith(' https://tracking.example.com', $scriptSrc, 'script-src must end with the analytics origin, nothing appended after it');
    }

    /**
     * Both admin-settable origins must land in the right directives at the
     * same time: analytics in script-src/connect-src, embed hosts in
     * frame-src. Guards against a change to one option's handling silently
     * clobbering the other's directive.
     */
    public function testCSPCarriesBothAnalyticsAndEmbedOriginsSimultaneously(): void
    {
        update_option('rybbit_script_url', 'https://tracking.example.com/api/script.js');
        $this->setMockField('embed_allowed_hosts', 'calendly.com', 'option');

        $header = Security::getCSPHeader();

        $this->assertStringContainsString('https://tracking.example.com', $this->directive($header, 'script-src'));
        $this->assertStringContainsString('https://tracking.example.com', $this->directive($header, 'connect-src'));
        $this->assertStringContainsString('https://calendly.com', $this->directive($header, 'frame-src'));
        $this->assertStringNotContainsString('https://calendly.com', $this->directive($header, 'script-src'));
        $this->assertStringNotContainsString('https://tracking.example.com', $this->directive($header, 'frame-src'));
    }

    /** Ohne eigene Option gilt der Standard des Plugins, nicht gar nichts. */
    public function testCSPFallsBackToThePluginDefaultOrigin(): void
    {
        delete_option('rybbit_script_url');

        $this->assertStringContainsString(
            'https://app.rybbit.io',
            $this->directive(Security::getCSPHeader(), 'script-src'),
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
        $header = Security::getCSPHeader();
        $scriptSrc = $this->directive($header, 'script-src');

        // Gegen den GANZEN Header pruefen, nicht nur gegen das erste Segment,
        // das directive() herausschneidet: eine durchgerutschte "; script-src *"
        // wuerde im Header ein zweites "script-src" erzeugen, das directive()
        // (nimmt nur den ersten Treffer) nie zu Gesicht bekaeme.
        $this->assertSame(1, preg_match_all('/(?:^|;\s*)script-src /', $header), $warum);
        $this->assertStringNotContainsString('script-src *', $header, $warum);
        if ($url !== '') {
            $this->assertStringNotContainsString($url, $header, $warum);
        }

        if ($erwarteterHost === null) {
            $this->assertStringNotContainsString('https://', $scriptSrc, $warum);

            return;
        }

        $this->assertStringContainsString($erwarteterHost, $scriptSrc, $warum);
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

    /**
     * All font faces are self-hosted (resources/css/fonts.css), so the CSP
     * must not carry the Google Fonts origins: an origin present here is
     * attack surface the theme does not need.
     */
    public function testGetCSPHeaderExcludesGoogleFontsOrigins(): void
    {
        $header = Security::getCSPHeader();

        $this->assertStringNotContainsString('fonts.gstatic.com', $header);
        $this->assertStringNotContainsString('fonts.googleapis.com', $header);
    }

    public function testGetCSPHeaderIncludesUnsafeInlineForStyles(): void
    {
        $header = Security::getCSPHeader();

        // Extract the style-src directive
        preg_match('/style-src ([^;]+)/', $header, $matches);
        $styleSrc = $matches[1] ?? '';

        $this->assertStringContainsString("'unsafe-inline'", $styleSrc);
    }

    public function testGetCSPHeaderContainsEachRequiredDirectiveExactlyOnce(): void
    {
        $header = Security::getCSPHeader();

        $directiveNames = [
            'default-src', 'script-src', 'style-src', 'img-src', 'font-src',
            'frame-src', 'frame-ancestors', 'base-uri', 'connect-src',
            'worker-src', 'media-src',
        ];

        foreach ($directiveNames as $name) {
            $this->assertSame(
                1,
                preg_match_all('/\b' . preg_quote($name, '/') . ' /', $header),
                "{$name} must appear exactly once",
            );
        }
    }

    /**
     * No existing nonce/hash in either directive: 'unsafe-eval' is appended to
     * script-src, but our nonce is NOT injected anywhere, since 'unsafe-inline'
     * alone (no nonce/hash present) is still effective and must stay that way.
     */
    public function testAddUnsafeEvalToCSPSkipsNonceWhenNoNonceOrHashPresent(): void
    {
        $csp = "script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'";

        $result = Security::addUnsafeEvalToCSP($csp);

        $this->assertSame(
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'",
            $result,
        );
    }

    /**
     * Script-src already carries a nonce: our own nonce is added there (a
     * nonce source already makes the browser ignore 'unsafe-inline' in that
     * directive, so nothing is lost), plus 'unsafe-eval'. style-src has no
     * nonce/hash, so it is left untouched.
     */
    public function testAddUnsafeEvalToCSPAddsNonceOnlyToDirectiveThatAlreadyHasOne(): void
    {
        $nonce = Security::getNonce();
        $csp = "script-src 'self' 'nonce-abc'; style-src 'self'";

        $result = Security::addUnsafeEvalToCSP($csp);

        $this->assertSame(
            "script-src 'self' 'nonce-abc' 'unsafe-eval' 'nonce-{$nonce}'; style-src 'self'",
            $result,
        );
    }

    /**
     * A second, comma-separated policy in the same header value must stay
     * untouched: the patch only ever touches the first policy.
     */
    public function testAddUnsafeEvalToCSPStaysWithinFirstCommaSeparatedPolicy(): void
    {
        $csp = "script-src 'none', default-src 'self'";

        $result = Security::addUnsafeEvalToCSP($csp);

        $this->assertSame("script-src 'none' 'unsafe-eval', default-src 'self'", $result);
    }

    /** No script-src/style-src at all: nothing to touch, nothing errors. */
    public function testAddUnsafeEvalToCSPLeavesHeaderWithoutScriptOrStyleSrcUnchanged(): void
    {
        $csp = "default-src 'self'";

        $result = Security::addUnsafeEvalToCSP($csp);

        $this->assertSame("default-src 'self'", $result);
    }

    /**
     * Script-src-elem is a separate CSP3 directive and must not be matched by
     * the "script-src" patch; only the real script-src is touched.
     */
    public function testAddUnsafeEvalToCSPDoesNotMatchScriptSrcElem(): void
    {
        $csp = "script-src-elem 'self'; script-src 'self'";

        $result = Security::addUnsafeEvalToCSP($csp);

        $this->assertSame("script-src-elem 'self'; script-src 'self' 'unsafe-eval'", $result);
    }

    /** Unsafe-eval already present in script-src: it must not be appended twice. */
    public function testAddUnsafeEvalToCSPDoesNotDuplicateExistingUnsafeEval(): void
    {
        $csp = "script-src 'self' 'unsafe-eval'";

        $result = Security::addUnsafeEvalToCSP($csp);

        $this->assertSame("script-src 'self' 'unsafe-eval'", $result);
    }

    /**
     * Style-src already carries a nonce: our own nonce is added there too,
     * mirroring the script-src case, plus 'unsafe-eval' in script-src.
     */
    public function testAddUnsafeEvalToCSPAddsNonceToStyleSrcWhenItAlreadyHasOne(): void
    {
        $nonce = Security::getNonce();
        $csp = "script-src 'self'; style-src 'self' 'nonce-xyz'";

        $result = Security::addUnsafeEvalToCSP($csp);

        $this->assertSame(
            "script-src 'self' 'unsafe-eval'; style-src 'self' 'nonce-xyz' 'nonce-{$nonce}'",
            $result,
        );
    }

    /** A hash source (sha256/384/512) triggers the nonce injection just like a nonce source does. */
    public function testAddUnsafeEvalToCSPAddsNonceWhenScriptSrcCarriesAHash(): void
    {
        $nonce = Security::getNonce();
        $csp = "script-src 'self' 'sha256-abc123='; style-src 'self'";

        $result = Security::addUnsafeEvalToCSP($csp);

        $this->assertSame(
            "script-src 'self' 'sha256-abc123=' 'unsafe-eval' 'nonce-{$nonce}'; style-src 'self'",
            $result,
        );
    }

    /** A second call on an already-patched header must not add the nonce twice. */
    public function testAddUnsafeEvalToCSPDoesNotDuplicateNonceOnSecondCall(): void
    {
        $csp = "script-src 'self' 'nonce-abc'; style-src 'self'";

        $first = Security::addUnsafeEvalToCSP($csp);
        $second = Security::addUnsafeEvalToCSP($first);

        $this->assertSame($first, $second);
    }

    /**
     * The getLocalSources() gate ties development-only CSP sources to
     * WP_ENVIRONMENT_TYPE === 'local'. Runs in a separate process: the
     * constant, once defined, cannot be reset for a later test in the same
     * process, and other tests in this file rely on it being undefined
     * (production-like).
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGetLocalSourcesOmittedInProduction(): void
    {
        define('WP_ENVIRONMENT_TYPE', 'production');

        $header = Security::getCSPHeader();

        $this->assertStringNotContainsString('http://localhost:', $header);
        $this->assertStringNotContainsString('ws://localhost:', $header);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGetLocalSourcesIncludedInLocalEnvironment(): void
    {
        define('WP_ENVIRONMENT_TYPE', 'local');

        $header = Security::getCSPHeader();

        $this->assertStringContainsString('http://localhost:5180', $header);
        $this->assertStringContainsString('ws://localhost:5180', $header);
    }

    /**
     * The getLocalSources() method also reads the dynamic port from .vite-port. 5180 is
     * already a hardcoded default port, so the two tests above would still
     * pass even if that dynamic-port read were entirely broken. This test
     * points get_template_directory() at a fixture carrying a port that is
     * NOT in the hardcoded list, so only a real read of the file can produce
     * it, and asserts it appears in local but not in production.
     */
    private function withVitePortFixture(string $port, callable $assert): void
    {
        $tempDir = sys_get_temp_dir() . '/wp-starter-vite-port-test-' . uniqid();
        mkdir($tempDir);
        file_put_contents($tempDir . '/.vite-port', $port);
        $GLOBALS['wp_mock_template_directory'] = $tempDir;

        try {
            $assert(Security::getCSPHeader());
        } finally {
            unlink($tempDir . '/.vite-port');
            rmdir($tempDir);
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGetLocalSourcesOmitsTheDynamicVitePortInProduction(): void
    {
        define('WP_ENVIRONMENT_TYPE', 'production');

        $this->withVitePortFixture('6123', function (string $header): void {
            $this->assertStringNotContainsString(':6123', $header);
        });
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGetLocalSourcesIncludesTheDynamicVitePortInLocalEnvironment(): void
    {
        define('WP_ENVIRONMENT_TYPE', 'local');

        $this->withVitePortFixture('6123', function (string $header): void {
            $this->assertStringContainsString('http://localhost:6123', $header);
            $this->assertStringContainsString('ws://localhost:6123', $header);
        });
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function embedHostRohwerte(): array
    {
        return [
            'zwei gueltige Hosts' => ["calendly.com\ncore.example.com", ' https://calendly.com https://core.example.com'],
            'mit Schema' => ['https://calendly.com/booking', ' https://calendly.com'],
            'mit Pfad' => ['example.com/pfad/zu/etwas', ' https://example.com'],
            // Deliberate divergence from getAnalyticsOrigin(), which DOES keep
            // a port (see "mit Port bleibt erhalten" in unbrauchbareHerkuenfte()):
            // that origin comes from wp_parse_url()'s PHP_URL_PORT, while embed
            // hosts are entered as plain "host[:port]" text without a scheme,
            // so httpsOriginFromHost()'s host-only character check rejects the
            // colon and drops the whole entry rather than stripping just the port.
            'mit Port wird verworfen' => ['example.com:8443', ''],
            'leere Zeilen werden uebersprungen' => ["\ncalendly.com\n\n", ' https://calendly.com'],
            'Semikolon wird verworfen' => ['evil.test; script-src *', ''],
            'Leerzeichen wird verworfen' => ['evil test', ''],
        ];
    }

    /** The getEmbedOrigins() method reads the admin-maintained multiline host list and builds the frame-src suffix. */
    #[DataProvider('embedHostRohwerte')]
    public function testGetEmbedOriginsFiltersRawAdminInput(string $raw, string $expected): void
    {
        $this->setMockField('embed_allowed_hosts', $raw, 'option');

        $result = $this->invokeStaticMethod(Security::class, 'getEmbedOrigins');

        $this->assertSame($expected, $result);
    }

    /**
     * The embed-hosts option feeds frame-src the same way the analytics
     * option feeds script-src/connect-src: checked against the FULL header
     * here, not a directive slice, so an escape into a second directive
     * would not slip past unnoticed.
     */
    public function testGetEmbedOriginsInjectionDoesNotEscapeIntoFullCSPHeader(): void
    {
        $this->setMockField('embed_allowed_hosts', "evil.test; script-src *\ncalendly.com", 'option');

        $header = Security::getCSPHeader();

        $this->assertStringNotContainsString('script-src *', $header);
        $this->assertSame(1, preg_match_all('/(?:^|;\s*)script-src /', $header));
        $this->assertSame(1, preg_match_all('/(?:^|;\s*)frame-src /', $header));
        $this->assertStringContainsString('https://calendly.com', $this->directive($header, 'frame-src'));
        $this->assertStringNotContainsString('evil.test', $header);
    }

    /**
     * The isAllowedEmbedHost() method is the shared gate templates use before
     * rendering an iframe; it must accept exactly what getCSPHeader() writes
     * into frame-src, no more and no less.
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
            'hartkodierter Host erlaubt' => ['https://www.youtube-nocookie.com/embed/x', true, 'in HARDCODED_FRAME_SRC_HOSTS und in der CSP'],
            'Options-Host erlaubt' => ['https://calendly.com/meeting', true, 'aus der embed_allowed_hosts Option'],
            'Subdomain des Options-Hosts abgelehnt' => ['https://sub.calendly.com/meeting', false, 'heutige Semantik: nur exakte Eintraege, keine Subdomains'],
            'eigener Host abgelehnt' => ['https://' . (string) wp_parse_url(home_url(), PHP_URL_HOST) . '/x', false, 'allow-same-origin darf den Sandkasten nicht aushebeln'],
            'www-Alias des eigenen Hosts abgelehnt' => ['https://www.' . (string) wp_parse_url(home_url(), PHP_URL_HOST) . '/x', false, 'www-Alias ist derselbe same-origin Sandkasten-Bruch'],
            'Userinfo-Trick abgelehnt' => ['https://calendly.com@evil.test/x', false, 'wp_parse_url liest den echten Host (evil.test), nicht das Userinfo-Feld'],
            'Grossschreibung im Schema akzeptiert' => ['HTTPS://calendly.com/meeting', true, 'Schema-Vergleich ist case-insensitiv'],
            'http abgelehnt' => ['http://calendly.com/meeting', false, 'nur https ist zulaessig'],
            'expliziter Port 8443 abgelehnt' => ['https://calendly.com:8443/meeting', false, 'CSP frame-src schreibt den portlosen Origin, ein anderer Port waere same-origin blockiert'],
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

    /**
     * The patchCspHeaderValue() method is the pure logic extracted from the admin_init
     * header_register_callback closure, tested without relying on PHP
     * actually flushing headers.
     */
    #[DataProvider('cspHeaderLines')]
    public function testPatchCspHeaderValue(string $rawHeaderValue, bool $isCsp, ?string $expected = null): void
    {
        $result = Security::patchCspHeaderValue($rawHeaderValue);

        if (!$isCsp) {
            $this->assertNull($result, 'non-CSP header lines must be left untouched (null)');

            return;
        }

        $this->assertSame($expected, $result);
    }

    /** @return array<string, array{0: string, 1: bool, 2?: string}> */
    public static function cspHeaderLines(): array
    {
        return [
            'standard header with space' => ["Content-Security-Policy: script-src 'self'", true, "Content-Security-Policy: script-src 'self' 'unsafe-eval'"],
            'no space after colon (the past defect)' => ["content-security-policy:script-src 'self'", true, "Content-Security-Policy: script-src 'self' 'unsafe-eval'"],
            'no CSP prefix at all' => ['X-Frame-Options: SAMEORIGIN', false],
            'report-only is a different header and must not be patched as CSP' => ["Content-Security-Policy-Report-Only: script-src 'self'", false],
        ];
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

    /** The init() method registers the CSP send_headers hook and makes the nonce available globally. */
    public function testInitRegistersCspHookAndExposesNonceGlobal(): void
    {
        Security::init();

        $this->assertArrayHasKey('send_headers', $GLOBALS['wp_mock_hooks']['actions']);
        $this->assertGreaterThanOrEqual(2, count($GLOBALS['wp_mock_hooks']['actions']['send_headers']), 'hardening headers and CSP header are both hooked on send_headers');
        $this->assertArrayHasKey('wp_headers', $GLOBALS['wp_mock_hooks']['filters']);
        $this->assertArrayHasKey('script_loader_tag', $GLOBALS['wp_mock_hooks']['filters']);
        $this->assertArrayHasKey('wp_inline_script_attributes', $GLOBALS['wp_mock_hooks']['filters']);
        $this->assertArrayHasKey('wp_script_attributes', $GLOBALS['wp_mock_hooks']['filters']);
        $this->assertSame(Security::getNonce(), $GLOBALS['csp_nonce']);
    }

    /** Disabling the CSP in config must not register a CSP header hook or the wp_headers filter. */
    public function testInitSkipsCspHookAndFilterWhenDisabledInConfig(): void
    {
        Config::set('security.enable_csp', false);

        Security::init();

        // Only addHardeningHeaders() registers on send_headers when CSP is off.
        $this->assertCount(1, $GLOBALS['wp_mock_hooks']['actions']['send_headers'] ?? []);
        $this->assertArrayNotHasKey('wp_headers', $GLOBALS['wp_mock_hooks']['filters']);
    }

    /** The wp_headers filter callback only patches the CSP header on admin pages. */
    public function testWpHeadersFilterCallbackPatchesCspOnlyOnAdminPages(): void
    {
        Security::init();
        $callback = $GLOBALS['wp_mock_hooks']['filters']['wp_headers'][0]['callback'];

        $GLOBALS['wp_mock_is_admin'] = true;
        $patched = $callback(['Content-Security-Policy' => "script-src 'self'"]);
        $this->assertStringContainsString("'unsafe-eval'", $patched['Content-Security-Policy']);

        $GLOBALS['wp_mock_is_admin'] = false;
        $unpatched = $callback(['Content-Security-Policy' => "script-src 'self'"]);
        $this->assertSame("script-src 'self'", $unpatched['Content-Security-Policy']);
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
