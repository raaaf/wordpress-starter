<?php

declare(strict_types=1);

use Illuminate\View\Factory;
use WordpressStarter\Config;

if (!function_exists('getBladeViewFactory')) {
    function getBladeViewFactory(): ?Factory
    {
        return $GLOBALS['blade'] ?? null;
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Environment variables are server-side config, not user input
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('app')) {
    /**
     * Get the application instance or resolve a class from the container.
     *
     * @template T
     *
     * @param class-string<T>|null $abstract
     *
     * @return T|WordpressStarter\Application
     */
    function app(?string $abstract = null): mixed
    {
        $instance = WordpressStarter\Application::getInstance();

        if ($abstract === null) {
            return $instance;
        }

        return $instance->make($abstract);
    }
}

if (!function_exists('get_reading_time')) {
    /**
     * Calculate the estimated reading time for a post.
     *
     * @param int|WP_Post|null $post Post ID or post object. Default is the current post.
     * @param int $wordsPerMinute Average reading speed. Default 200 words/minute.
     *
     * @return string Formatted reading time (e.g., "5 Min. Lesezeit")
     */
    function get_reading_time(int|WP_Post|null $post = null, int $wordsPerMinute = 200): string
    {
        $post = get_post($post);

        if (!$post) {
            return '';
        }

        $content = wp_strip_all_tags($post->post_content);
        $wordCount = str_word_count($content);
        $minutes = max(1, (int) ceil($wordCount / $wordsPerMinute));

        return sprintf('%d Min. Lesezeit', $minutes);
    }
}

if (!function_exists('wp_starter_consent_banner')) {
    /**
     * Render the consent banner slot.
     *
     * The starter theme ships without a banner because the default analytics stack
     * (Rybbit) is cookie-free. When a client theme adds a service that requires
     * consent (GA4, Meta Pixel, YouTube, Maps, HubSpot, ...) it can hook into
     * `wp_starter_consent_banner` to render the banner markup at this point.
     *
     * Place a single call to this helper in the site layout (typically right
     * before `</body>`). Multiple callbacks are supported; each runs in priority
     * order. No output is produced until a callback is registered, so adopting
     * this helper is safe by default.
     */
    function wp_starter_consent_banner(): void
    {
        do_action('wp_starter_consent_banner');
    }
}

if (!function_exists('blade')) {
    /**
     * Get the Blade service or render a view.
     *
     * Provides backwards compatibility by falling back to $GLOBALS['blade']
     * if the container-based service is not available.
     *
     * @param string|null $view View name to render (or null to get the factory)
     * @param array<string, mixed> $data Data to pass to the view
     *
     * @return Factory|string
     *
     * @throws RuntimeException If Blade is not initialized.
     */
    function blade(?string $view = null, array $data = []): Factory|string
    {
        // Try to get from container first (new way)
        try {
            $container = Illuminate\Container\Container::getInstance();
            if ($container && $container->bound(WordpressStarter\Services\BladeService::class)) {
                $service = $container->make(WordpressStarter\Services\BladeService::class);

                if ($view === null) {
                    return $service->getViewFactory();
                }

                return $service->render($view, $data);
            }
        } catch (Throwable $e) {
            // Container not available or service not bound - fall through to global fallback
            unset($e);
        }

        // Fallback to global (backwards compatibility)
        $factory = $GLOBALS['blade'] ?? null;

        if ($factory === null) {
            throw new RuntimeException('Blade not initialized');
        }

        if ($view === null) {
            return $factory;
        }

        return $factory->make($view, $data)->render();
    }
}
