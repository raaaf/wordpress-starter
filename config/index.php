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
        echo $blade->make($templateName)->render();
        return;
    }
} catch (Exception $e) {
    // Log the exception if needed:
    error_log('Blade rendering error: ' . $e->getMessage());
}

// Fallback: load the default template from your theme
include locate_template($templateName . '.php', true);
