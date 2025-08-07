<?php
/*
|--------------------------------------------------------------------------
| Default Blocks Configuration
|--------------------------------------------------------------------------
|
| Default blocks that can be used by pages
|
*/

return [
    'example_block' => [
        'title' => 'Example Block',
        'content' => '<p>Example block content goes here.</p>',
        'enabled' => true,
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
