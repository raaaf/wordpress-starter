<?php

// Ensure functions are loaded so that getBladeViewFactory() exists
if (!function_exists('getBladeViewFactory')) {
    require_once get_stylesheet_directory() . '/config/functions.php';
}

// Determine the template to load – fallback to 'index' if not defined
$templateName = $GLOBALS['template_name'] ?? 'index';

// Try to render the Blade template. If it fails, fall back to a traditional PHP template.
try {
    $blade = getBladeViewFactory();
    if ($blade && $blade->exists($templateName)) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Blade template handles escaping via {{ }} syntax
        echo $blade->make($templateName)->render();
        return;
    }
} catch (Exception $e) {
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional error logging for debugging
    error_log('Blade rendering error: ' . $e->getMessage());
}

// Fallback: load the default template from your theme
require locate_template($templateName . '.php', true);
