<?php
/**
 * Customizable application defaults.
 * These values can be overridden by site admin in the .env file and/or admin interface.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Pages Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines all default pages for the AGNSTK application.
    | Developers can easily add, modify, or disable pages here without altering
    | the core application code.
    |
    */

    'site' => [
        'name' => 'AGNSTK Demo Site',
        'slogan' => 'One app. Many CMS.',
        'theme' => 'default',
        'theme_color' => '#2563eb',
        'home_page' => 'hello',
        'logo' => '/images/logo.png',
        'favicon' => '/images/favicon.ico',
        'icons' => [
            'apple_touch_icon' => '/images/apple-touch-icon.png',
            'android_chrome_192' => '/images/android-chrome-192x192.png',
            'android_chrome_512' => '/images/android-chrome-512x512.png',
            'safari_pinned_tab' => '/images/safari-pinned-tab.svg',
            'web_manifest' => '/manifest.json'
        ]
    ],
    'pages' => [
        'about' => [
            'title' => 'About AGNSTK',
            'slug' => 'about',
            'source' => 'README.md', // Show app README content
            'menu' => [
                'label' => 'About',
                'order' => 10,
                'enabled' => true,
            ],
            'enabled' => true,
        ],
        'developers' => [
            'title' => 'Developers',
            'slug' => 'developers',
            'source' => 'core/DEVELOPERS.md', // Show core DEVELOPERS content
            'menu' => [
                // 'menu_id' => 'main',     // If not set, defaults to 'main'
                // 'label' => 'Developers', // if not set, will use title
                // 'order' => 10,           // if not set, will default to 10
                'enabled' => true,       // if not set, will default to false
            ],
            'enabled' => true,
        ],
        
        'demo' => [
            'title' => 'Hello',
            'slug' => 'hello',
            'content_source' => 'service',
            'content_id' => 'HelloService@render',
            'menu' => [
                // 'label' => 'Hello',
                'order' => 20,
                'enabled' => true,
            ],
            'enabled' => true,
        ],
        
        'dashboard' => [
            'title' => 'Dashboard',
            'slug' => 'dashboard',
            'content_source' => 'view',
            'content_id' => 'dashboard-content',
            'menu' => [
                'menu_id' => 'user',        // The menu must be set here, not in layouts
                'label' => 'Dashboard',
                'order' => 30,
                'enabled' => false, // Don't show in main menu
                'auth_required' => true,
            ],
            'auth_required' => true,
            'enabled' => true,
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Blocks Configuration
    |--------------------------------------------------------------------------
    |
    | Default blocks that can be used by pages
    |
    */

    'blocks' => [
        'example_block' => [
            'title' => 'Example Block',
            'content' => '<p>Example block content goes here.</p>',
            'enabled' => true,
        ],
    ],
];
//     'logo' => env('APP_LOGO', '/images/logo.png'),
//     'blocks' => [
//         'hello' => [
//             'title' => 'Hello, Agnostic World',
//             'description' => 'A simple greeting block demonstrating AGNSTK capabilities',
//             'shortcode' => 'agnstk-hello',
//             'enabled' => true
//         ]
//     ],
//     'pages' => [
//         'hello' => [
//             'title' => 'Hello',
//             'slug' => 'hello',
//             'blocks' => ['hello'],
//             'enabled' => true
//         ],
//         'shortcodes' => [
//             'title' => 'Available Shortcodes',
//             'slug' => 'shortcodes',
//             'content' => 'shortcodes',
//             'enabled' => true
//         ]
//     ],
//     'routes' => [
//         'public' => [
//             'hello' => '/hello',
//             'shortcodes' => '/shortcodes'
//         ],
//         'admin' => [
//             'settings' => '/admin/settings'
//         ],
//         'api' => [
//             'manifest' => '/manifest.json'
//         ]
//     ],

//     'api' => [
//         'enabled' => true,
//         'require_auth' => true,
//         'endpoints' => [
//             'blocks' => '/api/blocks',
//             'config' => '/api/config',
//             'pages' => '/api/pages'
//         ],
//         'rate_limit' => [
//             'requests_per_minute' => 60,
//             'burst_limit' => 10
//         ]
//     ],
//     'security' => [
//         'csrf_protection' => true,
//         'xss_protection' => true,
//         'content_security_policy' => true,
//         'config_protection' => true
//     ]
// ];
