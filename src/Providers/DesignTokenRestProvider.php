<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

use WordpressStarter\Helpers\DesignTokenFiles;
use WordpressStarter\ThemeContext;

/**
 * Public, read-only REST route for the token generator on the design system
 * page: GET /wp-json/{prefix}/v1/design-tokens/{primitives|light|dark}.
 *
 * The generator cannot fetch config/design-tokens/ directly: hosts commonly
 * block config/ for the web (nginx on the showcase server answers 403), and
 * that block is right. The route serves exactly the three token files and
 * nothing else. Not admin-gated: REST requests are frontend requests.
 */
class DesignTokenRestProvider extends ServiceProvider
{
    private const MAX_AGE = HOUR_IN_SECONDS;

    public static function routeNamespace(): string
    {
        return ThemeContext::kebabPrefix() . '/v1';
    }

    /** Base URL the generator appends a file name to. */
    public static function endpoint(): string
    {
        return rest_url(self::routeNamespace() . '/design-tokens/');
    }

    public function register(): void
    {
        // No container bindings needed.
    }

    public function boot(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(self::routeNamespace(), '/design-tokens/(?P<file>' . implode('|', DesignTokenFiles::FILES) . ')', [
            'methods' => 'GET',
            'callback' => [$this, 'serve'],
            // INTENTIONAL: public. The token files are the theme's published
            // design values (the same ones tokens.css ships to every visitor),
            // and the generator on the public design system page needs them.
            'permission_callback' => '__return_true',
            'args' => [
                'file' => [
                    'type' => 'string',
                    'enum' => DesignTokenFiles::FILES,
                ],
            ],
        ]);
    }

    public function serve(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $file = (string) $request->get_param('file');
        $raw = DesignTokenFiles::raw($file);
        $data = $raw === null ? null : json_decode($raw, true);
        if (!is_array($data)) {
            return new \WP_Error('design_tokens_unavailable', 'Token file not available.', ['status' => 404]);
        }

        $etag = '"' . md5($raw) . '"';
        $headers = [
            'Cache-Control' => 'public, max-age=' . self::MAX_AGE,
            'ETag' => $etag,
        ];

        if (trim( (string) $request->get_header('if_none_match')) === $etag) {
            $response = new \WP_REST_Response(null, 304);
            $response->set_headers($headers);

            return $response;
        }

        $response = new \WP_REST_Response($data, 200);
        $response->set_headers($headers);

        return $response;
    }
}
