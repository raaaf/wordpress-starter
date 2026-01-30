<?php

return [
    'name' => 'WP Starter Test',
    'debug' => true,
    'vite' => [
        'dev_server' => [
            'host' => 'localhost',
            'port' => 5173,
        ],
        'manifest' => 'dist/.vite/manifest.json',
    ],
    'nested' => [
        'level1' => [
            'level2' => [
                'value' => 'deep-nested-value',
            ],
        ],
    ],
];
