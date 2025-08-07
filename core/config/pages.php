<?php
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

return [
    'about' => [
        'title' => 'About',
        'uri' => '/',                 // Defaults to key (page id)
        'source' => 'README.md',            // Markdown file to render
        // 'menu' => true,                     // boolean, string or array, defaults to true
        // 'menu' => 'main',                // string: menu_id to add to, defaults to 'main'
        // 'menu' => [                      // array: menu configuration
        //     'menu_id' => 'main',         // Defaults to 'main'
        //     'label' => 'About AGNSTK',   // Defaults to title
        //     'order' => 10,               // Defaults to 10
        //     'enabled' => true,           // Defaults to false
        // ],
        // 'enabled' => false,              // boolean|logged_in|logged_out|auth_required
    ],
    'developers' => [
        'title' => 'Developers',
        'source' => 'core/DEVELOPERS.md', // Show README content on home page
        'menu' => true,
    ],
    
    'dashboard' => [
        'title' => 'Dashboard',
        'uri' => '/user/dashboard',
        'content_source' => 'view',
        'content_id' => 'dashboard-content',
        'menu' => 'user',
        // 'auth_required' => true,
        'enabled' => 'auth_required', // Only enabled for logged-in users
    ],

    'static-content' => [
        'title' => 'Static Content',
        'content' => sprintf(
            '<p>%s</p><p>%s</p>',
            'This is a static page registered directly from config.',
            'It can be customized in the configuration file.',
            'Normal shortcode should be expanded: [hello] (end of shortcode)',
        ),
        'menu' => true,
    ],

    'rendered-service' => [
        'title' => 'Rendered Service',
        'content_source' => 'service',
        'content_id' => 'HelloService@render',
        'menu' => true,
    ],

    'hello-page-md' => [
        'source' => 'HELLO.md', // Show README content on this page
        'menu' => true,
    ],
];
