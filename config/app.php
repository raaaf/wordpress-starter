<?php

declare(strict_types=1);

return [
    'theme' => [
        'name' => 'WP Starter',
        'version' => '0.0.2',
        'text_domain' => 'wp-starter',
        'author' => 'Rafael Alex',
        'author_uri' => 'https://rafaelalex.de',
    ],
    
    'vite' => [
        'dev_server' => [
            'host' => env('VITE_DEV_SERVER_HOST', 'localhost'),
            'port' => env('VITE_DEV_SERVER_PORT', 5180),
        ],
    ],
    
    'browsersync' => [
        'proxy' => env('BROWSERSYNC_PROXY', 'http://localhost'),
    ],
    
    'assets' => [
        'critical_css' => env('LOAD_CRITICAL_CSS', true),
        'defer_scripts' => env('DEFER_SCRIPTS', true),
    ],
    
    'security' => [
        'enable_csp' => env('ENABLE_CSP', true),
        'csp_report_uri' => env('CSP_REPORT_URI', ''),
    ],
    
    'analytics' => [
        'pirsch_id' => env('PIRSCH_ID', ''),
    ],
    
    'debug' => [
        'show_grid' => env('WP_DEBUG', false),
    ],
];
