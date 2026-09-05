<?php

declare(strict_types=1);

return [
    'theme' => [
        'name' => 'WP Starter',
        'version' => function_exists('wp_get_theme') ? (string) wp_get_theme()->get('Version') : '',
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
    
    
    'debug' => [
        'show_grid' => env('WP_DEBUG', false),
    ],

    'member_area' => [
        'enabled' => env('MEMBER_AREA_ENABLED', true),
    ],
];
