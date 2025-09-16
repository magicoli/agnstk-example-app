<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Root Path
    |--------------------------------------------------------------------------
    |
    | This defines where AGNSTK should look for application-specific files
    | (README.md, HELLO.md, src/, config/, etc.) relative to the core.
    | 
    | This is automatically determined but can be overridden if needed.
    |
    */

    'app_root' => dirname(__DIR__, 2),

    /*
    |--------------------------------------------------------------------------
    | Bundle Information
    |--------------------------------------------------------------------------
    |
    | Information about this AGNSTK bundle/application.
    |
    */

    'name' => env('BUNDLE_NAME', 'AGNSTK Application'),
    'version' => '1.0.0',
    'description' => 'AGNSTK Application Bundle',

    /*
    |--------------------------------------------------------------------------
    | File Paths
    |--------------------------------------------------------------------------
    |
    | Default paths for application files relative to app_root.
    |
    */

    'paths' => [
        'src' => 'src',
        'config' => 'config', 
        'public' => 'public',
        'storage' => 'storage',
        'resources' => 'resources',
    ],
];
