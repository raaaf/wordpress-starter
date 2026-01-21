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
$autoload_path = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
}

// Load helper functions
require_once __DIR__ . '/../src/helpers.php';

// Initialize Vite integration (autoloaded from src/Vite.php)
WordpressStarter\Vite::init();

// Bootstrap the application
use WordpressStarter\Application;

$app = Application::getInstance();
$app->boot();
