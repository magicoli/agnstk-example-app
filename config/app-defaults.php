<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Developer Configuration
    |--------------------------------------------------------------------------
    |
    | This is the ONLY configuration file that developers should modify.
    | All other configuration is managed by the AGNSTK core.
    |
    | This file allows you to:
    | - Configure database connections
    | - Register custom services
    | - Set deployment targets (WordPress, Drupal, etc.)
    | - Override core configuration as needed
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your database connection. The AGNSTK core uses SQLite by
    | default for simplicity, but you can override to use MySQL, PostgreSQL,
    | or any other Laravel-supported database.
    |
    */
    'database' => [
        'default' => env('DB_CONNECTION', 'sqlite'),
        'connections' => [
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => database_path('database.sqlite'),
                'prefix' => '',
                'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            ],
            // Add other database connections as needed
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Services
    |--------------------------------------------------------------------------
    |
    | Register your custom services here. These will be automatically
    | registered with Laravel's service container.
    |
    | Example:
    | 'services' => [
    |     App\Services\CustomService::class,
    |     App\Services\AnotherService::class,
    | ],
    |
    */
    'services' => [
        // Add your custom services here
        // App\Services\HelloService::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | CMS Deployment Targets
    |--------------------------------------------------------------------------
    |
    | Configure which CMS platforms you want to deploy to. The AGNSTK core
    | can generate adapters for multiple platforms simultaneously.
    |
    | Available targets: 'wordpress', 'drupal', 'octobercms', 'joomla'
    |
    */
    'deployment_targets' => [
        // 'wordpress' => true,
        // 'drupal' => true,
        // 'octobercms' => false,
        // 'joomla' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Name & Details
    |--------------------------------------------------------------------------
    |
    | These values are used when generating CMS adapters and for general
    | application identification.
    |
    */
    'app_name' => env('APP_NAME', 'AGNSTK Application'),
    'app_description' => 'An AGNSTK-powered application',
    'app_version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Development Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration specifically for development environment.
    |
    */
    'development' => [
        'debug' => env('APP_DEBUG', true),
        'log_level' => env('LOG_LEVEL', 'debug'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Configuration Overrides
    |--------------------------------------------------------------------------
    |
    | Any additional configuration overrides. These will be merged with
    | the core configuration, allowing you to override specific values
    | without modifying core files.
    |
    */
    'overrides' => [
        // Add configuration overrides here
        // Example:
        // 'mail.driver' => 'log',
        // 'cache.default' => 'file',
    ],
];
