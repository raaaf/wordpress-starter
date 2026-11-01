<?php

declare(strict_types=1);

namespace WordpressStarter\Providers;

/**
 * Redirect provider.
 *
 * Minimal 301 map per site for moved or merged pages after a relaunch, without a
 * redirect plugin. Data: site option `wp_starter_redirects`, old path => new path,
 * both relative to the site's home (e.g. '/warum-mit-uns/' => '/ueber-uns/').
 * Written by a migration script or WP-CLI
 * (`wp option update wp_starter_redirects '{"/alt/":"/neu/"}' --format=json`), no admin UI.
 *
 * Only runs on 404s, so existing content is never shadowed.
 */
class RedirectServiceProvider extends ServiceProvider
{
    public const OPTION = 'wp_starter_redirects';

    public function register(): void
    {
        // No container bindings needed.
    }

    public function boot(): void
    {
        add_action('template_redirect', [$this, 'maybeRedirect'], 1);
    }

    public function maybeRedirect(): void
    {
        if (! is_404()) {
            return;
        }

        $map = get_option(self::OPTION, []);
        if (! is_array($map) || $map === []) {
            return;
        }

        $requestUri = isset($_SERVER['REQUEST_URI'])
            ? esc_url_raw(wp_unslash( (string) $_SERVER['REQUEST_URI']))
            : '';
        $homePath = (string) ( parse_url(home_url('/'), PHP_URL_PATH) ?: '/' );

        $target = self::resolve($requestUri, $homePath, $map);
        if ($target === null) {
            return;
        }

        wp_safe_redirect(home_url($target), 301);
        exit;
    }

    /**
     * Resolve a request path (optionally with query string) to a redirect target.
     *
     * Returns the target path (plus the original query string) or null when
     * there is no match or the entry is unsafe.
     *
     * @param array<mixed> $map old path => new path, relative to home
     */
    public static function resolve(string $requestPath, string $homePath, array $map): ?string
    {
        $query = '';
        $qPos = strpos($requestPath, '?');
        if ($qPos !== false) {
            $query = substr($requestPath, $qPos);
            $requestPath = substr($requestPath, 0, $qPos);
        }

        $prefix = rtrim($homePath, '/');
        if ($prefix !== '' && strcasecmp(substr($requestPath, 0, strlen($prefix) + 1), $prefix . '/') === 0) {
            $requestPath = substr($requestPath, strlen($prefix));
        } elseif ($prefix !== '' && strcasecmp($requestPath, $prefix) === 0) {
            $requestPath = '/';
        }

        $path = self::normalize($requestPath);

        foreach ($map as $from => $to) {
            if (! is_string($from) || ! is_string($to) || self::normalize($from) !== $path) {
                continue;
            }

            // Open-redirect guard: relative to home only.
            if ($to === '' || $to[0] !== '/' || str_contains($to, '//') || str_contains($to, '\\')) {
                return null;
            }

            // No loop.
            if (self::normalize($to) === $path) {
                return null;
            }

            if ($query === '') {
                return $to;
            }

            // Insert the query before any fragment and join with & when the target has one.
            $fragment = '';
            $hashPos = strpos($to, '#');
            if ($hashPos !== false) {
                $fragment = substr($to, $hashPos);
                $to = substr($to, 0, $hashPos);
            }
            $query = str_contains($to, '?') ? '&' . substr($query, 1) : $query;

            return $to . $query . $fragment;
        }

        return null;
    }

    private static function normalize(string $path): string
    {
        $path = mb_strtolower(rawurldecode( (string) ( parse_url($path, PHP_URL_PATH) ?: $path )));
        $path = trim($path, '/');

        return $path === '' ? '/' : '/' . $path . '/';
    }
}
