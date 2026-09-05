<?php

/**
 * Main Theme Index File
 *
 * This file acts as a gateway for the Blade templating system.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Forward to the custom template loader in the config folder.
require_once get_template_directory() . '/config/index.php';
