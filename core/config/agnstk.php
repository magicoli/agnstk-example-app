<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AGNSTK Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration is loaded from the root config.json file and provides
    | all the settings for blocks, pages, routes, and general app configuration.
    |
    */

    // Default configuration (fallback if config.json is not found)
    'defaults' => [
        'app' => [
            'name' => 'AGNSTK (debug, fallback from config/agnstk.php)',
            'version' => '1.0.0',
            'description' => 'Agnostic Glue for Non-Specific ToolKits - Example Application'
        ],
        'blocks' => [],
        'pages' => [],
        'routes' => [
            'public' => [],
            'admin' => []
        ]
    ]
];
