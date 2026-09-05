<?php

if (!defined('ABSPATH')) {
    exit;
}

// Ensure functions are loaded so that getBladeViewFactory() exists
if (!function_exists('getBladeViewFactory')) {
    require_once get_template_directory() . '/config/functions.php';
}

// Resolve a real PHP fallback template for $templateName, excluding this
// file's own gateway (get_template_directory() . '/index.php') so a missing
// Blade view can never fall back onto itself and produce a silent HTTP 200.
if (!function_exists('wpStarterResolveFallbackTemplate')) {
    function wpStarterResolveFallbackTemplate(string $templateName): ?string
    {
        $fallbackTemplate = locate_template($templateName . '.php', false);
        if (empty($fallbackTemplate)) {
            $fallbackTemplate = locate_template('index.php', false);
        }

        if (empty($fallbackTemplate) || realpath($fallbackTemplate) === realpath(get_template_directory() . '/index.php')) {
            return null;
        }

        return $fallbackTemplate;
    }
}

// Send a 500 response when no usable fallback template exists, so a missing
// Blade view never falls through to a blank HTTP 200 body.
if (!function_exists('wpStarterSendRenderFailure')) {
    function wpStarterSendRenderFailure(?Throwable $e = null): void
    {
        status_header(500);
        nocache_headers();
        $message = 'Diese Seite kann gerade nicht angezeigt werden.';
        if ($e && defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) {
            $message .= ' (' . $e->getMessage() . ')';
        }
        echo '<p>' . esc_html($message) . '</p>';
    }
}

// Determine the template to load – fallback to 'index' if not defined
$templateName = $GLOBALS['template_name'] ?? 'index';

// Try to render the Blade template
try {
    $blade = getBladeViewFactory();
    if ($blade && $blade->exists($templateName)) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Blade template handles escaping via {{ }} syntax
        echo $blade->make($templateName)->render();
        return;
    }

    // Try index.blade.php as fallback
    if ($blade && $blade->exists('index')) {
        $GLOBALS['template_name'] = 'index';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Blade template handles escaping via {{ }} syntax
        echo $blade->make('index')->render();
        return;
    }
} catch (Throwable $e) {
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional error logging for debugging
    error_log('Blade rendering error: ' . $e->getMessage());

    $fallbackTemplate = wpStarterResolveFallbackTemplate($templateName);
    if ($fallbackTemplate) {
        require $fallbackTemplate;

        return;
    }

    // No PHP fallback template exists either (this file is the gateway itself),
    // so falling through would just return HTTP 200 with an empty body.
    wpStarterSendRenderFailure($e);

    return;
}

// Fallback: try to load a PHP template
$fallbackTemplate = wpStarterResolveFallbackTemplate($templateName);

if ($fallbackTemplate) {
    require $fallbackTemplate;
} else {
    // No PHP fallback template exists either (this file is the gateway itself),
    // so falling through would just return HTTP 200 with an empty body.
    wpStarterSendRenderFailure();
}
