<?php

declare(strict_types=1);

// Suppress deprecation warnings during AJAX/REST requests to prevent JSON parse errors
// (ACF Pro block previews fail if PHP warnings are output before JSON responses)
if (
    ( defined('DOING_AJAX') && DOING_AJAX ) ||
    ( defined('REST_REQUEST') && REST_REQUEST ) ||
    ( isset($_SERVER['REQUEST_URI']) && str_contains( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/wp-json/' ) )
) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
}

// Load Composer dependencies
// Guard against duplicate autoloader class names when multiple themes share the same
// composer dependency set (identical hash). Only require_once if not yet declared.
$autoload_path = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload_path)) {
    $autoload_real = __DIR__ . '/../vendor/composer/autoload_real.php';
    if (file_exists($autoload_real)) {
        preg_match('/^class\s+(ComposerAutoloaderInit\w+)/m', file_get_contents($autoload_real), $matches);
        if (empty($matches[1]) || !class_exists($matches[1], false)) {
            require_once $autoload_path;
        }
    } else {
        require_once $autoload_path;
    }
}

// Load helper functions
require_once __DIR__ . '/../src/helpers.php';

// Initialize Vite integration (autoloaded from src/Vite.php)
WordpressStarter\Vite::init();

// Bootstrap the application
use WordpressStarter\Application;

$app = Application::getInstance();
$app->boot();
