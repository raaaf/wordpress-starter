<?php

// Ensure functions are loaded so that getBladeViewFactory() exists
if (!function_exists('getBladeViewFactory')) {
    require_once get_stylesheet_directory() . '/config/functions.php';
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
} catch (Exception $e) {
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional error logging for debugging
    error_log('Blade rendering error: ' . $e->getMessage());
}

// Fallback: try to load a PHP template
$fallbackTemplate = locate_template($templateName . '.php', false);
if (empty($fallbackTemplate)) {
    $fallbackTemplate = locate_template('index.php', false);
}

if (!empty($fallbackTemplate)) {
    require $fallbackTemplate;
} else {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '<p>No template found for: ' . esc_html($templateName) . '</p>';
}
