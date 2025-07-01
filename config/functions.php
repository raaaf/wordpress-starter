<?php

declare(strict_types=1);

// Load Composer dependencies
$autoload_path = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
}

// Load helper functions
require_once __DIR__ . '/../src/helpers.php';

// Initialize Vite integration
require_once __DIR__ . '/vite.php';
WordpressStarter\Vite::init();

// Bootstrap the application
use WordpressStarter\Application;

$app = Application::getInstance();
$app->boot();