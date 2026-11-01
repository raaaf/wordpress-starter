<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Helpers\DesignTokenFiles;
use WordpressStarter\Providers\DesignTokenRestProvider;

/**
 * The token generator reads the token files through this route, because
 * hosts block config/ for the web.
 */
final class DesignTokenRestProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_mock_template_directory'] = dirname(__DIR__, 3);
        $GLOBALS['wp_mock_rest_routes'] = [];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['wp_mock_template_directory'], $GLOBALS['wp_mock_rest_routes']);
        parent::tearDown();
    }

    /** @return array<string, mixed> */
    private function route(): array
    {
        ( new DesignTokenRestProvider() )->registerRoutes();
        $this->assertCount(1, $GLOBALS['wp_mock_rest_routes']);

        return array_values($GLOBALS['wp_mock_rest_routes'])[0];
    }

    private function request(string $file, ?string $ifNoneMatch = null): \WP_REST_Request
    {
        $request = new \WP_REST_Request('GET', '/design-tokens/' . $file);
        $request->set_param('file', $file);
        if ($ifNoneMatch !== null) {
            $request->set_header('If-None-Match', $ifNoneMatch);
        }

        return $request;
    }

    public function testRouteIsPublicReadOnlyAndAllowsOnlyTheThreeFiles(): void
    {
        $route = $this->route();
        $key = array_key_first($GLOBALS['wp_mock_rest_routes']);
        $this->assertSame(DesignTokenRestProvider::routeNamespace() . '/design-tokens/(?P<file>primitives|light|dark)', $key);

        $this->assertSame('GET', $route['methods']);
        $this->assertSame('__return_true', $route['permission_callback']);
        $this->assertSame(['primitives', 'light', 'dark'], $route['args']['file']['enum']);
    }

    public function testServesTheCurrentFileWithCacheHeaders(): void
    {
        $response = ( new DesignTokenRestProvider() )->serve($this->request('light'));

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $raw = (string) DesignTokenFiles::raw('light');
        $this->assertSame(json_decode($raw, true), $response->get_data());
        $this->assertSame('public, max-age=3600', $response->get_headers()['Cache-Control']);
        $this->assertSame('"' . md5($raw) . '"', $response->get_headers()['ETag']);
    }

    public function testAnswersNotModifiedForAMatchingEtag(): void
    {
        $etag = '"' . md5( (string) DesignTokenFiles::raw('dark')) . '"';
        $response = ( new DesignTokenRestProvider() )->serve($this->request('dark', $etag));

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertSame(304, $response->get_status());
        $this->assertNull($response->get_data());
    }

    public function testRefusesAnythingOutsideTheAllowlist(): void
    {
        foreach (['../../style', 'fonts', 'primitives.tokens.json', ''] as $file) {
            $this->assertInstanceOf(\WP_Error::class, ( new DesignTokenRestProvider() )->serve($this->request($file)), $file);
        }
    }

    public function testBaseColorsReadTheFiveHundredOfEveryGeneratorColour(): void
    {
        $colors = DesignTokenFiles::baseColors();

        $this->assertSame(DesignTokenFiles::COLORS, array_keys($colors));
        $this->assertSame('#FF6B35', $colors['accent']);
        foreach ($colors as $hex) {
            $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/', $hex);
        }
    }
}
